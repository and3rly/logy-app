<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Moneda extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Moneda_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Moneda_model->buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre") && 
				verPropiedad($datos, "simbolo")) {
				
				$moneda = new Moneda_model($id);

				if ($moneda->existe($datos)) {
					$data["mensaje"] = "La moneda que intenta guardar ya existe.";
				} else {
					if ($moneda->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Moneda guardada con éxito.";
						$data["linea"] = $moneda->buscar([
							"id"  => $moneda->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $moneda->getMensaje();
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

/* End of file Moneda.php */
/* Location: ./application/controllers/mnt/Moneda.php */