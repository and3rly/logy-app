<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inicio
{
	protected $ci;
	protected $permitidas = [];

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->permitidas = [
			"/sesion/iniciar",
			"/instalacion/estado",
			"/instalacion/guardar"
		];
	}

	public function validarSesion()
	{
		$tmp = "/" . strtolower($this->ci->router->fetch_class()) . "/" . strtolower($this->ci->router->fetch_method());

		if (in_array($tmp, $this->permitidas)) {
			# Continuar

		} else {
			$codigo  = null;
			$mensaje = "Acceso denegado, inicie sesión.";

			if ($this->ci->input->get_request_header("Authorization")) {
				$this->ci->load->library("Token");

				$cuenta = $this->ci->token->desdeCabecera();

				if ($cuenta) {
					set_var_sesion($cuenta);

				} else {
					$mensaje = "Sesión caducada, vuelva a iniciar sesión.";
					$codigo = 2;
				}
			} else {
				$codigo = 1;
			}

			if ($codigo !== null) {
				http_response_code(401);
				outputJson([
					"exito"   => false,
					"mensaje" => $mensaje,
					"codigo"  => $codigo,
					"sesion"  => true
				]);
				die;
			}
		}
	}
}

/* End of file Inicio.php */
/* Location: ./application/hooks/Inicio.php */
