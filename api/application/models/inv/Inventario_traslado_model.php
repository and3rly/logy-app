<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Traslado de producto de la sucursal de origen (sucursal_id) a otra de la empresa (sucursal_destino_id).
 * Borrador → Enviado (sale del origen con TRS) → Recibido (entra al destino con TRE, mismo vencimiento).
 * Un enviado se puede anular (vuelve al origen con ATS); un recibido no: se hace un traslado de regreso.
 */
class Inventario_traslado_model extends Centro_model {

	const BORRADOR = 1;
	const ENVIADO = 2;
	const RECIBIDO = 3;
	const ANULADO = 4;

	const PREFIJO = "TRA";
	const RUTA = "/traslado";

	public $numero;
	public $inventario_traslado_estado_id = self::BORRADOR;
	public $sucursal_destino_id;
	public $observacion = null;
	public $fecha_enviado = null;
	public $fecha_recibido = null;
	public $fecha_anulado = null;
	public $anulado_motivo = null;
	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;
	public $usuario_envio_id = null;
	public $usuario_recibio_id = null;
	public $usuario_anulo_id = null;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	/**
	 * Traslados de la sucursal de la sesión: los que envía (un usuario que no es administrador solo
	 * ve los suyos) y los que le envían (todos, menos los borradores). Con totales de sus líneas.
	 * Filtros: fdel, fal, estado y vista (enviados o recibidos).
	 */
	public function _buscar($args=[])
	{
		$sucursal = (int)$this->_ses->sucursal_id;

		# El subquery va primero: get_compiled_select() reinicia el query builder
		# Valor: costo congelado al enviar; mientras es borrador, el costo actual del producto
		$totales = $this->db
		->select("
			x.inventario_traslado_id,
			count(*) as lineas,
			sum(x.cantidad) as unidades,
			sum(round(x.cantidad * if(y.fecha_enviado is null, ifnull(p.costo, 0) * ifnull(z.factor, 1), x.costo), 2)) as valor", false)
		->from("inventario_traslado_detalle x")
		->join("inventario_traslado y", "y.id = x.inventario_traslado_id")
		->join("producto p", "p.id = x.producto_id")
		->join("producto_presentacion z", "z.id = x.producto_presentacion_id", "left")
		->group_by("x.inventario_traslado_id")
		->get_compiled_select();

		$origen = "a.sucursal_id = {$sucursal}" . (es_administrador() ? "" : " and a.usuario_id = " . (int)$this->_ses->id);
		$destino = "a.sucursal_destino_id = {$sucursal} and a.inventario_traslado_estado_id <> " . self::BORRADOR;

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
				$this->db->where("a.inventario_traslado_estado_id", $args["estado"]);
			}
		}

		switch (elemento($args, "vista")) {
			case "enviados":
				$this->db->where("({$origen})", null, false);
				break;
			case "recibidos":
				$this->db->where("({$destino})", null, false);
				break;
			default:
				$this->db->where("(({$origen}) or ({$destino}))", null, false);
		}

		$tmp = $this->db
		->select("
			a.*,
			if(a.sucursal_id = {$sucursal}, 'ENVIO', 'RECEPCION') as direccion,
			b.nombre as nestado,
			b.etiqueta as eestado,
			c.nombre as nsucursal,
			d.nombre as nsucursal_destino,
			f.nombre as nusuario,
			g.nombre as nusuario_envio,
			h.nombre as nusuario_recibio,
			i.nombre as nusuario_anulo,
			ifnull(t.lineas, 0) as lineas,
			ifnull(t.unidades, 0) as unidades,
			ifnull(t.valor, 0) as valor", false)
		->from("inventario_traslado a")
		->join("inventario_traslado_estado b", "b.id = a.inventario_traslado_estado_id")
		->join("sucursal c", "c.id = a.sucursal_id")
		->join("sucursal d", "d.id = a.sucursal_destino_id")
		->join("usuario f", "f.id = a.usuario_id")
		->join("usuario g", "g.id = a.usuario_envio_id", "left")
		->join("usuario h", "h.id = a.usuario_recibio_id", "left")
		->join("usuario i", "i.id = a.usuario_anulo_id", "left")
		->join("({$totales}) t", "t.inventario_traslado_id = a.id", "left", false)
		->where("a.empresa_id", $this->_ses->empresa_id)
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
		$det = new Inventario_traslado_detalle_model();

		return $det->_buscar([
			"inventario_traslado_id" => $this->getPK()
		]);
	}

	# Lo envía la sucursal de la sesión (y el usuario puede ver el documento): se edita, envía y anula
	public function esDelOrigen()
	{
		return $this->getPK() &&
			(int)$this->empresa_id === (int)$this->_ses->empresa_id &&
			(int)$this->sucursal_id === (int)$this->_ses->sucursal_id &&
			puede_ver_documento($this->usuario_id);
	}

	# Llega a la sucursal de la sesión (ya enviado): cualquier usuario de la sucursal lo ve y lo recibe
	public function esDelDestino()
	{
		return $this->getPK() &&
			(int)$this->empresa_id === (int)$this->_ses->empresa_id &&
			(int)$this->sucursal_destino_id === (int)$this->_ses->sucursal_id &&
			(int)$this->inventario_traslado_estado_id !== self::BORRADOR;
	}

	public function esVisible()
	{
		return $this->esDelOrigen() || $this->esDelDestino();
	}

	# Solo un borrador se puede modificar
	public function editable()
	{
		return (int)$this->inventario_traslado_estado_id === self::BORRADOR;
	}

	/**
	 * Crea el borrador con el siguiente número de la empresa (TRA-000001). Bloquea los parámetros
	 * de la empresa para que dos traslados creados a la vez no tomen el mismo número.
	 */
	public function crear($args)
	{
		$this->db->trans_begin();

		$this->db->query("
			select id
			from empresa_parametro
			where empresa_id = ?
			and activo = 1
			for update", [
				$this->_ses->empresa_id
			]);

		$tmp = $this->db->query("
			select ifnull(max(cast(substring(numero, ?) as unsigned)), 0) + 1 as siguiente
			from {$this->_tabla}
			where empresa_id = ?", [
				strlen(self::PREFIJO) + 2,
				$this->_ses->empresa_id
			])
		->row();

		$args["numero"] = self::PREFIJO . "-" . str_pad($tmp->siguiente, 6, "0", STR_PAD_LEFT);

		if (!$this->guardar($args) || $this->db->trans_status() === false) {
			return $this->cancelar("No se pudo crear el traslado, intente nuevamente.");
		}

		$this->db->trans_commit();

		return true;
	}

	/**
	 * Envía el borrador en una sola transacción: congela el costo de cada línea y descuenta el stock
	 * del origen (del lote indicado o, si no se indicó, del que vence primero) con TRS.
	 * Sin existencia no se envía nada. Avisa a la sucursal destino.
	 */
	public function enviar()
	{
		$detalle = $this->getDetalle();

		if (count($detalle) === 0) {
			$this->setMensaje("Agregue al menos un producto antes de enviar el traslado.");
			return false;
		}

		$tipo = $this->tipoMovimiento("TRS");

		if (!$tipo) {
			$this->setMensaje("No existe el tipo de movimiento del traslado (TRS).");
			return false;
		}

		$this->db->trans_begin();

		if (!$this->bloquear(self::BORRADOR)) {
			return $this->cancelar("El traslado ya fue enviado o anulado.");
		}

		$info = $this->getInfo();

		foreach ($detalle as $det) {
			$this->db
			->set("costo", $det->costo_unitario)
			->where("id", $det->id)
			->update("inventario_traslado_detalle");

			$stock = new Stock_model();
			$lotes = $stock->descontar([
				"sucursal_id" => $this->sucursal_id,
				"producto_id" => $det->producto_id,
				"unidad_medida_id" => $det->unidad_medida_id,
				"producto_presentacion_id" => $det->producto_presentacion_id,
				"fecha_vence" => $det->fecha_vence,
				"cantidad" => $det->cantidad
			]);

			if ($lotes === false) {
				$lote = $det->fecha_vence ? " en el lote que vence el " . date("d/m/Y", strtotime($det->fecha_vence)) : "";
				$presentacion = $det->npresentacion ? " ({$det->npresentacion})" : "";
				return $this->cancelar("No hay existencia suficiente de {$det->nproducto}{$presentacion}{$lote}.");
			}

			foreach ($lotes as $stockId => $cantidad) {
				$mov = new Movimiento_model();
				$mov->guardar([
					"stock_id" => $stockId,
					"movimiento_tipo_id" => $tipo->id,
					"cantidad" => -$cantidad,
					"inventario_traslado_detalle_id" => $det->id,
					"observacion" => "Traslado {$this->numero} a {$info->nsucursal_destino}"
				]);
			}
		}

		$this->db
		->set("inventario_traslado_estado_id", self::ENVIADO)
		->set("fecha_enviado", "now()", false)
		->set("usuario_envio_id", $this->_ses->id)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$productos = count($detalle) === 1 ? "1 producto" : count($detalle) . " productos";

		$this->notificar($this->sucursal_destino_id, [
			"tipo" => "aviso",
			"icono" => "fa-solid fa-truck",
			"texto" => "Traslado {$this->numero} de {$info->nsucursal} por recibir ({$productos})"
		]);

		return $this->terminar("No se pudo enviar el traslado, intente nuevamente.");
	}

	/**
	 * Recibe el traslado en la sucursal destino: entra lo que salió del origen, lote por lote y con
	 * el mismo vencimiento, con TRE. Retira el aviso de "por recibir" y avisa al origen.
	 */
	public function recibir()
	{
		$tipo = $this->tipoMovimiento("TRE");
		$salida = $this->tipoMovimiento("TRS");

		if (!$tipo || !$salida) {
			$this->setMensaje("No existen los tipos de movimiento del traslado (TRS y TRE).");
			return false;
		}

		$this->db->trans_begin();

		if (!$this->bloquear(self::ENVIADO)) {
			return $this->cancelar("El traslado no está pendiente de recibir.");
		}

		# Lo que salió de cada lote del origen (los TRS se guardan negativos)
		$lotes = $this->db
		->select("
			b.id as inventario_traslado_detalle_id,
			b.producto_id,
			b.unidad_medida_id,
			b.producto_presentacion_id,
			c.fecha_vence,
			-sum(a.cantidad) as cantidad", false)
		->from("movimiento a")
		->join("inventario_traslado_detalle b", "b.id = a.inventario_traslado_detalle_id")
		->join("stock c", "c.id = a.stock_id")
		->where("b.inventario_traslado_id", $this->getPK())
		->where("a.movimiento_tipo_id", $salida->id)
		->group_by([
			"b.id",
			"b.producto_id",
			"b.unidad_medida_id",
			"b.producto_presentacion_id",
			"c.id",
			"c.fecha_vence"
		])
		->having("sum(a.cantidad) <>", 0)
		->get()
		->result();

		$info = $this->getInfo();

		foreach ($lotes as $lote) {
			$stock = new Stock_model();
			$stockId = $stock->sumar([
				"sucursal_id" => $this->sucursal_destino_id,
				"producto_id" => $lote->producto_id,
				"unidad_medida_id" => $lote->unidad_medida_id,
				"producto_presentacion_id" => $lote->producto_presentacion_id,
				"fecha_vence" => $lote->fecha_vence,
				"cantidad" => $lote->cantidad
			]);

			$mov = new Movimiento_model();
			$mov->guardar([
				"stock_id" => $stockId,
				"movimiento_tipo_id" => $tipo->id,
				"cantidad" => (float)$lote->cantidad,
				"inventario_traslado_detalle_id" => $lote->inventario_traslado_detalle_id,
				"observacion" => "Traslado {$this->numero} de {$info->nsucursal}"
			]);
		}

		$this->db
		->set("inventario_traslado_estado_id", self::RECIBIDO)
		->set("fecha_recibido", "now()", false)
		->set("usuario_recibio_id", $this->_ses->id)
		->where("id", $this->getPK())
		->update($this->_tabla);

		$this->notificar($this->sucursal_id, [
			"tipo" => "exito",
			"icono" => "fa-solid fa-check",
			"texto" => "Traslado {$this->numero} recibido en {$info->nsucursal_destino}"
		]);

		return $this->terminar("No se pudo recibir el traslado, intente nuevamente.");
	}

	/**
	 * Anula el traslado desde el origen. Un borrador solo cambia de estado. Uno enviado devuelve a
	 * cada lote del origen lo que salió, con ATS, y avisa al destino. Uno recibido no se anula.
	 */
	public function anular($motivo)
	{
		$estado = (int)$this->inventario_traslado_estado_id;

		if ($estado === self::ANULADO) {
			$this->setMensaje("El traslado ya está anulado.");
			return false;
		}

		if ($estado === self::RECIBIDO) {
			$this->setMensaje("El traslado ya fue recibido; para devolver el producto haga un traslado de regreso.");
			return false;
		}

		$this->db->trans_begin();

		if (!$this->bloquear($estado)) {
			return $this->cancelar("El traslado cambió de estado, vuelva a abrirlo.");
		}

		$info = $this->getInfo();

		if ($estado === self::ENVIADO) {
			$anulacion = $this->tipoMovimiento("ATS");

			if (!$anulacion) {
				return $this->cancelar("No existe el tipo de movimiento de anulación (ATS).");
			}

			$netos = $this->db
			->select("
				a.stock_id,
				a.inventario_traslado_detalle_id,
				sum(a.cantidad) as neto", false)
			->from("movimiento a")
			->join("inventario_traslado_detalle b", "b.id = a.inventario_traslado_detalle_id")
			->join("stock c", "c.id = a.stock_id")
			->where("b.inventario_traslado_id", $this->getPK())
			->where("c.sucursal_id", $this->sucursal_id)
			->group_by([
				"a.stock_id",
				"a.inventario_traslado_detalle_id"
			])
			->having("sum(a.cantidad) <>", 0)
			->get()
			->result();

			foreach ($netos as $neto) {
				$reverso = -(float)$neto->neto;

				$this->db
				->set("cantidad", "cantidad + " . $reverso, false)
				->where("id", $neto->stock_id)
				->update("stock");

				$mov = new Movimiento_model();
				$mov->guardar([
					"stock_id" => $neto->stock_id,
					"movimiento_tipo_id" => $anulacion->id,
					"cantidad" => $reverso,
					"inventario_traslado_detalle_id" => $neto->inventario_traslado_detalle_id,
					"observacion" => "Anulación del traslado {$this->numero}"
				]);
			}

			$this->notificar($this->sucursal_destino_id, [
				"tipo" => "info",
				"icono" => "fa-solid fa-ban",
				"texto" => "Traslado {$this->numero} anulado por {$info->nsucursal}"
			]);
		}

		$this->db
		->set("inventario_traslado_estado_id", self::ANULADO)
		->set("fecha_anulado", "now()", false)
		->set("usuario_anulo_id", $this->_ses->id)
		->set("anulado_motivo", $motivo)
		->where("id", $this->getPK())
		->update($this->_tabla);

		return $this->terminar("No se pudo anular el traslado, intente nuevamente.");
	}

	# Bloquea el traslado (dos usuarios no lo cambian a la vez) y confirma que sigue en el estado esperado
	private function bloquear($estado)
	{
		$actual = $this->db->query("
			select inventario_traslado_estado_id
			from {$this->_tabla}
			where id = ?
			for update", [$this->getPK()])
		->row();

		return $actual && (int)$actual->inventario_traslado_estado_id === (int)$estado;
	}

	private function tipoMovimiento($codigo)
	{
		return $this->catalogo->verMovimientoTipos([
			"codigo" => $codigo,
			"_uno" => true
		]);
	}

	# Retira los avisos anteriores del traslado y deja uno nuevo para la sucursal indicada
	private function notificar($sucursalId, $args)
	{
		$notificacion = new Notificacion_model();
		$notificacion->cerrarDocumento(self::RUTA, $this->getPK());
		$notificacion->crear(array_merge($args, [
			"sucursal_id" => $sucursalId,
			"ruta" => self::RUTA,
			"documento_id" => $this->getPK()
		]));
	}

	private function terminar($mensaje)
	{
		if ($this->db->trans_status() === false) {
			return $this->cancelar($mensaje);
		}

		$this->db->trans_commit();
		$this->cargar($this->getPK());

		return true;
	}

	private function cancelar($mensaje)
	{
		$this->db->trans_rollback();
		$this->setMensaje($mensaje);

		return false;
	}
}

/* End of file Inventario_traslado_model.php */
/* Location: ./application/models/inv/Inventario_traslado_model.php */
