<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Guarda los datos del token de la petición actual (los asigna el hook Inicio).
 * Quedan en get_instance()->_ses: los modelos los leen con $this->_ses,
 * porque CI_Model reenvía a la instancia de CodeIgniter las propiedades que no tiene.
 */
if (!function_exists("set_var_sesion")) {
	function set_var_sesion($datos)
	{
		get_instance()->_ses = $datos;
	}
}

/**
 * Datos de la sesión actual (id, empresa_id, sucursal_id, rol_id) o null.
 */
if (!function_exists("get_var_sesion")) {
	function get_var_sesion()
	{
		$ci =& get_instance();

		return isset($ci->_ses) ? $ci->_ses : null;
	}
}

/**
 * El rol de la sesión es administrador (ve los documentos de todos los usuarios).
 * Se consulta en la BD (no va en el token) para que un cambio de rol aplique de inmediato;
 * queda guardado en la sesión de la petición.
 */
if (!function_exists("es_administrador")) {
	function es_administrador()
	{
		$ci =& get_instance();

		if (!isset($ci->_ses) || !$ci->_ses) {
			return false;
		}

		if (!isset($ci->_ses->administrador)) {
			# query() y no el query builder: puede llamarse con una consulta a medio armar
			$rol = $ci->db
			->query("select administrador from rol where id = ?", [(int)$ci->_ses->rol_id])
			->row();

			$ci->_ses->administrador = $rol && (int)$rol->administrador === 1;
		}

		return $ci->_ses->administrador;
	}
}

/**
 * Los documentos de la sesión: un usuario que no es administrador solo ve los que él registró.
 * $columna es la columna usuario_id de la consulta (ej. "a.usuario_id").
 */
if (!function_exists("filtrar_por_usuario")) {
	function filtrar_por_usuario($columna="a.usuario_id")
	{
		$ci =& get_instance();

		if (!es_administrador()) {
			$ci->db->where($columna, $ci->_ses->id);
		}
	}
}

/**
 * El documento lo puede ver el usuario de la sesión (es suyo o la sesión es de un administrador).
 */
if (!function_exists("puede_ver_documento")) {
	function puede_ver_documento($usuarioId)
	{
		return es_administrador() || (int)$usuarioId === (int)get_instance()->_ses->id;
	}
}

/**
 * Escribe una respuesta JSON (para usar antes de que exista la salida del controlador).
 */
if (!function_exists("outputJson")) {
	function outputJson($datos)
	{
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode($datos);
	}
}

/* End of file sesion_helper.php */
/* Location: ./application/helpers/sesion_helper.php */
