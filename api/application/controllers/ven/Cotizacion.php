<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Cotizaciones: borrador editable → enviada → aceptada o rechazada; no mueven inventario
class Cotizacion extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"ven/Cotizacion_model",
			"ven/Cotizacion_detalle_model",
			"ven/Venta_model",
			"ven/Venta_detalle_model",
			"Stock_model",
			"Movimiento_model",
			"fin/Cuenta_cobrar_model",
			"mnt/Cliente_model",
			"mnt/Lista_precio_model"
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
			"lista" => $this->Cotizacion_model->_buscar($_GET)
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
			"dias_validez" => Cotizacion_model::DIAS_VALIDEZ,
			"cat" => [
				"series"        => $this->catalogo->verCotizacionSeries(),
				# Para convertir en venta
				"series_venta"  => $this->catalogo->verVentaSeries(),
				"formas_pago"   => $this->catalogo->verFormasPago(["_todos" => true]),
				"monedas"       => $this->catalogo->verMonedas(["_todos" => true]),
				"estados"       => $this->catalogo->verCotizacionEstados(["_todos" => true]),
				"categorias"    => $this->catalogo->verCategorias(["_todos" => true]),
				# Para crear un producto desde la cotización
				"marcas"        => $this->catalogo->verMarcas(["_todos" => true]),
				"unidades"      => $this->catalogo->verUnidadesMedida(["_todos" => true]),
				# Con la existencia de la sucursal, solo como referencia al cotizar
				"productos"     => $this->Stock_model->existencias(),
				# Incluye inactivas: una cotización puede conservar la suya
				"listas_precio" => $this->catalogo->verListasPrecio(["_todos" => true]),
				# Para crear un cliente desde la cotización
				"municipios"    => $this->catalogo->verMunicipios(),
				"departamentos" => $this->catalogo->verDepartamentos()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	# Encabezado: crea el borrador con su correlativo o actualiza el borrador
	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "moneda_id") && verPropiedad($datos, "valida_hasta")) {
				$cot = new Cotizacion_model($id);

				if (!empty($id) && !$cot->esDeLaSucursal()) {
					$data["mensaje"] = "La cotización no existe.";
				} else if (!empty($id) && !$cot->editable()) {
					$data["mensaje"] = "Solo un borrador se puede modificar.";
				} else if ($cot->guardarEncabezado($datos)) {
					$data["exito"] = 1;
					$data["mensaje"] = empty($id) ? "Cotización {$cot->numero} creada, agregue los productos." : "Cotización actualizada.";
					$data["linea"] = $cot->getInfo();
				} else {
					$data["mensaje"] = $cot->getMensaje();
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
		$cot = new Cotizacion_model($id);

		$data = [
			"det" => $cot->esDeLaSucursal() ? $cot->getDetalle() : []
		];

		$this->output->set_output(json_encode($data));
	}

	# accion: enviar, aceptar, rechazar o anular (con motivo)
	public function cambio_estado($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$accion = (string)verPropiedad($datos, "accion", "");
			$motivo = mb_substr(trim((string)verPropiedad($datos, "motivo", "")), 0, 500);

			$cot = new Cotizacion_model($id);

			$mensajes = [
				"enviar" => "Cotización {$cot->numero} enviada.",
				"aceptar" => "Cotización {$cot->numero} aceptada.",
				"rechazar" => "Cotización {$cot->numero} rechazada.",
				"anular" => "Cotización {$cot->numero} anulada."
			];

			if (!$cot->esDeLaSucursal()) {
				$data["mensaje"] = "La cotización no existe.";
			} else if ($cot->cambiarEstado($accion, $motivo)) {
				$data["exito"] = 1;
				$data["mensaje"] = $mensajes[$accion];
				$data["linea"] = $cot->getInfo();
			} else {
				$data["mensaje"] = $cot->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Nuevo borrador con los mismos datos y líneas
	public function duplicar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$cot = new Cotizacion_model($id);

			if (!$cot->esDeLaSucursal()) {
				$data["mensaje"] = "La cotización no existe.";
			} else if ($nueva = $cot->duplicar()) {
				$data["exito"] = 1;
				$data["mensaje"] = trim("Se creó la cotización {$nueva->numero} a partir de {$cot->numero}. " . $nueva->getMensaje());
				$data["linea"] = $nueva->getInfo();
			} else {
				$data["mensaje"] = $cot->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Venta con los precios cotizados; la cotización queda convertida
	public function convertir($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "venta_serie_id") && verPropiedad($datos, "forma_pago_id")) {
				$cot = new Cotizacion_model($id);

				if (!$cot->esDeLaSucursal()) {
					$data["mensaje"] = "La cotización no existe.";
				} else if ($venta = $cot->convertir($datos)) {
					$data["exito"] = 1;
					$data["mensaje"] = "Venta {$venta->correlativo} registrada a partir de {$cot->numero}.";
					$data["linea"] = $cot->getInfo();
					$data["venta"] = $venta->getInfo();
					$data["productos"] = $this->Stock_model->existencias();
				} else {
					$data["mensaje"] = $cot->getMensaje();
				}
			} else {
				$data["mensaje"] = "Seleccione la serie y la forma de pago.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Formato carta para enviar al cliente
	public function imprimir($id="")
	{
		$cot = new Cotizacion_model($id);

		if (!$cot->esDeLaSucursal()) {
			return $this->output
			->set_status_header(404)
			->set_output(json_encode([
				"exito" => 0,
				"mensaje" => "La cotización no existe."
			]));
		}

		$datos = $cot->datosImpresion();
		$html = $this->load->view("ven/cotizacion_pdf", $datos, true);

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

		$pdf->SetTitle("Cotización {$cot->numero}");
		$pdf->SetAuthor($datos["empresa"] ? $datos["empresa"]->nombre : "Logy");

		if ((int)$cot->anulado === 1) {
			$pdf->SetWatermarkText("ANULADA");
			$pdf->showWatermarkText = true;
			$pdf->watermarkTextAlpha = 0.08;
		}

		$pdf->SetHTMLFooter(
			'<table width="100%" style="font-size: 7pt; color: #64748b; border-top: 1px solid #e2e8f0;"><tr>' .
			'<td style="padding-top: 3px;">Impreso el ' . date("d/m/Y H:i") . ' por ' . html_escape($datos["impreso_por"]) . '</td>' .
			'<td style="padding-top: 3px; text-align: right;">' . html_escape($cot->numero) . ' · Página {PAGENO} de {nbpg}</td>' .
			'</tr></table>'
		);

		$pdf->WriteHTML($html);

		$this->output
		->set_content_type("application/pdf")
		->set_header("Content-Disposition: inline; filename=\"{$cot->numero}.pdf\"")
		->set_output($pdf->Output("", "S"));
	}
}

/* End of file Cotizacion.php */
/* Location: ./application/controllers/ven/Cotizacion.php */
