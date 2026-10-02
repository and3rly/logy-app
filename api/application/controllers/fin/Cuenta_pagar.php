<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Cuentas por pagar de las compras al crédito y sus pagos
class Cuenta_pagar extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"fin/Cuenta_pagar_model",
			"fin/Cuenta_pagar_pago_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Filtro estado: pendientes, vencidas, pagadas, anuladas o todas
	public function buscar()
	{
		$data = [
			"lista" => $this->Cuenta_pagar_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	# Formas de pago para pagar (crédito no aplica)
	public function get_datos()
	{
		$formas = array_values(array_filter($this->catalogo->verFormasPago(), function ($f) {
			return stripos(eliminarAcento($f->nombre), "credito") === false;
		}));

		$data = [
			"fecha" => Hoy(),
			"cat" => [
				"formas_pago" => $formas
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_pagos($id="")
	{
		$cuenta = new Cuenta_pagar_model($id);

		$data = [
			"pagos" => $cuenta->esDeLaSucursal() ? $cuenta->getPagos() : []
		];

		$this->output->set_output(json_encode($data));
	}

	public function pagar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			$cuenta = new Cuenta_pagar_model($id);
			$forma = verPropiedad($datos, "forma_pago_id") ? $this->catalogo->verFormasPago([
				"id" => $datos->forma_pago_id,
				"_uno" => true
			]) : null;

			if (!$cuenta->esDeLaSucursal()) {
				$data["mensaje"] = "La cuenta no existe.";
			} else if (!$forma || stripos(eliminarAcento($forma->nombre), "credito") !== false) {
				$data["mensaje"] = "Seleccione la forma de pago.";
			} else {
				$pago = $cuenta->pagar($datos);

				if ($pago) {
					$data["exito"] = 1;
					$data["mensaje"] = "Pago registrado con el comprobante {$pago->comprobante_numero}.";
					$data["pago"] = $pago->_buscar([
						"id" => $pago->getPK(),
						"_uno" => true
					]);
					$data["linea"] = $cuenta->getInfo();
				} else {
					$data["mensaje"] = $cuenta->getMensaje();
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function anular_pago($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$motivo = trim((string)verPropiedad($datos, "motivo", ""));

			$pago = new Cuenta_pagar_pago_model($id);
			$cuenta = new Cuenta_pagar_model($pago->getPK() ? $pago->cuenta_pagar_id : "");

			if (!$cuenta->esDeLaSucursal()) {
				$data["mensaje"] = "El pago no existe.";
			} else if ((int)$pago->anulado === 1) {
				$data["mensaje"] = "El pago ya está anulado.";
			} else if ((int)$cuenta->anulado === 1) {
				$data["mensaje"] = "La cuenta está anulada.";
			} else if ($motivo === "") {
				$data["mensaje"] = "Indique el motivo de la anulación.";
			} else if ($cuenta->anularPago($pago, mb_substr($motivo, 0, 200))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Pago {$pago->comprobante_numero} anulado.";
				$data["pago"] = $pago->_buscar([
					"id" => $pago->getPK(),
					"_uno" => true
				]);
				$data["linea"] = $cuenta->getInfo();
			} else {
				$data["mensaje"] = $cuenta->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Comprobante de egreso de un pago, media carta
	public function imprimir_comprobante($id="")
	{
		$pago = new Cuenta_pagar_pago_model($id);
		$cuenta = new Cuenta_pagar_model($pago->getPK() ? $pago->cuenta_pagar_id : "");

		if (!$cuenta->esDeLaSucursal()) {
			return $this->output
			->set_status_header(404)
			->set_output(json_encode([
				"exito" => 0,
				"mensaje" => "El pago no existe."
			]));
		}

		$info = $cuenta->getInfo();

		# Saldo que quedó después de este pago: el actual más los pagos vigentes posteriores
		$posteriores = $this->db
		->select("ifnull(sum(total), 0) as total", false)
		->where("cuenta_pagar_id", $cuenta->getPK())
		->where("anulado", 0)
		->where("id >", $pago->getPK())
		->get("cuenta_pagar_pago")
		->row();

		$datos = [
			"saldoDespues" => (float)$info->saldo + (float)$posteriores->total,
			"pago" => $pago->_buscar([
				"id" => $pago->getPK(),
				"_uno" => true
			]),
			"cuenta" => $info,
			"empresa" => $this->db
			->where("id", $cuenta->empresa_id)
			->get("empresa")
			->row(),
			"sucursal" => $this->db
			->where("id", $info->sucursal_id)
			->get("sucursal")
			->row()
		];

		$html = $this->load->view("fin/egreso_pdf", $datos, true);

		$temporal = APPPATH . "cache/mpdf";
		if (!is_dir($temporal)) {
			mkdir($temporal, 0775, true);
		}

		$pdf = new \Mpdf\Mpdf([
			"mode" => "utf-8",
			"format" => [216, 140],
			"margin_top" => 10,
			"margin_bottom" => 10,
			"margin_left" => 12,
			"margin_right" => 12,
			"tempDir" => $temporal
		]);

		$pdf->SetTitle("Comprobante {$pago->comprobante_numero}");
		$pdf->SetAuthor($datos["empresa"] ? $datos["empresa"]->nombre : "Logy");

		if ((int)$pago->anulado === 1) {
			$pdf->SetWatermarkText("ANULADO");
			$pdf->showWatermarkText = true;
			$pdf->watermarkTextAlpha = 0.08;
		}

		$pdf->WriteHTML($html);

		$this->output
		->set_content_type("application/pdf")
		->set_header("Content-Disposition: inline; filename=\"{$pago->comprobante_numero}.pdf\"")
		->set_output($pdf->Output("", "S"));
	}
}

/* End of file Cuenta_pagar.php */
/* Location: ./application/controllers/fin/Cuenta_pagar.php */
