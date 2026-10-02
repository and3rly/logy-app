<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu_model extends Centro_model {
	public $modulo_id;
	public $nombre;
	public $orden = 0;
	public $icono;
	public $url;
	public $activo = 1;

	public function __construct($id = null)
	{
		parent::__construct();

		if ($id !== null) {
			$this->cargar($id);
		}
	}

	# Otra opción activa con la misma url (dos opciones no deben llevar a la misma pantalla)
	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("url", $args->url)
		->where("activo", 1)
		->get($this->_tabla);

		return $tmp->num_rows() > 0;
	}

	# Rutas de todos los módulos y opciones activos: las que se controlan con los accesos del rol
	public function getRutas()
	{
		$rutas = array_merge(
			array_field($this->db->select("url")->where("activo", 1)->get("modulo")->result(), "url"),
			array_field($this->db->select("url")->where("activo", 1)->get("menu")->result(), "url")
		);

		return array_values(array_unique(array_filter($rutas, function ($url) {
			return $url !== null && trim($url) !== "" && trim($url) !== "/";
		})));
	}

	# Sin $rol_id (o con un rol administrador) devuelve todo; si no, solo lo que está en rol_acceso
	public function getMenuCompleto($rol_id = null)
	{
		$accesos = null;

		if ($rol_id !== null) {
			$rol = $this->db
			->select("administrador")
			->where("id", $rol_id)
			->where("activo", 1)
			->get("rol")
			->row();

			if (!$rol || (int)$rol->administrador !== 1) {
				$accesos = $this->db
				->select("modulo_id, menu_id")
				->where("rol_id", $rol_id)
				->get("rol_acceso")
				->result();
			}
		}

		$modulos = $this->db
		->select("id, nombre, icono, url, detalle")
		->where("activo", 1)
		->order_by("orden", "asc")
		->order_by("id", "asc")
		->get("modulo")
		->result();

		$opciones = $this->db
		->select("id, modulo_id, nombre, icono, url")
		->where("activo", 1)
		->order_by("orden", "asc")
		->order_by("id", "asc")
		->get("menu")
		->result();

		if ($accesos !== null) {
			$directos = [];
			$permitidas = [];

			foreach ($accesos as $acceso) {
				if ($acceso->menu_id === null) {
					$directos[] = (int)$acceso->modulo_id;
				} else {
					$permitidas[] = (int)$acceso->menu_id;
				}
			}

			$opciones = array_values(array_filter($opciones, function ($opcion) use ($permitidas) {
				return in_array((int)$opcion->id, $permitidas);
			}));

			$modulos = array_values(array_filter($modulos, function ($modulo) use ($directos, $opciones) {
				if ((int)$modulo->detalle === 0) {
					return in_array((int)$modulo->id, $directos);
				}

				foreach ($opciones as $opcion) {
					if ((int)$opcion->modulo_id === (int)$modulo->id) {
						return true;
					}
				}

				return false;
			}));
		}

		return array_map(function ($modulo) use ($opciones) {
			$propias = array_filter($opciones, function ($opcion) use ($modulo) {
				return (int)$opcion->modulo_id === (int)$modulo->id;
			});

			return [
				"id"      => (int)$modulo->id,
				"nombre"  => $modulo->nombre,
				"icono"   => $modulo->icono,
				"url"     => $modulo->url,
				"detalle" => (int)$modulo->detalle === 1,
				"menu"    => array_values(array_map(function ($opcion) {
					return [
						"id" => (int)$opcion->id,
						"nombre" => $opcion->nombre,
						"icono" => $opcion->icono,
						"url" => $opcion->url
					];
				}, $propias))
			];
		}, $modulos);
	}
}

/* End of file Menu_model.php */
/* Location: ./application/models/Menu_model.php */
