<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kardex extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
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

	# Productos (bienes activos) con su existencia actual y el período sugerido: el mes en curso
	public function get_datos()
	{
		$data = [
			"fecha" => Hoy(),
			"fecha_inicial" => date("Y-m-01"),
			"productos" => $this->Stock_model->existencias()
		];

		$this->output->set_output(json_encode($data));
	}

	# Filtros: producto (sin él, todos los productos), fdel, fal y lote.
	# Cada movimiento lleva el saldo de su producto después de aplicarse
	public function buscar()
	{
		$filtros = $this->filtros();
		$saldos = $this->Movimiento_model->saldosKardex($filtros);
		$lista = $this->conSaldo($this->Movimiento_model->kardex($filtros), $saldos);

		$data = [
			"exito" => true,
			"saldo_inicial" => $filtros["producto"] ? elemento($saldos, $this->Movimiento_model->llaveSaldo($filtros["producto"], $filtros["presentacion"]), 0) : null,
			"lista" => $lista,
			"lotes" => $filtros["producto"] ? $this->Movimiento_model->lotesKardex($filtros["producto"], $filtros["presentacion"]) : []
		];

		$this->output->set_output(json_encode($data));
	}

	# Descarga en Excel el kardex con los mismos filtros de la pantalla.
	# Con producto: saldo inicial y saldo por fórmula; sin producto: columnas del producto y su saldo
	public function excel()
	{
		$filtros = $this->filtros();
		$producto = null;

		if ($filtros["producto"]) {
			$producto = $this->catalogo->verProductos([
				"id" => $filtros["producto"],
				"_todos" => true,
				"_uno" => true
			]);

			if (!$producto) {
				$this->output->set_status_header("404");
				return;
			}

			if ($filtros["presentacion"]) {
				$presentacion = $this->db
				->where("id", $filtros["presentacion"])
				->where("producto_id", $producto->id)
				->get("producto_presentacion")
				->row();

				$producto->nombre .= $presentacion ? " · {$presentacion->nombre}" : "";
			}
		}

		$saldos = $this->Movimiento_model->saldosKardex($filtros);
		$lista = $this->conSaldo($this->Movimiento_model->kardex($filtros), $saldos);
		$sucursal = $this->catalogo->verSucursales([
			"id" => $this->_ses->sucursal_id,
			"_todos" => true,
			"_uno" => true
		]);

		$libro = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$libro->getProperties()
		->setCreator("Logy")
		->setTitle("Kardex");

		$hoja = $libro->getActiveSheet();
		$hoja->setTitle("Kardex");

		# Encabezado del reporte
		$hoja->setCellValue("A1", $producto ? "Kardex de {$producto->nombre}" : "Kardex general");
		$hoja->setCellValueExplicit("A2", $producto ? "Código: {$producto->codigo}" : "Todos los productos", \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$hoja->setCellValue("A3", "Sucursal: " . ($sucursal ? $sucursal->nombre : ""));
		$hoja->setCellValue("A4", "Período: " . $this->textoPeriodo($filtros) . ($producto && $filtros["lote"] ? " · Lote que vence el " . date("d/m/Y", strtotime($filtros["lote"])) : ""));
		$hoja->setCellValue("A5", "Generado el " . date("d/m/Y H:i"));
		$hoja->getStyle("A1")->getFont()->setBold(true)->setSize(14);
		$hoja->getStyle("A2:A5")->getFont()->getColor()->setRGB("64748B");

		# Columnas; en el general van código y producto después de la fecha
		$columnas = ["Fecha"];

		if (!$producto) {
			$columnas[] = "Código";
			$columnas[] = "Producto";
		}

		$columnas = array_merge($columnas, [
			"Movimiento",
			"Documento",
			"Detalle",
			"Lote (vence)",
			"Usuario",
			"Entrada",
			"Salida",
			"Saldo"
		]);

		# Letra de cada columna por su nombre
		$letra = [];

		foreach ($columnas as $i => $nombre) {
			$letra[$nombre] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
		}

		$ult = end($letra);
		$ent = $letra["Entrada"];
		$sal = $letra["Salida"];
		$sdo = $letra["Saldo"];
		$lote = $letra["Lote (vence)"];

		$filaTitulos = 7;
		$hoja->fromArray($columnas, null, "A{$filaTitulos}");
		$hoja->getStyle("A{$filaTitulos}:{$ult}{$filaTitulos}")->applyFromArray([
			"font" => ["bold" => true],
			"fill" => [
				"fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				"startColor" => ["rgb" => "E2E8F0"]
			],
			"borders" => [
				"bottom" => ["borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
			]
		]);

		$fila = $filaTitulos;

		# Un solo producto: primera fila con la existencia con la que empieza el período
		if ($producto) {
			$fila++;
			$hoja->setCellValue("{$letra['Detalle']}{$fila}", "Saldo inicial");
			$hoja->setCellValue("{$sdo}{$fila}", elemento($saldos, $this->Movimiento_model->llaveSaldo($producto->id, $filtros["presentacion"]), 0));
			$hoja->getStyle("A{$fila}:{$ult}{$fila}")->getFont()->setItalic(true);
		}

		$filaSaldoInicial = $fila;
		$primera = $fila + 1;

		foreach ($lista as $reg) {
			$fila++;
			$cantidad = (float)$reg->cantidad;

			$hoja->setCellValue("A{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($reg->fecha)));
			$hoja->setCellValue("{$letra['Movimiento']}{$fila}", $reg->ntipo);
			$hoja->setCellValueExplicit("{$letra['Documento']}{$fila}", (string)$reg->documento, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$hoja->setCellValue("{$letra['Detalle']}{$fila}", $reg->observacion);
			$hoja->setCellValue("{$letra['Usuario']}{$fila}", $reg->nusuario);

			if ($producto) {
				# Saldo por fórmula (anterior + entrada - salida): se recalcula si se edita
				$hoja->setCellValue("{$sdo}{$fila}", "={$sdo}" . ($fila - 1) . "+{$ent}{$fila}-{$sal}{$fila}");
			} else {
				$hoja->setCellValueExplicit("{$letra['Código']}{$fila}", (string)$reg->cproducto, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$hoja->setCellValue("{$letra['Producto']}{$fila}", $reg->nproducto . ($reg->npresentacion ? " · {$reg->npresentacion}" : ""));
				$hoja->setCellValue("{$sdo}{$fila}", $reg->saldo);
			}

			if ($reg->fecha_vence) {
				$hoja->setCellValue("{$lote}{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($reg->fecha_vence)));
			}

			if ($cantidad >= 0) {
				$hoja->setCellValue("{$ent}{$fila}", $cantidad);
				$hoja->getStyle("{$ent}{$fila}")->getFont()->getColor()->setRGB("15803D");
			} else {
				$hoja->setCellValue("{$sal}{$fila}", abs($cantidad));
				$hoja->getStyle("{$sal}{$fila}")->getFont()->getColor()->setRGB("B91C1C");
			}
		}

		$ultima = max($fila, $primera);

		# Totales del período; con un producto, también el saldo final
		$filaTotal = $ultima + 1;
		$hoja->setCellValue("A{$filaTotal}", "Total");
		$hoja->setCellValue("{$letra['Movimiento']}{$filaTotal}", count($lista) . (count($lista) === 1 ? " movimiento" : " movimientos"));
		$hoja->setCellValue("{$ent}{$filaTotal}", "=SUM({$ent}{$primera}:{$ent}{$ultima})");
		$hoja->setCellValue("{$sal}{$filaTotal}", "=SUM({$sal}{$primera}:{$sal}{$ultima})");

		if ($producto) {
			$hoja->setCellValue("{$letra['Detalle']}{$filaTotal}", "Saldo final");
			$hoja->setCellValue("{$sdo}{$filaTotal}", "={$sdo}{$filaSaldoInicial}+{$ent}{$filaTotal}-{$sal}{$filaTotal}");
		}

		$hoja->getStyle("A{$filaTotal}:{$ult}{$filaTotal}")->applyFromArray([
			"font" => ["bold" => true],
			"fill" => [
				"fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				"startColor" => ["rgb" => "E2E8F0"]
			],
			"borders" => [
				"top" => ["borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
			]
		]);

		# Formatos de fecha y cantidad
		$hoja->getStyle("A{$primera}:A{$ultima}")->getNumberFormat()->setFormatCode("dd/mm/yyyy hh:mm");
		$hoja->getStyle("{$lote}{$primera}:{$lote}{$ultima}")->getNumberFormat()->setFormatCode("dd/mm/yyyy");
		$hoja->getStyle("{$ent}" . ($filaTitulos + 1) . ":{$sdo}{$filaTotal}")->getNumberFormat()->setFormatCode("#,##0.00");

		foreach ($letra as $col) {
			$hoja->getColumnDimension($col)->setAutoSize(true);
		}

		# Títulos fijos al desplazarse e impresión horizontal a una página de ancho
		$hoja->freezePane("A" . ($filaTitulos + 1));
		$hoja->getPageSetup()
		->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
		->setFitToWidth(1)
		->setFitToHeight(0);

		$escritor = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro);

		ob_start();
		$escritor->save("php://output");
		$contenido = ob_get_clean();

		$nombre = "kardex_" . ($producto ? preg_replace("/[^A-Za-z0-9_-]/", "", $producto->codigo) : "general") . "_" . date("Ymd_Hi") . ".xlsx";

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
			"producto" => (int)$this->input->get("producto"),
			"presentacion" => (int)$this->input->get("presentacion"),
			"fdel" => $fecha($this->input->get("fdel")),
			"fal" => $fecha($this->input->get("fal")),
			"lote" => $fecha($this->input->get("lote"))
		];
	}

	# Agrega a cada movimiento el saldo acumulado de su producto y presentación, a partir de su saldo inicial
	private function conSaldo($lista, $saldos)
	{
		foreach ($lista as $reg) {
			$llave = $this->Movimiento_model->llaveSaldo($reg->producto_id, $reg->producto_presentacion_id);
			$saldo = round(elemento($saldos, $llave, 0) + (float)$reg->cantidad, 2);
			$saldos[$llave] = $saldo;
			$reg->saldo = $saldo;
		}

		return $lista;
	}

	# Período en texto para el encabezado del Excel
	private function textoPeriodo($filtros)
	{
		$del = $filtros["fdel"] ? date("d/m/Y", strtotime($filtros["fdel"])) : "";
		$al = $filtros["fal"] ? date("d/m/Y", strtotime($filtros["fal"])) : "";

		if ($del && $al) {
			return "del {$del} al {$al}";
		}

		if ($del) {
			return "desde el {$del}";
		}

		return $al ? "hasta el {$al}" : "todo el historial";
	}
}

/* End of file Kardex.php */
/* Location: ./application/controllers/inv/Kardex.php */
