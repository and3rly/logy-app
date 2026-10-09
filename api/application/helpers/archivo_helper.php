<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('subirArchivo'))
{
	function subirArchivo($datos=[])
	{
		$data = ['exito' => 0];

		if (elemento($datos, 'tmp_name') &&
			elemento($datos, 'type') &&
			elemento($datos, 'name')) {

			# Sin carpeta por empresa: va directo a la carpeta raíz (la librería usa "varios" si no se indica)
			$carpeta = "";

			$archivo = new Drive();

			if (isset($datos['carpeta'])) {
				$carpeta = $datos['carpeta'];
			}

			$archivo->set_subcarpeta($carpeta);

			$fileId = $archivo->subirArchivo([
				'name' => $datos['name'],
				'type' => $datos['type'],
				'tmp_name' => file_get_contents($datos['tmp_name'])
			]);

		    $documento = $archivo->getArchivo($fileId);

			$data['exito']  = 1;
			$data['key']    = $documento->getId();
			$data['nombre'] = $documento->getName(); 
			$data['tipo']   = $documento->getMimeType(); 
			$data['link']   = "https://drive.google.com/open?id={$documento->id}";
		} else {

			$data['mensaje'] = 'Hacen falta datos obligatorios.';
		}

		return (object) $data;
	}
}
if (!function_exists('getTipoArchivo')){
	function getTipoArchivo($type) {
		$pt = explode('/', $type);
		$ret = 1;
		if ($pt) {
			switch ($pt[0]) {
				case 'image':
					$ret = 2;
					break;
				case 'video':
					$ret = 3;
					break;
				case 'audio':
					$ret = 4;
					break;
				
				default:
					$ret = 1;
					break;
			}
		}

		return $ret;
	}
}

if (!function_exists('get_imagen_drive'))
{
	function get_imagen_drive($key_drive)
	{
		$gd = new Drive();
		$archivo = $gd->descargarArchivo(['fileId' => $key_drive]);

		return (object) [
			'tipo' => $archivo['mimeType'],
			'imagen' => base64_encode($archivo['contents'])
		];
	}
}

if (!function_exists('get_url_drive'))
{
	function get_url_drive($drive_id)
	{
		return "https://drive.google.com/open?id={$drive_id}";
	}
}

/* End of file archivo_helper.php */
/* Location: ./application/helpers/archivo_helper.php */
