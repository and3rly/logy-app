<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Reporte de ventas por día con su costo y ganancia (solo lectura)
class Venta_dia extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"ven/Venta_model",
			"rep/Venta_dia_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Catálogos de los filtros; el vendedor solo lo elige un administrador
	public function get_datos()
	{
		$admin = es_administrador();

		$data = [
			"fecha" => Hoy(),
			"simbolo" => $this->simbolo(),
			"administrador" => $admin,
			"formas_pago" => $this->catalogo->verFormasPago(),
			"vendedores" => $admin ? $this->Venta_dia_model->vendedores() : []
		];

		$this->output->set_output(json_encode($data));
	}

	# Filtros: fdel, fal, forma_pago y usuario
	public function buscar()
	{
		$data = [
			"exito" => true,
			"lista" => $this->Venta_dia_model->porDia($this->filtros())
		];

		$this->output->set_output(json_encode($data));
	}

	# Detalle de un día (AAAA-MM-DD): sus ventas y los productos vendidos, con los mismos filtros
	public function get_dia($dia="")
	{
		if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dia)) {
			return $this->output->set_output(json_encode([
				"exito" => false,
				"mensaje" => "La fecha no es válida."
			]));
		}

		$filtros = array_merge($this->filtros(), [
			"fdel" => $dia,
			"fal" => $dia
		]);

		$data = [
			"exito" => true,
			"ventas" => $this->ventas($filtros),
			"productos" => $this->Venta_dia_model->productos($filtros)
		];

		$this->output->set_output(json_encode($data));
	}

	# Excel con los mismos filtros: hoja del resumen por día y hoja con cada venta
	public function excel()
	{
		$filtros = $this->filtros();
		$dias = $this->Venta_dia_model->porDia($filtros);
		$ventas = $this->ventas($filtros);
		$formato = formatoExcelMonto($this->simbolo());
		$sucursal = $this->catalogo->verSucursales([
			"id" => $this->_ses->sucursal_id,
			"_todos" => true,
			"_uno" => true
		]);

		$libro = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$libro->getProperties()
		->setCreator("Logy")
		->setTitle("Ventas por día");

		# Hoja 1: un renglón por día
		$hoja = $libro->getActiveSheet();
		$hoja->setTitle("Por día");
		$this->encabezado($hoja, "Ventas por día", $sucursal, $filtros);

		$columnas = [
			"Día",
			"Ventas",
			"Vendido",
			"Costo",
			"Ganancia",
			"Margen",
			"Ticket promedio",
			"Anuladas"
		];

		$filaTitulos = 7;
		$hoja->fromArray($columnas, null, "A{$filaTitulos}");
		$this->estiloFila($hoja, "A{$filaTitulos}:H{$filaTitulos}", "bottom");

		$fila = $filaTitulos;
		$primera = $fila + 1;

		foreach ($dias as $reg) {
			$fila++;
			$hoja->setCellValue("A{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($reg->dia)));
			$hoja->setCellValue("B{$fila}", (int)$reg->ventas);
			$hoja->setCellValue("C{$fila}", (float)$reg->total);
			$hoja->setCellValue("D{$fila}", (float)$reg->costo);
			$hoja->setCellValue("E{$fila}", (float)$reg->ganancia);
			$hoja->setCellValue("F{$fila}", "=IF(C{$fila}=0,0,E{$fila}/C{$fila})");
			$hoja->setCellValue("G{$fila}", "=IF(B{$fila}=0,0,C{$fila}/B{$fila})");
			$hoja->setCellValue("H{$fila}", (int)$reg->anuladas);
		}

		$ultima = max($fila, $primera);
		$total = $ultima + 1;

		$hoja->setCellValue("A{$total}", "Total");
		foreach (["B", "C", "D", "E", "H"] as $col) {
			$hoja->setCellValue("{$col}{$total}", "=SUM({$col}{$primera}:{$col}{$ultima})");
		}
		$hoja->setCellValue("F{$total}", "=IF(C{$total}=0,0,E{$total}/C{$total})");
		$hoja->setCellValue("G{$total}", "=IF(B{$total}=0,0,C{$total}/B{$total})");
		$this->estiloFila($hoja, "A{$total}:H{$total}", "top");

		$hoja->getStyle("A{$primera}:A{$ultima}")->getNumberFormat()->setFormatCode("dd/mm/yyyy");
		$hoja->getStyle("C{$primera}:E{$total}")->getNumberFormat()->setFormatCode($formato);
		$hoja->getStyle("G{$primera}:G{$total}")->getNumberFormat()->setFormatCode($formato);
		$hoja->getStyle("F{$primera}:F{$total}")->getNumberFormat()->setFormatCode("0.0%");
		$this->terminarHoja($hoja, "H", $filaTitulos);

		# Hoja 2: cada venta del período
		$hoja = $libro->createSheet();
		$hoja->setTitle("Ventas");
		$this->encabezado($hoja, "Ventas del período", $sucursal, $filtros);

		$columnas = [
			"Fecha",
			"Correlativo",
			"Cliente",
			"Forma de pago",
			"Vendedor",
			"Estado",
			"Vendido",
			"Costo",
			"Ganancia"
		];

		$hoja->fromArray($columnas, null, "A{$filaTitulos}");
		$this->estiloFila($hoja, "A{$filaTitulos}:I{$filaTitulos}", "bottom");

		$fila = $filaTitulos;
		$primera = $fila + 1;

		# Las anuladas se listan en gris y sin montos, para que el total cuadre con la hoja por día
		foreach ($ventas as $reg) {
			$fila++;
			$anulada = (int)$reg->venta_estado_id === Venta_model::ANULADA;

			$hoja->setCellValue("A{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($reg->fecha)));
			$hoja->setCellValueExplicit("B{$fila}", (string)$reg->correlativo, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$hoja->setCellValue("C{$fila}", $reg->ncliente);
			$hoja->setCellValue("D{$fila}", $reg->nforma_pago);
			$hoja->setCellValue("E{$fila}", $reg->nusuario);
			$hoja->setCellValue("F{$fila}", $reg->nestado);

			if ($anulada) {
				$hoja->getStyle("A{$fila}:I{$fila}")->getFont()->getColor()->setRGB("94A3B8");
			} else {
				$hoja->setCellValue("G{$fila}", (float)$reg->total_precio);
				$hoja->setCellValue("H{$fila}", (float)$reg->total_costo);
				$hoja->setCellValue("I{$fila}", (float)$reg->ganancia);
			}
		}

		$ultima = max($fila, $primera);
		$total = $ultima + 1;

		$hoja->setCellValue("A{$total}", "Total");
		foreach (["G", "H", "I"] as $col) {
			$hoja->setCellValue("{$col}{$total}", "=SUM({$col}{$primera}:{$col}{$ultima})");
		}
		$this->estiloFila($hoja, "A{$total}:I{$total}", "top");

		$hoja->getStyle("A{$primera}:A{$ultima}")->getNumberFormat()->setFormatCode("dd/mm/yyyy hh:mm");
		$hoja->getStyle("G{$primera}:I{$total}")->getNumberFormat()->setFormatCode($formato);
		$this->terminarHoja($hoja, "I", $filaTitulos);

		$libro->setActiveSheetIndex(0);
		$escritor = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro);

		ob_start();
		$escritor->save("php://output");
		$contenido = ob_get_clean();

		$nombre = "ventas_por_dia_" . date("Ymd_Hi") . ".xlsx";

		$this->output
		->set_content_type("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
		->set_header("Content-Disposition: attachment; filename=\"{$nombre}\"")
		->set_header("Cache-Control: max-age=0")
		->set_output($contenido);
	}

	# Filtros de la petición; las fechas solo se aceptan como AAAA-MM-DD
	private function filtros()
	{
		$fecha = function ($valor) {
			$valor = trim((string)$valor);

			return preg_match("/^\d{4}-\d{2}-\d{2}$/", $valor) ? $valor : "";
		};

		return [
			"fdel" => $fecha($this->input->get("fdel")),
			"fal" => $fecha($this->input->get("fal")),
			"forma_pago" => (int)$this->input->get("forma_pago"),
			"usuario" => (int)$this->input->get("usuario")
		];
	}

	# Ventas del período con los nombres de Venta_model::_buscar (ya filtra sucursal y usuario),
	# más la forma de pago y el vendedor elegidos
	private function ventas($filtros)
	{
		$lista = $this->Venta_model->_buscar([
			"fdel" => $filtros["fdel"],
			"fal" => $filtros["fal"]
		]);

		return array_values(array_filter($lista, function ($v) use ($filtros) {
			return (!$filtros["forma_pago"] || (int)$v->forma_pago_id === $filtros["forma_pago"]) &&
				(!$filtros["usuario"] || !es_administrador() || (int)$v->usuario_id === $filtros["usuario"]);
		}));
	}

	# Símbolo de la moneda de los parámetros de la empresa
	private function simbolo()
	{
		$param = $this->catalogo->verEmpresaParametro();

		if (!$param || !$param->moneda_id) {
			return "";
		}

		$moneda = $this->db
		->select("simbolo")
		->where("id", $param->moneda_id)
		->get("moneda")
		->row();

		return $moneda ? $moneda->simbolo : "";
	}

	private function encabezado($hoja, $titulo, $sucursal, $filtros)
	{
		$del = $filtros["fdel"] ? date("d/m/Y", strtotime($filtros["fdel"])) : "el inicio";
		$al = $filtros["fal"] ? date("d/m/Y", strtotime($filtros["fal"])) : "hoy";

		$hoja->setCellValue("A1", $titulo);
		$hoja->setCellValue("A2", "Sucursal: " . ($sucursal ? $sucursal->nombre : ""));
		$hoja->setCellValue("A3", "Período: del {$del} al {$al}");
		$hoja->setCellValue("A4", "Las ventas anuladas no suman al vendido ni a la ganancia");
		$hoja->setCellValue("A5", "Generado el " . date("d/m/Y H:i"));
		$hoja->getStyle("A1")->getFont()->setBold(true)->setSize(14);
		$hoja->getStyle("A2:A5")->getFont()->getColor()->setRGB("64748B");
	}

	# Fila de títulos o de totales: negrita, fondo gris y una línea
	private function estiloFila($hoja, $rango, $borde)
	{
		$hoja->getStyle($rango)->applyFromArray([
			"font" => ["bold" => true],
			"fill" => [
				"fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				"startColor" => ["rgb" => "E2E8F0"]
			],
			"borders" => [
				$borde => ["borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
			]
		]);
	}

	# Ancho automático, títulos fijos e impresión horizontal a una página de ancho
	private function terminarHoja($hoja, $ultimaColumna, $filaTitulos)
	{
		foreach (range("A", $ultimaColumna) as $col) {
			$hoja->getColumnDimension($col)->setAutoSize(true);
		}

		$hoja->freezePane("A" . ($filaTitulos + 1));
		$hoja->getPageSetup()
		->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
		->setFitToWidth(1)
		->setFitToHeight(0);
	}
}

/* End of file Venta_dia.php */
/* Location: ./application/controllers/rep/Venta_dia.php */
