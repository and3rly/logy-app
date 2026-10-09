<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cliente_model extends Centro_model {

	const CODIGO_CF = "CF";

	public $nombre;
	public $razon_social = null;
	public $identificacion = null;
	public $codigo = null;
	public $direccion = null;
	public $telefono = null;
	public $correo = null;
	public $activo = 1;
	public $credito = 0;
	public $credito_limite = null;
	public $credito_dias = 0;
	public $lista_precio_id = null;
	public $municipio_id = null;
	public $empresa_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otro cliente activo de la empresa con la misma identificación (salvo CF) o el mismo código
	public function existe($args=[])
	{
		$identificacion = strtoupper(trim(verPropiedad($args, "identificacion", "")));
		$codigo = trim(verPropiedad($args, "codigo", ""));

		if (($identificacion === "" || $identificacion === "CF") && $codigo === "") {
			return false;
		}

		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->where("activo", 1)
		->group_start();

		if ($identificacion !== "" && $identificacion !== "CF") {
			$this->db->where("identificacion", $identificacion);
		}

		if ($codigo !== "") {
			$this->db->or_where("codigo", $codigo);
		}

		$tmp = $this->db
		->group_end()
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}

	# Cliente genérico de la empresa para las ventas sin cliente (código CF); si no existe lo crea
	public function consumidorFinal()
	{
		$cf = $this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->where("codigo", self::CODIGO_CF)
		->order_by("id", "asc")
		->get("cliente")
		->row();

		if ($cf) {
			return $cf;
		}

		$cliente = new Cliente_model();
		$cliente->guardar([
			"nombre" => "Consumidor final",
			"identificacion" => "CF",
			"codigo" => self::CODIGO_CF
		]);

		return $cliente->getPK() ? $this->db
			->where("id", $cliente->getPK())
			->get("cliente")
			->row() : null;
	}
}

/* End of file Cliente_model.php */
/* Location: ./application/models/mnt/Cliente_model.php */
