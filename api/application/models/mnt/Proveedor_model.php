<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Proveedor_model extends Centro_model {

	public $nombre;
	public $identificacion = null;
	public $direccion = null;
	public $telefono = null;
	public $correo = null;
	public $credito = 0;
	public $credito_limite = 0;
	public $credito_dias = 0;
	public $activo = 1;
	public $empresa_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otro proveedor activo de la empresa con la misma identificación (salvo CF)
	public function existe($args=[])
	{
		$identificacion = strtoupper(trim(verPropiedad($args, "identificacion", "")));

		if ($identificacion === "" || $identificacion === "CF") {
			return false;
		}

		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->where("identificacion", $identificacion)
		->where("activo", 1)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}
}

/* End of file Proveedor_model.php */
/* Location: ./application/models/mnt/Proveedor_model.php */
