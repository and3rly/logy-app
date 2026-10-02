<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cuenta_pagar_model extends Centro_model {

	public $proveedor_id;
	public $factura_fecha;
	public $factura_numero;
	public $factura_documento = null;
	public $credito_dias = 0;
	public $fecha_vence;
	public $total = 0;
	public $abono = 0;
	public $saldo = 0;
	public $moneda_id;
	public $empresa_id;
	public $usuario_id;
	public $compra_id = null;
	public $referencia = null;
	public $anulado = 0;
	public $anulado_fecha = null;
	public $anulado_motivo = null;
	public $anulado_usuario = null;
	public $origen = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	/**
	 * Cuentas de las compras de la sucursal de la sesión (la sucursal sale de la compra).
	 * Filtro estado: pendientes (por defecto), vencidas, pagadas, anuladas o todas.
	 * "dias" = días para el vencimiento (negativo si ya venció).
	 */
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		} else {
			switch (elemento($args, "estado", "pendientes")) {
				case "vencidas":
					$this->db
					->where("a.anulado", 0)
					->where("a.saldo >", 0)
					->where("a.fecha_vence <", Hoy());
					break;
				case "pagadas":
					$this->db
					->where("a.anulado", 0)
					->where("a.saldo", 0);
					break;
				case "anuladas":
					$this->db->where("a.anulado", 1);
					break;
				case "todas":
					break;
				default:
					$this->db
					->where("a.anulado", 0)
					->where("a.saldo >", 0);
			}
		}

		$tmp = $this->db
		->select("
			a.*,
			datediff(a.fecha_vence, curdate()) as dias,
			b.nombre as nproveedor,
			b.identificacion as nit_proveedor,
			c.codigo as cmoneda,
			c.simbolo as smoneda,
			d.numero as compra_numero,
			d.sucursal_id", false)
		->from("cuenta_pagar a")
		->join("proveedor b", "b.id = a.proveedor_id")
		->join("moneda c", "c.id = a.moneda_id")
		->join("compra d", "d.id = a.compra_id")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("d.sucursal_id", $this->_ses->sucursal_id)
		->order_by("a.fecha_vence", "asc")
		->order_by("a.id", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	public function getInfo()
	{
		return $this->_buscar([
			"id" => $this->getPK(),
			"_uno" => true
		]);
	}

	public function getPagos()
	{
		$pago = new Cuenta_pagar_pago_model();

		return $pago->_buscar([
			"cuenta_pagar_id" => $this->getPK()
		]);
	}

	# La cuenta es de la empresa y su compra, de la sucursal de la sesión
	public function esDeLaSucursal()
	{
		if (!$this->getPK() || (int)$this->empresa_id !== (int)$this->_ses->empresa_id || !$this->compra_id) {
			return false;
		}

		$compra = $this->db
		->select("sucursal_id")
		->where("id", $this->compra_id)
		->get("compra")
		->row();

		return $compra && (int)$compra->sucursal_id === (int)$this->_ses->sucursal_id;
	}

	/**
	 * Registra un pago al proveedor con su número de comprobante y baja el saldo.
	 * $datos: total, forma_pago_id, documento_numero, documento_fecha
	 * Devuelve el pago (Cuenta_pagar_pago_model) o false.
	 */
	public function pagar($datos)
	{
		$monto = round((float)verPropiedad($datos, "total", 0), 2);

		if ($monto <= 0) {
			$this->setMensaje("Indique un monto mayor a cero.");
			return false;
		}

		$this->db->trans_begin();

		# La cuenta se bloquea hasta terminar: dos pagos a la vez no dejan el saldo mal
		$cuenta = $this->db->query("
			select anulado, saldo
			from {$this->_tabla}
			where id = ?
			for update", [
				$this->getPK()
			])
		->row();

		if ((int)$cuenta->anulado === 1) {
			return $this->cancelar("La cuenta está anulada.");
		}

		if ($monto > round((float)$cuenta->saldo, 2)) {
			return $this->cancelar("El pago no puede superar el saldo (" . monto($cuenta->saldo) . ").");
		}

		$pago = new Cuenta_pagar_pago_model();
		$pago->guardar([
			"cuenta_pagar_id" => $this->getPK(),
			"forma_pago_id" => $datos->forma_pago_id,
			"comprobante_numero" => $pago->siguienteComprobante(),
			"documento_numero" => verPropiedad($datos, "documento_numero", null),
			"documento_fecha" => verPropiedad($datos, "documento_fecha", null),
			"total" => $monto
		]);

		if (!$pago->getPK()) {
			return $this->cancelar("No se pudo registrar el pago, intente nuevamente.");
		}

		$this->db
		->set("abono", "abono + " . $monto, false)
		->set("saldo", round((float)$cuenta->saldo - $monto, 2))
		->where("id", $this->getPK())
		->update($this->_tabla);

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo registrar el pago, intente nuevamente.");
		}

		$this->db->trans_commit();

		return $pago;
	}

	# Anula un pago: el monto regresa al saldo de la cuenta
	public function anularPago($pago, $motivo)
	{
		$this->db->trans_start();

		$this->db
		->set("anulado", 1)
		->set("anulado_fecha", Hoy(true))
		->set("anulado_motivo", $motivo)
		->set("anulado_usuario", $this->_ses->id)
		->where("id", $pago->getPK())
		->update("cuenta_pagar_pago");

		$this->db
		->set("abono", "abono - " . (float)$pago->total, false)
		->set("saldo", "saldo + " . (float)$pago->total, false)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->setMensaje("No se pudo anular el pago, intente nuevamente.");
			return false;
		}

		return true;
	}

	# Deshace la transacción abierta y deja el mensaje
	private function cancelar($mensaje)
	{
		$this->db->trans_rollback();
		$this->setMensaje($mensaje);

		return false;
	}
}

/* End of file Cuenta_pagar_model.php */
/* Location: ./application/models/fin/Cuenta_pagar_model.php */
