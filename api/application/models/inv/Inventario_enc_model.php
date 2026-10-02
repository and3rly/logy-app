<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Inventario de una sucursal (por ahora solo el inicial): carga existencias con IVP al procesarse
class Inventario_enc_model extends Centro_model {

	# inventario_tipo e inventario_estado (ids de config/instalacion.php)
	const INICIAL = 1;

	const BORRADOR = 1;
	const PROCESADO = 3;
	const ANULADO = 4;

	public $numero;
	public $inventario_tipo_id = self::INICIAL;
	public $inventario_estado_id = self::BORRADOR;
	public $observacion = null;
	public $archivo_nombre = null;
	public $archivo_hash = null;
	public $fecha_procesado = null;
	public $fecha_anulado = null;
	public $anulado_motivo = null;
	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;
	public $usuario_proceso_id = null;
	public $usuario_anulo_id = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Inventarios de la sucursal de la sesión con estado, usuarios y totales de sus líneas
	public function _buscar($args=[])
	{
		# El subquery va primero: get_compiled_select() reinicia el query builder
		$totales = $this->db
		->select("
			inventario_enc_id,
			count(*) as lineas,
			count(distinct producto_id) as productos,
			sum(diferencia) as unidades,
			sum(round(diferencia * ifnull(costo, 0), 2)) as valor", false)
		->from("inventario_det")
		->group_by("inventario_enc_id")
		->get_compiled_select();

		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "tipo")) {
			$this->db->where("a.inventario_tipo_id", $args["tipo"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.nombre as ntipo,
			c.codigo as cestado,
			c.nombre as nestado,
			d.nombre as nsucursal,
			e.nombre as nusuario,
			f.nombre as nusuario_proceso,
			g.nombre as nusuario_anulo,
			ifnull(t.lineas, 0) as lineas,
			ifnull(t.productos, 0) as productos,
			ifnull(t.unidades, 0) as unidades,
			ifnull(t.valor, 0) as valor", false)
		->from("inventario_enc a")
		->join("inventario_tipo b", "b.id = a.inventario_tipo_id")
		->join("inventario_estado c", "c.id = a.inventario_estado_id")
		->join("sucursal d", "d.id = a.sucursal_id")
		->join("usuario e", "e.id = a.usuario_id")
		->join("usuario f", "f.id = a.usuario_proceso_id", "left")
		->join("usuario g", "g.id = a.usuario_anulo_id", "left")
		->join("({$totales}) t", "t.inventario_enc_id = a.id", "left", false)
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
		$det = new Inventario_det_model();

		return $det->_buscar([
			"inventario_enc_id" => $this->getPK()
		]);
	}

	# Inventario inicial vigente (en borrador o procesado) de la sucursal de la sesión; cada sucursal tiene uno
	public function cargarInicial()
	{
		$tmp = $this->db
		->select("id")
		->where("empresa_id", $this->_ses->empresa_id)
		->where("sucursal_id", $this->_ses->sucursal_id)
		->where("inventario_tipo_id", self::INICIAL)
		->where("inventario_estado_id <>", self::ANULADO)
		->order_by("id", "desc")
		->get($this->_tabla)
		->row();

		if ($tmp) {
			$this->cargar($tmp->id);
		}

		return $tmp !== null;
	}

	# El inventario es de la empresa y de la sucursal de la sesión
	public function esDeLaSucursal()
	{
		return $this->getPK() &&
			(int)$this->empresa_id === (int)$this->_ses->empresa_id &&
			(int)$this->sucursal_id === (int)$this->_ses->sucursal_id;
	}

	# Solo un borrador se puede modificar
	public function editable()
	{
		return (int)$this->inventario_estado_id === self::BORRADOR;
	}

	# Correlativo por empresa, ej. INV-000001
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

		$this->numero = "INV-" . str_pad($tmp->siguiente, 6, "0", STR_PAD_LEFT);
	}

	/**
	 * Procesa el borrador en una sola transacción: cada línea suma su cantidad al lote de la
	 * sucursal (si ya tenía existencia, se suma) con un movimiento IVP. Deja registrada la
	 * existencia que había (cantidad_sistema) y la resultante (cantidad_fisica).
	 */
	public function procesar()
	{
		$detalle = $this->getDetalle();

		if (count($detalle) === 0) {
			$this->setMensaje("Importe al menos un producto antes de procesar el inventario.");
			return false;
		}

		$tipo = $this->catalogo->verMovimientoTipos([
			"codigo" => "IVP",
			"_uno" => true
		]);

		if (!$tipo) {
			$this->setMensaje("No existe el tipo de movimiento de inventario positivo (IVP).");
			return false;
		}

		$this->db->trans_begin();

		# Bloquea el inventario: dos usuarios no lo procesan a la vez
		$actual = $this->db->query("
			select inventario_estado_id
			from {$this->_tabla}
			where id = ?
			for update", [$this->getPK()])
		->row();

		if ((int)$actual->inventario_estado_id !== self::BORRADOR) {
			return $this->cancelar("El inventario ya fue procesado o anulado.");
		}

		foreach ($detalle as $det) {
			$cantidad = (float)$det->diferencia;
			$sistema = $this->existenciaLote($det);

			$this->db
			->set("cantidad_sistema", $sistema)
			->set("cantidad_fisica", $sistema + $cantidad)
			->set("diferencia", $cantidad)
			->where("id", $det->id)
			->update("inventario_det");

			$stock = new Stock_model();
			$stockId = $stock->sumar([
				"sucursal_id" => $this->sucursal_id,
				"producto_id" => $det->producto_id,
				"unidad_medida_id" => $det->unidad_medida_id,
				"producto_presentacion_id" => $det->producto_presentacion_id,
				"fecha_vence" => $det->fecha_vence,
				"cantidad" => $cantidad
			]);

			$mov = new Movimiento_model();
			$mov->guardar([
				"stock_id" => $stockId,
				"movimiento_tipo_id" => $tipo->id,
				"cantidad" => $cantidad,
				"inventario_det_id" => $det->id,
				"observacion" => "Inventario inicial {$this->numero}"
			]);
		}

		$this->db
		->set("inventario_estado_id", self::PROCESADO)
		->set("fecha_procesado", "now()", false)
		->set("usuario_proceso_id", $this->_ses->id)
		->where("id", $this->getPK())
		->update($this->_tabla);

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo procesar el inventario, intente nuevamente.");
		}

		$this->db->trans_commit();
		$this->cargar($this->getPK());

		return true;
	}

	/**
	 * Anula el inventario. Un borrador solo cambia de estado. Uno procesado resta de cada lote lo
	 * que entró (AIP); no se puede si parte de esa existencia ya salió del inventario.
	 * Después la sucursal puede cargar otro inventario inicial.
	 */
	public function anular($motivo)
	{
		if ((int)$this->inventario_estado_id === self::ANULADO) {
			$this->setMensaje("El inventario ya está anulado.");
			return false;
		}

		$this->db->trans_begin();

		if ((int)$this->inventario_estado_id === self::PROCESADO) {
			$anulacion = $this->catalogo->verMovimientoTipos([
				"codigo" => "AIP",
				"_uno" => true
			]);

			if (!$anulacion) {
				return $this->cancelar("No existe el tipo de movimiento de anulación (AIP).");
			}

			$netos = $this->db
			->select("
				a.stock_id,
				a.inventario_det_id,
				sum(a.cantidad) as neto,
				c.nombre as nproducto", false)
			->from("movimiento a")
			->join("inventario_det b", "b.id = a.inventario_det_id")
			->join("producto c", "c.id = b.producto_id")
			->where("b.inventario_enc_id", $this->getPK())
			->group_by([
				"a.stock_id",
				"a.inventario_det_id",
				"c.nombre"
			])
			->having("sum(a.cantidad) <>", 0)
			->get()
			->result();

			foreach ($netos as $neto) {
				$reverso = -(float)$neto->neto;

				$lote = $this->db->query("
					select cantidad
					from stock
					where id = ?
					for update", [$neto->stock_id])
				->row();

				if (round((float)$lote->cantidad, 2) < round(-$reverso, 2)) {
					return $this->cancelar("No se puede anular: parte de la existencia de {$neto->nproducto} que entró con este inventario ya salió.");
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
					"inventario_det_id" => $neto->inventario_det_id,
					"observacion" => "Anulación del inventario {$this->numero}"
				]);
			}
		}

		$this->db
		->set("inventario_estado_id", self::ANULADO)
		->set("fecha_anulado", "now()", false)
		->set("usuario_anulo_id", $this->_ses->id)
		->set("anulado_motivo", $motivo)
		->where("id", $this->getPK())
		->update($this->_tabla);

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo anular el inventario, intente nuevamente.");
		}

		$this->db->trans_commit();
		$this->cargar($this->getPK());

		return true;
	}

	# Existencia actual del lote de una línea en la sucursal del inventario
	private function existenciaLote($det)
	{
		$this->db
		->select("ifnull(sum(cantidad), 0) as cantidad", false)
		->where("sucursal_id", $this->sucursal_id)
		->where("producto_id", $det->producto_id)
		->where("unidad_medida_id", $det->unidad_medida_id)
		->where("activo", 1);

		$det->producto_presentacion_id === null
			? $this->db->where("producto_presentacion_id IS NULL", null, false)
			: $this->db->where("producto_presentacion_id", $det->producto_presentacion_id);

		$det->fecha_vence === null
			? $this->db->where("fecha_vence IS NULL", null, false)
			: $this->db->where("fecha_vence", $det->fecha_vence);

		return max(0, (float)$this->db->get("stock")->row()->cantidad);
	}

	private function cancelar($mensaje)
	{
		$this->db->trans_rollback();
		$this->setMensaje($mensaje);

		return false;
	}
}

/* End of file Inventario_enc_model.php */
/* Location: ./application/models/inv/Inventario_enc_model.php */
