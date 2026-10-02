<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Producto extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"mnt/Producto_model",
			"mnt/Producto_presentacion_model",
			"Stock_model"
		]);
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
			"lista" => $this->Producto_model->buscar([
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
				"categorias" => $this->catalogo->verCategorias(["_todos" => true]),
				"marcas"     => $this->catalogo->verMarcas(["_todos" => true]),
				"unidades"   => $this->catalogo->verUnidadesMedida(["_todos" => true])
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre") &&
				verPropiedad($datos, "categoria_id") &&
				verPropiedad($datos, "marca_id") &&
				verPropiedad($datos, "unidad_medida_id")) {

				foreach (["codigo_barra", "precio", "costo", "foto"] as $campo) {
					if (property_exists($datos, $campo) && trim((string)$datos->$campo) === "") {
						$datos->$campo = null;
					}
				}

				# Viene del editor de texto: se guarda solo el formato permitido
				$datos->descripcion = limpiarHtml(verPropiedad($datos, "descripcion", ""));

				if (!verPropiedad($datos, "existencia_minima")) {
					$datos->existencia_minima = 0;
				}

				if (verPropiedad($datos, "tipo_producto") === "S") {
					$datos->control_vence = 0;
					$datos->existencia_minima = 0;
				}

				$producto = new Producto_model($id);

				# El código lo genera el sistema al crear y no cambia al editar
				$this->db->trans_begin();
				$datos->codigo = $producto->getPK() ? $producto->codigo : $producto->siguienteCodigo();

				if ($producto->existe($datos)) {
					$data["mensaje"] = "Ya existe un producto con el mismo código o código de barras.";
				} else {
					if ($producto->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Producto guardado con éxito.";
						$data["linea"] = $producto->buscar([
							"id"  => $producto->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $producto->getMensaje();
					}
				}

				if ($data["exito"] === 1 && $this->db->trans_status() !== false) {
					$this->db->trans_commit();
				} else {
					$this->db->trans_rollback();

					if ($data["exito"] === 1) {
						$data = [
							"exito"   => 0,
							"mensaje" => "No pude guardar los datos, por favor intente nuevamente."
						];
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

	# Lo que acompaña al formulario en la ficha: presentaciones y existencia en la sucursal de la sesión
	public function get_ficha($id="")
	{
		$data = [
			"presentaciones" => [],
			"existencias" => []
		];

		if ($this->esDeLaEmpresa($id)) {
			$data["presentaciones"] = $this->Producto_presentacion_model->buscar([
				"producto_id" => $id,
				"_orden_asc" => "factor"
			]);

			$data["existencias"] = $this->Stock_model->existenciaProducto($id);
		}

		$this->output->set_output(json_encode($data));
	}

	public function guardar_presentacion($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$datos->nombre = trim((string)verPropiedad($datos, "nombre", ""));
			$factor = round((float)verPropiedad($datos, "factor", 0), 5);

			$presentacion = new Producto_presentacion_model($id);

			# Al editar, el producto no cambia
			if ($presentacion->getPK()) {
				$datos->producto_id = $presentacion->producto_id;
			}

			$producto = $this->esDeLaEmpresa(verPropiedad($datos, "producto_id"));

			if (!$producto) {
				$data["mensaje"] = "El producto no existe.";
			} else if ($producto->tipo_producto === "S") {
				$data["mensaje"] = "Los servicios no llevan presentaciones.";
			} else if ($datos->nombre === "" || $factor <= 0) {
				$data["mensaje"] = "Indique el nombre y un factor mayor a cero.";
			} else if ($presentacion->existe($datos)) {
				$data["mensaje"] = "El producto ya tiene una presentación con ese nombre.";
			} else if ($presentacion->getPK() &&
				(float)$presentacion->factor !== $factor &&
				$presentacion->enUso()) {
				$data["mensaje"] = "La presentación ya tiene movimientos: el factor no se puede cambiar.";
			} else {
				$guardar = [
					"nombre" => $datos->nombre,
					"factor" => $factor,
					"producto_id" => $producto->id
				];

				if (property_exists($datos, "activo")) {
					$guardar["activo"] = (int)$datos->activo === 1 ? 1 : 0;
				}

				if ($presentacion->guardar($guardar)) {
					$data["exito"] = 1;
					$data["mensaje"] = "Presentación guardada con éxito.";
					$data["linea"] = $presentacion->buscar([
						"id" => $presentacion->getPK(),
						"_uno" => true
					]);
				} else {
					$data["mensaje"] = $presentacion->getMensaje();
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# El producto, si es de la empresa de la sesión
	private function esDeLaEmpresa($id)
	{
		if (!$id) {
			return null;
		}

		return $this->Producto_model->buscar([
			"id" => $id,
			"empresa_id" => $this->_ses->empresa_id,
			"_uno" => true
		]);
	}
}

/* End of file Producto.php */
/* Location: ./application/controllers/mnt/Producto.php */
