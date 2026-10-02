<?php 
if (!function_exists('elemento'))
{
	function elemento($dato, $indice, $valor=false) 
	{	
		if (array_key_exists($indice, $dato) && 
			!empty($dato[$indice])) {
			
			return $dato[$indice];
		}

		return $valor;
	}
}

if (!function_exists('verPropiedad'))
{
	function verPropiedad($dato, $indice, $valor=false) 
	{
		if (property_exists($dato, $indice) && 
			!empty($dato->$indice)) {
			
			return $dato->$indice;
		}

		return $valor;
	}
}

if (!function_exists('verConsulta'))
{
	function verConsulta($datos, $args)
	{
		if ($datos->num_rows() > 0) {
			if (isset($args['uno'])) {
				return $datos->row();
			} else {
				return $datos->result();
			}
		}

		return [];
	}
}

if (!function_exists('var_session'))
{
	function var_session($data=[])
	{
		$sesion = [
			"id" => $data->id,
			"nombre"     => $data->nombre,
			"alias"      => $data->alias,
			"empresa_id" => $data->empresa_id
		];

		return $sesion;
	}
}

if (!function_exists('outputJson')) {
	function outputJson ($data=[])
	{
		header('Content-type: application/json');
		echo json_encode($data);	
	}
}

if (!function_exists('generarCodigo')) {
	function generarCodigo($longitud=10) 
	{
		$cadena ='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ#$@';
		$cadenaAncho = strlen($cadena);

		$codigo = '';
		for ($i=0; $i < $longitud ; $i++) { 
			$codigo .= $cadena[rand(0, $cadenaAncho - 1)];
		}
		return $codigo;

	}
}

if (!function_exists('get_tiempo_token')) {
	function get_tiempo_token()
	{
		return time()+7200;
	}
}

if (!function_exists('getRealIP')) {
	function getRealIP() {
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
			return $_SERVER['HTTP_CLIENT_IP'];
		   
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
	   
		return $_SERVER['REMOTE_ADDR'];
	}
}

if (!function_exists('eliminaAcento'))
{
	function eliminarAcento($text)
	{
		$text = htmlentities($text, ENT_QUOTES, 'UTF-8');
		$text = strtolower($text);
		$patron = array (
			// Espacios, puntos y comas por guion
			//'/[\., ]+/' => ' ',
 
			// Vocales
			'/\+/' => '',
			'/&agrave;/' => 'a',
			'/&egrave;/' => 'e',
			'/&igrave;/' => 'i',
			'/&ograve;/' => 'o',
			'/&ugrave;/' => 'u',
 
			'/&aacute;/' => 'a',
			'/&eacute;/' => 'e',
			'/&iacute;/' => 'i',
			'/&oacute;/' => 'o',
			'/&uacute;/' => 'u',
 
			'/&acirc;/' => 'a',
			'/&ecirc;/' => 'e',
			'/&icirc;/' => 'i',
			'/&ocirc;/' => 'o',
			'/&ucirc;/' => 'u',
 
			'/&atilde;/' => 'a',
			'/&etilde;/' => 'e',
			'/&itilde;/' => 'i',
			'/&otilde;/' => 'o',
			'/&utilde;/' => 'u',
 
			'/&auml;/' => 'a',
			'/&euml;/' => 'e',
			'/&iuml;/' => 'i',
			'/&ouml;/' => 'o',
			'/&uuml;/' => 'u',
 
			'/&auml;/' => 'a',
			'/&euml;/' => 'e',
			'/&iuml;/' => 'i',
			'/&ouml;/' => 'o',
			'/&uuml;/' => 'u',
 
			// Otras letras y caracteres especiales
			'/&aring;/' => 'a',
			'/&ntilde;/' => 'n',
 
			// Agregar aqui mas caracteres si es necesario
 
		);
 
		$text = preg_replace(array_keys($patron),array_values($patron),$text);
		return $text;
	}
}

if (!function_exists('script_tag')) {
	function script_tag($src, $print=false)
	{
		if ($print) {
			$link = "<script type='text/javascript'>\n" . file_get_contents(base_url($src)) . "\n</script>\n";
		} else {
			$CI =& get_instance();
			$link = '<script type="text/javascript" ';
			if (preg_match('#^([a-z]+:)?//#i', $src)) {
				$link .= 'src="'.$src.'" ';
			}
			else {
				$link .= 'src="'.$CI->config->slash_item('base_url').$src.'" ';
			}
			
			$link .= "></script>\n";
		}
		return $link;
	}
}

if (!function_exists('censurar_mail')) {
	function censurar_mail($mail='')
	{
		if ($mail) {
			$em   = explode("@",$mail);
			$name = implode('@', array_slice($em, 0, count($em)-1));
			$len  = floor(strlen($name)/2);

			return substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);   
		}

		return false;
	}
}

if (!function_exists('sumar_tiempo')) {
	function sumar_tiempo($tiempo) {		
		$ahora = date('Y-m-d H:i:s');
		$tiempo = explode(':',  $tiempo);
		$nuevaFecha = strtotime("+"."{$tiempo[0]}"."hour"."{$tiempo[1]}"."minute" ,strtotime ($ahora)); 

		return date('Y-m-d H:i:s', $nuevaFecha);
	}
}

if (!function_exists('array_field')) {
	function array_field($data, $field)
	{
		$values = [];

		if ($data) {
			foreach ($data as $row) {
				$values[] = $row->$field;
			}
		}

		return $values;
	}
}

if (!function_exists('verLetra')) {
	function verLetra($num) {

		$numero = $num % 26;
		$letra  = chr(65 + $numero);
		$num2   = intval($num / 26);

		if ($num2 > 0) {
			return verLetra($num2 - 1) . $letra;
		} else {
			return $letra;
		}
	}
}

if (!function_exists('Hoy')) {
	function Hoy($hora = false)
	{
		if ($hora === true) {
			return date('Y-m-d H:i:s');
		}

		return date('Y-m-d');
	}
}

if (!function_exists("getTipoProducto")) {
	function getTiposProductos() {
		return ["B", "S"];
	}
}


/**
 * HTML de los editores de texto (Tiptap): deja solo el formato básico y quita
 * atributos, scripts y estilos. En los enlaces conserva el href si es http(s) o mailto,
 * y en los párrafos la alineación (text-align).
 */
if (!function_exists("limpiarHtml")) {
	function limpiarHtml($html)
	{
		$html = trim((string)$html);

		if ($html === "") {
			return "";
		}

		$permitidas = [
			"p",
			"br",
			"strong",
			"em",
			"u",
			"s",
			"ul",
			"ol",
			"li",
			"a"
		];

		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		$raiz = $doc->getElementsByTagName("div")->item(0);
		$limpiar = function ($nodo) use (&$limpiar, $permitidas) {
			foreach (iterator_to_array($nodo->childNodes) as $hijo) {
				if ($hijo->nodeType === XML_TEXT_NODE) {
					continue;
				}

				if ($hijo->nodeType !== XML_ELEMENT_NODE) {
					$nodo->removeChild($hijo);
					continue;
				}

				$etiqueta = strtolower($hijo->nodeName);

				# script y style se quitan con su contenido; las demás no permitidas dejan su texto
				if (in_array($etiqueta, ["script", "style"])) {
					$nodo->removeChild($hijo);
					continue;
				}

				$limpiar($hijo);

				if (!in_array($etiqueta, $permitidas)) {
					while ($hijo->firstChild) {
						$nodo->insertBefore($hijo->firstChild, $hijo);
					}

					$nodo->removeChild($hijo);
					continue;
				}

				$href = $etiqueta === "a" ? trim($hijo->getAttribute("href")) : "";

				# Alineación del editor (style="text-align: center"): solo en párrafos y con valores conocidos
				$alinear = $etiqueta === "p" && preg_match('/text-align:\s*(center|right|justify)\b/i', $hijo->getAttribute("style"), $m) ? strtolower($m[1]) : "";

				foreach (iterator_to_array($hijo->attributes) as $atributo) {
					$hijo->removeAttribute($atributo->nodeName);
				}

				if ($alinear !== "") {
					$hijo->setAttribute("style", "text-align: {$alinear}");
				}

				if ($etiqueta === "a" && preg_match('/^(https?:\/\/|mailto:)/i', $href)) {
					$hijo->setAttribute("href", $href);
					$hijo->setAttribute("target", "_blank");
					$hijo->setAttribute("rel", "noopener noreferrer nofollow");
				}
			}
		};

		$limpiar($raiz);

		$salida = "";

		foreach ($raiz->childNodes as $hijo) {
			$salida .= $doc->saveHTML($hijo);
		}

		# Sin texto (ej. "<p></p>") se guarda vacío
		return trim(textoPlano($salida)) === "" ? "" : trim($salida);
	}
}

/**
 * Texto sin etiquetas de un HTML (para documentos, impresiones y columnas cortas).
 */
if (!function_exists("textoPlano")) {
	function textoPlano($html)
	{
		$texto = preg_replace('/<(br|\/p|\/li)\s*\/?>/i', "$0 ", (string)$html);
		$texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, "UTF-8");

		return trim(preg_replace('/\s+/u', " ", $texto));
	}
}

/**
 * Texto de un editor para imprimir en un documento (PDF): el HTML se vuelve a limpiar
 * y el texto plano (guardado antes del editor) se escapa conservando los saltos de línea.
 */
if (!function_exists("htmlDocumento")) {
	function htmlDocumento($valor)
	{
		$valor = (string)$valor;

		if (preg_match('/^\s*</', $valor)) {
			return limpiarHtml($valor);
		}

		return nl2br(htmlspecialchars($valor, ENT_QUOTES, "UTF-8"));
	}
}

if (!function_exists('decimalesMonto'))
{
	# Decimales de montos de los parámetros de la empresa de la sesión (sin definir, 2)
	function decimalesMonto()
	{
		static $decimales = null;

		if ($decimales === null) {
			$ci =& get_instance();
			$ses = get_var_sesion();

			$tmp = $ses ? $ci->db
			->select("decimal_monto")
			->where("empresa_id", $ses->empresa_id)
			->where("activo", 1)
			->get("empresa_parametro")
			->row() : null;

			$decimales = ($tmp && $tmp->decimal_monto !== null) ? (int)$tmp->decimal_monto : 2;
		}

		return $decimales;
	}
}

if (!function_exists('monto'))
{
	# Monto con separador de miles y los decimales de los parámetros: 1234.5 → "1,234.50"
	function monto($valor)
	{
		return number_format((float)$valor, decimalesMonto());
	}
}

if (!function_exists('formatoExcelMonto'))
{
	# Formato de celda de Excel para montos con los decimales de los parámetros, ej. "Q" #,##0.00
	function formatoExcelMonto($simbolo="")
	{
		$decimales = decimalesMonto();
		$formato = "#,##0" . ($decimales > 0 ? "." . str_repeat("0", $decimales) : "");

		return $simbolo ? "\"{$simbolo}\" {$formato}" : $formato;
	}
}
