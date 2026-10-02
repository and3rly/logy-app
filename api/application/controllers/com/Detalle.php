<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Líneas de producto de una compra (solo mientras la compra está creada)
class Detalle extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"com/Compra_model",
			"com/Compra_detalle_model"
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

			if (verPropiedad($datos, "compra_id") &&
				verPropiedad($datos, "producto_id") &&
				(float)verPropiedad($datos, "cantidad", 0) > 0 &&
				property_exists($datos, "precio_costo") &&
				(float)$datos->precio_costo >= 0) {

				$compra = new Compra_model($datos->compra_id);
				$producto = $this->catalogo->verProductos([
					"id" => $datos->producto_id,
					"_uno" => true
				]);
				$det = new Compra_detalle_model($id);
				$presentacionId = verPropiedad($datos, "producto_presentacion_id", null);
				$presentacion = ($producto && $presentacionId) ? $this->catalogo->verPresentacion($producto->id, $presentacionId) : null;

				if (!$compra->esDeLaSucursal()) {
					$data["mensaje"] = "La compra no existe.";
				} else if (!$compra->editable()) {
					$data["mensaje"] = "La compra ya fue recibida o anulada, no se puede modificar.";
				} else if (!$producto) {
					$data["mensaje"] = "El producto no existe o está inactivo.";
				} else if ($presentacionId && !$presentacion) {
					$data["mensaje"] = "La presentación no existe o está inactiva.";
				} else if (!empty($id) && (int)$det->compra_id !== (int)$compra->getPK()) {
					$data["mensaje"] = "La línea no pertenece a esta compra.";
				} else {
					$cantidad = (float)$datos->cantidad;
					$costo = (float)$datos->precio_costo;

					$guardado = $det->guardar([
						"compra_id" => $compra->getPK(),
						"producto_id" => $producto->id,
						"unidad_medida_id" => $producto->unidad_medida_id,
						"producto_presentacion_id" => $presentacion ? $presentacion->id : null,
						"cantidad" => $cantidad,
						"precio_costo" => $costo,
						"total_costo" => round($cantidad * $costo, 2),
						# La compra no pide vencimiento; se conserva si la línea ya lo tenía
						"fecha_vence" => (int)$producto->control_vence === 1 ? verPropiedad($datos, "fecha_vence", null) : null
					]);

					if ($guardado) {
						$det->totalesCompra();

						$data["exito"] = 1;
						$data["mensaje"] = empty($id) ? "Producto agregado." : "Producto actualizado.";
						$data["linea"] = $det->_buscar([
							"id" => $det->getPK(),
							"_uno" => true
						]);
						$data["compra"] = $compra->getInfo();
					} else {
						$data["mensaje"] = $det->getMensaje();
					}
				}
			} else {
				$data["mensaje"] = "Seleccione el producto e indique una cantidad mayor a cero y el costo.";
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
				$det = new Compra_detalle_model($datos->id);
				$compra = new Compra_model($det->compra_id);

				if (!$compra->esDeLaSucursal()) {
					$data["mensaje"] = "La línea no existe.";
				} else if (!$compra->editable()) {
					$data["mensaje"] = "La compra ya fue recibida o anulada, no se puede modificar.";
				} else if ($det->guardar(["anulado" => 1])) {
					$det->totalesCompra();

					$data["exito"] = 1;
					$data["mensaje"] = "Producto quitado de la compra.";
					$data["compra"] = $compra->getInfo();
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

/* End of file Detalle.php */
/* Location: ./application/controllers/com/Detalle.php */
