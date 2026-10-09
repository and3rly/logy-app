<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Unidad_medida_model extends Centro_model {

	public $codigo;
	public $nombre;
	public $activo = 1;
	public $empresa_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("codigo", $args->codigo)
		->where("activo", 1)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}

	/**
	 * Equivalencias de una unidad, en los dos sentidos (ella como grande o como menor).
	 * Cada fila: 1 unidad_medida_id (ngrande) = cantidad unidad_menor_id (nmenor).
	 */
	public function equivalencias($unidadId)
	{
		return $this->db
		->select("
			a.id,
			a.unidad_medida_id,
			a.unidad_menor_id,
			a.cantidad,
			a.activo,
			b.nombre as ngrande,
			b.codigo as cgrande,
			c.nombre as nmenor,
			c.codigo as cmenor", false)
		->from("unidad_equivalencia a")
		->join("unidad_medida b", "b.id = a.unidad_medida_id")
		->join("unidad_medida c", "c.id = a.unidad_menor_id")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->group_start()
		->where("a.unidad_medida_id", $unidadId)
		->or_where("a.unidad_menor_id", $unidadId)
		->group_end()
		->order_by("a.activo", "desc")
		->order_by("b.nombre", "asc")
		->order_by("c.nombre", "asc")
		->get()
		->result();
	}

	/**
	 * Crea o edita una equivalencia: 1 unidad_medida_id = cantidad unidad_menor_id.
	 * Devuelve la fila guardada o false (el motivo queda en getMensaje()).
	 */
	public function guardarEquivalencia($id, $datos)
	{
		$grande = (int)verPropiedad($datos, "unidad_medida_id", 0);
		$menor = (int)verPropiedad($datos, "unidad_menor_id", 0);
		$cantidad = round((float)verPropiedad($datos, "cantidad", 0), 5);
		$activo = (int)verPropiedad($datos, "activo", 1) === 1 ? 1 : 0;

		$actual = null;

		if ($id) {
			$actual = $this->db
			->where("id", $id)
			->where("empresa_id", $this->_ses->empresa_id)
			->get("unidad_equivalencia")
			->row();

			if (!$actual) {
				$this->setMensaje("La equivalencia no existe.");
				return false;
			}
		}

		$unidades = $this->db
		->where_in("id", [$grande, $menor])
		->where("empresa_id", $this->_ses->empresa_id)
		->where("activo", 1)
		->count_all_results($this->_tabla);

		if ($grande === $menor || $unidades !== 2) {
			$this->setMensaje("Elija otra unidad de medida activa.");
			return false;
		}

		if ($cantidad <= 1) {
			$this->setMensaje("La cantidad debe ser mayor que 1: escriba la equivalencia desde la unidad grande (1 Quintal = 100 Libras).");
			return false;
		}

		# Un solo registro por par, en cualquiera de los dos sentidos
		if ($id) {
			$this->db->where("id <>", $id);
		}

		$repetida = $this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->group_start()
			->group_start()
			->where("unidad_medida_id", $grande)
			->where("unidad_menor_id", $menor)
			->group_end()
			->or_group_start()
			->where("unidad_medida_id", $menor)
			->where("unidad_menor_id", $grande)
			->group_end()
		->group_end()
		->count_all_results("unidad_equivalencia");

		if ($repetida > 0) {
			$this->setMensaje("Ya existe una equivalencia entre esas dos unidades.");
			return false;
		}

		# Los productos copian el factor al crear la presentación: si ya se usó, solo se puede activar o desactivar
		$cambia = $actual && (
			(int)$actual->unidad_medida_id !== $grande ||
			(int)$actual->unidad_menor_id !== $menor ||
			round((float)$actual->cantidad, 5) !== $cantidad
		);

		if ($cambia && $this->equivalenciaEnUso($actual->unidad_medida_id, $actual->unidad_menor_id)) {
			$this->setMensaje("Hay productos con presentaciones de esta equivalencia: solo se puede activar o desactivar.");
			return false;
		}

		$fila = [
			"unidad_medida_id" => $grande,
			"unidad_menor_id" => $menor,
			"cantidad" => $cantidad,
			"activo" => $activo
		];

		if ($actual) {
			$this->db
			->where("id", $actual->id)
			->update("unidad_equivalencia", $fila);
			$id = $actual->id;
		} else {
			$fila["empresa_id"] = $this->_ses->empresa_id;
			$this->db->insert("unidad_equivalencia", $fila);
			$id = $this->db->insert_id();
		}

		foreach ($this->equivalencias($grande) as $eq) {
			if ((int)$eq->id === (int)$id) {
				return $eq;
			}
		}

		$this->setMensaje("No pude guardar los datos, por favor intente nuevamente.");
		return false;
	}

	/**
	 * Unidades que pueden ser presentación de un producto con esta unidad de medida, con su factor
	 * (unidades de medida que trae 1 presentación): mayor que 1 si es más grande, 1 / cantidad si es menor.
	 * Una menor solo sirve si cabe un número entero de veces en la unidad (1 Quintal = 100 Libras).
	 */
	public function presentacionesPosibles($unidadId)
	{
		$lista = [];

		foreach ($this->equivalencias($unidadId) as $eq) {
			if ((int)$eq->activo !== 1) {
				continue;
			}

			$esGrande = (int)$eq->unidad_menor_id === (int)$unidadId;
			$cantidad = (float)$eq->cantidad;

			# Entera y que se recupere exacta del factor de 5 decimales (1 / 320 = 0.00313 daría 319 al convertir)
			$entera = abs($cantidad - round($cantidad)) < 0.00001 &&
				(int)round(1 / round(1 / $cantidad, 5)) === (int)round($cantidad);

			$lista[] = (object)[
				"unidad_medida_id" => $esGrande ? $eq->unidad_medida_id : $eq->unidad_menor_id,
				"nombre" => $esGrande ? $eq->ngrande : $eq->nmenor,
				"codigo" => $esGrande ? $eq->cgrande : $eq->cmenor,
				"cantidad" => $cantidad,
				"mayor" => $esGrande,
				"factor" => $esGrande ? round($cantidad, 5) : ($entera ? round(1 / $cantidad, 5) : null)
			];
		}

		return $lista;
	}

	# Alguna presentación de producto usa la equivalencia (el producto en una unidad y la presentación en la otra)
	private function equivalenciaEnUso($grande, $menor)
	{
		$tmp = $this->db
		->from("producto_presentacion a")
		->join("producto b", "b.id = a.producto_id")
		->group_start()
			->group_start()
			->where("b.unidad_medida_id", $grande)
			->where("a.unidad_medida_id", $menor)
			->group_end()
			->or_group_start()
			->where("b.unidad_medida_id", $menor)
			->where("a.unidad_medida_id", $grande)
			->group_end()
		->group_end()
		->count_all_results();

		return $tmp > 0;
	}
}

/* End of file Unidad_medida_model.php */
/* Location: ./application/models/mnt/Unidad_medida_model.php */
