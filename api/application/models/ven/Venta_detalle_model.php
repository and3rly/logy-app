<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Venta_detalle_model extends Centro_model {

	public $venta_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id = null;
	public $cantidad = 0;
	public $precio = 0;
	public $costo = 0;
	public $total_precio = 0;
	public $descuento = 0;
	public $descuento_total = 0;
	public $total_costo = 0;
	public $ganancia = 0;
	public $anulado = 0;

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

		if (elemento($args, "venta_id")) {
			$this->db->where("a.venta_id", $args["venta_id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.codigo as cproducto,
			b.nombre as nproducto,
			c.codigo as cunidad,
			c.nombre as nunidad,
			d.nombre as npresentacion,
			d.factor")
		->from("venta_detalle a")
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("producto_presentacion d", "d.id = a.producto_presentacion_id", "left")
		->where("a.anulado", 0)
		->order_by("a.id", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}
}

/* End of file Venta_detalle_model.php */
/* Location: ./application/models/ven/Venta_detalle_model.php */
