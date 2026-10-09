<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Listas de precios: encabezado (nombre, descripción, activo) y sus precios por producto o presentación
class Lista_precio extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Lista_precio_model"]);
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
			"lista" => $this->Lista_precio_model->_buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (trim((string)verPropiedad($datos, "nombre", "")) !== "") {
				$lista = new Lista_precio_model($id);

				$datos->nombre = mb_substr(trim($datos->nombre), 0, 100);
				$datos->descripcion = trim((string)verPropiedad($datos, "descripcion", "")) !== "" ? mb_substr(trim($datos->descripcion), 0, 300) : null;

				if (!empty($id) && !$lista->esDeLaEmpresa()) {
					$data["mensaje"] = "La lista de precios no existe.";
				} else if ($lista->existe($datos)) {
					$data["mensaje"] = "Ya existe una lista de precios con ese nombre.";
				} else {
					if ($lista->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Lista de precios guardada con éxito.";
						$data["linea"] = $lista->_buscar([
							"id" => $lista->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $lista->getMensaje();
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

	# Pantalla de precios: productos y presentaciones con costo, precio general y el de la lista
	public function get_articulos($id="")
	{
		$lista = new Lista_precio_model($id);

		$data = [
			"lista" => $lista->esDeLaEmpresa() ? $lista->articulos() : [],
			"cat" => [
				"categorias" => $this->catalogo->verCategorias(["_todos" => true])
			]
		];

		$this->output->set_output(json_encode($data));
	}

	# Reemplaza los precios: lineas [{producto_id, producto_presentacion_id, precio}]
	public function guardar_precios($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$lista = new Lista_precio_model($id);

			if (!$lista->esDeLaEmpresa()) {
				$data["mensaje"] = "La lista de precios no existe.";
			} else if ($lista->guardarPrecios(verPropiedad($datos, "lineas", []))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Precios de {$lista->nombre} guardados con éxito.";
				$data["linea"] = $lista->_buscar([
					"id" => $lista->getPK(),
					"_uno" => true
				]);
			} else {
				$data["mensaje"] = $lista->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Precios de la lista para el punto de venta y la cotización (vacío si está inactiva)
	public function get_precios($id="")
	{
		$lista = new Lista_precio_model($id);
		$activa = $lista->esDeLaEmpresa() && (int)$lista->activo === 1;

		$data = [
			"nombre" => $activa ? $lista->nombre : null,
			"lista" => $lista->precios()
		];

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Lista_precio.php */
/* Location: ./application/controllers/mnt/Lista_precio.php */
