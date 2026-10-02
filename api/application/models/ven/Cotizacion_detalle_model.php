<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cotizacion_detalle_model extends Centro_model {

	public $cotizacion_id;
	public $orden = 1;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id = null;
	public $producto_codigo;
	public $producto_nombre;
	public $producto_descripcion = null;
	public $unidad_codigo;
	public $presentacion_nombre = null;
	public $cantidad = 0;
	public $precio = 0;
	public $costo = 0;
	public $subtotal = 0;
	public $descuento_porcentaje = 0;
	public $descuento_total = 0;
	public $total = 0;
	public $total_costo = 0;
	public $ganancia = 0;
	public $observacion = null;
	public $anulado = 0;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Líneas vigentes en el orden en que se agregaron
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "cotizacion_id")) {
			$this->db->where("a.cotizacion_id", $args["cotizacion_id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.foto as fproducto")
		->from("cotizacion_detalle a")
		->join("producto b", "b.id = a.producto_id")
		->where("a.anulado", 0)
		->order_by("a.orden", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	/**
	 * Crea o actualiza la línea con los datos del producto copiados y los importes calculados.
	 * $datos: presentacion (fila de producto_presentacion o null = unidad de medida), cantidad,
	 * precio, descuento_porcentaje, observacion
	 */
	public function registrar($cotizacion, $producto, $datos)
	{
		$presentacion = elemento($datos, "presentacion", null);
		$factor = $presentacion ? (float)$presentacion->factor : 1;

		$cantidad = round((float)$datos["cantidad"], 2);
		$precio = round((float)$datos["precio"], 2);
		$porcentaje = round((float)$datos["descuento_porcentaje"], 2);
		$costo = round((float)$producto->costo * $factor, 5);

		$subtotal = round($cantidad * $precio, 2);
		$descuento = round($subtotal * $porcentaje / 100, 2);
		$total = $subtotal - $descuento;
		$totalCosto = round($cantidad * $costo, 2);

		$unidad = $this->db
		->select("codigo")
		->where("id", $producto->unidad_medida_id)
		->get("unidad_medida")
		->row();

		# La descripción del producto viene del editor (HTML): en el documento va como texto
		$descripcion = textoPlano($producto->descripcion);

		$campos = [
			"cotizacion_id" => $cotizacion->getPK(),
			"producto_id" => $producto->id,
			"unidad_medida_id" => $producto->unidad_medida_id,
			"producto_presentacion_id" => $presentacion ? $presentacion->id : null,
			"producto_codigo" => mb_substr($producto->codigo, 0, 150),
			"producto_nombre" => mb_substr($producto->nombre, 0, 300),
			"producto_descripcion" => $descripcion !== "" ? mb_substr($descripcion, 0, 300) : null,
			"unidad_codigo" => $unidad ? mb_substr($unidad->codigo, 0, 10) : "",
			"presentacion_nombre" => $presentacion ? mb_substr($presentacion->nombre, 0, 50) : null,
			"cantidad" => $cantidad,
			"precio" => $precio,
			"costo" => $costo,
			"subtotal" => $subtotal,
			"descuento_porcentaje" => $porcentaje,
			"descuento_total" => $descuento,
			"total" => $total,
			"total_costo" => $totalCosto,
			"ganancia" => round($total - $totalCosto, 2),
			"observacion" => $datos["observacion"] ? mb_substr(trim($datos["observacion"]), 0, 300) : null
		];

		if (!$this->getPK()) {
			$campos["orden"] = $this->siguienteOrden($cotizacion->getPK());
		}

		# Sin cambios también es correcto
		return $this->guardar($campos) || ($this->getPK() && $this->getMensaje() === "Nada que actualizar");
	}

	# El orden es único por cotización, incluidas las líneas quitadas
	private function siguienteOrden($cotizacionId)
	{
		$tmp = $this->db
		->select("ifnull(max(orden), 0) + 1 as siguiente", false)
		->where("cotizacion_id", $cotizacionId)
		->get($this->_tabla)
		->row();

		return (int)$tmp->siguiente;
	}
}

/* End of file Cotizacion_detalle_model.php */
/* Location: ./application/models/ven/Cotizacion_detalle_model.php */
