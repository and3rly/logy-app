<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Compra extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"com/Compra_model",
			"com/Compra_detalle_model",
			"Stock_model",
			"Movimiento_model",
			"fin/Cuenta_pagar_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Filtros: fdel, fal (fechas) y estado
	public function buscar()
	{
		$data = [
			"lista" => $this->Compra_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$param = $this->catalogo->verEmpresaParametro();

		$data = [
			"fecha" => Hoy(),
			"fecha_inicial" => date("Y-m-01"),
			"moneda_id" => $param ? $param->moneda_id : null,
			"cat" => [
				"proveedores" => $this->catalogo->verProveedores(),
				"formas_pago" => $this->catalogo->verFormasPago(),
				"monedas"     => $this->catalogo->verMonedas(),
				"estados"     => $this->catalogo->verCompraEstados(["_todos" => true]),
				"productos"   => $this->catalogo->verProductos(),
				"presentaciones" => $this->catalogo->verPresentaciones(),
				"categorias"  => $this->catalogo->verCategorias(["_todos" => true]),
				"marcas"      => $this->catalogo->verMarcas(["_todos" => true]),
				"unidades"    => $this->catalogo->verUnidadesMedida(["_todos" => true])
			]
		];

		$this->output->set_output(json_encode($data));
	}

	# Encabezado de la compra (solo mientras está creada)
	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "proveedor_id") &&
				verPropiedad($datos, "forma_pago_id") &&
				verPropiedad($datos, "moneda_id")) {

				$compra = new Compra_model($id);

				if (!empty($id) && !$compra->esDeLaSucursal()) {
					$data["mensaje"] = "La compra no existe.";
				} else if (!empty($id) && !$compra->editable()) {
					$data["mensaje"] = "La compra ya fue recibida o anulada, no se puede modificar.";
				} else {
					$compra->asignarNumero();

					$guardado = $compra->guardar([
						"proveedor_id" => $datos->proveedor_id,
						"forma_pago_id" => $datos->forma_pago_id,
						"moneda_id" => $datos->moneda_id,
						"factura_numero" => verPropiedad($datos, "factura_numero", null),
						"factura_fecha" => verPropiedad($datos, "factura_fecha", null)
					]);

					if ($guardado) {
						$data["exito"] = 1;
						$data["mensaje"] = empty($id) ? "Compra {$compra->numero} creada, agregue los productos." : "Compra actualizada con éxito.";
						$data["linea"] = $compra->getInfo();
					} else {
						$data["mensaje"] = $compra->getMensaje();
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

	public function get_detalle($id="")
	{
		$compra = new Compra_model($id);

		$data = [
			"det" => $compra->esDeLaSucursal() ? $compra->getDetalle() : []
		];

		$this->output->set_output(json_encode($data));
	}

	# compra_estado_id: 2 = recibir (inventario y cuenta por pagar), 3 = anular
	public function cambio_estado($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$estado = (int)verPropiedad($datos, "compra_estado_id", 0);

			$compra = new Compra_model($id);

			if (!$compra->esDeLaSucursal()) {
				$data["mensaje"] = "La compra no existe.";
			} else if (!$compra->editable()) {
				$data["mensaje"] = "La compra ya fue recibida o anulada.";
			} else if ($estado === Compra_model::RECIBIDA) {
				if ($compra->recibir()) {
					$data["exito"] = 1;
					$data["mensaje"] = "Compra recibida: los productos ya están en inventario.";
				} else {
					$data["mensaje"] = $compra->getMensaje();
				}
			} else if ($estado === Compra_model::ANULADA) {
				$compra->anular();
				$data["exito"] = 1;
				$data["mensaje"] = "Compra anulada.";
			} else {
				$data["mensaje"] = "No se indicó el nuevo estado de la compra.";
			}

			if ($data["exito"]) {
				$data["compra"] = $compra->getInfo();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function imprimir($id="")
	{
		$compra = new Compra_model($id);

		if (!$compra->esDeLaSucursal()) {
			return $this->output
			->set_status_header(404)
			->set_output(json_encode([
				"exito" => 0,
				"mensaje" => "La compra no existe."
			]));
		}

		$datos = $compra->datosImpresion();
		$html = $this->load->view("com/compra_pdf", $datos, true);

		$temporal = APPPATH . "cache/mpdf";
		if (!is_dir($temporal)) {
			mkdir($temporal, 0775, true);
		}

		$pdf = new \Mpdf\Mpdf([
			"mode" => "utf-8",
			"format" => "Letter",
			"margin_top" => 12,
			"margin_bottom" => 16,
			"margin_left" => 12,
			"margin_right" => 12,
			"margin_footer" => 6,
			"tempDir" => $temporal
		]);

		$pdf->SetTitle("Orden de compra {$compra->numero}");
		$pdf->SetAuthor($datos["empresa"] ? $datos["empresa"]->nombre : "Logy");

		if ((int)$compra->anulado === 1) {
			$pdf->SetWatermarkText("ANULADA");
			$pdf->showWatermarkText = true;
			$pdf->watermarkTextAlpha = 0.08;
		}

		$pdf->SetHTMLFooter(
			'<table width="100%" style="font-size: 7pt; color: #64748b; border-top: 1px solid #e2e8f0;"><tr>' .
			'<td style="padding-top: 3px;">Impreso el ' . date("d/m/Y H:i") . ' por ' . html_escape($datos["impreso_por"]) . '</td>' .
			'<td style="padding-top: 3px; text-align: right;">' . html_escape($compra->numero) . ' · Página {PAGENO} de {nbpg}</td>' .
			'</tr></table>'
		);

		$pdf->WriteHTML($html);

		$this->output
		->set_content_type("application/pdf")
		->set_header("Content-Disposition: inline; filename=\"{$compra->numero}.pdf\"")
		->set_output($pdf->Output("", "S"));
	}
}

/* End of file Compra.php */
/* Location: ./application/controllers/com/Compra.php */
