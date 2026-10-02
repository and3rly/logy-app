<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Ajuste de inventario: entrada o salida por un motivo conocido (merma, daño, consumo...)
class Inventario_ajuste_model extends Centro_model {

	const BORRADOR = 1;
	const APLICADO = 2;
	const ANULADO = 3;

	public $numero;
	public $inventario_ajuste_tipo_id;
	public $inventario_ajuste_estado_id = self::BORRADOR;
	public $observacion = null;
	public $fecha_aplicado = null;
	public $fecha_anulado = null;
	public $anulado_motivo = null;
	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;
	public $usuario_aplico_id = null;
	public $usuario_anulo_id = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Ajustes de la sucursal de la sesión con tipo, sentido, estado, usuarios y totales de sus líneas
	public function _buscar($args=[])
	{
		# El subquery va primero: get_compiled_select() reinicia el query builder
		# Valor: costo congelado al aplicar; mientras no se aplica, el costo actual del producto
		$totales = $this->db
		->select("
			x.inventario_ajuste_id,
			count(*) as lineas,
			sum(x.cantidad) as unidades,
			sum(round(x.cantidad * if(y.fecha_aplicado is null, ifnull(p.costo, 0) * ifnull(z.factor, 1), x.costo), 2)) as valor", false)
		->from("inventario_ajuste_detalle x")
		->join("inventario_ajuste y", "y.id = x.inventario_ajuste_id")
		->join("producto p", "p.id = x.producto_id")
		->join("producto_presentacion z", "z.id = x.producto_presentacion_id", "left")
		->group_by("x.inventario_ajuste_id")
		->get_compiled_select();

		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		} else {
			if (elemento($args, "fdel")) {
				$this->db->where("a.fecha >=", "{$args['fdel']} 00:00:00");
			}

			if (elemento($args, "fal")) {
				$this->db->where("a.fecha <=", "{$args['fal']} 23:59:59");
			}

			if (elemento($args, "estado")) {
				$this->db->where("a.inventario_ajuste_estado_id", $args["estado"]);
			}

			if (elemento($args, "sentido")) {
				$this->db->where("c.sentido", $args["sentido"]);
			}
		}

		# Sin rol administrador: solo los documentos del usuario
		filtrar_por_usuario("a.usuario_id");

		$tmp = $this->db
		->select("
			a.*,
			b.nombre as ntipo,
			b.requiere_observacion,
			c.codigo as cmovimiento,
			c.sentido,
			d.nombre as nestado,
			d.etiqueta as eestado,
			e.nombre as nsucursal,
			f.nombre as nusuario,
			g.nombre as nusuario_aplico,
			h.nombre as nusuario_anulo,
			ifnull(t.lineas, 0) as lineas,
			ifnull(t.unidades, 0) as unidades,
			ifnull(t.valor, 0) as valor", false)
		->from("inventario_ajuste a")
		->join("inventario_ajuste_tipo b", "b.id = a.inventario_ajuste_tipo_id")
		->join("movimiento_tipo c", "c.id = b.movimiento_tipo_id")
		->join("inventario_ajuste_estado d", "d.id = a.inventario_ajuste_estado_id")
		->join("sucursal e", "e.id = a.sucursal_id")
		->join("usuario f", "f.id = a.usuario_id")
		->join("usuario g", "g.id = a.usuario_aplico_id", "left")
		->join("usuario h", "h.id = a.usuario_anulo_id", "left")
		->join("({$totales}) t", "t.inventario_ajuste_id = a.id", "left", false)
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.sucursal_id", $this->_ses->sucursal_id)
		->order_by("a.fecha", "desc")
		->order_by("a.id", "desc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	public function getInfo()
	{
		return $this->_buscar([
			"id" => $this->getPK(),
			"_uno" => true
		]);
	}

	public function getDetalle()
	{
		$det = new Inventario_ajuste_detalle_model();

		return $det->_buscar([
			"inventario_ajuste_id" => $this->getPK()
		]);
	}

	# Tipo del ajuste con el sentido de su movimiento
	public function getTipo()
	{
		return $this->catalogo->verAjusteTipos([
			"id" => $this->inventario_ajuste_tipo_id,
			"_todos" => true,
			"_uno" => true
		]);
	}

	# El ajuste es de la empresa y de la sucursal de la sesión
	public function esDeLaSucursal()
	{
		return $this->getPK() &&
			(int)$this->empresa_id === (int)$this->_ses->empresa_id &&
			(int)$this->sucursal_id === (int)$this->_ses->sucursal_id &&
			puede_ver_documento($this->usuario_id);
	}

	# Solo un borrador se puede modificar
	public function editable()
	{
		return (int)$this->inventario_ajuste_estado_id === self::BORRADOR;
	}

	public function tieneLineas()
	{
		return $this->db
		->where("inventario_ajuste_id", $this->getPK())
		->count_all_results("inventario_ajuste_detalle") > 0;
	}

	# Correlativo por empresa, ej. AJ-000006
	public function asignarNumero()
	{
		if (!empty($this->numero)) {
			return;
		}

		$tmp = $this->db
		->select("count(*) + 1 as siguiente", false)
		->where("empresa_id", $this->_ses->empresa_id)
		->get($this->_tabla)
		->row();

		$this->numero = "AJ-" . str_pad($tmp->siguiente, 6, "0", STR_PAD_LEFT);
	}

	/**
	 * Aplica el borrador en una sola transacción: congela el costo de cada línea y mueve el stock
	 * de la sucursal. Entrada: suma al lote indicado (o sin vencimiento). Salida: descuenta del
	 * lote indicado o, si no se indicó, del que vence primero; sin existencia no se aplica nada.
	 */
	public function aplicar()
	{
		$tipo = $this->getTipo();
		$detalle = $this->getDetalle();

		if (count($detalle) === 0) {
			$this->setMensaje("Agregue al menos un producto antes de aplicar el ajuste.");
			return false;
		}

		if ((int)$tipo->requiere_observacion === 1 && trim((string)$this->observacion) === "") {
			$this->setMensaje("El tipo {$tipo->nombre} requiere una observación; guárdela antes de aplicar.");
			return false;
		}

		$entrada = $tipo->sentido === "ENTRADA";

		$this->db->trans_begin();

		# Bloquea el ajuste: dos usuarios no lo aplican a la vez
		$actual = $this->db->query("
			select inventario_ajuste_estado_id
			from {$this->_tabla}
			where id = ?
			for update", [$this->getPK()])
		->row();

		if ((int)$actual->inventario_ajuste_estado_id !== self::BORRADOR) {
			return $this->cancelar("El ajuste ya fue aplicado o anulado.");
		}

		foreach ($detalle as $det) {
			$this->db
			->set("costo", $det->costo_unitario)
			->where("id", $det->id)
			->update("inventario_ajuste_detalle");

			$stock = new Stock_model();
			$observacion = "{$tipo->nombre} {$this->numero}";

			if ($entrada) {
				$lotes = [
					$stock->sumar([
						"sucursal_id" => $this->sucursal_id,
						"producto_id" => $det->producto_id,
						"unidad_medida_id" => $det->unidad_medida_id,
						"producto_presentacion_id" => $det->producto_presentacion_id,
						"fecha_vence" => $det->fecha_vence,
						"cantidad" => $det->cantidad
					]) => (float)$det->cantidad
				];
			} else {
				$lotes = $stock->descontar([
					"sucursal_id" => $this->sucursal_id,
					"producto_id" => $det->producto_id,
					"unidad_medida_id" => $det->unidad_medida_id,
					"producto_presentacion_id" => $det->producto_presentacion_id,
					"fecha_vence" => $det->fecha_vence,
					"cantidad" => $det->cantidad
				]);

				if ($lotes === false) {
					$lote = $det->fecha_vence ? " en el lote que vence el " . date("d/m/Y", strtotime($det->fecha_vence)) : "";
					$presentacion = $det->npresentacion ? " ({$det->npresentacion})" : "";
					return $this->cancelar("No hay existencia suficiente de {$det->nproducto}{$presentacion}{$lote}.");
				}
			}

			foreach ($lotes as $stockId => $cantidad) {
				$mov = new Movimiento_model();
				$mov->guardar([
					"stock_id" => $stockId,
					"movimiento_tipo_id" => $tipo->movimiento_tipo_id,
					"cantidad" => $entrada ? $cantidad : -$cantidad,
					"inventario_ajuste_detalle_id" => $det->id,
					"observacion" => $observacion
				]);
			}
		}

		$this->db
		->set("inventario_ajuste_estado_id", self::APLICADO)
		->set("fecha_aplicado", "now()", false)
		->set("usuario_aplico_id", $this->_ses->id)
		->where("id", $this->getPK())
		->update($this->_tabla);

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo aplicar el ajuste, intente nuevamente.");
		}

		$this->db->trans_commit();
		$this->cargar($this->getPK());

		return true;
	}

	/**
	 * Anula el ajuste. Un borrador solo cambia de estado. Uno aplicado revierte su neto por lote
	 * con AAE (anula una entrada: resta) o AAS (anula una salida: suma); una entrada no se puede
	 * revertir si esa existencia ya salió del lote.
	 */
	public function anular($motivo)
	{
		if ((int)$this->inventario_ajuste_estado_id === self::ANULADO) {
			$this->setMensaje("El ajuste ya está anulado.");
			return false;
		}

		$this->db->trans_begin();

		if ((int)$this->inventario_ajuste_estado_id === self::APLICADO) {
			$tipo = $this->getTipo();
			$codigo = $tipo->sentido === "ENTRADA" ? "AAE" : "AAS";

			$anulacion = $this->catalogo->verMovimientoTipos([
				"codigo" => $codigo,
				"_uno" => true
			]);

			if (!$anulacion) {
				return $this->cancelar("No existe el tipo de movimiento de anulación ({$codigo}).");
			}

			$netos = $this->db
			->select("
				a.stock_id,
				a.inventario_ajuste_detalle_id,
				sum(a.cantidad) as neto,
				c.nombre as nproducto", false)
			->from("movimiento a")
			->join("inventario_ajuste_detalle b", "b.id = a.inventario_ajuste_detalle_id")
			->join("producto c", "c.id = b.producto_id")
			->where("b.inventario_ajuste_id", $this->getPK())
			->group_by([
				"a.stock_id",
				"a.inventario_ajuste_detalle_id",
				"c.nombre"
			])
			->having("sum(a.cantidad) <>", 0)
			->get()
			->result();

			foreach ($netos as $neto) {
				$reverso = -(float)$neto->neto;

				if ($reverso < 0) {
					$lote = $this->db->query("
						select cantidad
						from stock
						where id = ?
						for update", [$neto->stock_id])
					->row();

					if (round((float)$lote->cantidad, 2) < round(-$reverso, 2)) {
						return $this->cancelar("No se puede anular: parte de la existencia de {$neto->nproducto} que entró con este ajuste ya salió del inventario.");
					}
				}

				$this->db
				->set("cantidad", "cantidad + " . $reverso, false)
				->where("id", $neto->stock_id)
				->update("stock");

				$mov = new Movimiento_model();
				$mov->guardar([
					"stock_id" => $neto->stock_id,
					"movimiento_tipo_id" => $anulacion->id,
					"cantidad" => $reverso,
					"inventario_ajuste_detalle_id" => $neto->inventario_ajuste_detalle_id,
					"observacion" => "Anulación del ajuste {$this->numero}"
				]);
			}
		}

		$this->db
		->set("inventario_ajuste_estado_id", self::ANULADO)
		->set("fecha_anulado", "now()", false)
		->set("usuario_anulo_id", $this->_ses->id)
		->set("anulado_motivo", $motivo)
		->where("id", $this->getPK())
		->update($this->_tabla);

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo anular el ajuste, intente nuevamente.");
		}

		$this->db->trans_commit();
		$this->cargar($this->getPK());

		return true;
	}

	private function cancelar($mensaje)
	{
		$this->db->trans_rollback();
		$this->setMensaje($mensaje);

		return false;
	}
}

/* End of file Inventario_ajuste_model.php */
/* Location: ./application/models/inv/Inventario_ajuste_model.php */
