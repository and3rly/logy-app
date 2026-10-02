<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cotizacion_model extends Centro_model {

	# Códigos de cotizacion_estado (los id cambian por empresa)
	const BORRADOR = "BORRADOR";
	const ENVIADA = "ENVIADA";
	const ACEPTADA = "ACEPTADA";
	const RECHAZADA = "RECHAZADA";
	const ANULADA = "ANULADA";
	const CONVERTIDA = "CONVERTIDA";

	# Días de validez de una cotización nueva
	const DIAS_VALIDEZ = 15;

	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;
	public $vendedor_id = null;
	public $moneda_id;
	public $cliente_id = null;
	public $forma_pago_id = null;
	public $cotizacion_estado_id;
	public $cotizacion_serie_id;
	public $numero_correlativo;
	public $numero;
	public $valida_hasta;
	public $cliente_nombre;
	public $cliente_razon_social = null;
	public $cliente_identificacion = null;
	public $cliente_direccion = null;
	public $cliente_telefono = null;
	public $cliente_correo = null;
	public $subtotal = 0;
	public $descuento = 0;
	public $total = 0;
	public $total_costo = 0;
	public $ganancia = 0;
	public $tipo_cambio = 1;
	public $referencia = null;
	public $observaciones = null;
	public $condiciones = null;
	public $fecha_envio = null;
	public $fecha_aceptacion = null;
	public $fecha_rechazo = null;
	public $anulado = 0;
	public $anulado_fecha = null;
	public $anulado_usuario = null;
	public $anulado_motivo = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Cotizaciones de la sucursal de la sesión; vencida = abierta con la validez pasada
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
				$this->db->where("a.cotizacion_estado_id", $args["estado"]);
			}
		}

		# Sin rol administrador: solo los documentos del usuario
		filtrar_por_usuario("a.usuario_id");

		$tmp = $this->db
		->select("
			a.*,
			b.codigo as cmoneda,
			b.simbolo as smoneda,
			c.nombre as nforma_pago,
			d.codigo as cestado,
			d.nombre as nestado,
			d.etiqueta as eestado,
			e.nombre as nsucursal,
			f.nombre as nusuario,
			g.id as venta_id,
			g.correlativo as venta_correlativo,
			datediff(a.valida_hasta, curdate()) as dias_restantes,
			(a.valida_hasta < curdate() and d.codigo in ('BORRADOR', 'ENVIADA', 'ACEPTADA')) as vencida", false)
		->from("cotizacion a")
		->join("moneda b", "b.id = a.moneda_id")
		->join("forma_pago c", "c.id = a.forma_pago_id", "left")
		->join("cotizacion_estado d", "d.id = a.cotizacion_estado_id")
		->join("sucursal e", "e.id = a.sucursal_id")
		->join("usuario f", "f.id = a.usuario_id")
		->join("venta g", "g.cotizacion_id = a.id", "left")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.sucursal_id", $this->_ses->sucursal_id)
		->order_by("a.fecha", "desc")
		->order_by("a.id", "desc")
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
		$det = new Cotizacion_detalle_model();

		return $det->_buscar([
			"cotizacion_id" => $this->getPK()
		]);
	}

	# La cotización es de la empresa y de la sucursal de la sesión
	public function esDeLaSucursal()
	{
		return $this->getPK() &&
			(int)$this->empresa_id === (int)$this->_ses->empresa_id &&
			(int)$this->sucursal_id === (int)$this->_ses->sucursal_id &&
			puede_ver_documento($this->usuario_id);
	}

	public function codigoEstado()
	{
		$tmp = $this->db
		->select("codigo")
		->where("id", $this->cotizacion_estado_id)
		->get("cotizacion_estado")
		->row();

		return $tmp ? $tmp->codigo : "";
	}

	# Solo el borrador se modifica: lo enviado es lo que vio el cliente
	public function editable()
	{
		return $this->codigoEstado() === self::BORRADOR && (int)$this->anulado === 0;
	}

	public function vencida()
	{
		return $this->valida_hasta < Hoy();
	}

	/**
	 * Guarda el encabezado. Si es nueva toma el correlativo de la serie (bloqueada hasta
	 * terminar) y queda en borrador. Los datos del cliente se copian: la cotización
	 * conserva lo que se le envió aunque el cliente cambie después.
	 * $datos: cliente_id, moneda_id, forma_pago_id, valida_hasta, referencia, observaciones, condiciones
	 */
	public function guardarEncabezado($datos)
	{
		$validaHasta = verPropiedad($datos, "valida_hasta", "");

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $validaHasta)) {
			$this->setMensaje("Indique hasta qué fecha es válida la cotización.");
			return false;
		}

		if ($validaHasta < Hoy()) {
			$this->setMensaje("La fecha de validez no puede ser anterior a hoy.");
			return false;
		}

		$moneda = $this->catalogo->verMonedas([
			"id" => verPropiedad($datos, "moneda_id", 0),
			"_uno" => true
		]);

		if (!$moneda) {
			$this->setMensaje("Seleccione una moneda activa.");
			return false;
		}

		$formaPago = verPropiedad($datos, "forma_pago_id", null);

		if ($formaPago && !$this->catalogo->verFormasPago(["id" => $formaPago, "_uno" => true])) {
			$this->setMensaje("La forma de pago no existe o está inactiva.");
			return false;
		}

		$cliente = $this->datosCliente(verPropiedad($datos, "cliente_id", null));

		if ($cliente === false) {
			return false;
		}

		$campos = array_merge($cliente, [
			"moneda_id" => $moneda->id,
			"forma_pago_id" => $formaPago,
			"valida_hasta" => $validaHasta,
			"referencia" => $this->texto($datos, "referencia", 300),
			"observaciones" => $this->html($datos, "observaciones"),
			"condiciones" => $this->html($datos, "condiciones")
		]);

		if ($this->getPK()) {
			# Sin cambios también es correcto
			return $this->guardar($campos) || $this->getMensaje() === "Nada que actualizar";
		}

		$borrador = $this->estadoId(self::BORRADOR);

		if (!$borrador) {
			$this->setMensaje("No existe el estado Borrador de cotizaciones.");
			return false;
		}

		$this->db->trans_begin();

		$serie = $this->tomarSerie(verPropiedad($datos, "cotizacion_serie_id", null));

		if (!$serie) {
			return $this->cancelar("No hay una serie de cotizaciones activa.");
		}

		$numero = max((int)$serie->correlativo + 1, (int)$serie->inicio);

		if ($numero > (int)$serie->fin) {
			return $this->cancelar("La serie {$serie->codigo} llegó a su número final ({$serie->fin}).");
		}

		$this->guardar(array_merge($campos, [
			"cotizacion_estado_id" => $borrador,
			"cotizacion_serie_id" => $serie->id,
			"numero_correlativo" => $numero,
			"numero" => $serie->codigo . "-" . str_pad($numero, 9, "0", STR_PAD_LEFT)
		]));

		if (!$this->getPK()) {
			return $this->cancelar("No se pudo crear la cotización, intente nuevamente.");
		}

		$this->db
		->set("correlativo", $numero)
		->where("id", $serie->id)
		->update("cotizacion_serie");

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo crear la cotización, intente nuevamente.");
		}

		$this->db->trans_commit();
		return true;
	}

	# Recalcula los totales con las líneas vigentes
	public function actualizarTotales()
	{
		$tmp = $this->db
		->select("
			ifnull(sum(subtotal), 0) as subtotal,
			ifnull(sum(descuento_total), 0) as descuento,
			ifnull(sum(total), 0) as total,
			ifnull(sum(total_costo), 0) as total_costo,
			ifnull(sum(ganancia), 0) as ganancia", false)
		->where("cotizacion_id", $this->getPK())
		->where("anulado", 0)
		->get("cotizacion_detalle")
		->row();

		$this->db
		->set("subtotal", $tmp->subtotal)
		->set("descuento", $tmp->descuento)
		->set("total", $tmp->total)
		->set("total_costo", $tmp->total_costo)
		->set("ganancia", $tmp->ganancia)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->setDatos($tmp);
	}

	/**
	 * Avanza el estado: enviar (borrador → enviada), aceptar o rechazar (enviada)
	 * y anular (borrador, enviada o aceptada, con motivo)
	 */
	public function cambiarEstado($accion, $motivo="")
	{
		$actual = $this->codigoEstado();

		if ((int)$this->anulado === 1) {
			$this->setMensaje("La cotización está anulada.");
			return false;
		}

		switch ($accion) {
			case "enviar":
				if ($actual !== self::BORRADOR) {
					$this->setMensaje("Solo un borrador se puede enviar.");
					return false;
				}

				if (count($this->getDetalle()) === 0) {
					$this->setMensaje("Agregue al menos un producto antes de enviarla.");
					return false;
				}

				if ($this->vencida()) {
					$this->setMensaje("La validez ya pasó; actualice la fecha antes de enviarla.");
					return false;
				}

				return $this->pasarA(self::ENVIADA, ["fecha_envio" => Hoy(true)]);

			case "aceptar":
			case "rechazar":
				if ($actual !== self::ENVIADA) {
					$this->setMensaje("Solo una cotización enviada se puede aceptar o rechazar.");
					return false;
				}

				if ($accion === "aceptar" && $this->vencida()) {
					$this->setMensaje("La cotización está vencida; duplíquela para cotizar de nuevo.");
					return false;
				}

				return $accion === "aceptar"
					? $this->pasarA(self::ACEPTADA, ["fecha_aceptacion" => Hoy(true)])
					: $this->pasarA(self::RECHAZADA, ["fecha_rechazo" => Hoy(true)]);

			case "anular":
				if (!in_array($actual, [self::BORRADOR, self::ENVIADA, self::ACEPTADA])) {
					$this->setMensaje("Esta cotización ya no se puede anular.");
					return false;
				}

				if ($motivo === "") {
					$this->setMensaje("Indique el motivo de la anulación.");
					return false;
				}

				return $this->pasarA(self::ANULADA, [
					"anulado" => 1,
					"anulado_fecha" => Hoy(true),
					"anulado_usuario" => $this->_ses->id,
					"anulado_motivo" => $motivo
				]);
		}

		$this->setMensaje("No se indicó la acción.");
		return false;
	}

	/**
	 * Crea un borrador nuevo con el cliente, las condiciones y las líneas de esta cotización
	 * (mismos precios y descuentos). Los productos inactivos se omiten.
	 */
	public function duplicar()
	{
		$nueva = new Cotizacion_model();

		$this->db->trans_begin();

		$creada = $nueva->guardarEncabezado((object)[
			"cliente_id" => $this->cliente_id,
			"moneda_id" => $this->moneda_id,
			"forma_pago_id" => $this->forma_pago_id,
			"valida_hasta" => date("Y-m-d", strtotime("+" . self::DIAS_VALIDEZ . " days")),
			"referencia" => $this->referencia,
			"observaciones" => $this->observaciones,
			"condiciones" => $this->condiciones
		]);

		if (!$creada) {
			$this->db->trans_rollback();
			$this->setMensaje($nueva->getMensaje());
			return false;
		}

		$omitidos = 0;

		foreach ($this->getDetalle() as $linea) {
			$producto = $this->catalogo->verProductos([
				"id" => $linea->producto_id,
				"_uno" => true
			]);

			# Sin presentación la línea es en la unidad de medida
			$presentacion = ($producto && $linea->producto_presentacion_id)
				? $this->catalogo->verPresentacion($producto->id, $linea->producto_presentacion_id)
				: null;

			if (!$producto || ($linea->producto_presentacion_id && !$presentacion)) {
				$omitidos++;
				continue;
			}

			$det = new Cotizacion_detalle_model();
			$det->registrar($nueva, $producto, [
				"presentacion" => $presentacion,
				"cantidad" => $linea->cantidad,
				"precio" => $linea->precio,
				"descuento_porcentaje" => $linea->descuento_porcentaje,
				"observacion" => $linea->observacion
			]);
		}

		$nueva->actualizarTotales();

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$this->setMensaje("No se pudo duplicar la cotización, intente nuevamente.");
			return false;
		}

		$this->db->trans_commit();

		if ($omitidos > 0) {
			$nueva->setMensaje("{$omitidos} " . ($omitidos === 1 ? "producto o presentación inactivo no se copió." : "productos o presentaciones inactivos no se copiaron."));
		}

		return $nueva;
	}

	/**
	 * Convierte la cotización aceptada en venta con los precios y descuentos cotizados:
	 * descuenta inventario (bloquea si no alcanza), genera la cuenta por cobrar si es a crédito
	 * y la cotización queda convertida. Todo en una transacción.
	 * $datos: venta_serie_id, forma_pago_id. Devuelve la venta o false.
	 */
	public function convertir($datos)
	{
		if ((int)$this->anulado === 1 || $this->codigoEstado() !== self::ACEPTADA) {
			$this->setMensaje("Solo una cotización aceptada se puede convertir en venta.");
			return false;
		}

		if ($this->vencida()) {
			$this->setMensaje("La cotización está vencida; duplíquela para cotizar de nuevo.");
			return false;
		}

		$this->db->trans_begin();

		# La fila queda bloqueada: dos usuarios no la convierten a la vez
		$actual = $this->db->query("
			select cotizacion_estado_id
			from cotizacion
			where id = ?
			for update", [
				$this->getPK()
			])
		->row();

		if ((int)$actual->cotizacion_estado_id !== (int)$this->estadoId(self::ACEPTADA)) {
			$this->db->trans_rollback();
			$this->setMensaje("La cotización cambió de estado; recárguela.");
			return false;
		}

		$lineas = array_map(function ($d) {
			return (object)[
				"producto_id" => $d->producto_id,
				"unidad_medida_id" => $d->unidad_medida_id,
				"producto_presentacion_id" => $d->producto_presentacion_id,
				"cantidad" => $d->cantidad,
				"precio" => $d->precio,
				"descuento" => $d->descuento_porcentaje
			];
		}, $this->getDetalle());

		$venta = new Venta_model();

		$registrada = $venta->registrar((object)[
			"cliente_id" => $this->cliente_id,
			"cotizacion_id" => $this->getPK(),
			"venta_serie_id" => verPropiedad($datos, "venta_serie_id"),
			"forma_pago_id" => verPropiedad($datos, "forma_pago_id"),
			"moneda_id" => $this->moneda_id,
			"lineas" => $lineas
		]);

		if (!$registrada) {
			$this->db->trans_rollback();
			$this->setMensaje($venta->getMensaje());
			return false;
		}

		$this->pasarA(self::CONVERTIDA);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$this->setMensaje("No se pudo convertir la cotización, intente nuevamente.");
			return false;
		}

		$this->db->trans_commit();
		return $venta;
	}

	# Todo lo que lleva el formato de impresión
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

		$creo = $this->db
		->select("nombre, correo")
		->where("id", $this->usuario_id)
		->get("usuario")
		->row();

		$imprime = $this->db
		->select("nombre")
		->where("id", $this->_ses->id)
		->get("usuario")
		->row();

		return [
			"cotizacion" => $this->getInfo(),
			"detalle" => $this->getDetalle(),
			"empresa" => $empresa,
			"sucursal" => $sucursal,
			"creado_por" => $creo ? $creo->nombre : "",
			"impreso_por" => $imprime ? $imprime->nombre : ""
		];
	}

	public function estadoId($codigo)
	{
		$tmp = $this->catalogo->verCotizacionEstados([
			"codigo" => $codigo,
			"_todos" => true,
			"_uno" => true
		]);

		return $tmp ? $tmp->id : null;
	}

	# Datos del cliente que se copian; sin cliente es consumidor final
	private function datosCliente($clienteId)
	{
		if (!$clienteId) {
			return [
				"cliente_id" => null,
				"cliente_nombre" => "Consumidor final",
				"cliente_razon_social" => null,
				"cliente_identificacion" => "CF",
				"cliente_direccion" => null,
				"cliente_telefono" => null,
				"cliente_correo" => null
			];
		}

		$cliente = $this->catalogo->verClientes([
			"id" => $clienteId,
			"_uno" => true
		]);

		if (!$cliente) {
			$this->setMensaje("El cliente no existe o está inactivo.");
			return false;
		}

		return [
			"cliente_id" => $cliente->id,
			"cliente_nombre" => mb_substr($cliente->nombre, 0, 150),
			"cliente_razon_social" => $cliente->razon_social ? mb_substr($cliente->razon_social, 0, 150) : null,
			"cliente_identificacion" => $cliente->identificacion ? mb_substr($cliente->identificacion, 0, 20) : "CF",
			"cliente_direccion" => $cliente->direccion ? mb_substr($cliente->direccion, 0, 200) : null,
			"cliente_telefono" => $cliente->telefono ? mb_substr($cliente->telefono, 0, 30) : null,
			"cliente_correo" => $cliente->correo ? mb_substr($cliente->correo, 0, 150) : null
		];
	}

	# La serie indicada o la primera activa, bloqueada hasta terminar la transacción
	private function tomarSerie($serieId)
	{
		$filtro = $serieId ? "and id = " . (int)$serieId : "";

		return $this->db->query("
			select *
			from cotizacion_serie
			where empresa_id = ?
			and activo = 1
			{$filtro}
			order by id
			limit 1
			for update", [
				$this->_ses->empresa_id
			])
		->row();
	}

	private function pasarA($codigo, $campos=[])
	{
		$estado = $this->estadoId($codigo);

		if (!$estado) {
			$this->setMensaje("No existe el estado {$codigo} de cotizaciones.");
			return false;
		}

		$this->db
		->set($campos)
		->set("cotizacion_estado_id", $estado)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->setDatos(array_merge($campos, ["cotizacion_estado_id" => $estado]));

		return true;
	}

	# Texto opcional recortado; vacío queda en null
	private function texto($datos, $campo, $largo=null)
	{
		$valor = trim((string)verPropiedad($datos, $campo, ""));

		if ($valor === "") {
			return null;
		}

		return $largo ? mb_substr($valor, 0, $largo) : $valor;
	}

	# Texto del editor (HTML): solo el formato permitido; vacío = null
	private function html($datos, $campo)
	{
		$valor = limpiarHtml(verPropiedad($datos, $campo, ""));

		return $valor === "" ? null : $valor;
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

/* End of file Cotizacion_model.php */
/* Location: ./application/models/ven/Cotizacion_model.php */
