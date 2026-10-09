<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Empresa_parametro_model extends Centro_model {

	# Formato de impresión de la venta (formato_impresion)
	const IMPRESION_TICKET = 1;
	const IMPRESION_CARTA = 2;

	public $empresa_id;
	public $moneda_id = null;
	public $abr_recepcion = null;
	public $abr_producto = null;
	public $abr_cotizacion = null;
	public $abr_compra = null;
	public $abr_venta = null;
	public $decimal_cantidad = null;
	public $decimal_monto = null;
	public $formato_impresion = self::IMPRESION_TICKET;
	public $activo = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function esDeLaEmpresa()
	{
		return (int)$this->empresa_id === (int)$this->_ses->empresa_id;
	}
}

/* End of file Empresa_parametro_model.php */
/* Location: ./application/models/mnt/Empresa_parametro_model.php */
