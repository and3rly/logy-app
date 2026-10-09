<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Catalogo_model extends CI_Model {

	public function verPaises($args=[])
	{
		return $this->consultar("pais", $args);
	}

	public function verDepartamentos($args=[])
	{
		return $this->consultar("departamento", $args);
	}

	public function verMunicipios($args=[])
	{
		return $this->consultar("municipio", $args);
	}

	public function verMonedas($args=[])
	{
		return $this->consultar("moneda", $args, true);
	}

	public function verUnidadesMedida($args=[])
	{
		return $this->consultar("unidad_medida", $args, true);
	}

	public function verMarcas($args=[])
	{
		return $this->consultar("marca", $args, true);
	}

	public function verCategorias($args=[])
	{
		return $this->consultar("categoria", $args, true);
	}

	public function verProveedores($args=[])
	{
		return $this->consultar("proveedor", $args, true);
	}

	public function verProductos($args=[])
	{
		return $this->consultar("producto", $args, true);
	}

	# Presentaciones activas de los productos de la empresa (o las de un producto con "producto_id")
	public function verPresentaciones($args=[])
	{
		if (elemento($args, "producto_id")) {
			$this->db->where("a.producto_id", $args["producto_id"]);
		}

		return $this->db
		->select("
			a.id,
			a.nombre,
			a.factor,
			a.producto_id", false)
		->from("producto_presentacion a")
		->join("producto b", "b.id = a.producto_id")
		->where("b.empresa_id", $this->_ses->empresa_id)
		->where("a.activo", 1)
		->order_by("a.producto_id", "asc")
		->order_by("a.factor", "asc")
		->get()
		->result();
	}

	# Presentación activa de ese producto; null si no existe, está inactiva o es de otro producto
	public function verPresentacion($productoId, $presentacionId)
	{
		return $this->db
		->where("id", $presentacionId)
		->where("producto_id", $productoId)
		->where("activo", 1)
		->get("producto_presentacion")
		->row();
	}

	public function verRoles($args=[])
	{
		return $this->consultar("rol", $args);
	}

	public function verSucursales($args=[])
	{
		return $this->consultar("sucursal", $args, true);
	}

	public function verFormasPago($args=[])
	{
		return $this->consultar("forma_pago", $args, true);
	}

	public function verCompraEstados($args=[])
	{
		return $this->consultar("compra_estado", $args, true);
	}

	public function verClientes($args=[])
	{
		return $this->consultar("cliente", $args, true);
	}

	public function verListasPrecio($args=[])
	{
		return $this->consultar("lista_precio", $args, true);
	}

	# Búsqueda de clientes activos por nombre, razón social, NIT o código (buscador del punto de venta)
	public function buscarClientes($termino, $limite=8)
	{
		return $this->db
		->select("id, nombre, razon_social, identificacion, codigo, direccion, credito, credito_limite, credito_dias, lista_precio_id")
		->where("empresa_id", $this->_ses->empresa_id)
		->where("activo", 1)
		->group_start()
		->like("nombre", $termino)
		->or_like("razon_social", $termino)
		->or_like("identificacion", $termino)
		->or_like("codigo", $termino)
		->group_end()
		->order_by("nombre", "asc")
		->limit($limite)
		->get("cliente")
		->result();
	}

	public function verVentaSeries($args=[])
	{
		return $this->consultar("venta_serie", $args, true);
	}

	# venta_estado es general (no tiene empresa_id)
	public function verVentaEstados($args=[])
	{
		return $this->consultar("venta_estado", $args);
	}

	public function verCotizacionSeries($args=[])
	{
		return $this->consultar("cotizacion_serie", $args, true);
	}

	# cotizacion_estado es por empresa; se identifica por su código (BORRADOR, ENVIADA...)
	public function verCotizacionEstados($args=[])
	{
		return $this->consultar("cotizacion_estado", $args, true);
	}

	public function verMovimientoTipos($args=[])
	{
		return $this->consultar("movimiento_tipo", $args, true);
	}

	# Tipos de ajuste con el sentido de su tipo de movimiento (ENTRADA suma, SALIDA resta)
	public function verAjusteTipos($args=[])
	{
		if (!isset($args["_todos"])) {
			$this->db->where("a.activo", 1);
		}

		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "codigo")) {
			$this->db->where("a.codigo", $args["codigo"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.codigo as cmovimiento,
			b.sentido")
		->from("inventario_ajuste_tipo a")
		->join("movimiento_tipo b", "b.id = a.movimiento_tipo_id")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->order_by("a.nombre", "asc")
		->get();

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}

	public function verAjusteEstados($args=[])
	{
		return $this->consultar("inventario_ajuste_estado", $args, true);
	}

	public function verTrasladoEstados($args=[])
	{
		return $this->consultar("inventario_traslado_estado", $args, true);
	}

	# Parámetros de la empresa de la sesión (abreviaturas de correlativos, moneda por defecto)
	public function verEmpresaParametro()
	{
		return $this->db
		->where("empresa_id", $this->_ses->empresa_id)
		->where("activo", 1)
		->get("empresa_parametro")
		->row();
	}

	private function consultar($tabla, $args=[], $porEmpresa=false)
	{
		if ($porEmpresa) {
			$this->db->where("empresa_id", $this->_ses->empresa_id);
		}

		if (!isset($args["_todos"])) {
			$this->db->where("activo", 1);
		}

		foreach ($args as $campo => $valor) {
			if ($campo[0] !== "_") {
				$this->db->where($campo, $valor);
			}
		}

		$tmp = $this->db
		->order_by("nombre", "asc")
		->get($tabla);

		return isset($args["_uno"]) ? $tmp->row() : $tmp->result();
	}
}

/* End of file Catalogo_model.php */
/* Location: ./application/models/Catalogo_model.php */
