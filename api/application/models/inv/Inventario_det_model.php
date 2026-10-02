<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Línea de un inventario por producto y lote. diferencia = cantidad cargada; en borrador
 * cantidad_sistema es 0 y al procesar se guarda la existencia que ya había en el lote.
 */
class Inventario_det_model extends Centro_model {

	public $inventario_enc_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id = null;
	public $fecha_vence = null;
	public $cantidad_sistema = 0;
	public $cantidad_fisica = 0;
	public $diferencia = 0;
	public $costo = 0;
	public $observacion = null;
	public $activo = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Líneas con datos del producto, su categoría, marca y unidad
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "inventario_enc_id")) {
			$this->db->where("a.inventario_enc_id", $args["inventario_enc_id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.codigo as cproducto,
			b.nombre as nproducto,
			b.codigo_barra,
			b.control_vence,
			c.nombre as nunidad,
			d.nombre as ncategoria,
			d.etiqueta as ecategoria,
			e.nombre as nmarca,
			round(a.diferencia * ifnull(a.costo, 0), 2) as valor", false)
		->from("inventario_det a")
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("categoria d", "d.id = b.categoria_id", "left")
		->join("marca e", "e.id = b.marca_id", "left")
		->order_by("b.nombre", "asc")
		->order_by("a.fecha_vence is null", "", false)
		->order_by("a.fecha_vence", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	/**
	 * Agrega la cantidad al lote del producto en el inventario: si la línea ya existe se suma y el
	 * costo queda promediado por cantidad; si no, se crea. Solo para inventarios en borrador.
	 */
	public function agregar($encId, $args)
	{
		$this->db
		->where("inventario_enc_id", $encId)
		->where("producto_id", $args["producto_id"])
		->where("unidad_medida_id", $args["unidad_medida_id"])
		->where("producto_presentacion_id IS NULL", null, false);

		$args["fecha_vence"] === null
			? $this->db->where("fecha_vence IS NULL", null, false)
			: $this->db->where("fecha_vence", $args["fecha_vence"]);

		$tmp = $this->db->get($this->_tabla)->row();

		if ($tmp) {
			$cantidad = round((float)$tmp->diferencia + $args["cantidad"], 2);
			$costo = $cantidad > 0
				? ((float)$tmp->diferencia * (float)$tmp->costo + $args["cantidad"] * $args["costo"]) / $cantidad
				: $args["costo"];

			return $this->db
			->set("cantidad_fisica", $cantidad)
			->set("diferencia", $cantidad)
			->set("costo", round($costo, 5))
			->where("id", $tmp->id)
			->update($this->_tabla);
		}

		$det = new Inventario_det_model();

		return $det->guardar([
			"inventario_enc_id" => $encId,
			"producto_id" => $args["producto_id"],
			"unidad_medida_id" => $args["unidad_medida_id"],
			"fecha_vence" => $args["fecha_vence"],
			"cantidad_sistema" => 0,
			"cantidad_fisica" => $args["cantidad"],
			"diferencia" => $args["cantidad"],
			"costo" => $args["costo"]
		]);
	}

	public function eliminar()
	{
		return $this->db
		->where("id", $this->getPK())
		->delete($this->_tabla);
	}
}

/* End of file Inventario_det_model.php */
/* Location: ./application/models/inv/Inventario_det_model.php */
