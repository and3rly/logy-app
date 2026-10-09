<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Punto de venta: la venta se registra completa al cobrar (no hay borradores)
class Venta extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"ven/Venta_model",
			"ven/Venta_detalle_model",
			"Stock_model",
			"Movimiento_model",
			"fin/Cuenta_cobrar_model",
			"mnt/Cliente_model",
			"mnt/Lista_precio_model",
			"mnt/Empresa_parametro_model"
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
			"lista" => $this->Venta_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$param = $this->catalogo->verEmpresaParametro();

		$data = [
			"fecha" => Hoy(),
			"moneda_id" => $param ? $param->moneda_id : null,
			"cat" => [
				"series"      => $this->catalogo->verVentaSeries(),
				"formas_pago" => $this->catalogo->verFormasPago(),
				"monedas"     => $this->catalogo->verMonedas(),
				"estados"     => $this->catalogo->verVentaEstados(["_todos" => true]),
				"categorias"  => $this->catalogo->verCategorias(["_todos" => true]),
				"productos"   => $this->Stock_model->existencias(),
				"listas_precio" => $this->catalogo->verListasPrecio(),
				# Para crear un cliente desde el punto de venta
				"municipios"    => $this->catalogo->verMunicipios(),
				"departamentos" => $this->catalogo->verDepartamentos()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	# Productos con la existencia actual de la sucursal (para refrescar el punto de venta)
	public function get_productos()
	{
		$data = [
			"lista" => $this->Stock_model->existencias()
		];

		$this->output->set_output(json_encode($data));
	}

	# Clientes por nombre, NIT o código (mínimo 2 caracteres)
	public function buscar_cliente()
	{
		$termino = trim((string)$this->input->get("termino"));

		$data = [
			"lista" => mb_strlen($termino) >= 2 ? $this->catalogo->buscarClientes($termino) : []
		];

		$this->output->set_output(json_encode($data));
	}

	# Cobra el ticket: crea la venta con sus líneas y descuenta el inventario
	public function guardar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "venta_serie_id") &&
				verPropiedad($datos, "forma_pago_id") &&
				verPropiedad($datos, "moneda_id")) {

				# El vendedor puede cambiar el precio (no menor al costo) y elegir la lista de precios
				# (null = precio general); descuento y cotización solo los pone la conversión de una cotización
				unset($datos->cotizacion_id);

				$lista = verPropiedad($datos, "lista_precio_id", null);

				if (!$lista) {
					$datos->lista_precio_id = null;
				}

				foreach ((array)verPropiedad($datos, "lineas", []) as $linea) {
					if (is_object($linea)) {
						unset($linea->descuento);
					}
				}

				$venta = new Venta_model();

				if ($lista && !$this->catalogo->verListasPrecio([
					"id" => $lista,
					"_uno" => true
				])) {
					$data["mensaje"] = "La lista de precios no existe o está inactiva.";
				} else if ($venta->registrar($datos)) {
					$data["exito"] = 1;
					$data["mensaje"] = "Venta {$venta->correlativo} registrada.";
					$data["linea"] = $venta->getInfo();
					$data["productos"] = $this->Stock_model->existencias();
				} else {
					$data["mensaje"] = $venta->getMensaje();
				}
			} else {
				# La moneda no se ve en el POS: sale de Parámetros
				$data["mensaje"] = verPropiedad($datos, "moneda_id")
					? "Seleccione la serie y la forma de pago."
					: "La empresa no tiene moneda configurada. Elíjala en Parámetros.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function get_detalle($id="")
	{
		$venta = new Venta_model($id);

		$data = [
			"det" => $venta->esDeLaSucursal() ? $venta->getDetalle() : []
		];

		$this->output->set_output(json_encode($data));
	}

	public function anular($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$motivo = trim((string)verPropiedad($datos, "motivo", ""));

			$venta = new Venta_model($id);

			if (!$venta->esDeLaSucursal()) {
				$data["mensaje"] = "La venta no existe.";
			} else if ($motivo === "") {
				$data["mensaje"] = "Indique el motivo de la anulación.";
			} else if ($venta->anular(mb_substr($motivo, 0, 500))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Venta {$venta->correlativo} anulada: los productos regresaron al inventario.";
				$data["linea"] = $venta->getInfo();
			} else {
				$data["mensaje"] = $venta->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Ticket de 80 mm o carta, según el formato de impresión de los parámetros
	public function imprimir($id="")
	{
		$venta = new Venta_model($id);

		if (!$venta->esDeLaSucursal()) {
			return $this->output
			->set_status_header(404)
			->set_output(json_encode([
				"exito" => 0,
				"mensaje" => "La venta no existe."
			]));
		}

		$datos = $venta->datosImpresion();

		# Sin parámetros o con un valor desconocido se imprime el ticket
		$param = $this->catalogo->verEmpresaParametro();
		$carta = $param && isset($param->formato_impresion) &&
			(int)$param->formato_impresion === Empresa_parametro_model::IMPRESION_CARTA;

		$temporal = APPPATH . "cache/mpdf";
		if (!is_dir($temporal)) {
			mkdir($temporal, 0775, true);
		}

		if ($carta) {
			$html = $this->load->view("ven/venta_carta_pdf", $datos, true);

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

			$pdf->SetHTMLFooter(
				'<table width="100%" style="font-size: 7pt; color: #64748b; border-top: 1px solid #e2e8f0;"><tr>' .
				'<td style="padding-top: 3px;">Impreso el ' . date("d/m/Y H:i") . '</td>' .
				'<td style="padding-top: 3px; text-align: right;">' . html_escape($venta->correlativo) . ' · Página {PAGENO} de {nbpg}</td>' .
				'</tr></table>'
			);
		} else {
			$html = $this->load->view("ven/venta_pdf", $datos, true);

			# Alto según las líneas: el ticket no se corta en páginas
			$alto = 120 + (count($datos["detalle"]) * 9);

			$pdf = new \Mpdf\Mpdf([
				"mode" => "utf-8",
				"format" => [80, $alto],
				"margin_top" => 4,
				"margin_bottom" => 4,
				"margin_left" => 4,
				"margin_right" => 4,
				"tempDir" => $temporal
			]);
		}

		$pdf->SetTitle("Venta {$venta->correlativo}");
		$pdf->SetAuthor($datos["empresa"] ? $datos["empresa"]->nombre : "Logy");

		if ((int)$venta->anulado === 1) {
			$pdf->SetWatermarkText("ANULADA");
			$pdf->showWatermarkText = true;
			$pdf->watermarkTextAlpha = $carta ? 0.08 : 0.1;
		}

		$pdf->WriteHTML($html);

		$this->output
		->set_content_type("application/pdf")
		->set_header("Content-Disposition: inline; filename=\"{$venta->correlativo}.pdf\"")
		->set_output($pdf->Output("", "S"));
	}
}

/* End of file Venta.php */
/* Location: ./application/controllers/ven/Venta.php */
