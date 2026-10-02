<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token {
	protected $_llave;
	protected $_algoritmo = "HS256";
	protected $_duracion = 43200; #12 hrs
	const LARGO_MINIMO_LLAVE = 32;

	public function __construct()
	{
		$this->_llave = get_instance()->config->item('encryption_key');

		if (empty($this->_llave)) {
			show_error("Falta configurar encryption_key para firmar los tokens.");
		}

		if (strlen($this->_llave) < self::LARGO_MINIMO_LLAVE) {
			show_error("encryption_key debe tener al menos " . self::LARGO_MINIMO_LLAVE . " caracteres para firmar los tokens.");
		}
	}

	public function generar($datos = [])
	{
		$ahora = time();

		$datos = array_merge((array)$datos, [
			"iat" => $ahora,
			"exp" => $ahora + $this->_duracion
		]);

		return JWT::encode($datos, $this->_llave, $this->_algoritmo);
	}

	public function validar($token)
	{
		try {
			return JWT::decode((string)$token, new Key($this->_llave, $this->_algoritmo));
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function desdeCabecera()
	{
		$cabecera = get_instance()->input->get_request_header("Authorization", TRUE);

		if ($cabecera && preg_match('/Bearer\s+(\S+)/i', $cabecera, $tmp)) {
			return $this->validar($tmp[1]);
		}

		return null;
	}
}

/* End of file Token.php */
/* Location: ./application/libraries/Token.php */
