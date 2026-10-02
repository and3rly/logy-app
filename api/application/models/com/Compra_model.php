<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Compra_model extends Centro_model {

	const CREADA = 1;
	const RECIBIDA = 2;
	const ANULADA = 3;

	public $compra_estado_id = self::CREADA;
	public $proveedor_id;
	public $empresa_id;
	public $usuario_id;
	public $forma_pago_id;
	public $moneda_id;
	public $sucursal_id;
	public $numero;
	public $factura_numero = null;
	public $factura_fecha = null;
	public $total_costo = 0;
	public $anulado = 0;
	public $fecha_anulado = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Compras de la sucursal de la sesión con nombres de proveedor, moneda, forma de pago, estado y sucursal
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		} else {
			if (elemento($args, "fdel")) {
				$this->db->where("a.fecha >=", "{$args['fdel']} 00:00:00");
			}

			if (elemento($args, "fal")) {
				$this->db->where("a.fecha <=", "{$args['fal']} 23:59:59");
			}

			if (elemento($args, "estado")) {
				$this->db->where("a.compra_estado_id", $args["estado"]);
			}
		}

		# Sin rol administrador: solo los documentos del usuario
		filtrar_por_usuario("a.usuario_id");

		$tmp = $this->db
		->select("
			a.*,
			b.nombre as nproveedor,
			b.identificacion as nit_proveedor,
			c.codigo as cmoneda,
			c.simbolo as smoneda,
			d.nombre as nforma_pago,
			e.nombre as nestado,
			e.etiqueta as eestado,
			f.nombre as nsucursal")
		->from("compra a")
		->join("proveedor b", "b.id = a.proveedor_id")
		->join("moneda c", "c.id = a.moneda_id")
		->join("forma_pago d", "d.id = a.forma_pago_id")
		->join("compra_estado e", "e.id = a.compra_estado_id")
		->join("sucursal f", "f.id = a.sucursal_id")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.sucursal_id", $this->_ses->sucursal_id)
		->order_by("a.fecha", "desc")
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

	public function getDetalle()
	{
		$det = new Compra_detalle_model();

		return $det->_buscar([
			"compra_id" => $this->getPK()
		]);
	}

	# Todo lo que lleva el formato de impresión: compra, detalle, empresa, sucursal, proveedor y usuarios
	public function datosImpresion()
	{
		$compra = $this->getInfo();

		$empresa = $this->db
		->where("id", $this->empresa_id)
		->get("empresa")
		->row();

		$sucursal = $this->db
		->where("id", $this->sucursal_id)
		->get("sucursal")
		->row();

		$proveedor = $this->db
		->where("id", $this->proveedor_id)
		->get("proveedor")
		->row();

		$creo = $this->db
		->select("nombre")
		->where("id", $this->usuario_id)
		->get("usuario")
		->row();

		$imprime = $this->db
		->select("nombre")
		->where("id", $this->_ses->id)
		->get("usuario")
		->row();

		return [
			"compra" => $compra,
			"detalle" => $this->getDetalle(),
			"empresa" => $empresa,
			"sucursal" => $sucursal,
			"proveedor" => $proveedor,
			"creado_por" => $creo ? $creo->nombre : "",
			"impreso_por" => $imprime ? $imprime->nombre : ""
		];
	}

	# La OC es de la empresa y de la sucursal de la sesión (las OC se manejan por sucursal)
	public function esDeLaSucursal()
	{
		return $this->getPK() &&
			(int)$this->empresa_id === (int)$this->_ses->empresa_id &&
			(int)$this->sucursal_id === (int)$this->_ses->sucursal_id &&
			puede_ver_documento($this->usuario_id);
	}

	# Solo una compra creada (sin recibir ni anular) se puede modificar
	public function editable()
	{
		return (int)$this->compra_estado_id === self::CREADA && (int)$this->anulado === 0;
	}

	# Correlativo por empresa con la abreviatura de los parámetros, ej. COM-0000000008
	public function asignarNumero()
	{
		if (!empty($this->numero)) {
			return;
		}

		$param = $this->catalogo->verEmpresaParametro();
		$abr = ($param && $param->abr_compra) ? $param->abr_compra : "COM";

		$tmp = $this->db
		->select("count(*) + 1 as siguiente", false)
		->where("empresa_id", $this->_ses->empresa_id)
		->get($this->_tabla)
		->row();

		$this->numero = $abr . "-" . str_pad($tmp->siguiente, 10, "0", STR_PAD_LEFT);
	}

	# La forma de pago es a crédito (forma_pago no tiene columna para indicarlo: se usa el nombre)
	public function esCredito()
	{
		$fpago = $this->catalogo->verFormasPago([
			"id" => $this->forma_pago_id,
			"_todos" => true,
			"_uno" => true
		]);

		return $fpago && stripos(eliminarAcento($fpago->nombre), "credito") !== false;
	}

	/**
	 * Recibe la compra: suma cada producto al stock de la sucursal, registra el movimiento
	 * de recepción, actualiza el costo del producto y, si es a crédito, crea la cuenta por pagar.
	 */
	public function recibir()
	{
		$detalle = $this->getDetalle();

		if (count($detalle) === 0) {
			$this->setMensaje("Agregue al menos un producto antes de recibir la compra.");
			return false;
		}

		$credito = $this->esCredito();

		if ($credito && (empty($this->factura_numero) || empty($this->factura_fecha))) {
			$this->setMensaje("Una compra al crédito necesita número y fecha de factura para generar la cuenta por pagar.");
			return false;
		}

		$tipo = $this->catalogo->verMovimientoTipos([
			"codigo" => "REC",
			"_uno" => true
		]);

		if (!$tipo) {
			$this->setMensaje("No existe el tipo de movimiento de recepción (REC).");
			return false;
		}

		$this->db->trans_start();

		foreach ($detalle as $det) {
			$stock = new Stock_model();
			# El inventario entra a la sucursal de la OC
			$stockId = $stock->sumar([
				"sucursal_id" => $this->sucursal_id,
				"producto_id" => $det->producto_id,
				"unidad_medida_id" => $det->unidad_medida_id,
				"producto_presentacion_id" => $det->producto_presentacion_id,
				"fecha_vence" => $det->fecha_vence,
				"cantidad" => $det->cantidad
			]);

			$mov = new Movimiento_model();
			$mov->guardar([
				"stock_id" => $stockId,
				"movimiento_tipo_id" => $tipo->id,
				"cantidad" => $det->cantidad,
				"compra_id" => $this->getPK(),
				"observacion" => "Recepción de orden {$this->numero}"
			]);

			# Último costo de compra del producto, por unidad de medida (el de la presentación entre su factor)
			$this->db
			->set("costo", round((float)$det->precio_costo / (float)$det->factor, 5))
			->where("id", $det->producto_id)
			->update("producto");
		}

		if ($credito) {
			$this->generarCuentaPagar();
		}

		$this->db
		->set("compra_estado_id", self::RECIBIDA)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->setMensaje("No se pudo recibir la compra, intente nuevamente.");
			return false;
		}

		$this->compra_estado_id = self::RECIBIDA;
		return true;
	}

	# Solo para compras creadas: todavía no afectaron inventario ni cuentas por pagar
	public function anular()
	{
		$this->db
		->set("compra_estado_id", self::ANULADA)
		->set("anulado", 1)
		->set("fecha_anulado", Hoy(true))
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->compra_estado_id = self::ANULADA;
		$this->anulado = 1;

		return true;
	}

	private function generarCuentaPagar()
	{
		$prov = $this->db
		->where("id", $this->proveedor_id)
		->get("proveedor")
		->row();

		$dias = $prov ? (int)$prov->credito_dias : 0;

		$cxp = new Cuenta_pagar_model();
		$cxp->guardar([
			"proveedor_id" => $this->proveedor_id,
			"factura_fecha" => $this->factura_fecha,
			"factura_numero" => $this->factura_numero,
			"credito_dias" => $dias,
			"fecha_vence" => date("Y-m-d", strtotime("{$this->factura_fecha} + {$dias} days")),
			"total" => $this->total_costo,
			"abono" => 0,
			"saldo" => $this->total_costo,
			"moneda_id" => $this->moneda_id,
			"compra_id" => $this->getPK(),
			"referencia" => "Orden de compra {$this->numero}",
			"origen" => 2
		]);
	}
}

/* End of file Compra_model.php */
/* Location: ./application/models/com/Compra_model.php */
