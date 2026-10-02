<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Pantalla de instalación: pública (ver hooks/Inicio.php) y solo mientras no exista una empresa
class Instalacion extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("Instalacion_model", "instalacion");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Si ya está instalado solo se responde eso; si no, van las ubicaciones del formulario
	public function estado()
	{
		$data = ["instalado" => $this->instalacion->instalado()];

		if (!$data["instalado"]) {
			$data = array_merge($data, $this->instalacion->getUbicaciones());
		}

		$this->output->set_output(json_encode($data));
	}

	public function guardar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$empresa = [];
			$campos = [
				"nombre",
				"razon_social",
				"identificacion",
				"direccion",
				"telefono",
				"correo"
			];

			foreach ($campos as $campo) {
				$empresa[$campo] = trim((string)verPropiedad($datos, $campo, ""));
			}

			$empresa["municipio_id"] = (int)verPropiedad($datos, "municipio_id", 0);

			if ($empresa["nombre"] === "" || $empresa["razon_social"] === "" || $empresa["identificacion"] === "" || $empresa["direccion"] === "") {
				$data["mensaje"] = "Complete los campos obligatorios.";
			} else if (!$this->instalacion->existeMunicipio($empresa["municipio_id"])) {
				$data["mensaje"] = "Seleccione el municipio.";
			} else if ($empresa["correo"] !== "" && !filter_var($empresa["correo"], FILTER_VALIDATE_EMAIL)) {
				$data["mensaje"] = "El correo no es válido.";
			} else {
				$empresa["telefono"] = $empresa["telefono"] === "" ? null : $empresa["telefono"];
				$empresa["correo"] = $empresa["correo"] === "" ? null : $empresa["correo"];

				if ($this->instalacion->instalar($empresa)) {
					$data["exito"] = 1;
					$data["mensaje"] = "Instalación completada. Ingrese con el usuario admin y la contraseña admin.";
				} else {
					$data["mensaje"] = $this->instalacion->getMensaje();
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Instalacion.php */
/* Location: ./application/controllers/Instalacion.php */
