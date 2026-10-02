<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Compra_detalle_model extends Centro_model {

	public $compra_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id = null;
	public $cantidad = 0;
	public $precio_costo = 0;
	public $total_costo = 0;
	public $anulado = 0;
	public $fecha_vence = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Líneas vigentes con datos del producto y la unidad
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "compra_id")) {
			$this->db->where("a.compra_id", $args["compra_id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.codigo as cproducto,
			b.nombre as nproducto,
			b.foto as fproducto,
			b.control_vence,
			c.codigo as cunidad,
			c.nombre as nunidad,
			d.nombre as npresentacion,
			ifnull(d.factor, 1) as factor", false)
		->from("compra_detalle a")
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("producto_presentacion d", "d.id = a.producto_presentacion_id", "left")
		->where("a.anulado", 0)
		->order_by("a.id", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	# Recalcula el total de la compra con las líneas vigentes
	public function totalesCompra()
	{
		$tmp = $this->db
		->select("ifnull(sum(total_costo), 0) as total_costo", false)
		->where("compra_id", $this->compra_id)
		->where("anulado", 0)
		->get($this->_tabla)
		->row();

		$this->db
		->set("total_costo", $tmp->total_costo)
		->where("id", $this->compra_id)
		->update("compra");
	}
}

/* End of file Compra_detalle_model.php */
/* Location: ./application/models/com/Compra_detalle_model.php */
