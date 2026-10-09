<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Umedida extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Unidad_medida_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Unidad_medida_model->buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre") &&
				verPropiedad($datos, "codigo")) {

				$medida = new Unidad_medida_model($id);

				if ($medida->existe($datos)) {
					$data["mensaje"] = "Ya existe una unidad de medida con el mismo código.";
				} else {
					if ($medida->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Unidad de medida guardada con éxito.";
						$data["linea"] = $medida->buscar([
							"id"  => $medida->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $medida->getMensaje();
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

	# Equivalencias de la unidad, en los dos sentidos
	public function get_equivalencias($id="")
	{
		$data = [
			"lista" => $id ? $this->Unidad_medida_model->equivalencias($id) : []
		];

		$this->output->set_output(json_encode($data));
	}

	# Datos: unidad_medida_id (la grande), unidad_menor_id, cantidad (> 1) y activo
	public function guardar_equivalencia($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$linea = $this->Unidad_medida_model->guardarEquivalencia($id, $datos);

			if ($linea) {
				$data["exito"] = 1;
				$data["mensaje"] = "Equivalencia guardada con éxito.";
				$data["linea"] = $linea;
			} else {
				$data["mensaje"] = $this->Unidad_medida_model->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Umedida.php */
/* Location: ./application/controllers/mnt/Umedida.php */
