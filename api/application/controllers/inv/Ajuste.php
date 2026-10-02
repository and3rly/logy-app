<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajuste extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"inv/Inventario_ajuste_model",
			"inv/Inventario_ajuste_detalle_model",
			"Stock_model",
			"Movimiento_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Filtros: fdel, fal (fechas), estado y sentido (ENTRADA o SALIDA)
	public function buscar()
	{
		$data = [
			"lista" => $this->Inventario_ajuste_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"fecha" => Hoy(),
			"fecha_inicial" => date("Y-m-01"),
			"simbolo" => $this->simboloMoneda(),
			"cat" => [
				"tipos"      => $this->catalogo->verAjusteTipos(["_todos" => true]),
				"estados"    => $this->catalogo->verAjusteEstados(["_todos" => true]),
				"categorias" => $this->catalogo->verCategorias(["_todos" => true])
			]
		];

		$this->output->set_output(json_encode($data));
	}

	# Productos (bienes activos) con su existencia en la sucursal, para agregarlos al ajuste
	public function get_productos()
	{
		$data = [
			"lista" => $this->Stock_model->existencias()
		];

		$this->output->set_output(json_encode($data));
	}

	# Lotes con existencia de un producto en la sucursal (para elegir de cuál sale); ?presentacion= para una presentación
	public function get_lotes($productoId="")
	{
		$data = [
			"lotes" => $this->Inventario_ajuste_detalle_model->lotes($productoId, $this->input->get("presentacion") ?: null)
		];

		$this->output->set_output(json_encode($data));
	}

	# Encabezado: tipo y observación (solo en borrador)
	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "inventario_ajuste_tipo_id")) {
				$ajuste = new Inventario_ajuste_model($id);
				$tipo = $this->catalogo->verAjusteTipos([
					"id" => $datos->inventario_ajuste_tipo_id,
					"_todos" => true,
					"_uno" => true
				]);
				$observacion = trim((string)verPropiedad($datos, "observacion", ""));

				if (!empty($id) && !$ajuste->esDeLaSucursal()) {
					$data["mensaje"] = "El ajuste no existe.";
				} else if (!empty($id) && !$ajuste->editable()) {
					$data["mensaje"] = "El ajuste ya fue aplicado o anulado, no se puede modificar.";
				} else if (!$tipo || ((int)$tipo->activo === 0 && (int)$tipo->id !== (int)$ajuste->inventario_ajuste_tipo_id)) {
					$data["mensaje"] = "El tipo de ajuste no existe o está inactivo.";
				} else if ((int)$tipo->requiere_observacion === 1 && $observacion === "") {
					$data["mensaje"] = "El tipo {$tipo->nombre} requiere una observación.";
				} else if (!empty($id) && $ajuste->tieneLineas() && $ajuste->getTipo()->sentido !== $tipo->sentido) {
					$data["mensaje"] = "No se puede cambiar entre entrada y salida con productos agregados; quítelos primero.";
				} else {
					$ajuste->asignarNumero();

					$guardado = $ajuste->guardar([
						"inventario_ajuste_tipo_id" => $tipo->id,
						"observacion" => $observacion === "" ? null : $observacion
					]);

					if ($guardado) {
						$data["exito"] = 1;
						$data["mensaje"] = empty($id) ? "Ajuste {$ajuste->numero} creado, agregue los productos." : "Ajuste actualizado.";
						$data["linea"] = $ajuste->getInfo();
					} else {
						$data["mensaje"] = $ajuste->getMensaje();
					}
				}
			} else {
				$data["mensaje"] = "Seleccione el tipo de ajuste.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function get_detalle($id="")
	{
		$ajuste = new Inventario_ajuste_model($id);

		$data = [
			"det" => $ajuste->esDeLaSucursal() ? $ajuste->getDetalle() : []
		];

		$this->output->set_output(json_encode($data));
	}

	public function aplicar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$ajuste = new Inventario_ajuste_model($id);

			if (!$ajuste->esDeLaSucursal()) {
				$data["mensaje"] = "El ajuste no existe.";
			} else if (!$ajuste->editable()) {
				$data["mensaje"] = "El ajuste ya fue aplicado o anulado.";
			} else if ($ajuste->aplicar()) {
				$data["exito"] = 1;
				$data["mensaje"] = "Ajuste {$ajuste->numero} aplicado al inventario.";
				$data["ajuste"] = $ajuste->getInfo();
			} else {
				$data["mensaje"] = $ajuste->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function anular($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$motivo = trim((string)verPropiedad($datos, "motivo", ""));

			$ajuste = new Inventario_ajuste_model($id);

			if (!$ajuste->esDeLaSucursal()) {
				$data["mensaje"] = "El ajuste no existe.";
			} else if ($motivo === "") {
				$data["mensaje"] = "Indique el motivo de la anulación.";
			} else if ($ajuste->anular(mb_substr($motivo, 0, 500))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Ajuste {$ajuste->numero} anulado.";
				$data["ajuste"] = $ajuste->getInfo();
			} else {
				$data["mensaje"] = $ajuste->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	private function simboloMoneda()
	{
		$param = $this->catalogo->verEmpresaParametro();
		$moneda = $param ? $this->catalogo->verMonedas([
			"id" => $param->moneda_id,
			"_todos" => true,
			"_uno" => true
		]) : null;

		return $moneda ? $moneda->simbolo : "";
	}
}

/* End of file Ajuste.php */
/* Location: ./application/controllers/inv/Ajuste.php */
