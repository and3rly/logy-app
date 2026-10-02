<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Líneas de producto de una cotización (solo mientras es borrador)
class Cotizacion_detalle extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"ven/Cotizacion_model",
			"ven/Cotizacion_detalle_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$porcentaje = (float)verPropiedad($datos, "descuento_porcentaje", 0);

			if (verPropiedad($datos, "cotizacion_id") &&
				verPropiedad($datos, "producto_id") &&
				(float)verPropiedad($datos, "cantidad", 0) > 0 &&
				property_exists($datos, "precio") &&
				is_numeric($datos->precio) &&
				(float)$datos->precio >= 0) {

				$cot = new Cotizacion_model($datos->cotizacion_id);
				$producto = $this->catalogo->verProductos([
					"id" => $datos->producto_id,
					"_uno" => true
				]);
				$det = new Cotizacion_detalle_model($id);
				$presentacionId = verPropiedad($datos, "producto_presentacion_id", null);
				$presentacion = ($producto && $presentacionId) ? $this->catalogo->verPresentacion($producto->id, $presentacionId) : null;

				# Precio ya con el descuento contra el costo de la presentación (o de la unidad)
				$costo = $producto ? round((float)$producto->costo * ($presentacion ? (float)$presentacion->factor : 1), 2) : 0;
				$neto = round((float)$datos->precio * (1 - $porcentaje / 100), 2);

				if (!$cot->esDeLaSucursal()) {
					$data["mensaje"] = "La cotización no existe.";
				} else if (!$cot->editable()) {
					$data["mensaje"] = "Solo un borrador se puede modificar.";
				} else if (!$producto) {
					$data["mensaje"] = "El producto no existe o está inactivo.";
				} else if ($presentacionId && !$presentacion) {
					$data["mensaje"] = "La presentación no existe o está inactiva.";
				} else if (!empty($id) && (int)$det->cotizacion_id !== (int)$cot->getPK()) {
					$data["mensaje"] = "La línea no pertenece a esta cotización.";
				} else if ($porcentaje < 0 || $porcentaje > 100) {
					$data["mensaje"] = "El descuento debe estar entre 0 y 100 %.";
				} else if ($neto < $costo) {
					$data["mensaje"] = "El precio con descuento no puede ser menor al costo (" . monto($costo) . ").";
				} else if ($det->registrar($cot, $producto, [
					"presentacion" => $presentacion,
					"cantidad" => $datos->cantidad,
					"precio" => $datos->precio,
					"descuento_porcentaje" => $porcentaje,
					"observacion" => verPropiedad($datos, "observacion", null)
				])) {
					$cot->actualizarTotales();

					$data["exito"] = 1;
					$data["mensaje"] = empty($id) ? "Producto agregado." : "Producto actualizado.";
					$data["linea"] = $det->_buscar([
						"id" => $det->getPK(),
						"_uno" => true
					]);
					$data["cotizacion"] = $cot->getInfo();
				} else {
					$data["mensaje"] = $det->getMensaje();
				}
			} else {
				$data["mensaje"] = "Seleccione el producto e indique una cantidad mayor a cero y el precio.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function anular()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "id")) {
				$det = new Cotizacion_detalle_model($datos->id);
				$cot = new Cotizacion_model($det->cotizacion_id);

				if (!$cot->esDeLaSucursal()) {
					$data["mensaje"] = "La línea no existe.";
				} else if (!$cot->editable()) {
					$data["mensaje"] = "Solo un borrador se puede modificar.";
				} else if ($det->guardar(["anulado" => 1])) {
					$cot->actualizarTotales();

					$data["exito"] = 1;
					$data["mensaje"] = "Producto quitado de la cotización.";
					$data["cotizacion"] = $cot->getInfo();
				} else {
					$data["mensaje"] = $det->getMensaje();
				}
			} else {
				$data["mensaje"] = "Seleccione el producto a quitar.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Cotizacion_detalle.php */
/* Location: ./application/controllers/ven/Cotizacion_detalle.php */
