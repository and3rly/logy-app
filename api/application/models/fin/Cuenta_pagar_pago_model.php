<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Pagos a una cuenta por pagar
class Cuenta_pagar_pago_model extends Centro_model {

	# Prefijo del comprobante de egreso (no hay abreviatura en empresa_parametro)
	const PREFIJO = "EGR";

	public $cuenta_pagar_id;
	public $forma_pago_id;
	public $comprobante_numero = null;
	public $documento_fecha = null;
	public $documento_numero = null;
	public $documento_comprobante = null;
	public $total = 0;
	public $usuario_id;
	public $anulado = 0;
	public $anulado_fecha = null;
	public $anulado_motivo = null;
	public $anulado_usuario = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Pagos (vigentes y anulados) con forma de pago y usuario
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "cuenta_pagar_id")) {
			$this->db->where("a.cuenta_pagar_id", $args["cuenta_pagar_id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.nombre as nforma_pago,
			c.nombre as nusuario")
		->from("cuenta_pagar_pago a")
		->join("forma_pago b", "b.id = a.forma_pago_id")
		->join("usuario c", "c.id = a.usuario_id")
		->order_by("a.fecha", "asc")
		->order_by("a.id", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	/**
	 * Siguiente número de comprobante de egreso de la empresa, ej. EGR-000002.
	 * Debe llamarse dentro de una transacción: bloquea los parámetros para que dos pagos
	 * simultáneos no tomen el mismo número.
	 */
	public function siguienteComprobante()
	{
		$this->db->query("
			select id
			from empresa_parametro
			where empresa_id = ?
			and activo = 1
			for update", [
				$this->_ses->empresa_id
			]);

		$tmp = $this->db
		->select("count(*) + 1 as siguiente", false)
		->from("cuenta_pagar_pago a")
		->join("cuenta_pagar b", "b.id = a.cuenta_pagar_id")
		->where("b.empresa_id", $this->_ses->empresa_id)
		->get()
		->row();

		return self::PREFIJO . "-" . str_pad($tmp->siguiente, 6, "0", STR_PAD_LEFT);
	}
}

/* End of file Cuenta_pagar_pago_model.php */
/* Location: ./application/models/fin/Cuenta_pagar_pago_model.php */
