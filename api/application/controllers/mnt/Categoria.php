<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Categoria extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Categoria_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Categoria_model->buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {

				$categoria = new Categoria_model($id);

				if ($categoria->existe($datos)) {
					$data["mensaje"] = "Ya existe una categoría con el mismo nombre.";
				} else {
					if ($categoria->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Categoría guardada con éxito.";
						$data["linea"] = $categoria->buscar([
							"id"  => $categoria->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $categoria->getMensaje();
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

/* End of file Categoria.php */
/* Location: ./application/controllers/mnt/Categoria.php */
