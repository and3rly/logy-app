<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Existencia por producto, unidad, presentación, vencimiento y sucursal
class Stock_model extends Centro_model {

	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id = null;
	public $fecha_vence = null;
	public $cantidad = 0;
	public $activo = 1;
	public $usuario_id;
	public $sucursal_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	/**
	 * Suma (o resta, con cantidad negativa) a la existencia de una sucursal: la indicada en
	 * "sucursal_id" (ej. la de la OC) o, si no se indica, la de la sesión.
	 * Si no hay registro para esa combinación, lo crea. Devuelve el id del stock.
	 */
	public function sumar($args=[])
	{
		$presentacion = elemento($args, "producto_presentacion_id", null);
		$vence = elemento($args, "fecha_vence", null);
		$sucursal = elemento($args, "sucursal_id", $this->_ses->sucursal_id);

		$this->db
		->where("producto_id", $args["producto_id"])
		->where("unidad_medida_id", $args["unidad_medida_id"])
		->where("sucursal_id", $sucursal)
		->where("activo", 1);

		$presentacion === null
			? $this->db->where("producto_presentacion_id IS NULL", null, false)
			: $this->db->where("producto_presentacion_id", $presentacion);

		$vence === null
			? $this->db->where("fecha_vence IS NULL", null, false)
			: $this->db->where("fecha_vence", $vence);

		$tmp = $this->db->get($this->_tabla)->row();

		if ($tmp) {
			$this->db
			->set("cantidad", "cantidad + " . (float)$args["cantidad"], false)
			->where("id", $tmp->id)
			->update($this->_tabla);

			return $tmp->id;
		}

		$stock = new Stock_model();
		$stock->guardar([
			"sucursal_id" => $sucursal,
			"producto_id" => $args["producto_id"],
			"unidad_medida_id" => $args["unidad_medida_id"],
			"producto_presentacion_id" => $presentacion,
			"fecha_vence" => $vence,
			"cantidad" => $args["cantidad"]
		]);

		return $stock->getPK();
	}

	/**
	 * Descuenta una cantidad de la existencia de una sucursal, empezando por el lote que vence
	 * primero (los que no vencen van al final); con "fecha_vence" solo descuenta de ese lote.
	 * Solo toca la existencia de la presentación indicada (sin "producto_presentacion_id", la de la unidad).
	 * Debe llamarse dentro de una transacción: bloquea los registros de stock hasta que termine.
	 * Devuelve [stock_id => cantidad descontada] o false si no alcanza la existencia (no descuenta nada).
	 */
	public function descontar($args=[])
	{
		$sucursal = elemento($args, "sucursal_id", $this->_ses->sucursal_id);
		$pendiente = round((float)$args["cantidad"], 2);
		$vence = elemento($args, "fecha_vence", null);
		$presentacion = elemento($args, "producto_presentacion_id", null);

		$params = [
			$args["producto_id"],
			$args["unidad_medida_id"],
			$sucursal
		];

		if ($presentacion === null) {
			$lote = "and producto_presentacion_id is null";
		} else {
			$lote = "and producto_presentacion_id = ?";
			$params[] = $presentacion;
		}

		if ($vence !== null) {
			$lote .= " and fecha_vence = ?";
			$params[] = $vence;
		}

		$lotes = $this->db->query("
			select id, cantidad
			from {$this->_tabla}
			where producto_id = ?
			and unidad_medida_id = ?
			and sucursal_id = ?
			and activo = 1
			and cantidad > 0
			{$lote}
			order by fecha_vence is null, fecha_vence, id
			for update", $params)
		->result();

		$existencia = array_sum(array_map(function ($l) {
			return (float)$l->cantidad;
		}, $lotes));

		if (round($existencia, 2) < $pendiente) {
			return false;
		}

		$descontado = [];

		foreach ($lotes as $lote) {
			if ($pendiente <= 0) {
				break;
			}

			$cantidad = min((float)$lote->cantidad, $pendiente);

			$this->db
			->set("cantidad", "cantidad - " . $cantidad, false)
			->where("id", $lote->id)
			->update($this->_tabla);

			$descontado[$lote->id] = $cantidad;
			$pendiente = round($pendiente - $cantidad, 2);
		}

		return $descontado;
	}

	/**
	 * Existencia de los productos (bienes activos) de la empresa en la sucursal de la sesión,
	 * agrupada por producto y unidad. Un producto sin stock aparece con existencia 0.
	 * La existencia de la fila es la de la unidad de medida (sin presentación); cada producto
	 * trae en "presentaciones" su existencia por presentación (ver presentacionesExistencia()).
	 * Filtros opcionales (los mismos de la pantalla): termino, categoria, marca y
	 * estado (disponible, minimo o agotado; ver estadoExistencia()).
	 */
	public function existencias($args=[])
	{
		$sucursal = (int)$this->_ses->sucursal_id;
		$existencia = "ifnull(b.existencia, 0)";

		# El subquery va primero: get_compiled_select() reinicia el query builder
		$sub = $this->db
		->select("
			producto_id,
			unidad_medida_id,
			sum(cantidad) as existencia,
			min(case when cantidad > 0 then fecha_vence end) as proximo_vence", false)
		->from($this->_tabla)
		->where("sucursal_id", $sucursal)
		->where("activo", 1)
		->where("producto_presentacion_id IS NULL", null, false)
		->group_by([
			"producto_id",
			"unidad_medida_id"
		])
		->get_compiled_select();

		if (elemento($args, "termino")) {
			$this->db
			->group_start()
			->like("a.nombre", $args["termino"])
			->or_like("a.codigo", $args["termino"])
			->or_like("c.nombre", $args["termino"])
			->or_like("d.nombre", $args["termino"])
			->or_like("e.nombre", $args["termino"])
			->group_end();
		}

		if (elemento($args, "categoria")) {
			$this->db->where("a.categoria_id", $args["categoria"]);
		}

		if (elemento($args, "marca")) {
			$this->db->where("a.marca_id", $args["marca"]);
		}

		switch (elemento($args, "estado")) {
			case "agotado":
				$this->db->where("{$existencia} <= 0", null, false);
				break;
			case "minimo":
				$this->db->where("{$existencia} > 0 and a.existencia_minima > 0 and {$existencia} < a.existencia_minima", null, false);
				break;
			case "disponible":
				$this->db->where("{$existencia} > 0 and ({$existencia} >= a.existencia_minima or a.existencia_minima <= 0)", null, false);
				break;
		}

		$lista = $this->db
		->select("
			a.id as producto_id,
			a.codigo,
			a.nombre,
			a.codigo_barra,
			a.foto,
			a.categoria_id,
			a.marca_id,
			a.control_vence,
			a.existencia_minima,
			ifnull(a.costo, 0) as costo,
			ifnull(a.precio, 0) as precio,
			ifnull(b.existencia, 0) as existencia,
			b.proximo_vence,
			c.id as unidad_medida_id,
			c.nombre as nunidad,
			d.nombre as ncategoria,
			d.etiqueta as ecategoria,
			e.nombre as nmarca", false)
		->from("producto a")
		->join("({$sub}) b", "b.producto_id = a.id", "left", false)
		->join("unidad_medida c", "c.id = ifnull(b.unidad_medida_id, a.unidad_medida_id)", "", false)
		->join("categoria d", "d.id = a.categoria_id", "left")
		->join("marca e", "e.id = a.marca_id", "left")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.tipo_producto", "B")
		->where("a.activo", 1)
		->order_by("a.nombre", "asc")
		->get()
		->result();

		$presentaciones = $this->presentacionesExistencia();

		foreach ($lista as $fila) {
			$fila->presentaciones = [];

			foreach (elemento($presentaciones, $fila->producto_id, []) as $pre) {
				# Precio y costo de la presentación: los del producto por su factor
				$pre->precio = round((float)$fila->precio * (float)$pre->factor, 2);
				$pre->costo = round((float)$fila->costo * (float)$pre->factor, 5);
				$fila->presentaciones[] = $pre;
			}
		}

		return $lista;
	}

	/**
	 * Presentaciones de los productos de la empresa con su existencia en la sucursal de la sesión:
	 * [producto_id => [presentaciones]]. Las inactivas solo aparecen si todavía tienen existencia.
	 */
	public function presentacionesExistencia()
	{
		$tmp = $this->db
		->select("
			a.id as producto_presentacion_id,
			a.producto_id,
			a.nombre,
			a.factor,
			a.activo,
			ifnull(sum(s.cantidad), 0) as existencia,
			min(case when s.cantidad > 0 then s.fecha_vence end) as proximo_vence", false)
		->from("producto_presentacion a")
		->join("producto b", "b.id = a.producto_id")
		->join("{$this->_tabla} s", "s.producto_presentacion_id = a.id and s.activo = 1 and s.sucursal_id = " . (int)$this->_ses->sucursal_id, "left", false)
		->where("b.empresa_id", $this->_ses->empresa_id)
		->group_by([
			"a.id",
			"a.producto_id",
			"a.nombre",
			"a.factor",
			"a.activo"
		])
		->having("a.activo = 1 or ifnull(sum(s.cantidad), 0) <> 0", null, false)
		->order_by("a.factor", "asc")
		->get()
		->result();

		$lista = [];

		foreach ($tmp as $pre) {
			$lista[$pre->producto_id][] = $pre;
		}

		return $lista;
	}

	# Estado de una fila de existencias(): agotado (sin existencia), minimo (bajo el mínimo) o disponible
	public function estadoExistencia($fila)
	{
		$existencia = (float)$fila->existencia;
		$minimo = (float)$fila->existencia_minima;

		if ($existencia <= 0) {
			return "agotado";
		}

		return $minimo > 0 && $existencia < $minimo ? "minimo" : "disponible";
	}

	/**
	 * Existencia de un producto en la sucursal de la sesión, por unidad, presentación y lote
	 * (sin vencimiento al final). Solo las combinaciones que tienen existencia.
	 */
	public function existenciaProducto($productoId)
	{
		return $this->db
		->select("
			a.unidad_medida_id,
			b.codigo as cunidad,
			a.producto_presentacion_id,
			c.nombre as npresentacion,
			a.fecha_vence,
			sum(a.cantidad) as cantidad", false)
		->from("{$this->_tabla} a")
		->join("unidad_medida b", "b.id = a.unidad_medida_id")
		->join("producto_presentacion c", "c.id = a.producto_presentacion_id", "left")
		->where("a.producto_id", $productoId)
		->where("a.sucursal_id", $this->_ses->sucursal_id)
		->where("a.activo", 1)
		->group_by([
			"a.unidad_medida_id",
			"b.codigo",
			"a.producto_presentacion_id",
			"c.nombre",
			"a.fecha_vence"
		])
		->having("sum(a.cantidad) <> 0", null, false)
		->order_by("a.producto_presentacion_id is null", "desc", false)
		->order_by("c.nombre", "asc")
		->order_by("a.fecha_vence is null", "", false)
		->order_by("a.fecha_vence", "asc")
		->get()
		->result();
	}
}

/* End of file Stock_model.php */
/* Location: ./application/models/Stock_model.php */
