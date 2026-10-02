<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Existencia extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["Stock_model"]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Stock_model->existencias()
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"fecha" => Hoy(true),
			"simbolo" => $this->simboloMoneda(),
			"cat" => [
				"categorias" => $this->catalogo->verCategorias(["_todos" => true]),
				"marcas"     => $this->catalogo->verMarcas(["_todos" => true])
			]
		];

		$this->output->set_output(json_encode($data));
	}

	# Descarga en Excel lo que se ve en pantalla: filtros termino, categoria, marca y estado
	public function excel()
	{
		$filtros = [
			"termino" => trim((string)$this->input->get("termino")),
			"categoria" => $this->input->get("categoria"),
			"marca" => $this->input->get("marca"),
			"estado" => $this->input->get("estado")
		];

		$lista = $this->Stock_model->existencias($filtros);
		$simbolo = $this->simboloMoneda();
		$sucursal = $this->catalogo->verSucursales([
			"id" => $this->_ses->sucursal_id,
			"_todos" => true,
			"_uno" => true
		]);

		$estados = [
			"disponible" => [
				"nombre" => "Con existencia",
				"color" => "EAF6EE"
			],
			"minimo" => [
				"nombre" => "Bajo mínimo",
				"color" => "FFF6DB"
			],
			"agotado" => [
				"nombre" => "Sin existencia",
				"color" => "FCEBEC"
			]
		];

		$libro = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$libro->getProperties()
		->setCreator("Logy")
		->setTitle("Existencias");

		$hoja = $libro->getActiveSheet();
		$hoja->setTitle("Existencias");

		# Encabezado del reporte
		$hoja->setCellValue("A1", "Reporte de existencias");
		$hoja->setCellValue("A2", "Sucursal: " . ($sucursal ? $sucursal->nombre : ""));
		$hoja->setCellValue("A3", "Generado el " . date("d/m/Y H:i"));
		$hoja->setCellValue("A4", "Filtros: " . $this->textoFiltros($filtros, $estados));
		$hoja->getStyle("A1")->getFont()->setBold(true)->setSize(14);
		$hoja->getStyle("A2:A4")->getFont()->getColor()->setRGB("64748B");

		# Columnas
		$columnas = [
			"Código",
			"Producto",
			"Categoría",
			"Marca",
			"Unidad",
			"Existencia",
			"Mínimo",
			"Costo",
			"Valor",
			"Próx. vence",
			"Estado"
		];

		$filaTitulos = 6;
		$hoja->fromArray($columnas, null, "A{$filaTitulos}");
		$hoja->getStyle("A{$filaTitulos}:K{$filaTitulos}")->applyFromArray([
			"font" => ["bold" => true],
			"fill" => [
				"fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				"startColor" => ["rgb" => "E2E8F0"]
			],
			"borders" => [
				"bottom" => ["borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
			]
		]);

		# Filas: color pálido según el estado, como en la pantalla
		$fila = $filaTitulos;

		foreach ($lista as $reg) {
			$fila++;
			$estado = $this->Stock_model->estadoExistencia($reg);

			$hoja->setCellValueExplicit("A{$fila}", $reg->codigo, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$hoja->setCellValue("B{$fila}", $reg->nombre);
			$hoja->setCellValue("C{$fila}", $reg->ncategoria);
			$hoja->setCellValue("D{$fila}", $reg->nmarca);
			$hoja->setCellValue("E{$fila}", $reg->nunidad);
			$hoja->setCellValue("F{$fila}", (float)$reg->existencia);
			$hoja->setCellValue("G{$fila}", (float)$reg->existencia_minima);
			$hoja->setCellValue("H{$fila}", (float)$reg->costo);
			$hoja->setCellValue("I{$fila}", "=F{$fila}*H{$fila}");
			$hoja->setCellValue("K{$fila}", $estados[$estado]["nombre"]);

			if ($reg->proximo_vence) {
				$hoja->setCellValue("J{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($reg->proximo_vence)));
			}

			$hoja->getStyle("A{$fila}:K{$fila}")->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setRGB($estados[$estado]["color"]);

			# Debajo, las presentaciones con existencia (se cuentan aparte de la unidad)
			foreach ($reg->presentaciones as $pre) {
				if ((float)$pre->existencia == 0) {
					continue;
				}

				$fila++;
				$hoja->setCellValueExplicit("A{$fila}", $reg->codigo, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$hoja->setCellValue("B{$fila}", $reg->nombre);
				$hoja->setCellValue("C{$fila}", $reg->ncategoria);
				$hoja->setCellValue("D{$fila}", $reg->nmarca);
				$hoja->setCellValue("E{$fila}", $pre->nombre);
				$hoja->setCellValue("F{$fila}", (float)$pre->existencia);
				$hoja->setCellValue("H{$fila}", (float)$pre->costo);
				$hoja->setCellValue("I{$fila}", "=F{$fila}*H{$fila}");
				$hoja->setCellValue("K{$fila}", "Presentación");

				if ($pre->proximo_vence) {
					$hoja->setCellValue("J{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($pre->proximo_vence)));
				}
			}
		}

		$primera = $filaTitulos + 1;
		$ultima = max($fila, $primera);

		# Totales con fórmula: si se edita el Excel, se recalculan
		$filaTotal = $ultima + 1;
		$hoja->setCellValue("A{$filaTotal}", "Total");
		$hoja->setCellValue("B{$filaTotal}", count($lista) . (count($lista) === 1 ? " producto" : " productos"));
		$hoja->setCellValue("F{$filaTotal}", "=SUM(F{$primera}:F{$ultima})");
		$hoja->setCellValue("I{$filaTotal}", "=SUM(I{$primera}:I{$ultima})");
		$hoja->getStyle("A{$filaTotal}:K{$filaTotal}")->applyFromArray([
			"font" => ["bold" => true],
			"fill" => [
				"fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				"startColor" => ["rgb" => "E2E8F0"]
			],
			"borders" => [
				"top" => ["borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
			]
		]);

		# Formatos de número, moneda y fecha
		$moneda = formatoExcelMonto($simbolo);
		$hoja->getStyle("F{$primera}:G{$filaTotal}")->getNumberFormat()->setFormatCode("#,##0.00");
		$hoja->getStyle("H{$primera}:I{$filaTotal}")->getNumberFormat()->setFormatCode($moneda);
		$hoja->getStyle("J{$primera}:J{$ultima}")->getNumberFormat()->setFormatCode("dd/mm/yyyy");

		foreach (range("A", "K") as $col) {
			$hoja->getColumnDimension($col)->setAutoSize(true);
		}

		# Títulos fijos al desplazarse, autofiltro e impresión horizontal a una página de ancho
		$hoja->freezePane("A{$primera}");
		$hoja->setAutoFilter("A{$filaTitulos}:K{$ultima}");
		$hoja->getPageSetup()
		->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
		->setFitToWidth(1)
		->setFitToHeight(0);

		$escritor = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro);

		ob_start();
		$escritor->save("php://output");
		$contenido = ob_get_clean();

		$nombre = "existencias_" . date("Ymd_Hi") . ".xlsx";

		$this->output
		->set_content_type("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
		->set_header("Content-Disposition: attachment; filename=\"{$nombre}\"")
		->set_header("Cache-Control: max-age=0")
		->set_output($contenido);
	}

	# Símbolo de la moneda de los parámetros de la empresa
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

	# Filtros aplicados, en texto para el encabezado del Excel
	private function textoFiltros($filtros, $estados)
	{
		$texto = [];

		if ($filtros["termino"] !== "") {
			$texto[] = "Búsqueda \"{$filtros['termino']}\"";
		}

		if ($filtros["categoria"]) {
			$tmp = $this->catalogo->verCategorias([
				"id" => $filtros["categoria"],
				"_todos" => true,
				"_uno" => true
			]);
			$texto[] = "Categoría " . ($tmp ? $tmp->nombre : "");
		}

		if ($filtros["marca"]) {
			$tmp = $this->catalogo->verMarcas([
				"id" => $filtros["marca"],
				"_todos" => true,
				"_uno" => true
			]);
			$texto[] = "Marca " . ($tmp ? $tmp->nombre : "");
		}

		if (isset($estados[$filtros["estado"]])) {
			$texto[] = "Estado " . $estados[$filtros["estado"]]["nombre"];
		}

		return count($texto) ? implode(" · ", $texto) : "ninguno";
	}
}

/* End of file Existencia.php */
/* Location: ./application/controllers/inv/Existencia.php */
