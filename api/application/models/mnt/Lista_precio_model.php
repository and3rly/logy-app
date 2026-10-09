<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Lista de precios: precio fijo por producto (unidad de medida) o presentación, asignada al cliente.
# Lo que no está en la lista se vende al precio general (producto.precio por el factor).
class Lista_precio_model extends Centro_model {

	public $nombre;
	public $descripcion = null;
	public $activo = 1;
	public $empresa_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	# Otra lista activa de la empresa con el mismo nombre
	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->where("nombre", trim(verPropiedad($args, "nombre", "")))
		->where("activo", 1)
		->get($this->_tabla);

		return $tmp->num_rows() > 0;
	}

	public function esDeLaEmpresa()
	{
		return $this->getPK() && (int)$this->empresa_id === (int)$this->_ses->empresa_id;
	}

	# Listas de la empresa con cuántos precios tienen y cuántos clientes activos la usan
	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			(select count(*) from lista_precio_detalle b where b.lista_precio_id = a.id) as precios,
			(select count(*) from cliente c where c.lista_precio_id = a.id and c.activo = 1) as clientes", false)
		->from("{$this->_tabla} a")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->order_by("a.nombre", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	/**
	 * Precios de la lista: [{producto_id, producto_presentacion_id, precio}].
	 * Una lista inactiva no tiene precios: se vende al precio general.
	 */
	public function precios()
	{
		if (!$this->esDeLaEmpresa() || (int)$this->activo !== 1) {
			return [];
		}

		return $this->db
		->select("
			producto_id,
			producto_presentacion_id,
			round(precio, 2) as precio", false)
		->where("lista_precio_id", $this->getPK())
		->get("lista_precio_detalle")
		->result();
	}

	# Precio de la lista para el producto en esa presentación (null = unidad); null si no está en la lista
	public function precioDe($productoId, $presentacionId=null)
	{
		if (!$this->esDeLaEmpresa() || (int)$this->activo !== 1) {
			return null;
		}

		if ($presentacionId) {
			$this->db->where("producto_presentacion_id", $presentacionId);
		} else {
			$this->db->where("producto_presentacion_id IS NULL", null, false);
		}

		$tmp = $this->db
		->select("precio")
		->where("lista_precio_id", $this->getPK())
		->where("producto_id", $productoId)
		->get("lista_precio_detalle")
		->row();

		return $tmp ? round((float)$tmp->precio, 2) : null;
	}

	/**
	 * Para la pantalla de precios: cada producto activo (bien) en su unidad de medida y en cada
	 * presentación activa, con su costo, el precio general y el de esta lista (null si no está).
	 */
	public function articulos()
	{
		$productos = $this->db
		->select("
			a.id as producto_id,
			a.codigo,
			a.nombre,
			a.categoria_id,
			ifnull(a.costo, 0) as costo,
			ifnull(a.precio, 0) as precio_general,
			b.nombre as nunidad,
			c.nombre as ncategoria", false)
		->from("producto a")
		->join("unidad_medida b", "b.id = a.unidad_medida_id")
		->join("categoria c", "c.id = a.categoria_id", "left")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.tipo_producto", "B")
		->where("a.activo", 1)
		->order_by("a.nombre", "asc")
		->get()
		->result();

		$presentaciones = [];

		foreach ($this->catalogo->verPresentaciones() as $pre) {
			$presentaciones[$pre->producto_id][] = $pre;
		}

		$precios = [];

		foreach ($this->db->where("lista_precio_id", $this->getPK())->get("lista_precio_detalle")->result() as $fila) {
			$precios[$this->llave($fila->producto_id, $fila->producto_presentacion_id)] = round((float)$fila->precio, 2);
		}

		$lista = [];

		foreach ($productos as $p) {
			# Primero la unidad de medida y después cada presentación con precio y costo por su factor
			$filas = [(object)[
				"producto_presentacion_id" => null,
				"npresentacion" => "",
				"factor" => 1
			]];

			foreach (elemento($presentaciones, $p->producto_id, []) as $pre) {
				$filas[] = (object)[
					"producto_presentacion_id" => $pre->id,
					"npresentacion" => $pre->nombre,
					"factor" => (float)$pre->factor
				];
			}

			foreach ($filas as $f) {
				$lista[] = [
					"producto_id" => $p->producto_id,
					"producto_presentacion_id" => $f->producto_presentacion_id,
					"codigo" => $p->codigo,
					"nombre" => $p->nombre,
					"categoria_id" => $p->categoria_id,
					"ncategoria" => $p->ncategoria,
					"nunidad" => $p->nunidad,
					"npresentacion" => $f->npresentacion,
					"costo" => round((float)$p->costo * $f->factor, 2),
					"precio_general" => round((float)$p->precio_general * $f->factor, 2),
					"precio" => elemento($precios, $this->llave($p->producto_id, $f->producto_presentacion_id), null)
				];
			}
		}

		return $lista;
	}

	/**
	 * Reemplaza los precios de la lista por los enviados: [{producto_id, producto_presentacion_id, precio}].
	 * Lo que no se envía sale de la lista. Ningún precio puede quedar bajo el costo.
	 */
	public function guardarPrecios($lineas)
	{
		$filas = [];
		$bajoCosto = [];

		foreach ((array)$lineas as $linea) {
			$productoId = (int)verPropiedad($linea, "producto_id", 0);
			$presentacionId = (int)verPropiedad($linea, "producto_presentacion_id", 0) ?: null;
			$precio = verPropiedad($linea, "precio", null);

			if ($precio === null || trim((string)$precio) === "") {
				continue;
			}

			if (!is_numeric($precio) || (float)$precio <= 0) {
				$this->setMensaje("Los precios deben ser mayores a cero.");
				return false;
			}

			$producto = $this->catalogo->verProductos([
				"id" => $productoId,
				"_uno" => true
			]);

			if (!$producto || $producto->tipo_producto !== "B") {
				$this->setMensaje("Uno de los productos no existe o está inactivo.");
				return false;
			}

			$factor = 1;
			$nombre = $producto->nombre;

			if ($presentacionId) {
				$presentacion = $this->catalogo->verPresentacion($producto->id, $presentacionId);

				if (!$presentacion) {
					$this->setMensaje("La presentación de {$producto->nombre} no existe o está inactiva.");
					return false;
				}

				$factor = (float)$presentacion->factor;
				$nombre .= " ({$presentacion->nombre})";
			}

			$precio = round((float)$precio, 2);
			$costo = round((float)$producto->costo * $factor, 2);

			if ($precio < $costo) {
				$bajoCosto[] = "{$nombre}: costo " . monto($costo);
			}

			# Si llega repetido queda el último
			$filas[$this->llave($producto->id, $presentacionId)] = [
				"lista_precio_id" => $this->getPK(),
				"producto_id" => $producto->id,
				"producto_presentacion_id" => $presentacionId,
				"precio" => $precio,
				"usuario_id" => $this->_ses->id
			];
		}

		if (count($bajoCosto) > 0) {
			$mas = count($bajoCosto) > 3 ? " y " . (count($bajoCosto) - 3) . " más" : "";
			$this->setMensaje("El precio no puede ser menor al costo. " . implode("; ", array_slice($bajoCosto, 0, 3)) . "{$mas}.");
			return false;
		}

		$this->db->trans_begin();

		# La lista queda bloqueada: dos usuarios no la reemplazan a la vez
		$this->db->query("
			select id
			from {$this->_tabla}
			where id = ?
			for update", [
				$this->getPK()
			]);

		# Solo se toca lo que cambió: así "fecha" queda como el último cambio de cada precio
		$actuales = $this->db
		->where("lista_precio_id", $this->getPK())
		->get("lista_precio_detalle")
		->result();

		foreach ($actuales as $actual) {
			$llave = $this->llave($actual->producto_id, $actual->producto_presentacion_id);

			if (!isset($filas[$llave])) {
				# Sin precio el artículo sale de la lista (vuelve al precio general)
				$this->db
				->where("id", $actual->id)
				->delete("lista_precio_detalle");
			} else {
				if (round((float)$actual->precio, 2) !== $filas[$llave]["precio"]) {
					$this->db
					->set("precio", $filas[$llave]["precio"])
					->set("usuario_id", $this->_ses->id)
					->where("id", $actual->id)
					->update("lista_precio_detalle");
				}

				unset($filas[$llave]);
			}
		}

		if (count($filas) > 0) {
			$this->db->insert_batch("lista_precio_detalle", array_values($filas));
		}

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$this->setMensaje("No se pudieron guardar los precios, intente nuevamente.");
			return false;
		}

		$this->db->trans_commit();
		return true;
	}

	private function llave($productoId, $presentacionId)
	{
		return "{$productoId}-" . ($presentacionId ?: 0);
	}
}

/* End of file Lista_precio_model.php */
/* Location: ./application/models/mnt/Lista_precio_model.php */
