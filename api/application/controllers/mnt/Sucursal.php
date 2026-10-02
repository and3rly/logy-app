<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sucursal extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Sucursal_model"]);
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
			"lista" => $this->Sucursal_model->buscar([
				"empresa_id" => $this->_ses->empresa_id,
				"_orden_asc" => "nombre"
			])
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"cat" => [
				"municipios"    => $this->catalogo->verMunicipios(),
				"departamentos" => $this->catalogo->verDepartamentos()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre") && verPropiedad($datos, "municipio_id")) {

				# Campos opcionales vacíos se guardan como NULL
				foreach (["direccion", "telefono", "correo"] as $campo) {
					if (property_exists($datos, $campo) && trim((string)$datos->$campo) === "") {
						$datos->$campo = null;
					}
				}

				$datos->nombre = trim($datos->nombre);

				# Datos que no se toman del formulario
				unset($datos->empresa_id, $datos->usuario_id, $datos->fecha);

				$sucursal = new Sucursal_model($id);

				if (!empty($id) && !$sucursal->esDeLaEmpresa()) {
					$data["mensaje"] = "La sucursal no pertenece a su empresa.";
				} else if ((string)$id === (string)$this->_ses->sucursal_id && isset($datos->activo) && (int)$datos->activo === 0) {
					$data["mensaje"] = "No puede desactivar la sucursal en la que está trabajando.";
				} else if ($sucursal->existe($datos)) {
					$data["mensaje"] = "Ya existe una sucursal con el mismo nombre.";
				} else {
					if ($sucursal->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Sucursal guardada con éxito.";
						$data["linea"] = $sucursal->buscar([
							"id"   => $sucursal->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $sucursal->getMensaje();
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

/* End of file Sucursal.php */
/* Location: ./application/controllers/mnt/Sucursal.php */
