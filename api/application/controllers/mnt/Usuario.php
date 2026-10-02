<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["Usuario_model"]);
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
			"lista" => $this->Usuario_model->getLista()
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"cat" => [
				"roles"      => $this->catalogo->verRoles(),
				"sucursales" => $this->catalogo->verSucursales()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			# Solo sucursales activas de la empresa de la sesión
			$permitidas = array_field($this->catalogo->verSucursales(), "id");
			$sucursales = array_values(array_filter(
				(array)verPropiedad($datos, "sucursales", []),
				function ($s) use ($permitidas) {
					return in_array($s, $permitidas);
				}
			));
			$principal = verPropiedad($datos, "sucursal_id");

			if (verPropiedad($datos, "nombre") &&
				verPropiedad($datos, "alias") &&
				verPropiedad($datos, "rol_id")) {

				$clave = trim((string)verPropiedad($datos, "clave", ""));

				foreach (["correo", "telefono"] as $campo) {
					if (property_exists($datos, $campo) && trim((string)$datos->$campo) === "") {
						$datos->$campo = null;
					}
				}

				$datos->alias = trim($datos->alias);

				# Datos que no se toman del formulario
				unset($datos->empresa_id, $datos->foto, $datos->clave);

				if (!empty($id) && !$this->Usuario_model->esDeLaEmpresa($id)) {
					$data["mensaje"] = "El usuario no pertenece a su empresa.";
				} else if ($id === "" && $clave === "") {
					$data["mensaje"] = "Ingrese la contraseña del usuario.";
				} else if ($clave !== "" && strlen($clave) < 6) {
					$data["mensaje"] = "La contraseña debe tener al menos 6 caracteres.";
				} else if ($clave !== "" && $clave !== (string)verPropiedad($datos, "clave2", "")) {
					$data["mensaje"] = "Las contraseñas no coinciden.";
				} else if (count($sucursales) === 0) {
					$data["mensaje"] = "Asigne al menos una sucursal.";
				} else if (!in_array($principal, $sucursales)) {
					$data["mensaje"] = "Indique la sucursal principal entre las asignadas.";
				} else if ((string)$id === (string)$this->_ses->id && isset($datos->activo) && (int)$datos->activo === 0) {
					$data["mensaje"] = "No puede desactivar su propio usuario.";
				} else {
					if ($clave !== "") {
						$datos->clave = password_hash($clave, PASSWORD_DEFAULT);
					}

					$usuario = new Usuario_model($id === "" ? null : $id);

					if ($usuario->existe($datos)) {
						$data["mensaje"] = "El nombre de usuario ya está en uso.";
					} else {
						$guardado = $usuario->guardar($datos);

						# Las sucursales se guardan aunque los datos del usuario no cambien
						$sucursal = $usuario->getPK() && $usuario->setSucursales($sucursales, $principal);

						if ($guardado || $sucursal) {
							$data["exito"] = 1;
							$data["mensaje"] = "Usuario guardado con éxito.";
							$data["linea"] = $usuario->getLista([
								"id"   => $usuario->getPK(),
								"_uno" => true
							]);
						} else {
							$data["mensaje"] = $usuario->getMensaje();
						}
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

/* End of file Usuario.php */
/* Location: ./application/controllers/mnt/Usuario.php */
