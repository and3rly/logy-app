<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Cuentas por cobrar de las ventas al crédito y sus abonos
class Cuenta_cobrar extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"fin/Cuenta_cobrar_model",
			"fin/Cuenta_cobrar_pago_model",
			"ven/Venta_model"
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
			"lista" => $this->Cuenta_cobrar_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	# Formas de pago para abonar (crédito no aplica)
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
		$cuenta = new Cuenta_cobrar_model($id);

		$data = [
			"pagos" => $cuenta->esDeLaSucursal() ? $cuenta->getPagos() : []
		];

		$this->output->set_output(json_encode($data));
	}

	public function abonar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			$cuenta = new Cuenta_cobrar_model($id);
			$forma = verPropiedad($datos, "forma_pago_id") ? $this->catalogo->verFormasPago([
				"id" => $datos->forma_pago_id,
				"_uno" => true
			]) : null;

			if (!$cuenta->esDeLaSucursal()) {
				$data["mensaje"] = "La cuenta no existe.";
			} else if (!$forma || stripos(eliminarAcento($forma->nombre), "credito") !== false) {
				$data["mensaje"] = "Seleccione la forma de pago.";
			} else {
				$pago = $cuenta->abonar($datos);

				if ($pago) {
					$data["exito"] = 1;
					$data["mensaje"] = "Abono registrado con el recibo {$pago->recibo_numero}.";
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

			$pago = new Cuenta_cobrar_pago_model($id);
			$cuenta = new Cuenta_cobrar_model($pago->getPK() ? $pago->cuenta_cobrar_id : "");

			if (!$cuenta->esDeLaSucursal()) {
				$data["mensaje"] = "El abono no existe.";
			} else if ((int)$pago->anulado === 1) {
				$data["mensaje"] = "El abono ya está anulado.";
			} else if ((int)$cuenta->anulado === 1) {
				$data["mensaje"] = "La cuenta está anulada.";
			} else if ($motivo === "") {
				$data["mensaje"] = "Indique el motivo de la anulación.";
			} else if ($cuenta->anularPago($pago, mb_substr($motivo, 0, 200))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Abono {$pago->recibo_numero} anulado.";
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

	# Recibo de un abono, media carta
	public function imprimir_recibo($id="")
	{
		$pago = new Cuenta_cobrar_pago_model($id);
		$cuenta = new Cuenta_cobrar_model($pago->getPK() ? $pago->cuenta_cobrar_id : "");

		if (!$cuenta->esDeLaSucursal()) {
			return $this->output
			->set_status_header(404)
			->set_output(json_encode([
				"exito" => 0,
				"mensaje" => "El abono no existe."
			]));
		}

		$info = $cuenta->getInfo();

		# Saldo que quedó después de este abono: el actual más los abonos vigentes posteriores
		$posteriores = $this->db
		->select("ifnull(sum(total), 0) as total", false)
		->where("cuenta_cobrar_id", $cuenta->getPK())
		->where("anulado", 0)
		->where("id >", $pago->getPK())
		->get("cuenta_cobrar_pago")
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

		$html = $this->load->view("fin/recibo_pdf", $datos, true);

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

		$pdf->SetTitle("Recibo {$pago->recibo_numero}");
		$pdf->SetAuthor($datos["empresa"] ? $datos["empresa"]->nombre : "Logy");

		if ((int)$pago->anulado === 1) {
			$pdf->SetWatermarkText("ANULADO");
			$pdf->showWatermarkText = true;
			$pdf->watermarkTextAlpha = 0.08;
		}

		$pdf->WriteHTML($html);

		$this->output
		->set_content_type("application/pdf")
		->set_header("Content-Disposition: inline; filename=\"{$pago->recibo_numero}.pdf\"")
		->set_output($pdf->Output("", "S"));
	}
}

/* End of file Cuenta_cobrar.php */
/* Location: ./application/controllers/fin/Cuenta_cobrar.php */
