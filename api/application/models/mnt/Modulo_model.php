<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Grupos del menú lateral (tabla modulo); sus opciones están en la tabla menu (Menu_model)
class Modulo_model extends Centro_model {

	public $nombre;
	public $icono = null;
	public $url = null;
	public $orden = 0;
	public $detalle = 1;
	public $activo = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otro módulo activo con el mismo nombre
	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("nombre", trim($args->nombre))
		->where("activo", 1)
		->get($this->_tabla);

		return $tmp->num_rows() > 0;
	}

	# Módulos (activos e inactivos) con sus opciones en "menu", en el orden del menú lateral
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("id", $args["id"]);
		}

		$modulos = $this->db
		->order_by("orden", "asc")
		->order_by("id", "asc")
		->get($this->_tabla)
		->result();

		$ids = array_field($modulos, "id");
		$opciones = count($ids) ? $this->db
			->where_in("modulo_id", $ids)
			->order_by("orden", "asc")
			->order_by("id", "asc")
			->get("menu")
			->result() : [];

		foreach ($modulos as $modulo) {
			$modulo->menu = array_values(array_filter($opciones, function ($opcion) use ($modulo) {
				return (int)$opcion->modulo_id === (int)$modulo->id;
			}));
		}

		return isset($args["_uno"]) ? ($modulos[0] ?? null) : $modulos;
	}

	public function getInfo()
	{
		return $this->_buscar([
			"id" => $this->getPK(),
			"_uno" => true
		]);
	}
}

/* End of file Modulo_model.php */
/* Location: ./application/models/mnt/Modulo_model.php */
