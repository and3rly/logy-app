<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cuenta_cobrar_model extends Centro_model {

	public $cliente_id;
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
	public $venta_id = null;
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
	 * Cuentas de las ventas de la sucursal de la sesión (la sucursal sale de la venta).
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
			b.nombre as ncliente,
			b.identificacion as nit_cliente,
			c.codigo as cmoneda,
			c.simbolo as smoneda,
			d.correlativo,
			d.venta_estado_id,
			d.sucursal_id", false)
		->from("cuenta_cobrar a")
		->join("cliente b", "b.id = a.cliente_id")
		->join("moneda c", "c.id = a.moneda_id")
		->join("venta d", "d.id = a.venta_id")
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
		$pago = new Cuenta_cobrar_pago_model();

		return $pago->_buscar([
			"cuenta_cobrar_id" => $this->getPK()
		]);
	}

	# La cuenta es de la empresa y su venta, de la sucursal de la sesión
	public function esDeLaSucursal()
	{
		if (!$this->getPK() || (int)$this->empresa_id !== (int)$this->_ses->empresa_id || !$this->venta_id) {
			return false;
		}

		$venta = $this->db
		->select("sucursal_id")
		->where("id", $this->venta_id)
		->get("venta")
		->row();

		return $venta && (int)$venta->sucursal_id === (int)$this->_ses->sucursal_id;
	}

	# Saldo pendiente del cliente (cuentas vigentes), para validar su límite de crédito
	public function saldoCliente($clienteId)
	{
		$tmp = $this->db
		->select("ifnull(sum(saldo), 0) as saldo", false)
		->where("cliente_id", $clienteId)
		->where("empresa_id", $this->_ses->empresa_id)
		->where("anulado", 0)
		->get($this->_tabla)
		->row();

		return (float)$tmp->saldo;
	}

	/**
	 * Registra un abono con su número de recibo y baja el saldo. Si el saldo llega a 0,
	 * la venta pasa a pagada. $datos: total, forma_pago_id, documento_numero, documento_fecha
	 * Devuelve el abono (Cuenta_cobrar_pago_model) o false.
	 */
	public function abonar($datos)
	{
		$monto = round((float)verPropiedad($datos, "total", 0), 2);

		if ($monto <= 0) {
			$this->setMensaje("Indique un monto mayor a cero.");
			return false;
		}

		$this->db->trans_begin();

		# La cuenta se bloquea hasta terminar: dos abonos a la vez no dejan el saldo mal
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
			return $this->cancelar("El abono no puede superar el saldo (" . monto($cuenta->saldo) . ").");
		}

		$pago = new Cuenta_cobrar_pago_model();
		$pago->guardar([
			"cuenta_cobrar_id" => $this->getPK(),
			"forma_pago_id" => $datos->forma_pago_id,
			"recibo_numero" => $pago->siguienteRecibo(),
			"documento_numero" => verPropiedad($datos, "documento_numero", null),
			"documento_fecha" => verPropiedad($datos, "documento_fecha", null),
			"total" => $monto
		]);

		if (!$pago->getPK()) {
			return $this->cancelar("No se pudo registrar el abono, intente nuevamente.");
		}

		$saldo = round((float)$cuenta->saldo - $monto, 2);

		$this->db
		->set("abono", "abono + " . $monto, false)
		->set("saldo", $saldo)
		->where("id", $this->getPK())
		->update($this->_tabla);

		if ($saldo <= 0) {
			$this->db
			->set("venta_estado_id", Venta_model::PAGADA)
			->where("id", $this->venta_id)
			->where("venta_estado_id", Venta_model::CREADA)
			->update("venta");
		}

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo registrar el abono, intente nuevamente.");
		}

		$this->db->trans_commit();

		return $pago;
	}

	# Anula un abono: el monto regresa al saldo y, si la venta estaba pagada, vuelve a creada
	public function anularPago($pago, $motivo)
	{
		$this->db->trans_start();

		$this->db
		->set("anulado", 1)
		->set("anulado_fecha", Hoy(true))
		->set("anulado_motivo", $motivo)
		->set("anulado_usuario", $this->_ses->id)
		->where("id", $pago->getPK())
		->update("cuenta_cobrar_pago");

		$this->db
		->set("abono", "abono - " . (float)$pago->total, false)
		->set("saldo", "saldo + " . (float)$pago->total, false)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->db
		->set("venta_estado_id", Venta_model::CREADA)
		->where("id", $this->venta_id)
		->where("venta_estado_id", Venta_model::PAGADA)
		->update("venta");

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->setMensaje("No se pudo anular el abono, intente nuevamente.");
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

/* End of file Cuenta_cobrar_model.php */
/* Location: ./application/models/fin/Cuenta_cobrar_model.php */
