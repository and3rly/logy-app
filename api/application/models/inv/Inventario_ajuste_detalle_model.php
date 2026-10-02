<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Línea de un ajuste: la cantidad siempre es positiva, el signo lo da el tipo del ajuste
class Inventario_ajuste_detalle_model extends Centro_model {

	public $inventario_ajuste_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id = null;
	public $cantidad = 0;
	public $costo = 0;
	public $fecha_vence = null;
	public $observacion = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	/**
	 * Líneas con datos del producto y la unidad. costo_unitario: el congelado al aplicar o, en
	 * borrador, el costo actual del producto. existencia: la del lote indicado (o de todos los
	 * lotes si no se indicó) en la sucursal del ajuste.
	 */
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "inventario_ajuste_id")) {
			$this->db->where("a.inventario_ajuste_id", $args["inventario_ajuste_id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.codigo as cproducto,
			b.nombre as nproducto,
			b.control_vence,
			c.codigo as cunidad,
			c.nombre as nunidad,
			if(d.fecha_aplicado is null, round(ifnull(b.costo, 0) * ifnull(e.factor, 1), 5), a.costo) as costo_unitario,
			e.nombre as npresentacion,
			(select ifnull(sum(s.cantidad), 0)
				from stock s
				where s.producto_id = a.producto_id
				and s.unidad_medida_id = a.unidad_medida_id
				and s.producto_presentacion_id <=> a.producto_presentacion_id
				and s.sucursal_id = d.sucursal_id
				and s.activo = 1
				and s.cantidad > 0
				and (a.fecha_vence is null or s.fecha_vence = a.fecha_vence)) as existencia", false)
		->from("inventario_ajuste_detalle a")
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("inventario_ajuste d", "d.id = a.inventario_ajuste_id")
		->join("producto_presentacion e", "e.id = a.producto_presentacion_id", "left")
		->order_by("a.id", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	# Lotes con existencia de un producto (en la presentación indicada o en la unidad) en la sucursal de la sesión (sin vencimiento al final)
	public function lotes($productoId, $presentacionId=null)
	{
		$presentacionId
			? $this->db->where("producto_presentacion_id", $presentacionId)
			: $this->db->where("producto_presentacion_id IS NULL", null, false);

		return $this->db
		->select("
			fecha_vence,
			sum(cantidad) as cantidad", false)
		->where("producto_id", $productoId)
		->where("sucursal_id", $this->_ses->sucursal_id)
		->where("activo", 1)
		->where("cantidad >", 0)
		->group_by("fecha_vence")
		->order_by("fecha_vence is null", "", false)
		->order_by("fecha_vence", "asc")
		->get("stock")
		->result();
	}

	public function eliminar()
	{
		return $this->db
		->where("id", $this->getPK())
		->delete($this->_tabla);
	}
}

/* End of file Inventario_ajuste_detalle_model.php */
/* Location: ./application/models/inv/Inventario_ajuste_detalle_model.php */
