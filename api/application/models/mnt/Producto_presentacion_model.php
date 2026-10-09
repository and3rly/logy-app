<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Presentación de un producto: cuántas unidades de medida contiene (factor), ej. Caja 12 = 12 UNIDAD.
# Con unidad_medida_id es otra unidad con equivalencia (Libra en un producto en Quintal, factor 0.01)
class Producto_presentacion_model extends Centro_model {

	public $nombre;
	public $factor;
	public $unidad_medida_id = null;
	public $activo = 1;
	public $producto_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otra presentación activa del mismo producto con el mismo nombre
	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("producto_id", verPropiedad($args, "producto_id"))
		->where("nombre", trim(verPropiedad($args, "nombre", "")))
		->where("activo", 1)
		->get($this->_tabla);

		return $tmp->num_rows() > 0;
	}

	# Ya tiene existencia o documentos: cambiar el factor descuadraría lo registrado
	public function enUso()
	{
		foreach (["stock", "compra_detalle", "inventario_ajuste_detalle", "inventario_det"] as $tabla) {
			$tmp = $this->db
			->where("producto_presentacion_id", $this->getPK())
			->count_all_results($tabla);

			if ($tmp > 0) {
				return true;
			}
		}

		return false;
	}
}

/* End of file Producto_presentacion_model.php */
/* Location: ./application/models/mnt/Producto_presentacion_model.php */
