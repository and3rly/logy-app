<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

# Reporte de ventas por día: solo lectura sobre venta y venta_detalle de la sucursal de la sesión.
# Las ventas anuladas no suman al vendido ni a la ganancia; se cuentan aparte.
class Venta_dia_model extends CI_Model {

	const ANULADA = 4;

	# Filtros comunes: empresa, sucursal, período, forma de pago y vendedor (un no administrador solo ve lo suyo)
	private function filtrar($f)
	{
		$this->db
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.sucursal_id", $this->_ses->sucursal_id);

		if ($f["fdel"]) {
			$this->db->where("a.fecha >=", "{$f['fdel']} 00:00:00");
		}

		if ($f["fal"]) {
			$this->db->where("a.fecha <=", "{$f['fal']} 23:59:59");
		}

		if ($f["forma_pago"]) {
			$this->db->where("a.forma_pago_id", $f["forma_pago"]);
		}

		if ($f["usuario"] && es_administrador()) {
			$this->db->where("a.usuario_id", $f["usuario"]);
		}

		filtrar_por_usuario("a.usuario_id");
	}

	# Un renglón por día con ventas: cantidad, vendido, costo, ganancia y anuladas
	public function porDia($f)
	{
		$anulada = self::ANULADA;

		$this->filtrar($f);

		return $this->db
		->select("
			date(a.fecha) as dia,
			sum(a.venta_estado_id <> {$anulada}) as ventas,
			sum(a.venta_estado_id = {$anulada}) as anuladas,
			sum(if(a.venta_estado_id <> {$anulada}, a.total_precio, 0)) as total,
			sum(if(a.venta_estado_id <> {$anulada}, a.total_costo, 0)) as costo,
			sum(if(a.venta_estado_id <> {$anulada}, a.ganancia, 0)) as ganancia,
			sum(if(a.venta_estado_id = {$anulada}, a.total_precio, 0)) as total_anulado", false)
		->from("venta a")
		->group_by("date(a.fecha)")
		->order_by("dia", "desc")
		->get()
		->result();
	}

	# Productos vendidos en el período (ventas no anuladas), del que más ganancia dejó al que menos
	public function productos($f)
	{
		$this->filtrar($f);

		return $this->db
		->select("
			b.producto_id,
			b.producto_presentacion_id,
			c.codigo as cproducto,
			c.nombre as nproducto,
			d.nombre as npresentacion,
			e.nombre as nunidad,
			sum(b.cantidad) as cantidad,
			sum(b.total_precio) as total,
			sum(b.total_costo) as costo,
			sum(b.ganancia) as ganancia", false)
		->from("venta a")
		->join("venta_detalle b", "b.venta_id = a.id")
		->join("producto c", "c.id = b.producto_id")
		->join("producto_presentacion d", "d.id = b.producto_presentacion_id", "left")
		->join("unidad_medida e", "e.id = b.unidad_medida_id")
		->where("a.venta_estado_id <>", self::ANULADA)
		->where("b.anulado", 0)
		->group_by("b.producto_id, b.producto_presentacion_id, b.unidad_medida_id")
		->order_by("ganancia", "desc")
		->get()
		->result();
	}

	# Vendedores con ventas en la sucursal (para el filtro del administrador)
	public function vendedores()
	{
		return $this->db
		->select("distinct b.id, b.nombre", false)
		->from("venta a")
		->join("usuario b", "b.id = a.usuario_id")
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.sucursal_id", $this->_ses->sucursal_id)
		->order_by("b.nombre")
		->get()
		->result();
	}
}

/* End of file Venta_dia_model.php */
/* Location: ./application/models/rep/Venta_dia_model.php */
