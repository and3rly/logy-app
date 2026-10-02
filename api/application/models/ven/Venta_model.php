<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Venta_model extends Centro_model {

	const CREADA = 1;
	const FACTURADA = 2;
	const PAGADA = 3;
	const ANULADA = 4;

	public $empresa_id;
	public $usuario_id;
	public $moneda_id;
	public $cliente_id = null;
	public $cotizacion_id = null;
	public $forma_pago_id;
	public $venta_estado_id = self::CREADA;
	public $venta_serie_id;
	public $total_precio = 0;
	public $total_costo = 0;
	public $ganancia = 0;
	public $descuento = 0;
	public $correlativo = null;
	public $referencia = null;
	public $factura_fecha = null;
	public $factura_numero = null;
	public $factura_serie = null;
	public $anulado = 0;
	public $anulado_fecha = null;
	public $anulado_usuario = null;
	public $anulado_motivo = null;
	public $sucursal_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Ventas de la sucursal de la sesión con nombres de cliente, serie, moneda, forma de pago, estado y cajero
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
				$this->db->where("a.venta_estado_id", $args["estado"]);
			}
		}

		# Sin rol administrador: solo los documentos del usuario
		filtrar_por_usuario("a.usuario_id");

		$tmp = $this->db
		->select("
			a.*,
			ifnull(b.nombre, 'Consumidor final') as ncliente,
			b.identificacion as nit_cliente,
			c.codigo as cmoneda,
			c.simbolo as smoneda,
			d.nombre as nforma_pago,
			e.nombre as nestado,
			e.etiqueta as eestado,
			f.nombre as nserie,
			g.nombre as nsucursal,
			h.nombre as nusuario", false)
		->from("venta a")
		->join("cliente b", "b.id = a.cliente_id", "left")
		->join("moneda c", "c.id = a.moneda_id")
		->join("forma_pago d", "d.id = a.forma_pago_id")
		->join("venta_estado e", "e.id = a.venta_estado_id")
		->join("venta_serie f", "f.id = a.venta_serie_id")
		->join("sucursal g", "g.id = a.sucursal_id")
		->join("usuario h", "h.id = a.usuario_id")
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
		$det = new Venta_detalle_model();

		return $det->_buscar([
			"venta_id" => $this->getPK()
		]);
	}

	# La venta es de la empresa y de la sucursal de la sesión
	public function esDeLaSucursal()
	{
		return $this->getPK() &&
			(int)$this->empresa_id === (int)$this->_ses->empresa_id &&
			(int)$this->sucursal_id === (int)$this->_ses->sucursal_id &&
			puede_ver_documento($this->usuario_id);
	}

	# La forma de pago es a crédito (forma_pago no tiene columna para indicarlo: se usa el nombre)
	public function esCredito($formaPagoId)
	{
		$fpago = $this->catalogo->verFormasPago([
			"id" => $formaPagoId,
			"_todos" => true,
			"_uno" => true
		]);

		return $fpago && stripos(eliminarAcento($fpago->nombre), "credito") !== false;
	}

	/**
	 * Registra la venta del punto de venta en una sola transacción: correlativo de la serie,
	 * encabezado y líneas, descuento de stock por lote (vence primero), movimientos VTA y,
	 * si es a crédito, la cuenta por cobrar. Contado queda pagada; crédito, creada hasta que se liquide.
	 * Facturada queda reservado para la certificación ante el ente.
	 * $datos: cliente_id, venta_serie_id, forma_pago_id, moneda_id y lineas [{producto_id, unidad_medida_id, cantidad, precio?}]
	 */
	public function registrar($datos)
	{
		$lineas = $this->agruparLineas(verPropiedad($datos, "lineas", []));

		if (count($lineas) === 0) {
			$this->setMensaje("Agregue al menos un producto.");
			return false;
		}

		$tipo = $this->catalogo->verMovimientoTipos([
			"codigo" => "VTA",
			"_uno" => true
		]);

		if (!$tipo) {
			$this->setMensaje("No existe el tipo de movimiento de venta (VTA).");
			return false;
		}

		$credito = $this->esCredito($datos->forma_pago_id);
		$clienteId = verPropiedad($datos, "cliente_id", null);
		$cliente = null;

		if ($clienteId) {
			$cliente = $this->catalogo->verClientes([
				"id" => $clienteId,
				"_uno" => true
			]);

			if (!$cliente) {
				$this->setMensaje("El cliente no existe o está inactivo.");
				return false;
			}
		} else {
			# Sin cliente elegido la venta queda a nombre de Consumidor final
			$cf = new Cliente_model();
			$cliente = $cf->consumidorFinal();

			if (!$cliente) {
				$this->setMensaje("No se pudo obtener el cliente Consumidor final (CF).");
				return false;
			}
		}

		if ($credito && ($cliente->codigo === Cliente_model::CODIGO_CF || (int)$cliente->credito !== 1)) {
			$this->setMensaje("Una venta al crédito necesita un cliente con crédito autorizado.");
			return false;
		}

		# Productos y totales antes de tocar la base
		$total = 0;
		$costo = 0;
		$descuento = 0;

		foreach ($lineas as $linea) {
			$producto = $this->catalogo->verProductos([
				"id" => $linea->producto_id,
				"_uno" => true
			]);

			if (!$producto || $producto->tipo_producto !== "B") {
				$this->setMensaje("Uno de los productos no existe o está inactivo.");
				return false;
			}

			# Con presentación, precio y costo son los del producto por su factor
			$factor = 1;
			$linea->npresentacion = "";

			if ($linea->producto_presentacion_id !== null) {
				$presentacion = $this->catalogo->verPresentacion($producto->id, $linea->producto_presentacion_id);

				if (!$presentacion) {
					$this->setMensaje("La presentación de {$producto->nombre} no existe o está inactiva.");
					return false;
				}

				$factor = (float)$presentacion->factor;
				$linea->npresentacion = " ({$presentacion->nombre})";
			}

			# Desde una cotización llega el precio pactado; en el punto de venta, el del producto
			if ($linea->precio === null && (float)$producto->precio <= 0) {
				$this->setMensaje("El producto {$producto->nombre} no tiene precio de venta.");
				return false;
			}

			$linea->producto = $producto;
			$linea->costo = round((float)$producto->costo * $factor, 5);

			# El precio cambiado en el punto de venta no puede quedar bajo el costo; el de una
			# cotización se respeta tal como se pactó
			if ($linea->precio !== null && !verPropiedad($datos, "cotizacion_id") && $linea->precio < round($linea->costo, 2)) {
				$this->setMensaje("El precio de {$producto->nombre}{$linea->npresentacion} no puede ser menor al costo (" . monto(round($linea->costo, 2)) . ").");
				return false;
			}

			$linea->precio = $linea->precio === null ? round((float)$producto->precio * $factor, 2) : $linea->precio;
			$subtotal = round($linea->cantidad * $linea->precio, 2);
			$linea->descuento_total = round($subtotal * $linea->descuento / 100, 2);
			$linea->total_precio = $subtotal - $linea->descuento_total;
			$linea->total_costo = round($linea->cantidad * $linea->costo, 2);

			$total += $linea->total_precio;
			$costo += $linea->total_costo;
			$descuento += $linea->descuento_total;
		}

		if ($credito && (float)$cliente->credito_limite > 0) {
			$cxc = new Cuenta_cobrar_model();
			$disponible = (float)$cliente->credito_limite - $cxc->saldoCliente($cliente->id);

			if ($total > $disponible) {
				$this->setMensaje("La venta supera el crédito disponible del cliente (" . monto(max($disponible, 0)) . ").");
				return false;
			}
		}

		$this->db->trans_begin();

		# La serie se bloquea hasta terminar: dos cajas no toman el mismo correlativo
		$serie = $this->db->query("
			select *
			from venta_serie
			where id = ?
			and empresa_id = ?
			and activo = 1
			for update", [
				$datos->venta_serie_id,
				$this->_ses->empresa_id
			])
		->row();

		if (!$serie) {
			return $this->cancelar("La serie no existe o está inactiva.");
		}

		$numero = max((int)$serie->correlativo + 1, (int)$serie->inicio);

		if ($numero > (int)$serie->fin) {
			return $this->cancelar("La serie {$serie->codigo} llegó a su número final ({$serie->fin}).");
		}

		$this->guardar([
			"moneda_id" => $datos->moneda_id,
			"cliente_id" => $cliente->id,
			"cotizacion_id" => verPropiedad($datos, "cotizacion_id", null),
			"forma_pago_id" => $datos->forma_pago_id,
			"venta_estado_id" => $credito ? self::CREADA : self::PAGADA,
			"venta_serie_id" => $serie->id,
			"total_precio" => round($total, 2),
			"total_costo" => round($costo, 2),
			"ganancia" => round($total - $costo, 2),
			"descuento" => round($descuento, 2),
			"correlativo" => $serie->codigo . "-" . str_pad($numero, 9, "0", STR_PAD_LEFT),
			"factura_fecha" => Hoy(),
			"factura_numero" => $numero,
			"factura_serie" => $serie->codigo
		]);

		if (!$this->getPK()) {
			return $this->cancelar("No se pudo registrar la venta, intente nuevamente.");
		}

		foreach ($lineas as $linea) {
			$stock = new Stock_model();
			$lotes = $stock->descontar([
				"sucursal_id" => $this->sucursal_id,
				"producto_id" => $linea->producto_id,
				"unidad_medida_id" => $linea->unidad_medida_id,
				"producto_presentacion_id" => $linea->producto_presentacion_id,
				"cantidad" => $linea->cantidad
			]);

			if ($lotes === false) {
				return $this->cancelar("No hay existencia suficiente de {$linea->producto->nombre}{$linea->npresentacion}.");
			}

			$det = new Venta_detalle_model();
			$det->guardar([
				"venta_id" => $this->getPK(),
				"producto_id" => $linea->producto_id,
				"unidad_medida_id" => $linea->unidad_medida_id,
				"producto_presentacion_id" => $linea->producto_presentacion_id,
				"cantidad" => $linea->cantidad,
				"precio" => $linea->precio,
				"costo" => $linea->costo,
				"total_precio" => $linea->total_precio,
				"descuento" => $linea->descuento,
				"descuento_total" => $linea->descuento_total,
				"total_costo" => $linea->total_costo,
				"ganancia" => round($linea->total_precio - $linea->total_costo, 2)
			]);

			foreach ($lotes as $stockId => $cantidad) {
				$mov = new Movimiento_model();
				$mov->guardar([
					"stock_id" => $stockId,
					"movimiento_tipo_id" => $tipo->id,
					"cantidad" => -$cantidad,
					"venta_detalle_id" => $det->getPK(),
					"observacion" => "Venta {$this->correlativo}"
				]);
			}
		}

		if ($credito) {
			$dias = (int)$cliente->credito_dias;

			$cxc = new Cuenta_cobrar_model();
			$cxc->guardar([
				"cliente_id" => $cliente->id,
				"factura_fecha" => Hoy(),
				"factura_numero" => $this->correlativo,
				"credito_dias" => $dias,
				"fecha_vence" => date("Y-m-d", strtotime("+{$dias} days")),
				"total" => $this->total_precio,
				"abono" => 0,
				"saldo" => $this->total_precio,
				"moneda_id" => $this->moneda_id,
				"venta_id" => $this->getPK(),
				"referencia" => "Venta {$this->correlativo}",
				"origen" => 2
			]);
		}

		$this->db
		->set("correlativo", $numero)
		->where("id", $serie->id)
		->update("venta_serie");

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo registrar la venta, intente nuevamente.");
		}

		$this->db->trans_commit();
		return true;
	}

	/**
	 * Anula la venta: devuelve al stock lo que salió por sus movimientos (movimiento VAN al mismo
	 * lote) y anula la cuenta por cobrar. No se puede si la cuenta ya tiene abonos.
	 */
	public function anular($motivo)
	{
		if ((int)$this->anulado === 1 || (int)$this->venta_estado_id === self::ANULADA) {
			$this->setMensaje("La venta ya está anulada.");
			return false;
		}

		$cxc = $this->db
		->where("venta_id", $this->getPK())
		->where("anulado", 0)
		->get("cuenta_cobrar")
		->row();

		if ($cxc && (float)$cxc->abono > 0) {
			$this->setMensaje("La cuenta por cobrar de esta venta ya tiene abonos; anúlelos antes de anular la venta.");
			return false;
		}

		$tipo = $this->catalogo->verMovimientoTipos([
			"codigo" => "VAN",
			"_uno" => true
		]);

		if (!$tipo) {
			$this->setMensaje("No existe el tipo de movimiento de anulación de venta (VAN).");
			return false;
		}

		# Neto por lote y línea de todos los movimientos de la venta (incluye líneas quitadas antes)
		$salidas = $this->db
		->select("a.stock_id, a.venta_detalle_id, sum(a.cantidad) as neto", false)
		->from("movimiento a")
		->join("venta_detalle b", "b.id = a.venta_detalle_id")
		->where("b.venta_id", $this->getPK())
		->group_by([
			"a.stock_id",
			"a.venta_detalle_id"
		])
		->having("sum(a.cantidad) <", 0)
		->get()
		->result();

		$this->db->trans_start();

		foreach ($salidas as $salida) {
			$cantidad = abs((float)$salida->neto);

			$this->db
			->set("cantidad", "cantidad + " . $cantidad, false)
			->where("id", $salida->stock_id)
			->update("stock");

			$mov = new Movimiento_model();
			$mov->guardar([
				"stock_id" => $salida->stock_id,
				"movimiento_tipo_id" => $tipo->id,
				"cantidad" => $cantidad,
				"venta_detalle_id" => $salida->venta_detalle_id,
				"observacion" => "Anulación de venta {$this->correlativo}"
			]);
		}

		if ($cxc) {
			$this->db
			->set("anulado", 1)
			->set("anulado_fecha", Hoy(true))
			->set("anulado_motivo", $motivo)
			->set("anulado_usuario", $this->_ses->id)
			->where("id", $cxc->id)
			->update("cuenta_cobrar");
		}

		$this->db
		->set("venta_estado_id", self::ANULADA)
		->set("anulado", 1)
		->set("anulado_fecha", Hoy(true))
		->set("anulado_usuario", $this->_ses->id)
		->set("anulado_motivo", $motivo)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->setMensaje("No se pudo anular la venta, intente nuevamente.");
			return false;
		}

		$this->venta_estado_id = self::ANULADA;
		$this->anulado = 1;

		return true;
	}

	# Todo lo que lleva el ticket: venta, detalle, empresa, sucursal, cliente y cajero
	public function datosImpresion()
	{
		$empresa = $this->db
		->where("id", $this->empresa_id)
		->get("empresa")
		->row();

		$sucursal = $this->db
		->where("id", $this->sucursal_id)
		->get("sucursal")
		->row();

		$cliente = $this->cliente_id ? $this->db
		->where("id", $this->cliente_id)
		->get("cliente")
		->row() : null;

		return [
			"venta" => $this->getInfo(),
			"detalle" => $this->getDetalle(),
			"empresa" => $empresa,
			"sucursal" => $sucursal,
			"cliente" => $cliente
		];
	}

	/**
	 * Une líneas repetidas del mismo producto, unidad, presentación, precio y descuento; descarta cantidades no válidas.
	 * precio llega del punto de venta o de una cotización y descuento (%) solo de una cotización;
	 * sin precio se usa el del producto.
	 */
	private function agruparLineas($lineas)
	{
		$tmp = [];

		foreach ((array)$lineas as $linea) {
			$producto = (int)verPropiedad($linea, "producto_id", 0);
			$unidad = (int)verPropiedad($linea, "unidad_medida_id", 0);
			$presentacion = (int)verPropiedad($linea, "producto_presentacion_id", 0) ?: null;
			$cantidad = round((float)verPropiedad($linea, "cantidad", 0), 2);
			$precio = property_exists($linea, "precio") && is_numeric($linea->precio) ? round((float)$linea->precio, 2) : null;
			$descuento = min(max(round((float)verPropiedad($linea, "descuento", 0), 2), 0), 100);

			if ($producto <= 0 || $unidad <= 0 || $cantidad <= 0 || ($precio !== null && $precio < 0)) {
				continue;
			}

			$llave = "{$producto}-{$unidad}-{$presentacion}-{$precio}-{$descuento}";

			if (isset($tmp[$llave])) {
				$tmp[$llave]->cantidad += $cantidad;
			} else {
				$tmp[$llave] = (object)[
					"producto_id" => $producto,
					"unidad_medida_id" => $unidad,
					"producto_presentacion_id" => $presentacion,
					"cantidad" => $cantidad,
					"precio" => $precio,
					"descuento" => $descuento
				];
			}
		}

		return array_values($tmp);
	}

	# Deshace la transacción abierta y deja el mensaje
	private function cancelar($mensaje)
	{
		$this->db->trans_rollback();
		$this->setPK(null);
		$this->setMensaje($mensaje);

		return false;
	}
}

/* End of file Venta_model.php */
/* Location: ./application/models/ven/Venta_model.php */
