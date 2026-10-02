<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Proveedor extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Proveedor_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Proveedor_model->buscar([
				"empresa_id" => $this->_ses->empresa_id,
				"_orden_asc" => "nombre"
			])
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {

				# Campos opcionales vacíos se guardan como NULL
				foreach (["identificacion", "direccion", "telefono", "correo"] as $campo) {
					if (property_exists($datos, $campo) && trim((string)$datos->$campo) === "") {
						$datos->$campo = null;
					}
				}

				if (property_exists($datos, "identificacion") && $datos->identificacion !== null) {
					$datos->identificacion = strtoupper(trim($datos->identificacion));
				}

				# Sin crédito, límite y días en cero (la base no admite NULL en el límite)
				if (!verPropiedad($datos, "credito")) {
					$datos->credito = 0;
					$datos->credito_limite = 0;
					$datos->credito_dias = 0;
				} else {
					if (!verPropiedad($datos, "credito_limite")) {
						$datos->credito_limite = 0;
					}

					if (!verPropiedad($datos, "credito_dias")) {
						$datos->credito_dias = 0;
					}
				}

				$proveedor = new Proveedor_model($id);

				if ($proveedor->existe($datos)) {
					$data["mensaje"] = "Ya existe un proveedor con la misma identificación.";
				} else {
					if ($proveedor->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Proveedor guardado con éxito.";
						$data["linea"] = $proveedor->buscar([
							"id"  => $proveedor->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $proveedor->getMensaje();
					}
				}
			} else {
				$data["mensaje"] = "Complete los campos marcados con *.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Proveedor.php */
/* Location: ./application/controllers/mnt/Proveedor.php */
