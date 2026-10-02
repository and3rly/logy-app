<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("Menu_model");
	}

	public function index()
	{
		if ($this->input->method() !== "get") {
			return $this->responder([
				"exito" => false,
				"mensaje" => "Método no permitido."
			], 405);
		}

		$this->responder([
			"exito"   => true,
			"modulos" => $this->Menu_model->getMenuCompleto(get_var_sesion()->rol_id),
			"rutas"   => $this->Menu_model->getRutas()
		]);
	}

	private function responder($datos, $estado = 200)
	{
		$this->output
		->set_status_header($estado)
		->set_content_type("application/json", "utf-8")
		->set_output(json_encode($datos));
	}
}

/* End of file Menu.php */
/* Location: ./application/controllers/Menu.php */
