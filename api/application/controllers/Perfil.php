<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Mi perfil: el usuario de la sesión consulta y edita sus propios datos
class Perfil extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("Usuario_model");
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function get_datos()
	{
		$usuario = new Usuario_model($this->_ses->id);
		$param = $this->catalogo->verEmpresaParametro();
		$moneda = $param ? $this->catalogo->verMonedas([
			"id" => $param->moneda_id,
			"_todos" => true,
			"_uno" => true
		]) : null;

		$data = [
			"perfil"    => $usuario->getPerfil(),
			"actividad" => $usuario->getActividad(),
			"simbolo"   => $moneda ? $moneda->simbolo : ""
		];

		$this->output->set_output(json_encode($data));
	}

	# Nombre, correo y teléfono; el usuario (alias), el rol y las sucursales los cambia un administrador
	public function guardar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$nombre = trim((string)verPropiedad($datos, "nombre", ""));
			$correo = trim((string)verPropiedad($datos, "correo", ""));
			$telefono = trim((string)verPropiedad($datos, "telefono", ""));

			if ($nombre === "") {
				$data["mensaje"] = "Ingrese su nombre.";
			} else if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
				$data["mensaje"] = "El correo no es válido.";
			} else {
				$usuario = new Usuario_model($this->_ses->id);

				if ($usuario->guardar([
					"nombre"   => $nombre,
					"correo"   => $correo === "" ? null : $correo,
					"telefono" => $telefono === "" ? null : $telefono
				])) {
					$data["exito"] = 1;
					$data["mensaje"] = "Perfil guardado con éxito.";
					$data["perfil"] = $usuario->getPerfil();
				} else {
					$data["mensaje"] = $usuario->getMensaje();
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Pide la contraseña actual antes de poner la nueva
	public function cambiar_clave()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$actual = (string)verPropiedad($datos, "actual", "");
			$clave = trim((string)verPropiedad($datos, "clave", ""));
			$clave2 = trim((string)verPropiedad($datos, "clave2", ""));

			$usuario = new Usuario_model($this->_ses->id);

			if ($actual === "" || $clave === "") {
				$data["mensaje"] = "Complete los campos marcados con *.";
			} else if (!password_verify($actual, $usuario->clave)) {
				$data["mensaje"] = "La contraseña actual no es correcta.";
			} else if (strlen($clave) < 6) {
				$data["mensaje"] = "La contraseña debe tener al menos 6 caracteres.";
			} else if ($clave !== $clave2) {
				$data["mensaje"] = "Las contraseñas no coinciden.";
			} else if (password_verify($clave, $usuario->clave)) {
				$data["mensaje"] = "La nueva contraseña debe ser distinta de la actual.";
			} else if ($usuario->guardar(["clave" => password_hash($clave, PASSWORD_DEFAULT)])) {
				$data["exito"] = 1;
				$data["mensaje"] = "Contraseña actualizada con éxito.";
			} else {
				$data["mensaje"] = $usuario->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Perfil.php */
/* Location: ./application/controllers/Perfil.php */
