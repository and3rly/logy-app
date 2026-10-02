<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"Dashboard_model",
			"Stock_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Todos los indicadores del inicio en una sola llamada
	public function resumen()
	{
		$f = $this->Dashboard_model->fechas();
		$param = $this->catalogo->verEmpresaParametro();
		$moneda = $param ? $this->catalogo->verMonedas([
			"id" => $param->moneda_id,
			"_todos" => true,
			"_uno" => true
		]) : null;

		$data = [
			"fecha" => $f->hoy,
			"simbolo" => $moneda ? $moneda->simbolo : "",
			"ventas" => [
				"hoy" => $this->Dashboard_model->ventasEntre($f->hoy, $f->hoy),
				"ayer" => $this->Dashboard_model->ventasEntre($f->ayer, $f->ayer),
				"mes" => $this->Dashboard_model->ventasEntre($f->mes_inicio, $f->hoy),
				"mes_anterior" => $this->Dashboard_model->ventasEntre($f->anterior_inicio, $f->anterior_al)
			],
			"por_hora" => $this->Dashboard_model->ventasPorHora(),
			"diario" => $this->Dashboard_model->ventasPorDia(30),
			"mensual" => $this->Dashboard_model->ventasPorMes(12),
			"top_productos" => $this->Dashboard_model->topProductos($f->mes_inicio),
			"formas_pago" => $this->Dashboard_model->formasPago($f->mes_inicio),
			"cobrar" => $this->Dashboard_model->cuentasCobrar(),
			"pagar" => $this->Dashboard_model->cuentasPagar(),
			"cotizaciones" => $this->Dashboard_model->cotizaciones(),
			"inventario" => $this->inventario($f->hoy),
			"ultimas_ventas" => $this->Dashboard_model->ultimasVentas()
		];

		$this->output->set_output(json_encode($data));
	}

	# Valor del inventario al costo, conteo por estado y productos que requieren atención
	private function inventario($hoy)
	{
		$limite = date("Y-m-d", strtotime("{$hoy} +" . Dashboard_model::DIAS_AVISO_VENCE . " day"));
		$datos = [
			"productos" => 0,
			"valor" => 0,
			"disponible" => 0,
			"minimo" => 0,
			"agotado" => 0,
			"por_vencer" => 0,
			"alertas" => []
		];

		foreach ($this->Stock_model->existencias() as $reg) {
			$estado = $this->Stock_model->estadoExistencia($reg);
			$vence = $reg->proximo_vence && (float)$reg->existencia > 0 && $reg->proximo_vence <= $limite;

			$datos["productos"]++;
			$datos[$estado]++;
			$datos["valor"] += max((float)$reg->existencia, 0) * (float)$reg->costo;

			foreach ($reg->presentaciones as $pre) {
				$datos["valor"] += max((float)$pre->existencia, 0) * (float)$pre->costo;
			}

			if ($vence) {
				$datos["por_vencer"]++;
			}

			if ($estado !== "disponible" || $vence) {
				$datos["alertas"][] = [
					"producto_id" => $reg->producto_id,
					"nombre" => $reg->nombre,
					"nunidad" => $reg->nunidad,
					"existencia" => (float)$reg->existencia,
					"existencia_minima" => (float)$reg->existencia_minima,
					"proximo_vence" => $vence ? $reg->proximo_vence : null,
					"estado" => $estado
				];
			}
		}

		# Primero lo agotado, luego lo que vence y al final lo que está bajo el mínimo
		$prioridad = function ($a) {
			if ($a["estado"] === "agotado") {
				return 0;
			}

			return $a["proximo_vence"] ? 1 : 2;
		};

		usort($datos["alertas"], function ($a, $b) use ($prioridad) {
			return $prioridad($a) <=> $prioridad($b) ?: strcmp($a["nombre"], $b["nombre"]);
		});

		$datos["alertas"] = array_slice($datos["alertas"], 0, 6);

		return $datos;
	}
}

/* End of file Dashboard.php */
/* Location: ./application/controllers/Dashboard.php */
