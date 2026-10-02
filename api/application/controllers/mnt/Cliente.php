<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cliente extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Cliente_model"]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Cliente_model->buscar([
				"empresa_id" => $this->_ses->empresa_id,
				"_orden_asc" => "nombre"
			])
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"cat" => [
				"municipios"    => $this->catalogo->verMunicipios(),
				"departamentos" => $this->catalogo->verDepartamentos()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {

				foreach (["razon_social", "identificacion", "codigo", "direccion", "telefono", "correo", "credito_limite", "municipio_id"] as $campo) {
					if (property_exists($datos, $campo) && trim((string)$datos->$campo) === "") {
						$datos->$campo = null;
					}
				}

				if (property_exists($datos, "identificacion") && $datos->identificacion !== null) {
					$datos->identificacion = strtoupper(trim($datos->identificacion));
				}
				
				if (!verPropiedad($datos, "credito")) {
					$datos->credito = 0;
					$datos->credito_limite = null;
					$datos->credito_dias = 0;
				}

				$cliente = new Cliente_model($id);

				if ($cliente->existe($datos)) {
					$data["mensaje"] = "Ya existe un cliente con la misma identificación o código.";
				} else {
					if ($cliente->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Cliente guardado con éxito.";
						$data["linea"] = $cliente->buscar([
							"id"  => $cliente->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $cliente->getMensaje();
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

/* End of file Cliente.php */
/* Location: ./application/controllers/mnt/Cliente.php */
