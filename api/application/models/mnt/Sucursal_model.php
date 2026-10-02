<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sucursal_model extends Centro_model {

	public $nombre;
	public $direccion = null;
	public $telefono = null;
	public $correo = null;
	public $activo = 1;
	public $municipio_id;
	public $empresa_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otra sucursal activa de la empresa con el mismo nombre
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
}

/* End of file Sucursal_model.php */
/* Location: ./application/models/mnt/Sucursal_model.php */
