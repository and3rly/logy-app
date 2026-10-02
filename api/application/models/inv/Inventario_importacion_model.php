<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Carga del inventario inicial desde Excel: plantilla, lectura y validación de las filas, y
 * creación de lo que falte (marca, categoría, unidad de medida y producto) al importar.
 * El producto existente se reconoce por código de barras o, si la fila no trae, por nombre;
 * los nombres se comparan sin mayúsculas ni tildes (collation de la base).
 */
class Inventario_importacion_model extends CI_Model {

	const MAX_FILAS = 2000;
	const HOJA = "Productos";

	# campo => [encabezado de la plantilla, ancho, obligatorio, ayuda]
	private $columnas = [
		"nombre" => ["Nombre del producto", 42, true, "Máximo 300 caracteres. Se usa para saber si el producto ya existe cuando la fila no trae código de barras."],
		"descripcion" => ["Descripción", 40, false, "Texto libre."],
		"codigo_barra" => ["Código de barras", 18, false, "Si viene, se usa primero para buscar el producto existente. Escríbalo como texto para no perder ceros."],
		"categoria" => ["Categoría", 18, true, "Si no existe en el sistema se crea automáticamente."],
		"marca" => ["Marca", 20, true, "Si no existe se crea automáticamente. Use \"Genérica\" para productos sin marca."],
		"unidad" => ["Unidad de medida", 18, true, "Elija de la hoja Unidades; si no existe en el sistema se crea con su código."],
		"costo" => ["Costo unitario", 15, true, "Costo de compra por unidad de medida, mayor o igual a 0."],
		"precio" => ["Precio de venta", 15, true, "Precio por unidad de medida, mayor a 0."],
		"minimo" => ["Existencia mínima", 15, false, "Por defecto 0. Sirve para la alerta de existencia baja."],
		"controla" => ["Controla vencimiento", 14, false, "Sí o No. Por defecto No."],
		"vence" => ["Fecha de vencimiento", 16, false, "Obligatoria si controla vencimiento; vacía si no. Formato dd/mm/aaaa. Cada fecha es un lote."],
		"cantidad" => ["Cantidad", 12, true, "Mayor a 0, en la unidad de medida indicada. Se suma a la existencia de la sucursal en sesión."]
	];

	# Unidades sugeridas: nombre => código (el código se usa al crearlas)
	private $unidades = [
		"Unidad" => "UND",
		"Par" => "PAR",
		"Docena" => "DOC",
		"Caja" => "CJA",
		"Bolsa" => "BLS",
		"Saco" => "SAC",
		"Quintal" => "QQ",
		"Libra" => "LB",
		"Kilogramo" => "KG",
		"Onza" => "OZ",
		"Litro" => "LT",
		"Galón" => "GL",
		"Mililitro" => "ML",
		"Metro" => "M",
		"Rollo" => "RLL",
		"Pliego" => "PLG"
	];

	# Catálogos ya resueltos durante una validación: [tabla][nombre normalizado] => fila o null
	private $cache = [];

	protected $mensaje = "";

	# ---------------------------------------------------------------- Plantilla

	public function plantilla($simbolo="")
	{
		$libro = new Spreadsheet();
		$libro->getProperties()
		->setCreator("Logy")
		->setTitle("Plantilla de inventario inicial");

		$hoja = $libro->getActiveSheet();
		$hoja->setTitle(self::HOJA);
		$this->hojaProductos($hoja, $simbolo, $this->unidadesPlantilla());

		$ejemplo = $libro->createSheet();
		$ejemplo->setTitle("Ejemplo");
		$this->hojaProductos($ejemplo, $simbolo);

		foreach ($this->filasEjemplo() as $n => $reg) {
			$fila = $n + 2;
			$ejemplo->fromArray(array_slice($reg, 0, 10), null, "A{$fila}");
			$ejemplo->setCellValueExplicit("C{$fila}", $reg[2], DataType::TYPE_STRING);

			if ($reg[10] !== "") {
				[$d, $m, $a] = explode("/", $reg[10]);
				$ejemplo->setCellValue("K{$fila}", Date::formattedPHPToExcel((int)$a, (int)$m, (int)$d));
			}

			$ejemplo->setCellValue("L{$fila}", $reg[11]);
		}

		$ejemplo->getTabColor()->setRGB("ADB5BD");

		$this->hojaInstrucciones($libro->createSheet());

		$uni = $libro->createSheet();
		$uni->setTitle("Unidades");
		$uni->fromArray(["Código", "Nombre"], null, "A1");
		$uni->fromArray($this->unidadesPlantilla(), null, "A2");
		$uni->getStyle("A1:B1")->applyFromArray($this->estiloEncabezado());
		$uni->getColumnDimension("A")->setWidth(10);
		$uni->getColumnDimension("B")->setWidth(22);

		$libro->setActiveSheetIndex(0);

		return $libro;
	}

	# Encabezados, formatos y validaciones de 500 filas; con $unidades agrega la lista desplegable
	private function hojaProductos($hoja, $simbolo, $unidades=null)
	{
		$letra = "A";

		foreach ($this->columnas as $col) {
			$hoja->setCellValue("{$letra}1", $col[0] . ($col[2] ? " *" : ""));
			$hoja->getColumnDimension($letra)->setWidth($col[1]);
			$hoja->getComment("{$letra}1")->getText()->createTextRun(($col[2] ? "Obligatorio. " : "Opcional. ") . $col[3]);
			$letra++;
		}

		$hoja->getStyle("A1:L1")->applyFromArray($this->estiloEncabezado());
		$hoja->getRowDimension(1)->setRowHeight(32);

		$moneda = formatoExcelMonto($simbolo);
		$hoja->getStyle("C2:C501")->getNumberFormat()->setFormatCode("@");
		$hoja->getStyle("G2:H501")->getNumberFormat()->setFormatCode($moneda);
		$hoja->getStyle("I2:I501")->getNumberFormat()->setFormatCode("#,##0.##");
		$hoja->getStyle("K2:K501")->getNumberFormat()->setFormatCode("dd/mm/yyyy");
		$hoja->getStyle("L2:L501")->getNumberFormat()->setFormatCode("#,##0.##");
		$hoja->getStyle("J2:K501")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

		if ($unidades !== null) {
			$this->validacion($hoja, "F2:F501", DataValidation::TYPE_LIST, "Unidades!\$B\$2:\$B\$" . (count($unidades) + 1), "Unidad de medida", "Elija una unidad de la hoja Unidades.", DataValidation::STYLE_WARNING);
			$this->validacion($hoja, "J2:J501", DataValidation::TYPE_LIST, '"Sí,No"', "Controla vencimiento", "Sí o No.");
			$this->validacion($hoja, "G2:G501", DataValidation::TYPE_DECIMAL, "0", "Costo", "Número mayor o igual a 0.", DataValidation::STYLE_STOP, DataValidation::OPERATOR_GREATERTHANOREQUAL);
			$this->validacion($hoja, "H2:H501", DataValidation::TYPE_DECIMAL, "0", "Precio de venta", "Número mayor a 0.", DataValidation::STYLE_STOP, DataValidation::OPERATOR_GREATERTHAN);
			$this->validacion($hoja, "I2:I501", DataValidation::TYPE_DECIMAL, "0", "Existencia mínima", "Número mayor o igual a 0.", DataValidation::STYLE_STOP, DataValidation::OPERATOR_GREATERTHANOREQUAL);
			$this->validacion($hoja, "K2:K501", DataValidation::TYPE_DATE, "DATE(2000,1,1)", "Fecha de vencimiento", "Fecha dd/mm/aaaa; solo si controla vencimiento.", DataValidation::STYLE_STOP, DataValidation::OPERATOR_GREATERTHAN);
			$this->validacion($hoja, "L2:L501", DataValidation::TYPE_DECIMAL, "0", "Cantidad", "Número mayor a 0.", DataValidation::STYLE_STOP, DataValidation::OPERATOR_GREATERTHAN);
		}

		$hoja->freezePane("B2");
		$hoja->setAutoFilter("A1:L1");
	}

	private function hojaInstrucciones($hoja)
	{
		$hoja->setTitle("Instrucciones");
		$hoja->setCellValue("A1", "Plantilla de inventario inicial");
		$hoja->getStyle("A1")->getFont()->setBold(true)->setSize(14);

		$reglas = [
			"Llene una fila por producto en la hoja Productos: es la única que se importa. La hoja Ejemplo solo sirve de guía.",
			"Las columnas con * son obligatorias. Pase el cursor sobre cada encabezado para ver su ayuda.",
			"No incluya el código interno: el sistema lo genera con el prefijo configurado en la empresa (PRD-000001, PRD-000002...).",
			"El producto ya existe si coincide su código de barras o, si la fila no trae, su nombre (sin importar mayúsculas ni tildes). Si existe se usa ese producto y no se modifican sus datos.",
			"Marca, categoría y unidad de medida se buscan por nombre; si no existen se crean automáticamente.",
			"Todo el inventario entra a la sucursal con la que inició sesión. Cada sucursal tiene un solo inventario inicial.",
			"Un mismo producto con varias fechas de vencimiento va en varias filas (una por lote), repitiendo los datos del producto.",
			"Si el producto ya tiene existencia en la sucursal, o se repite el mismo lote, las cantidades se suman.",
			"Montos con punto decimal. Fechas en formato dd/mm/aaaa."
		];

		foreach ($reglas as $i => $texto) {
			$hoja->setCellValue("A" . ($i + 3), ($i + 1) . ". " . $texto);
		}

		$fila = count($reglas) + 5;
		$hoja->fromArray(["Columna", "Obligatoria", "Descripción"], null, "A{$fila}");
		$hoja->getStyle("A{$fila}:C{$fila}")->applyFromArray($this->estiloEncabezado());

		foreach ($this->columnas as $col) {
			$fila++;
			$hoja->fromArray([
				$col[0],
				$col[2] ? "Sí" : "No",
				$col[3]
			], null, "A{$fila}");
		}

		$hoja->getColumnDimension("A")->setWidth(24);
		$hoja->getColumnDimension("B")->setWidth(12);
		$hoja->getColumnDimension("C")->setWidth(100);
		$hoja->getStyle("C1:C{$fila}")->getAlignment()->setWrapText(true);
	}

	# Unidades activas de la empresa y, después, las sugeridas que no existen: [[código, nombre]]
	private function unidadesPlantilla()
	{
		$lista = [];
		$nombres = [];

		foreach ($this->catalogo->verUnidadesMedida() as $u) {
			$lista[] = [$u->codigo, $u->nombre];
			$nombres[] = $this->normalizar($u->nombre);
		}

		foreach ($this->unidades as $nombre => $codigo) {
			if (!in_array($this->normalizar($nombre), $nombres)) {
				$lista[] = [$codigo, $nombre];
			}
		}

		return $lista;
	}

	private function validacion($hoja, $rango, $tipo, $formula, $titulo, $mensaje, $estilo=DataValidation::STYLE_STOP, $operador=null)
	{
		$v = new DataValidation();
		$v->setType($tipo)
		->setErrorStyle($estilo)
		->setAllowBlank(true)
		->setShowErrorMessage(true)
		->setShowInputMessage(true)
		->setShowDropDown($tipo === DataValidation::TYPE_LIST)
		->setErrorTitle($titulo)
		->setError($mensaje)
		->setPromptTitle($titulo)
		->setPrompt($mensaje)
		->setFormula1($formula);

		if ($operador) {
			$v->setOperator($operador);
		}

		$hoja->setDataValidation($rango, $v);
	}

	private function estiloEncabezado()
	{
		return [
			"font" => [
				"bold" => true,
				"color" => ["rgb" => "FFFFFF"]
			],
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"startColor" => ["rgb" => "0D6EFD"]
			],
			"alignment" => [
				"vertical" => Alignment::VERTICAL_CENTER,
				"wrapText" => true
			],
			"borders" => [
				"bottom" => ["borderStyle" => Border::BORDER_THIN]
			]
		];
	}

	# nombre, descripción, código de barras, categoría, marca, unidad, costo, precio, mínimo, controla, vence, cantidad
	private function filasEjemplo()
	{
		return [
			["Cemento gris UGC 4000 PSI 42.5 kg", "Cemento de uso general para construcción", "", "Construcción", "Cementos Progreso", "Saco", 78, 89, 30, "No", "", 120],
			["Varilla de hierro corrugado 3/8\" x 6 m", "Grado 40, legítima", "", "Construcción", "Aceros de Guatemala", "Unidad", 38.5, 46, 50, "No", "", 200],
			["Block de concreto 15 x 20 x 40 cm", "Resistencia 35 kg/cm²", "", "Construcción", "Genérica", "Unidad", 4.6, 5.75, 200, "No", "", 1500],
			["Clavo de acero 3\"", "Clavo liso con cabeza", "", "Ferretería", "Genérica", "Libra", 9.5, 13, 25, "No", "", 150],
			["Tubo PVC 1/2\" 315 PSI x 6 m", "Para agua potable", "7401001001158", "Plomería", "Amanco", "Unidad", 22, 29, 20, "No", "", 80],
			["Pegamento para PVC 118 ml", "Secado rápido", "7401001001226", "Plomería", "Amanco", "Unidad", 24, 32, 10, "No", "", 36],
			["Cable eléctrico THHN calibre 12 AWG", "Cobre, color negro", "", "Electricidad", "Condumex", "Metro", 4.25, 6, 100, "No", "", 300],
			["Foco LED 9 W luz blanca E27", "Equivale a 60 W", "8718699001018", "Electricidad", "Philips", "Unidad", 12, 18, 24, "No", "", 96],
			["Pintura látex blanco hueso", "Interior/exterior, acabado mate", "7402305000175", "Pinturas", "Sherwin-Williams", "Galón", 165, 215, 6, "No", "", 24],
			["Brocha de 3\"", "Cerda natural, mango de madera", "7506240103219", "Pinturas", "Truper", "Unidad", 14, 22, 12, "No", "", 48],
			["Martillo de uña 16 oz mango de fibra", "", "7506240160021", "Herramientas", "Truper", "Unidad", 58, 85, 5, "No", "", 15],
			["Cinta métrica 5 m", "Cinta de acero con freno", "0076973301260", "Herramientas", "Stanley", "Unidad", 45, 69, 5, "No", "", 20],
			["Candado de latón 40 mm", "Incluye 3 llaves", "", "Ferretería", "Yale", "Unidad", 55, 79, 5, "No", "", 18],
			["Guantes de cuero para trabajo", "Talla única", "", "Seguridad", "Truper", "Par", 28, 40, 10, "No", "", 40],
			["Machete 22\" cacha negra", "Hoja de acero al carbono", "", "Agrícola", "Imacasa", "Unidad", 42, 60, 10, "No", "", 35],
			["Bomba de mochila 16 L", "Aspersora manual", "7891018001607", "Agrícola", "Jacto", "Unidad", 385, 495, 2, "No", "", 6],
			["Manguera para jardín 1/2\" x 15 m", "Reforzada", "7506240123453", "Agrícola", "Truper", "Rollo", 95, 135, 3, "No", "", 10],
			["Fertilizante 15-15-15", "Saco de 100 lb", "", "Fertilizantes", "Disagro", "Quintal", 285, 330, 10, "No", "", 40],
			["Urea 46 %", "Saco de 100 lb", "", "Fertilizantes", "Disagro", "Quintal", 260, 305, 10, "No", "", 30],
			["Herbicida glifosato 35.6 SL", "Presentación de 1 litro", "7401005000140", "Agroquímicos", "Bayer", "Litro", 62, 80, 12, "Sí", "30/06/2027", 24],
			["Herbicida glifosato 35.6 SL", "Presentación de 1 litro", "7401005000140", "Agroquímicos", "Bayer", "Litro", 62, 80, 12, "Sí", "31/01/2028", 36],
			["Insecticida Karate Zeon 5 CS", "Presentación de 1 litro", "7401005000218", "Agroquímicos", "Syngenta", "Litro", 190, 240, 4, "Sí", "15/03/2028", 12],
			["Concentrado para pollo de engorde", "Saco de 100 lb", "", "Alimento animal", "Purina", "Saco", 245, 285, 10, "Sí", "30/11/2026", 25],
			["Semilla de maíz híbrido HB-83", "Bolsa de 25 lb", "", "Semillas", "ICTA", "Bolsa", 180, 225, 5, "Sí", "31/05/2027", 20]
		];
	}

	# ---------------------------------------------------------------- Lectura y validación

	/**
	 * Lee la hoja Productos (o la primera) y valida cada fila contra la base. No guarda nada.
	 * Devuelve ["filas" => [...], "resumen" => [...]] o false con el mensaje en getMensaje().
	 */
	public function validar($ruta)
	{
		$filas = $this->leer($ruta);

		if ($filas === false) {
			return false;
		}

		$this->cache = [];
		$productos = [];
		$nuevos = [
			"categoria" => [],
			"marca" => [],
			"unidad_medida" => []
		];

		foreach ($filas as &$fila) {
			$this->validarFila($fila);

			if (count($fila["errores"]) > 0) {
				continue;
			}

			# Producto: existente en la base, nuevo ya visto en el archivo o nuevo
			$llave = $fila["codigo_barra"] !== "" ? "b:" . mb_strtolower($fila["codigo_barra"]) : "n:" . $this->normalizar($fila["nombre"]);

			if (!array_key_exists($llave, $productos)) {
				$productos[$llave] = $this->buscarProducto($fila);
				$primera = true;
			} else {
				$primera = false;
			}

			$producto = $productos[$llave];

			if ($producto) {
				$fila["accion"] = "existente";
				$fila["producto_id"] = (int)$producto->id;
				$fila["cproducto"] = $producto->codigo;

				if ($this->normalizar($producto->nunidad) !== $this->normalizar($fila["unidad"]) &&
					mb_strtolower($producto->cunidad) !== mb_strtolower($fila["unidad"])) {
					$fila["errores"][] = "El producto ya existe ({$producto->codigo}) con la unidad {$producto->nunidad}.";
				}

				$fila["controla"] = (int)$producto->control_vence;
			} else {
				$fila["accion"] = "nuevo";

				if ($primera) {
					$productos[$llave] = false;
					$this->cache["_nuevo"][$llave] = $fila;

					# Catálogos del producto nuevo: existentes o por crear
					foreach (["categoria" => "categoria", "marca" => "marca", "unidad_medida" => "unidad"] as $tabla => $campo) {
						if (!$this->buscarCatalogo($tabla, $fila[$campo])) {
							$nuevos[$tabla][$this->normalizar($fila[$campo])] = $fila[$campo];
						}
					}
				} else {
					$base = $this->cache["_nuevo"][$llave];

					if ($this->normalizar($base["unidad"]) !== $this->normalizar($fila["unidad"])) {
						$fila["errores"][] = "La unidad no coincide con la fila {$base["fila"]} del mismo producto.";
					}

					if ($base["controla"] !== $fila["controla"]) {
						$fila["errores"][] = "\"Controla vencimiento\" no coincide con la fila {$base["fila"]} del mismo producto.";
					}
				}

				$fila["llave"] = $llave;
			}

			if ($fila["controla"] === 1 && $fila["vence"] === null) {
				$fila["errores"][] = "El producto controla vencimiento: indique la fecha.";
			} else if ($fila["controla"] === 0 && $fila["vence"] !== null) {
				$fila["errores"][] = "El producto no controla vencimiento: deje la fecha vacía.";
			}
		}
		unset($fila);

		$validas = array_filter($filas, function ($f) {
			return count($f["errores"]) === 0;
		});

		return [
			"filas" => $filas,
			"resumen" => [
				"filas" => count($filas),
				"validas" => count($validas),
				"errores" => count($filas) - count($validas),
				"productos_nuevos" => count(array_unique(array_column(array_filter($validas, function ($f) {
					return $f["accion"] === "nuevo";
				}), "llave"))),
				"productos_existentes" => count(array_unique(array_column(array_filter($validas, function ($f) {
					return $f["accion"] === "existente";
				}), "producto_id"))),
				"unidades" => round(array_sum(array_column($validas, "cantidad")), 2),
				"valor" => round(array_sum(array_map(function ($f) {
					return $f["cantidad"] * $f["costo"];
				}, $validas)), 2),
				"nuevos" => [
					"categorias" => array_values($nuevos["categoria"]),
					"marcas" => array_values($nuevos["marca"]),
					"unidades" => array_values($nuevos["unidad_medida"])
				]
			]
		];
	}

	/**
	 * Crea los catálogos y productos que falten y agrega cada fila al inventario (sumando si el
	 * lote ya está). Recibe el resultado de validar() sin errores; debe llamarse en una transacción.
	 */
	public function importar($encId, $validacion)
	{
		$creados = [];
		$det = new Inventario_det_model();

		foreach ($validacion["filas"] as $fila) {
			if ($fila["accion"] === "nuevo") {
				if (!isset($creados[$fila["llave"]])) {
					$categoria = $this->catalogoId("categoria", $fila["categoria"]);
					$marca = $this->catalogoId("marca", $fila["marca"]);
					$unidad = $this->catalogoId("unidad_medida", $fila["unidad"]);

					$producto = new Producto_model();
					$producto->guardar([
						"codigo" => $producto->siguienteCodigo(),
						"nombre" => $fila["nombre"],
						"descripcion" => $fila["descripcion"] === "" ? "" : "<p>" . htmlspecialchars($fila["descripcion"], ENT_QUOTES, "UTF-8") . "</p>",
						"tipo_producto" => "B",
						"codigo_barra" => $fila["codigo_barra"] === "" ? null : $fila["codigo_barra"],
						"precio" => $fila["precio"],
						"costo" => $fila["costo"],
						"control_vence" => $fila["controla"],
						"existencia_minima" => $fila["minimo"],
						"marca_id" => $marca,
						"unidad_medida_id" => $unidad,
						"categoria_id" => $categoria
					]);

					$creados[$fila["llave"]] = [
						"id" => $producto->getPK(),
						"unidad" => $unidad
					];
				}

				$productoId = $creados[$fila["llave"]]["id"];
				$unidadId = $creados[$fila["llave"]]["unidad"];
			} else {
				$productoId = $fila["producto_id"];
				$unidadId = (int)$this->db->select("unidad_medida_id")->where("id", $productoId)->get("producto")->row()->unidad_medida_id;
			}

			$det->agregar($encId, [
				"producto_id" => $productoId,
				"unidad_medida_id" => $unidadId,
				"fecha_vence" => $fila["vence"],
				"cantidad" => $fila["cantidad"],
				"costo" => $fila["costo"]
			]);
		}

		return count($creados);
	}

	# Filas con datos de la hoja: [fila, nombre, descripcion, ..., errores => []]
	private function leer($ruta)
	{
		try {
			$lector = IOFactory::createReaderForFile($ruta);
			$lector->setReadDataOnly(true);
			$libro = $lector->load($ruta);
		} catch (\Throwable $e) {
			$this->mensaje = "No se pudo leer el archivo; verifique que sea un Excel (.xlsx) válido.";
			return false;
		}

		$hoja = $libro->getSheetByName(self::HOJA) ?? $libro->getSheet(0);
		$datos = $hoja->toArray(null, true, false, false);

		if (count($datos) === 0) {
			$this->mensaje = "El archivo está vacío.";
			return false;
		}

		# Columnas por su encabezado (el orden no importa)
		$indices = [];
		$encabezados = [];

		foreach ($this->columnas as $campo => $col) {
			$encabezados[$this->normalizar($col[0])] = $campo;
		}

		foreach ($datos[0] as $i => $titulo) {
			$titulo = $this->normalizar(str_replace("*", "", (string)$titulo));

			if (isset($encabezados[$titulo])) {
				$indices[$encabezados[$titulo]] = $i;
			}
		}

		$faltan = [];

		foreach ($this->columnas as $campo => $col) {
			if ($col[2] && !isset($indices[$campo])) {
				$faltan[] = $col[0];
			}
		}

		if (count($faltan) > 0) {
			$this->mensaje = "Faltan columnas en la hoja " . $hoja->getTitle() . ": " . implode(", ", $faltan) . ". Use la plantilla.";
			return false;
		}

		$filas = [];

		foreach (array_slice($datos, 1, null, true) as $n => $valores) {
			$fila = ["fila" => $n + 1];
			$vacia = true;

			foreach ($this->columnas as $campo => $col) {
				$valor = isset($indices[$campo]) ? ($valores[$indices[$campo]] ?? null) : null;
				$fila[$campo] = is_string($valor) ? trim($valor) : $valor;

				if ($fila[$campo] !== null && $fila[$campo] !== "") {
					$vacia = false;
				}
			}

			if (!$vacia) {
				$filas[] = $fila;
			}
		}

		if (count($filas) === 0) {
			$this->mensaje = "La hoja " . $hoja->getTitle() . " no tiene productos.";
			return false;
		}

		if (count($filas) > self::MAX_FILAS) {
			$this->mensaje = "El archivo tiene " . count($filas) . " filas; el máximo es " . self::MAX_FILAS . ". Divídalo en varios archivos.";
			return false;
		}

		return $filas;
	}

	# Convierte los valores de la fila a su tipo y anota los errores de formato
	private function validarFila(&$fila)
	{
		$errores = [];

		foreach (["nombre", "descripcion", "categoria", "marca", "unidad"] as $campo) {
			$fila[$campo] = preg_replace("/\s+/u", " ", trim((string)$fila[$campo]));
		}

		foreach (["nombre" => 300, "categoria" => 150, "marca" => 100, "unidad" => 150] as $campo => $largo) {
			if ($fila[$campo] === "") {
				$errores[] = "Falta " . mb_strtolower($this->columnas[$campo][0]) . ".";
			} else if (mb_strlen($fila[$campo]) > $largo) {
				$errores[] = $this->columnas[$campo][0] . " admite hasta {$largo} caracteres.";
			}
		}

		# Un código de barras escrito como número llega como float: sin notación científica
		$barra = $fila["codigo_barra"];
		$fila["codigo_barra"] = is_float($barra) || is_int($barra) ? sprintf("%.0f", $barra) : trim((string)$barra);

		if (mb_strlen($fila["codigo_barra"]) > 100) {
			$errores[] = "El código de barras admite hasta 100 caracteres.";
		}

		$numeros = [
			"costo" => [true, true],
			"precio" => [true, false],
			"minimo" => [false, true],
			"cantidad" => [true, false]
		];

		foreach ($numeros as $campo => [$obligatorio, $cero]) {
			$valor = $this->numero($fila[$campo]);
			$nombre = mb_strtolower($this->columnas[$campo][0]);

			if ($valor === null) {
				if ($obligatorio && ($fila[$campo] === null || $fila[$campo] === "")) {
					$errores[] = "Falta {$nombre}.";
				} else if ($fila[$campo] !== null && $fila[$campo] !== "") {
					$errores[] = ucfirst($nombre) . " no es un número.";
				}

				$valor = 0;
			} else if ($valor < 0 || (!$cero && $valor == 0)) {
				$errores[] = ucfirst($nombre) . ($cero ? " no puede ser negativo." : " debe ser mayor a 0.");
			}

			$fila[$campo] = $campo === "costo" ? round($valor, 5) : round($valor, 2);
		}

		$controla = mb_strtolower(eliminarAcento(trim((string)$fila["controla"])));

		if (in_array($controla, ["si", "s", "1", "x", "true"], true)) {
			$fila["controla"] = 1;
		} else if (in_array($controla, ["", "no", "n", "0", "false"], true)) {
			$fila["controla"] = 0;
		} else {
			$errores[] = "\"Controla vencimiento\" debe ser Sí o No.";
			$fila["controla"] = 0;
		}

		$vence = $fila["vence"];
		$fila["vence"] = $this->fecha($vence);

		if ($vence !== null && $vence !== "" && $fila["vence"] === null) {
			$errores[] = "La fecha de vencimiento no es válida (dd/mm/aaaa).";
		}

		$fila["accion"] = null;
		$fila["errores"] = $errores;
	}

	# Número de una celda (acepta "Q 1,250.50"); null si está vacía o no es número
	private function numero($valor)
	{
		if (is_int($valor) || is_float($valor)) {
			return (float)$valor;
		}

		$valor = preg_replace("/[^\d.\-]/", "", str_replace(",", "", (string)$valor));

		return is_numeric($valor) ? (float)$valor : null;
	}

	# Fecha Y-m-d desde un número de Excel o un texto dd/mm/aaaa (o aaaa-mm-dd); null si no es válida
	private function fecha($valor)
	{
		if ($valor === null || $valor === "") {
			return null;
		}

		if (is_int($valor) || is_float($valor)) {
			return $valor > 0 ? Date::excelToDateTimeObject($valor)->format("Y-m-d") : null;
		}

		$valor = trim((string)$valor);

		if (preg_match("/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/", $valor, $m) && checkdate($m[2], $m[1], $m[3])) {
			return sprintf("%04d-%02d-%02d", $m[3], $m[2], $m[1]);
		}

		if (preg_match("/^(\d{4})-(\d{2})-(\d{2})/", $valor, $m) && checkdate($m[2], $m[3], $m[1])) {
			return "{$m[1]}-{$m[2]}-{$m[3]}";
		}

		return null;
	}

	# Producto activo de la empresa por código de barras o, si la fila no trae, por nombre
	private function buscarProducto($fila)
	{
		$fila["codigo_barra"] !== ""
			? $this->db->where("a.codigo_barra", $fila["codigo_barra"])
			: $this->db->where("a.nombre", $fila["nombre"]);

		return $this->db
		->select("
			a.id,
			a.codigo,
			a.control_vence,
			b.codigo as cunidad,
			b.nombre as nunidad")
		->from("producto a")
		->join("unidad_medida b", "b.id = a.unidad_medida_id")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.activo", 1)
		->order_by("a.id", "asc")
		->limit(1)
		->get()
		->row();
	}

	# Registro activo del catálogo con ese nombre (la unidad también por código); null si no existe
	private function buscarCatalogo($tabla, $nombre)
	{
		$llave = $this->normalizar($nombre);

		if (!array_key_exists($llave, $this->cache[$tabla] ?? [])) {
			$this->db
			->where("empresa_id", $this->_ses->empresa_id)
			->where("activo", 1)
			->group_start()
			->where("nombre", $nombre);

			if ($tabla === "unidad_medida") {
				$this->db->or_where("codigo", $nombre);
			}

			$this->cache[$tabla][$llave] = $this->db
			->group_end()
			->order_by("id", "asc")
			->limit(1)
			->get($tabla)
			->row();
		}

		return $this->cache[$tabla][$llave];
	}

	# Id del catálogo; si no existe lo crea (la unidad con el código sugerido o uno libre)
	private function catalogoId($tabla, $nombre)
	{
		$cat = $this->buscarCatalogo($tabla, $nombre);

		if ($cat) {
			return (int)$cat->id;
		}

		$datos = [
			"nombre" => $nombre,
			"activo" => 1,
			"empresa_id" => $this->_ses->empresa_id
		];

		if ($tabla === "unidad_medida") {
			$datos["codigo"] = $this->codigoUnidad($nombre);
		}

		$this->db->insert($tabla, $datos);

		$this->cache[$tabla][$this->normalizar($nombre)] = (object)array_merge($datos, [
			"id" => $this->db->insert_id()
		]);

		return (int)$this->db->insert_id();
	}

	private function codigoUnidad($nombre)
	{
		$base = null;

		foreach ($this->unidades as $sugerida => $codigo) {
			if ($this->normalizar($sugerida) === $this->normalizar($nombre)) {
				$base = $codigo;
			}
		}

		if ($base === null) {
			$base = strtoupper(substr(preg_replace("/[^a-z0-9]/", "", $this->normalizar($nombre)), 0, 3)) ?: "UM";
		}

		$codigo = $base;

		for ($i = 2; $this->db->where("empresa_id", $this->_ses->empresa_id)->where("codigo", $codigo)->count_all_results("unidad_medida") > 0; $i++) {
			$codigo = substr($base, 0, 8) . $i;
		}

		return $codigo;
	}

	# Minúsculas, sin tildes y con espacios simples, para comparar nombres
	private function normalizar($texto)
	{
		return preg_replace("/\s+/u", " ", trim(html_entity_decode(eliminarAcento((string)$texto), ENT_QUOTES, "UTF-8")));
	}

	public function getMensaje()
	{
		return $this->mensaje;
	}
}

/* End of file Inventario_importacion_model.php */
/* Location: ./application/models/inv/Inventario_importacion_model.php */
