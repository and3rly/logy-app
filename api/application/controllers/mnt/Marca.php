<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Marca extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Marca_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Marca_model->buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {

				$marca = new Marca_model($id);

				if ($marca->existe($datos)) {
					$data["mensaje"] = "Ya existe una marca con el mismo nombre.";
				} else {
					if ($marca->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Marca guardada con éxito.";
						$data["linea"] = $marca->buscar([
							"id"  => $marca->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $marca->getMensaje();
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

/* End of file Marca.php */
/* Location: ./application/controllers/mnt/Marca.php */
