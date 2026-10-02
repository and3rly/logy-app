<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rol extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Rol_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Rol_model->buscar([
				"empresa_id" => $this->_ses->empresa_id,
				"_orden_asc" => "nombre"
			])
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {

				$datos->nombre = trim($datos->nombre);

				# Datos que no se toman del formulario
				unset($datos->empresa_id);

				$rol = new Rol_model($id);

				if (!empty($id) && !$rol->esDeLaEmpresa()) {
					$data["mensaje"] = "El rol no pertenece a su empresa.";
				} else if ((string)$id === (string)$this->_ses->rol_id && isset($datos->activo) && (int)$datos->activo === 0) {
					$data["mensaje"] = "No puede desactivar el rol con el que está trabajando.";
				} else if ((string)$id === (string)$this->_ses->rol_id && isset($datos->administrador) && (int)$datos->administrador === 0 && (int)$rol->administrador === 1) {
					$data["mensaje"] = "No puede quitar el acceso total al rol con el que está trabajando.";
				} else if ($rol->existe($datos)) {
					$data["mensaje"] = "Ya existe un rol con el mismo nombre.";
				} else {
					if ($rol->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Rol guardado con éxito.";
						$data["linea"] = $rol->buscar([
							"id"   => $rol->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $rol->getMensaje();
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

	# Módulos y opciones activos del menú con los accesos que tiene el rol
	public function accesos($id="")
	{
		$rol = new Rol_model($id);

		if (empty($id) || !$rol->esDeLaEmpresa()) {
			$data = [
				"exito" => 0,
				"mensaje" => "El rol no pertenece a su empresa."
			];
		} else {
			$this->load->model("Menu_model");

			$data = [
				"exito" => 1,
				"modulos" => $this->Menu_model->getMenuCompleto(),
				"accesos" => $rol->getAccesos()
			];
		}

		$this->output->set_output(json_encode($data));
	}

	# Reemplaza los accesos del rol por los enviados: {accesos: [{modulo_id, menu_id}]}
	public function guardar_accesos($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$rol = new Rol_model($id);

			if (empty($id) || !$rol->esDeLaEmpresa()) {
				$data["mensaje"] = "El rol no pertenece a su empresa.";
			} else if ((int)$rol->administrador === 1) {
				$data["mensaje"] = "El rol tiene acceso total; no necesita accesos por opción.";
			} else if ($rol->setAccesos(verPropiedad($datos, "accesos", []))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Accesos guardados con éxito.";
				$data["accesos"] = $rol->getAccesos();
			} else {
				$data["mensaje"] = "No se pudieron guardar los accesos, intente nuevamente.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Rol.php */
/* Location: ./application/controllers/mnt/Rol.php */
