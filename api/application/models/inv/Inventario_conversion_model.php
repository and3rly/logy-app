<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Conversión entre una presentación y la unidad de medida del producto:
# explosión (presentación → unidades) o implosión (unidades → presentación)
class Inventario_conversion_model extends Centro_model {

	const EXPLOSION = "EXPLOSION";
	const IMPLOSION = "IMPLOSION";

	public $numero;
	public $sentido;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id;
	public $factor;
	public $cantidad;
	public $unidades;
	public $costo = 0;
	public $observacion = null;
	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Conversiones de la sucursal de la sesión. Filtros: fdel, fal (fechas), sentido y producto
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

			if (elemento($args, "sentido")) {
				$this->db->where("a.sentido", $args["sentido"]);
			}

			if (elemento($args, "producto")) {
				$this->db->where("a.producto_id", $args["producto"]);
			}
		}

		# Sin rol administrador: solo los documentos del usuario
		filtrar_por_usuario("a.usuario_id");

		$tmp = $this->db
		->select("
			a.*,
			b.codigo as cproducto,
			b.nombre as nproducto,
			c.nombre as nunidad,
			d.nombre as npresentacion,
			e.nombre as nsucursal,
			f.nombre as nusuario,
			round(a.unidades * a.costo, 2) as valor", false)
		->from("inventario_conversion a")
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("producto_presentacion d", "d.id = a.producto_presentacion_id")
		->join("sucursal e", "e.id = a.sucursal_id")
		->join("usuario f", "f.id = a.usuario_id")
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

	/**
	 * Registra la conversión y mueve el stock de la sucursal de la sesión en una sola transacción.
	 * $args: sentido (EXPLOSION: presentación → unidad; IMPLOSION: unidad → presentación), producto_id,
	 * producto_presentacion_id, cantidad (entero, del lado más grande) y observación.
	 * La presentación puede ser más grande que la unidad (Quintal = 100 LB, factor 100) o más pequeña
	 * (Libra con base Quintal, factor 0.01); la cantidad siempre es del lado grande para no partirlo.
	 * Abrir (grande → pequeño): sale lo grande (lo que vence primero) y entra lo pequeño con el mismo vencimiento.
	 * Armar (pequeño → grande): sale lo pequeño (lo que vence primero) y entra lo grande con el vencimiento
	 * más próximo de lo usado. El costo no cambia: la presentación vale el costo × factor.
	 */
	public function convertir($args=[])
	{
		$sentido = elemento($args, "sentido");
		$cantidad = elemento($args, "cantidad");

		$sentidos = [
			self::EXPLOSION,
			self::IMPLOSION
		];

		if (!in_array($sentido, $sentidos, true)) {
			$this->setMensaje("Indique si es explosión o implosión.");
			return false;
		}

		if (!is_numeric($cantidad) || (float)$cantidad != (int)$cantidad || (int)$cantidad < 1) {
			$this->setMensaje("La cantidad debe ser un número entero mayor que cero.");
			return false;
		}

		$cantidad = (int)$cantidad;

		$producto = $this->db
		->where("id", elemento($args, "producto_id"))
		->where("empresa_id", $this->_ses->empresa_id)
		->where("tipo_producto", "B")
		->where("activo", 1)
		->get("producto")
		->row();

		if (!$producto) {
			$this->setMensaje("El producto no existe o está inactivo.");
			return false;
		}

		$presentacion = $this->db
		->where("id", elemento($args, "producto_presentacion_id"))
		->where("producto_id", $producto->id)
		->get("producto_presentacion")
		->row();

		# Una presentación inactiva solo se puede abrir (para sacar la existencia que le queda)
		if (!$presentacion || ((int)$presentacion->activo === 0 && $sentido === self::IMPLOSION)) {
			$this->setMensaje("La presentación no existe o está inactiva.");
			return false;
		}

		$factor = (float)$presentacion->factor;

		if ($factor <= 0) {
			$this->setMensaje("La presentación {$presentacion->nombre} no tiene un factor válido.");
			return false;
		}

		# Más pequeña que la unidad: cuántas trae una unidad (factor 0.01 → 100)
		$menor = $factor < 1;
		$porUnidad = $menor ? round(1 / $factor) : 0;

		# El factor debe devolver exacto cuántas trae una unidad; si no, la conversión descuadraría
		if ($menor && ($porUnidad < 2 || abs(round(1 / $porUnidad, 5) - $factor) > 0.000001)) {
			$this->setMensaje("La presentación {$presentacion->nombre} no tiene un factor válido.");
			return false;
		}

		# La cantidad es del lado grande: presentaciones, o unidades si la presentación es más pequeña
		$unidades = $menor ? $cantidad : round($cantidad * $factor, 2);
		$presentaciones = $menor ? $cantidad * $porUnidad : $cantidad;
		$observacion = trim((string)elemento($args, "observacion", ""));

		$salida = $this->catalogo->verMovimientoTipos([
			"codigo" => "CVS",
			"_uno" => true
		]);

		$entrada = $this->catalogo->verMovimientoTipos([
			"codigo" => "CVE",
			"_uno" => true
		]);

		if (!$salida || !$entrada) {
			$this->setMensaje("No existen los tipos de movimiento de conversión (CVS y CVE).");
			return false;
		}

		$this->db->trans_begin();

		$this->asignarNumero();

		$guardado = $this->guardar([
			"sentido" => $sentido,
			"producto_id" => $producto->id,
			"unidad_medida_id" => $producto->unidad_medida_id,
			"producto_presentacion_id" => $presentacion->id,
			"factor" => $factor,
			"cantidad" => $presentaciones,
			"unidades" => $unidades,
			"costo" => (float)$producto->costo,
			"observacion" => $observacion === "" ? null : mb_substr($observacion, 0, 300)
		]);

		if (!$guardado) {
			return $this->cancelar("No se pudo registrar la conversión, intente nuevamente.");
		}

		$explosion = $sentido === self::EXPLOSION;
		$base = [
			"sucursal_id" => $this->sucursal_id,
			"producto_id" => $producto->id,
			"unidad_medida_id" => $producto->unidad_medida_id
		];

		# Origen y destino: la presentación (explosión sale de ella) o la unidad (implosión sale de ella)
		$origen = [
			"producto_presentacion_id" => $explosion ? $presentacion->id : null,
			"cantidad" => $explosion ? $presentaciones : $unidades
		];
		$destino = [
			"producto_presentacion_id" => $explosion ? null : $presentacion->id,
			"cantidad" => $explosion ? $unidades : $presentaciones
		];

		# Abrir: de lo grande a lo pequeño
		$abrir = $explosion !== $menor;

		$stock = new Stock_model();
		$lotes = $stock->descontar($base + $origen);

		if ($lotes === false) {
			$falta = $explosion ? $presentacion->nombre : $this->nombreUnidad($producto->unidad_medida_id);
			return $this->cancelar("No hay existencia suficiente de {$producto->nombre} ({$falta}) para convertir.");
		}

		$vencimientos = $this->vencimientos(array_keys($lotes));
		$detalle = $explosion ? "Explosión" : "Implosión";
		$texto = "{$detalle} {$this->numero}";

		foreach ($lotes as $stockId => $sale) {
			$this->movimiento($stockId, $salida->id, -$sale, $texto);
		}

		if ($abrir) {
			# Cada lote abierto entra en la medida pequeña con su mismo vencimiento
			$porLote = $destino["cantidad"] / $origen["cantidad"];

			foreach ($lotes as $stockId => $sale) {
				$entra = round($sale * $porLote, 2);
				$lote = (new Stock_model())->sumar($base + [
					"producto_presentacion_id" => $destino["producto_presentacion_id"],
					"fecha_vence" => $vencimientos[$stockId],
					"cantidad" => $entra
				]);

				$this->movimiento($lote, $entrada->id, $entra, $texto);
			}
		} else {
			# Lo armado vence cuando vence lo primero que se usó
			$fechas = array_filter($vencimientos);
			$lote = (new Stock_model())->sumar($base + [
				"producto_presentacion_id" => $destino["producto_presentacion_id"],
				"fecha_vence" => count($fechas) > 0 ? min($fechas) : null,
				"cantidad" => $destino["cantidad"]
			]);

			$this->movimiento($lote, $entrada->id, $destino["cantidad"], $texto);
		}

		if ($this->db->trans_status() === false) {
			return $this->cancelar("No se pudo registrar la conversión, intente nuevamente.");
		}

		$this->db->trans_commit();

		return true;
	}

	# Correlativo por empresa, ej. CNV-000001
	private function asignarNumero()
	{
		$tmp = $this->db
		->select("count(*) + 1 as siguiente", false)
		->where("empresa_id", $this->_ses->empresa_id)
		->get($this->_tabla)
		->row();

		$this->numero = "CNV-" . str_pad($tmp->siguiente, 6, "0", STR_PAD_LEFT);
	}

	# [stock_id => fecha_vence] de los lotes indicados
	private function vencimientos($ids)
	{
		$tmp = $this->db
		->select("id, fecha_vence")
		->where_in("id", $ids)
		->get("stock")
		->result();

		$lista = [];

		foreach ($tmp as $reg) {
			$lista[$reg->id] = $reg->fecha_vence;
		}

		return $lista;
	}

	private function nombreUnidad($id)
	{
		$tmp = $this->db
		->select("nombre")
		->where("id", $id)
		->get("unidad_medida")
		->row();

		return $tmp ? $tmp->nombre : "unidad de medida";
	}

	private function movimiento($stockId, $tipoId, $cantidad, $observacion)
	{
		$mov = new Movimiento_model();
		$mov->guardar([
			"stock_id" => $stockId,
			"movimiento_tipo_id" => $tipoId,
			"cantidad" => $cantidad,
			"inventario_conversion_id" => $this->getPK(),
			"observacion" => $observacion
		]);
	}

	private function cancelar($mensaje)
	{
		$this->db->trans_rollback();
		$this->setPK(null);
		$this->setMensaje($mensaje);

		return false;
	}
}

/* End of file Inventario_conversion_model.php */
/* Location: ./application/models/inv/Inventario_conversion_model.php */
