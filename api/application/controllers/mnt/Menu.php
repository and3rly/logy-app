<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Mantenimiento del menú lateral: módulos (tabla modulo) y sus opciones (tabla menu)
class Menu extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"mnt/Modulo_model",
			"Menu_model"
		]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Módulos con sus opciones en "menu"
	public function buscar()
	{
		$data = [
			"lista" => $this->Modulo_model->_buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	# Módulo: "detalle" 1 agrupa opciones; 0 es un enlace directo y necesita url
	public function guardar_modulo($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$detalle = (int)verPropiedad($datos, "detalle", 0) === 1 ? 1 : 0;
			$url = $this->ruta(verPropiedad($datos, "url", ""));

			if (!verPropiedad($datos, "nombre")) {
				$data["mensaje"] = "Complete los campos marcados con *.";
			} else if ($detalle === 0 && $url === null) {
				$data["mensaje"] = "Un módulo de enlace directo necesita la ruta.";
			} else {
				$modulo = new Modulo_model($id);

				if ($modulo->existe($datos)) {
					$data["mensaje"] = "Ya existe un módulo con ese nombre.";
				} else {
					$guardado = $modulo->guardar([
						"nombre" => trim($datos->nombre),
						"icono" => trim(verPropiedad($datos, "icono", "")) ?: null,
						"url" => $detalle === 1 ? null : $url,
						"orden" => (int)verPropiedad($datos, "orden", 0),
						"detalle" => $detalle,
						"activo" => property_exists($datos, "activo") ? (int)$datos->activo : 1
					]);

					if ($guardado) {
						$data["exito"] = 1;
						$data["mensaje"] = "Módulo guardado con éxito.";
						$data["linea"] = $modulo->getInfo();
					} else {
						$data["mensaje"] = $modulo->getMensaje();
					}
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Opción del menú (tabla menu)
	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "modulo_id") &&
				verPropiedad($datos, "nombre") &&
				verPropiedad($datos, "url")) {

				$datos->url = $this->ruta($datos->url);
				$opcion = new Menu_model(empty($id) ? null : $id);

				if ($opcion->existe($datos)) {
					$data["mensaje"] = "Otra opción ya lleva a la ruta {$datos->url}.";
				} else {
					$guardado = $opcion->guardar([
						"modulo_id" => $datos->modulo_id,
						"nombre" => trim($datos->nombre),
						"icono" => trim(verPropiedad($datos, "icono", "")) ?: null,
						"url" => $datos->url,
						"orden" => (int)verPropiedad($datos, "orden", 0),
						"activo" => property_exists($datos, "activo") ? (int)$datos->activo : 1
					]);

					if ($guardado) {
						$data["exito"] = 1;
						$data["mensaje"] = "Opción guardada con éxito.";
						$data["linea"] = $opcion->buscar([
							"id" => $opcion->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $opcion->getMensaje();
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

	# Ruta de la interfaz con "/" al inicio ("moneda" → "/moneda"); vacía o "/" → null
	private function ruta($url)
	{
		$url = trim((string)$url);

		if ($url === "" || $url === "/") {
			return null;
		}

		return "/" . ltrim($url, "/");
	}
}

/* End of file Menu.php */
/* Location: ./application/controllers/mnt/Menu.php */
