<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Producto_model extends Centro_model {

	public $codigo;
	public $nombre;
	public $descripcion = "";
	public $tipo_producto = "B";
	public $codigo_barra = null;
	public $precio = null;
	public $costo = null;
	public $foto = null;
	public $control_vence = 0;
	public $existencia_minima = 0;
	public $activo = 1;
	public $marca_id;
	public $unidad_medida_id;
	public $categoria_id;
	public $empresa_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otro producto activo de la empresa con el mismo código o código de barras
	public function existe($args=[])
	{
		$codigo = trim(verPropiedad($args, "codigo", ""));
		$barra = trim(verPropiedad($args, "codigo_barra", ""));

		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->where("activo", 1)
		->group_start()
		->where("codigo", $codigo);

		if ($barra !== "") {
			$this->db->or_where("codigo_barra", $barra);
		}

		$tmp = $this->db
		->group_end()
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}

	# Tiene existencia en alguna sucursal (en la unidad o en una presentación): la unidad de medida ya no cambia
	public function tieneExistencia()
	{
		$tmp = $this->db
		->where("producto_id", $this->getPK())
		->where("activo", 1)
		->where("cantidad <>", 0)
		->count_all_results("stock");

		return $tmp > 0;
	}

	/**
	 * Siguiente código de la empresa con la abreviatura de los parámetros, ej. PRD-000001.
	 * Toma el mayor número ya usado con esa abreviatura, así no choca con códigos anteriores.
	 * Debe llamarse dentro de una transacción: bloquea los parámetros para que dos productos
	 * guardados al mismo tiempo no tomen el mismo código.
	 */
	public function siguienteCodigo()
	{
		$param = $this->db->query("
			select abr_producto
			from empresa_parametro
			where empresa_id = ?
			and activo = 1
			for update", [
				$this->_ses->empresa_id
			])
		->row();

		$abr = ($param && $param->abr_producto) ? $param->abr_producto : "PRD";
		$largo = strlen($abr) + 2;

		$tmp = $this->db->query("
			select ifnull(max(cast(substring(codigo, ?) as unsigned)), 0) + 1 as siguiente
			from {$this->_tabla}
			where empresa_id = ?
			and left(codigo, ?) = ?
			and substring(codigo, ?) regexp '^[0-9]+$'", [
				$largo,
				$this->_ses->empresa_id,
				$largo - 1,
				$abr . "-",
				$largo
			])
		->row();

		return $abr . "-" . str_pad($tmp->siguiente, 6, "0", STR_PAD_LEFT);
	}
}

/* End of file Producto_model.php */
/* Location: ./application/models/mnt/Producto_model.php */
