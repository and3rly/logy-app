<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conversion extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"inv/Inventario_conversion_model",
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

	# Filtros: fdel, fal (fechas), sentido (EXPLOSION o IMPLOSION) y producto
	public function buscar()
	{
		$data = [
			"lista" => $this->Inventario_conversion_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"fecha" => Hoy(),
			"fecha_inicial" => date("Y-m-01"),
			"simbolo" => $this->simboloMoneda()
		];

		$this->output->set_output(json_encode($data));
	}

	# Productos con presentaciones y su existencia en la sucursal (unidad y cada presentación)
	public function get_productos()
	{
		$lista = array_values(array_filter($this->Stock_model->existencias(), function ($p) {
			return count($p->presentaciones) > 0;
		}));

		$data = [
			"lista" => $lista
		];

		$this->output->set_output(json_encode($data));
	}

	# Datos: sentido, producto_id, producto_presentacion_id, cantidad (entero) y observacion
	public function guardar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"), true) ?: [];
			$conversion = new Inventario_conversion_model();

			if ($conversion->convertir($datos)) {
				$info = $conversion->getInfo();
				$data["exito"] = 1;
				$data["mensaje"] = $info->sentido === Inventario_conversion_model::EXPLOSION
					? "Conversión {$info->numero}: {$info->cantidad} {$info->npresentacion} → " . (float)$info->unidades . " {$info->nunidad}."
					: "Conversión {$info->numero}: " . (float)$info->unidades . " {$info->nunidad} → {$info->cantidad} {$info->npresentacion}.";
				$data["linea"] = $info;
			} else {
				$data["mensaje"] = $conversion->getMensaje();
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

/* End of file Conversion.php */
/* Location: ./application/controllers/inv/Conversion.php */
