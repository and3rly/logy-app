<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Traslado extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"inv/Inventario_traslado_model",
			"inv/Inventario_traslado_detalle_model",
			"inv/Inventario_ajuste_detalle_model",
			"Stock_model",
			"Movimiento_model",
			"Notificacion_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Filtros: fdel, fal (fechas), estado y vista (enviados o recibidos)
	public function buscar()
	{
		$data = [
			"lista" => $this->Inventario_traslado_model->_buscar($_GET)
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
				"estados"    => $this->catalogo->verTrasladoEstados(["_todos" => true]),
				"sucursales" => $this->catalogo->verSucursales(["_todos" => true]),
				"categorias" => $this->catalogo->verCategorias(["_todos" => true])
			]
		];

		$this->output->set_output(json_encode($data));
	}

	# Un traslado de la sucursal (enviado por ella o que le llega), ej. al abrirlo desde una notificación
	public function get_info($id="")
	{
		$traslado = new Inventario_traslado_model($id);

		$data = [
			"traslado" => $traslado->esVisible() ? $traslado->getInfo() : null
		];

		$this->output->set_output(json_encode($data));
	}

	# Productos (bienes activos) con su existencia en la sucursal, para agregarlos al traslado
	public function get_productos()
	{
		$data = [
			"lista" => $this->Stock_model->existencias()
		];

		$this->output->set_output(json_encode($data));
	}

	# Lotes con existencia de un producto en la sucursal (los mismos que en los ajustes); ?presentacion=
	public function get_lotes($productoId="")
	{
		$data = [
			"lotes" => $this->Inventario_ajuste_detalle_model->lotes($productoId, $this->input->get("presentacion") ?: null)
		];

		$this->output->set_output(json_encode($data));
	}

	# Encabezado: sucursal destino y observación (solo en borrador, desde el origen)
	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "sucursal_destino_id")) {
				$traslado = new Inventario_traslado_model($id);
				$destino = $this->catalogo->verSucursales([
					"id" => $datos->sucursal_destino_id,
					"_uno" => true
				]);
				$observacion = trim((string)verPropiedad($datos, "observacion", ""));
				$args = [
					"sucursal_destino_id" => $destino ? $destino->id : null,
					"observacion" => $observacion === "" ? null : mb_substr($observacion, 0, 300)
				];

				if (!empty($id) && !$traslado->esDelOrigen()) {
					$data["mensaje"] = "El traslado no existe.";
				} else if (!empty($id) && !$traslado->editable()) {
					$data["mensaje"] = "El traslado ya fue enviado o anulado, no se puede modificar.";
				} else if (!$destino) {
					$data["mensaje"] = "La sucursal destino no existe o está inactiva.";
				} else if ((int)$destino->id === (int)$this->_ses->sucursal_id) {
					$data["mensaje"] = "La sucursal destino debe ser distinta de la sucursal de origen.";
				} else {
					$guardado = empty($id) ? $traslado->crear($args) : $traslado->guardar($args);

					if ($guardado) {
						$data["exito"] = 1;
						$data["mensaje"] = empty($id) ? "Traslado {$traslado->numero} creado, agregue los productos." : "Traslado actualizado.";
						$data["linea"] = $traslado->getInfo();
					} else {
						$data["mensaje"] = $traslado->getMensaje();
					}
				}
			} else {
				$data["mensaje"] = "Seleccione la sucursal destino.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function get_detalle($id="")
	{
		$traslado = new Inventario_traslado_model($id);

		$data = [
			"det" => $traslado->esVisible() ? $traslado->getDetalle() : []
		];

		$this->output->set_output(json_encode($data));
	}

	public function enviar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$traslado = new Inventario_traslado_model($id);

			if (!$traslado->esDelOrigen()) {
				$data["mensaje"] = "El traslado no existe.";
			} else if (!$traslado->editable()) {
				$data["mensaje"] = "El traslado ya fue enviado o anulado.";
			} else if ($traslado->enviar()) {
				$data["exito"] = 1;
				$data["mensaje"] = "Traslado {$traslado->numero} enviado.";
				$data["traslado"] = $traslado->getInfo();
			} else {
				$data["mensaje"] = $traslado->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function recibir($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$traslado = new Inventario_traslado_model($id);

			if (!$traslado->esDelDestino()) {
				$data["mensaje"] = "El traslado no existe o no llega a esta sucursal.";
			} else if ($traslado->recibir()) {
				$data["exito"] = 1;
				$data["mensaje"] = "Traslado {$traslado->numero} recibido en el inventario.";
				$data["traslado"] = $traslado->getInfo();
			} else {
				$data["mensaje"] = $traslado->getMensaje();
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

			$traslado = new Inventario_traslado_model($id);

			if (!$traslado->esDelOrigen()) {
				$data["mensaje"] = "Solo la sucursal que envía puede anular el traslado.";
			} else if ($motivo === "") {
				$data["mensaje"] = "Indique el motivo de la anulación.";
			} else if ($traslado->anular(mb_substr($motivo, 0, 500))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Traslado {$traslado->numero} anulado.";
				$data["traslado"] = $traslado->getInfo();
			} else {
				$data["mensaje"] = $traslado->getMensaje();
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

/* End of file Traslado.php */
/* Location: ./application/controllers/inv/Traslado.php */
