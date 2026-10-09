<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Notificaciones de la campana del usuario en la sucursal de la sesión
class Notificacion extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("Notificacion_model");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Notificacion_model->buscar(),
			"sin_leer" => $this->Notificacion_model->sinLeer()
		];

		$this->output->set_output(json_encode($data));
	}

	public function marcar_leida($id="")
	{
		$this->marcar($id ?: null);
	}

	public function marcar_todas()
	{
		$this->marcar(null);
	}

	private function marcar($id)
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$this->Notificacion_model->marcarLeida($id);

			$data["exito"] = 1;
			$data["sin_leer"] = $this->Notificacion_model->sinLeer();
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Notificacion.php */
/* Location: ./application/controllers/Notificacion.php */
