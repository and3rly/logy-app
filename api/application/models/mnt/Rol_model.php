<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rol_model extends Centro_model {

	public $nombre;
	public $administrador = 0;
	public $activo = 1;
	public $empresa_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otro rol activo de la empresa con el mismo nombre
	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->where("nombre", trim(verPropiedad($args, "nombre", "")))
		->where("activo", 1)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}

	public function esDeLaEmpresa()
	{
		return (int)$this->empresa_id === (int)$this->_ses->empresa_id;
	}

	# Accesos del rol en rol_acceso: menu_id es la opción; null es el módulo de enlace directo
	public function getAccesos()
	{
		return array_map(function ($fila) {
			return [
				"modulo_id" => (int)$fila->modulo_id,
				"menu_id" => $fila->menu_id === null ? null : (int)$fila->menu_id
			];
		}, $this->db
			->select("modulo_id, menu_id")
			->where("rol_id", $this->getPK())
			->get("rol_acceso")
			->result());
	}

	# Deja en rol_acceso solo $accesos ([{modulo_id, menu_id}]); se ignoran los que no existen en el menú
	public function setAccesos($accesos)
	{
		$modulos = [];
		$opciones = [];

		foreach ($this->db->select("id, detalle")->get("modulo")->result() as $fila) {
			$modulos[(int)$fila->id] = (int)$fila->detalle;
		}

		foreach ($this->db->select("id, modulo_id")->get("menu")->result() as $fila) {
			$opciones[(int)$fila->id] = (int)$fila->modulo_id;
		}

		$filas = [];

		foreach ($accesos as $acceso) {
			$modulo = (int)verPropiedad($acceso, "modulo_id", 0);
			$menu = verPropiedad($acceso, "menu_id") ? (int)$acceso->menu_id : null;

			$valido = $menu === null
				? isset($modulos[$modulo]) && $modulos[$modulo] === 0
				: isset($opciones[$menu]) && $opciones[$menu] === $modulo;

			if ($valido) {
				$filas["{$modulo}-{$menu}"] = [
					"rol_id" => $this->getPK(),
					"modulo_id" => $modulo,
					"menu_id" => $menu
				];
			}
		}

		$this->db->trans_start();
		$this->db->where("rol_id", $this->getPK())->delete("rol_acceso");

		if (count($filas) > 0) {
			$this->db->insert_batch("rol_acceso", array_values($filas));
		}

		$this->db->trans_complete();

		return $this->db->trans_status();
	}
}

/* End of file Rol_model.php */
/* Location: ./application/models/mnt/Rol_model.php */
