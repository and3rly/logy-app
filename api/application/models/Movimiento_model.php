<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Bitácora de entradas y salidas de inventario
class Movimiento_model extends Centro_model {

	public $stock_id;
	public $movimiento_tipo_id;
	public $cantidad;
	public $compra_id = null;
	public $inventario_ajuste_detalle_id = null;
	public $inventario_det_id = null;
	public $venta_detalle_id = null;
	public $inventario_conversion_id = null;
	public $usuario_id;
	public $observacion = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	/**
	 * Kardex de la sucursal de la sesión: movimientos entre fdel y fal (fechas) en orden cronológico,
	 * con la cantidad con signo según el sentido del tipo (+ entra, - sale).
	 * Filtros opcionales: producto (sin él, todos los productos), presentacion (con producto; sin ella,
	 * la unidad de medida), fdel, fal y lote (fecha_vence).
	 */
	public function kardex($args=[])
	{
		$this->filtrosKardex($args);

		if (elemento($args, "fdel")) {
			$this->db->where("m.fecha >=", $args["fdel"] . " 00:00:00");
		}

		if (elemento($args, "fal")) {
			$this->db->where("m.fecha <=", $args["fal"] . " 23:59:59");
		}

		return $this->db
		->select("
			m.id,
			m.fecha,
			m.observacion,
			s.producto_id,
			p.codigo as cproducto,
			p.nombre as nproducto,
			s.producto_presentacion_id,
			pp.nombre as npresentacion,
			um.nombre as nunidad,
			{$this->cantidadConSigno()} as cantidad,
			t.codigo as ctipo,
			t.nombre as ntipo,
			t.sentido,
			s.fecha_vence,
			u.nombre as nusuario,
			coalesce(c.numero, ia.numero, ie.numero, v.correlativo, cv.numero) as documento", false)
		->join("usuario u", "u.id = m.usuario_id")
		->join("unidad_medida um", "um.id = s.unidad_medida_id")
		->join("producto_presentacion pp", "pp.id = s.producto_presentacion_id", "left")
		->join("compra c", "c.id = m.compra_id", "left")
		->join("inventario_ajuste_detalle iad", "iad.id = m.inventario_ajuste_detalle_id", "left")
		->join("inventario_ajuste ia", "ia.id = iad.inventario_ajuste_id", "left")
		->join("inventario_det idt", "idt.id = m.inventario_det_id", "left")
		->join("inventario_enc ie", "ie.id = idt.inventario_enc_id", "left")
		->join("venta_detalle vd", "vd.id = m.venta_detalle_id", "left")
		->join("venta v", "v.id = vd.venta_id", "left")
		->join("inventario_conversion cv", "cv.id = m.inventario_conversion_id", "left")
		->order_by("m.fecha", "asc")
		->order_by("m.id", "asc")
		->get()
		->result();
	}

	# Existencia de cada producto y presentación antes del día fdel (suma de sus movimientos anteriores):
	# [llaveSaldo() => saldo]
	public function saldosKardex($args=[])
	{
		if (!elemento($args, "fdel")) {
			return [];
		}

		$this->filtrosKardex($args);

		$tmp = $this->db
		->select("s.producto_id, s.producto_presentacion_id, sum({$this->cantidadConSigno()}) as saldo", false)
		->where("m.fecha <", $args["fdel"] . " 00:00:00")
		->group_by([
			"s.producto_id",
			"s.producto_presentacion_id"
		])
		->get()
		->result();

		$saldos = [];

		foreach ($tmp as $reg) {
			$saldos[$this->llaveSaldo($reg->producto_id, $reg->producto_presentacion_id)] = (float)$reg->saldo;
		}

		return $saldos;
	}

	# Saldo por producto y presentación (sin presentación = unidad de medida)
	public function llaveSaldo($producto, $presentacion=null)
	{
		return $producto . "-" . (int)$presentacion;
	}

	# Lotes (vencimientos) que ha tenido el producto (en la presentación o en la unidad) en la sucursal, con o sin existencia
	public function lotesKardex($producto, $presentacion=null)
	{
		$presentacion
			? $this->db->where("producto_presentacion_id", $presentacion)
			: $this->db->where("producto_presentacion_id IS NULL", null, false);

		return $this->db
		->select("date(fecha_vence) as fecha, sum(cantidad) as existencia", false)
		->where("producto_id", $producto)
		->where("sucursal_id", $this->_ses->sucursal_id)
		->where("fecha_vence IS NOT NULL", null, false)
		->group_by("date(fecha_vence)")
		->order_by("fecha", "asc")
		->get("stock")
		->result();
	}

	# Tablas y filtros comunes del kardex: productos de la empresa, sucursal de la sesión, producto, presentación y lote
	private function filtrosKardex($args)
	{
		$this->db
		->from("movimiento m")
		->join("movimiento_tipo t", "t.id = m.movimiento_tipo_id")
		->join("stock s", "s.id = m.stock_id")
		->join("producto p", "p.id = s.producto_id")
		->where("s.sucursal_id", $this->_ses->sucursal_id)
		->where("p.empresa_id", $this->_ses->empresa_id);

		# Con producto, el kardex es de una presentación o de la unidad (no se mezclan)
		if (elemento($args, "producto")) {
			$this->db->where("s.producto_id", $args["producto"]);

			elemento($args, "presentacion")
				? $this->db->where("s.producto_presentacion_id", $args["presentacion"])
				: $this->db->where("s.producto_presentacion_id IS NULL", null, false);
		}

		if (elemento($args, "lote")) {
			$this->db->where("date(s.fecha_vence)", $args["lote"]);
		}
	}

	# El signo lo da el sentido del tipo, no la cantidad guardada
	private function cantidadConSigno()
	{
		return "case when t.sentido = 'ENTRADA' then abs(m.cantidad) else -abs(m.cantidad) end";
	}
}

/* End of file Movimiento_model.php */
/* Location: ./application/models/Movimiento_model.php */
