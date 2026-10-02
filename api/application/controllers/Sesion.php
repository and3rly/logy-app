<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sesion extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library("Token");
		$this->load->model("Usuario_model");
	}

	public function iniciar()
	{
		if ($this->input->method() !== "post") {
			return $this->responder([
				"exito" => false,
				"mensaje" => "Método no permitido."
			], 405);
		}

		$datos = json_decode($this->input->raw_input_stream);
		$alias = isset($datos->usuario) ? trim($datos->usuario) : "";
		$clave = isset($datos->clave) ? $datos->clave : "";

		if ($alias === "" || $clave === "") {
			return $this->responder([
				"exito" => false,
				"mensaje" => "Ingrese usuario y contraseña."
			], 400);
		}

		$usr = new Usuario_model();

		if (!$usr->iniciarSesion($alias, $clave)) {
			return $this->responder([
				"exito" => false,
				"mensaje" => $usr->getMensaje()
			], 401);
		}

		$this->responder([
			"exito"   => true,
			"mensaje" => "Bienvenido.",
			"token"   => $this->token->generar($usr->getSesion()),
			"usuario" => $usr->getPublico()
		]);
	}

	public function actual()
	{
		$ses = $this->token->desdeCabecera();

		if (!$ses) {
			return $this->responder([
				"exito" => false,
				"mensaje" => "Sesión inválida o expirada."
			], 401);
		}

		$existe = $this->Usuario_model->buscar([
			"id" => $ses->id,
			"activo" => 1,
			"_uno" => true
		]);

		if (!$existe) {
			return $this->responder([
				"exito" => false,
				"mensaje" => "Usuario inactivo."
			], 401);
		}

		$usr = new Usuario_model($ses->id);

		$this->responder([
			"exito" => true,
			"usuario" => $usr->getPublico($ses->sucursal_id)
		]);
	}

	# Cambia la sucursal de la sesión: emite un token nuevo con la sucursal elegida
	public function cambiar_sucursal()
	{
		if ($this->input->method() !== "post") {
			return $this->responder([
				"exito" => false,
				"mensaje" => "Método no permitido."
			], 405);
		}

		$datos = json_decode($this->input->raw_input_stream);
		$sucursal_id = isset($datos->sucursal_id) ? (int)$datos->sucursal_id : 0;

		$usr = new Usuario_model(get_var_sesion()->id);
		$sucursal = $usr->getSucursal($sucursal_id);

		if (!$sucursal) {
			return $this->responder([
				"exito" => false,
				"mensaje" => "La sucursal no está asignada al usuario."
			], 400);
		}

		$this->responder([
			"exito"   => true,
			"mensaje" => "Ahora trabaja en la sucursal {$sucursal->nombre}.",
			"token"   => $this->token->generar($usr->getSesion($sucursal->id)),
			"usuario" => $usr->getPublico($sucursal->id)
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

/* End of file Sesion.php */
/* Location: ./application/controllers/Sesion.php */
