<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Líneas de producto de un ajuste (solo mientras está en borrador)
class Ajuste_detalle extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"inv/Inventario_ajuste_model",
			"inv/Inventario_ajuste_detalle_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	/**
	 * fecha_vence: en una entrada, el vencimiento del lote nuevo (solo productos con control de
	 * vencimiento); en una salida, el lote del que sale (vacío = el que vence primero).
	 */
	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "inventario_ajuste_id") &&
				verPropiedad($datos, "producto_id") &&
				(float)verPropiedad($datos, "cantidad", 0) > 0) {

				$ajuste = new Inventario_ajuste_model($datos->inventario_ajuste_id);
				$producto = $this->catalogo->verProductos([
					"id" => $datos->producto_id,
					"_uno" => true
				]);
				$det = new Inventario_ajuste_detalle_model($id);
				$vence = verPropiedad($datos, "fecha_vence", null);
				$vence = $vence ? substr($vence, 0, 10) : null;
				$presentacionId = verPropiedad($datos, "producto_presentacion_id", null);
				$presentacion = ($producto && $presentacionId) ? $this->catalogo->verPresentacion($producto->id, $presentacionId) : null;

				if (!$ajuste->esDeLaSucursal()) {
					$data["mensaje"] = "El ajuste no existe.";
				} else if (!$ajuste->editable()) {
					$data["mensaje"] = "El ajuste ya fue aplicado o anulado, no se puede modificar.";
				} else if (!$producto || $producto->tipo_producto !== "B") {
					$data["mensaje"] = "El producto no existe, está inactivo o no maneja inventario.";
				} else if ($presentacionId && !$presentacion) {
					$data["mensaje"] = "La presentación no existe o está inactiva.";
				} else if (!empty($id) && (int)$det->inventario_ajuste_id !== (int)$ajuste->getPK()) {
					$data["mensaje"] = "La línea no pertenece a este ajuste.";
				} else {
					$entrada = $ajuste->getTipo()->sentido === "ENTRADA";

					if ($entrada && (int)$producto->control_vence !== 1) {
						$vence = null;
					}

					if (!$entrada && $vence !== null && !$this->existeLote($producto->id, $vence, $presentacionId)) {
						$data["mensaje"] = "El producto no tiene existencia en el lote que vence el " . date("d/m/Y", strtotime($vence)) . ".";
					} else {
						$guardado = $det->guardar([
							"inventario_ajuste_id" => $ajuste->getPK(),
							"producto_id" => $producto->id,
							"unidad_medida_id" => $producto->unidad_medida_id,
							"producto_presentacion_id" => $presentacion ? $presentacion->id : null,
							"cantidad" => round((float)$datos->cantidad, 2),
							"fecha_vence" => $vence
						]);

						if ($guardado) {
							$data["exito"] = 1;
							$data["mensaje"] = empty($id) ? "Producto agregado." : "Producto actualizado.";
							$data["linea"] = $det->_buscar([
								"id" => $det->getPK(),
								"_uno" => true
							]);
							$data["ajuste"] = $ajuste->getInfo();
						} else {
							$data["mensaje"] = $det->getMensaje();
						}
					}
				}
			} else {
				$data["mensaje"] = "Seleccione el producto e indique una cantidad mayor a cero.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Quita la línea (el borrador todavía no movió inventario)
	public function quitar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "id")) {
				$det = new Inventario_ajuste_detalle_model($datos->id);
				$ajuste = new Inventario_ajuste_model($det->inventario_ajuste_id);

				if (!$ajuste->esDeLaSucursal()) {
					$data["mensaje"] = "La línea no existe.";
				} else if (!$ajuste->editable()) {
					$data["mensaje"] = "El ajuste ya fue aplicado o anulado, no se puede modificar.";
				} else if ($det->eliminar()) {
					$data["exito"] = 1;
					$data["mensaje"] = "Producto quitado del ajuste.";
					$data["ajuste"] = $ajuste->getInfo();
				} else {
					$data["mensaje"] = "No se pudo quitar el producto, intente nuevamente.";
				}
			} else {
				$data["mensaje"] = "Seleccione el producto a quitar.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	private function existeLote($productoId, $vence, $presentacionId=null)
	{
		foreach ($this->Inventario_ajuste_detalle_model->lotes($productoId, $presentacionId) as $lote) {
			if ($lote->fecha_vence && substr($lote->fecha_vence, 0, 10) === $vence) {
				return true;
			}
		}

		return false;
	}
}

/* End of file Ajuste_detalle.php */
/* Location: ./application/controllers/inv/Ajuste_detalle.php */
