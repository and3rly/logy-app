<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Moneda_model extends Centro_model {

	public $codigo = null;
	public $simbolo = null;
	public $nombre;
	public $activo = 1;
	public $empresa_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function existe($args=[])
	{	
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("nombre", $args->nombre)
		->where("simbolo", $args->simbolo)
		->where("activo", 1)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}
}

/* End of file Moneda_model.php */
/* Location: ./application/models/Moneda_model.php */