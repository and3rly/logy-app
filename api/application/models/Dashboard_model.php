<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Indicadores del inicio (dashboard) de la sucursal de la sesión.
 * Las fechas se calculan con las de MySQL (curdate) porque las ventas se guardan con NOW().
 * Las ventas anuladas no cuentan en ningún indicador.
 */
class Dashboard_model extends CI_Model {

	const DIAS_AVISO_VENCE = 30;

	public function __construct()
	{
		parent::__construct();
	}

	private function empresa()
	{
		return (int)$this->_ses->empresa_id;
	}

	private function sucursal()
	{
		return (int)$this->_ses->sucursal_id;
	}

	# Total vendido, número de ventas y ganancia entre dos fechas (incluidas)
	public function ventasEntre($del, $al)
	{
		$tmp = $this->db
		->select("
			count(*) as ventas,
			ifnull(sum(total_precio), 0) as total,
			ifnull(sum(ganancia), 0) as ganancia", false)
		->where("empresa_id", $this->empresa())
		->where("sucursal_id", $this->sucursal())
		->where("anulado", 0)
		->where("fecha >=", "{$del} 00:00:00")
		->where("fecha <=", "{$al} 23:59:59")
		->get("venta")
		->row();

		return [
			"ventas" => (int)$tmp->ventas,
			"total" => (float)$tmp->total,
			"ganancia" => (float)$tmp->ganancia
		];
	}

	# Fechas de referencia: hoy, ayer, inicio del mes y el mismo tramo del mes anterior
	public function fechas()
	{
		return $this->db
		->select("
			curdate() as hoy,
			curdate() - interval 1 day as ayer,
			date_format(curdate(), '%Y-%m-01') as mes_inicio,
			date_format(curdate() - interval 1 month, '%Y-%m-01') as anterior_inicio,
			curdate() - interval 1 month as anterior_al", false)
		->get()
		->row();
	}

	# Venta por día de los últimos $dias días (los días sin ventas van en 0)
	public function ventasPorDia($dias = 30)
	{
		$lista = $this->db
		->select("
			date(fecha) as dia,
			count(*) as ventas,
			sum(total_precio) as total", false)
		->where("empresa_id", $this->empresa())
		->where("sucursal_id", $this->sucursal())
		->where("anulado", 0)
		->where("fecha >= curdate() - interval " . ($dias - 1) . " day", null, false)
		->group_by("date(fecha)")
		->get("venta")
		->result();

		$porDia = [];

		foreach ($lista as $reg) {
			$porDia[$reg->dia] = $reg;
		}

		$hoy = new DateTime($this->fechas()->hoy);
		$datos = [];

		for ($i = $dias - 1; $i >= 0; $i--) {
			$dia = (clone $hoy)->modify("-{$i} day")->format("Y-m-d");

			$datos[] = [
				"fecha" => $dia,
				"ventas" => isset($porDia[$dia]) ? (int)$porDia[$dia]->ventas : 0,
				"total" => isset($porDia[$dia]) ? (float)$porDia[$dia]->total : 0
			];
		}

		return $datos;
	}

	# Venta de hoy por hora (0 a 23; las horas sin ventas van en 0)
	public function ventasPorHora()
	{
		$lista = $this->db
		->select("
			hour(fecha) as hora,
			count(*) as ventas,
			sum(total_precio) as total", false)
		->where("empresa_id", $this->empresa())
		->where("sucursal_id", $this->sucursal())
		->where("anulado", 0)
		->where("fecha >= curdate()", null, false)
		->group_by("hour(fecha)")
		->get("venta")
		->result();

		$datos = [];

		for ($i = 0; $i < 24; $i++) {
			$datos[$i] = [
				"hora" => $i,
				"ventas" => 0,
				"total" => 0
			];
		}

		foreach ($lista as $reg) {
			$datos[(int)$reg->hora]["ventas"] = (int)$reg->ventas;
			$datos[(int)$reg->hora]["total"] = (float)$reg->total;
		}

		return $datos;
	}

	# Venta por mes de los últimos $meses meses, incluido el actual (los meses sin ventas van en 0)
	public function ventasPorMes($meses = 12)
	{
		$lista = $this->db
		->select("
			date_format(fecha, '%Y-%m') as mes,
			count(*) as ventas,
			sum(total_precio) as total", false)
		->where("empresa_id", $this->empresa())
		->where("sucursal_id", $this->sucursal())
		->where("anulado", 0)
		->where("fecha >= date_format(curdate() - interval " . ($meses - 1) . " month, '%Y-%m-01')", null, false)
		->group_by("date_format(fecha, '%Y-%m')")
		->get("venta")
		->result();

		$porMes = [];

		foreach ($lista as $reg) {
			$porMes[$reg->mes] = $reg;
		}

		$inicio = new DateTime(substr($this->fechas()->mes_inicio, 0, 10));
		$datos = [];

		for ($i = $meses - 1; $i >= 0; $i--) {
			$mes = (clone $inicio)->modify("-{$i} month")->format("Y-m");

			$datos[] = [
				"fecha" => $mes,
				"ventas" => isset($porMes[$mes]) ? (int)$porMes[$mes]->ventas : 0,
				"total" => isset($porMes[$mes]) ? (float)$porMes[$mes]->total : 0
			];
		}

		return $datos;
	}

	# Productos más vendidos (por monto) desde una fecha; la cantidad en la unidad de medida (presentaciones por su factor)
	public function topProductos($desde, $limite = 5)
	{
		return $this->db
		->select("
			c.id,
			c.nombre,
			d.nombre as nunidad,
			sum(a.cantidad * ifnull(e.factor, 1)) as cantidad,
			sum(a.total_precio) as total,
			sum(a.ganancia) as ganancia", false)
		->from("venta_detalle a")
		->join("venta b", "b.id = a.venta_id")
		->join("producto c", "c.id = a.producto_id")
		->join("unidad_medida d", "d.id = a.unidad_medida_id")
		->join("producto_presentacion e", "e.id = a.producto_presentacion_id", "left")
		->where("b.empresa_id", $this->empresa())
		->where("b.sucursal_id", $this->sucursal())
		->where("b.anulado", 0)
		->where("a.anulado", 0)
		->where("b.fecha >=", "{$desde} 00:00:00")
		->group_by([
			"c.id",
			"c.nombre",
			"d.nombre"
		])
		->order_by("total", "desc")
		->limit($limite)
		->get()
		->result();
	}

	# Venta por forma de pago desde una fecha
	public function formasPago($desde)
	{
		return $this->db
		->select("
			b.nombre,
			count(*) as ventas,
			sum(a.total_precio) as total", false)
		->from("venta a")
		->join("forma_pago b", "b.id = a.forma_pago_id")
		->where("a.empresa_id", $this->empresa())
		->where("a.sucursal_id", $this->sucursal())
		->where("a.anulado", 0)
		->where("a.fecha >=", "{$desde} 00:00:00")
		->group_by("b.nombre")
		->order_by("total", "desc")
		->get()
		->result();
	}

	# Saldo por cobrar (cuentas de las ventas de la sucursal), lo vencido y las próximas a vencer
	public function cuentasCobrar()
	{
		$tmp = $this->db
		->select("
			count(*) as cuentas,
			ifnull(sum(a.saldo), 0) as saldo,
			sum(a.fecha_vence < curdate()) as vencidas,
			ifnull(sum(case when a.fecha_vence < curdate() then a.saldo end), 0) as vencido", false)
		->from("cuenta_cobrar a")
		->join("venta b", "b.id = a.venta_id")
		->where("a.empresa_id", $this->empresa())
		->where("b.sucursal_id", $this->sucursal())
		->where("a.anulado", 0)
		->where("a.saldo >", 0)
		->get()
		->row();

		$proximas = $this->db
		->select("
			a.id,
			a.saldo,
			a.fecha_vence,
			datediff(a.fecha_vence, curdate()) as dias,
			b.correlativo,
			c.nombre as ncliente", false)
		->from("cuenta_cobrar a")
		->join("venta b", "b.id = a.venta_id")
		->join("cliente c", "c.id = a.cliente_id")
		->where("a.empresa_id", $this->empresa())
		->where("b.sucursal_id", $this->sucursal())
		->where("a.anulado", 0)
		->where("a.saldo >", 0)
		->order_by("a.fecha_vence", "asc")
		->limit(4)
		->get()
		->result();

		return [
			"cuentas" => (int)$tmp->cuentas,
			"saldo" => (float)$tmp->saldo,
			"vencidas" => (int)$tmp->vencidas,
			"vencido" => (float)$tmp->vencido,
			"proximas" => $proximas
		];
	}

	# Saldo por pagar (cuentas de las compras de la sucursal) y lo vencido
	public function cuentasPagar()
	{
		$tmp = $this->db
		->select("
			count(*) as cuentas,
			ifnull(sum(a.saldo), 0) as saldo,
			sum(a.fecha_vence < curdate()) as vencidas,
			ifnull(sum(case when a.fecha_vence < curdate() then a.saldo end), 0) as vencido", false)
		->from("cuenta_pagar a")
		->join("compra b", "b.id = a.compra_id")
		->where("a.empresa_id", $this->empresa())
		->where("b.sucursal_id", $this->sucursal())
		->where("a.anulado", 0)
		->where("a.saldo >", 0)
		->get()
		->row();

		return [
			"cuentas" => (int)$tmp->cuentas,
			"saldo" => (float)$tmp->saldo,
			"vencidas" => (int)$tmp->vencidas,
			"vencido" => (float)$tmp->vencido
		];
	}

	# Cotizaciones abiertas (borrador, enviada, aceptada) y cuántas vencen en los próximos 3 días
	public function cotizaciones()
	{
		$tmp = $this->db
		->select("
			b.codigo,
			count(*) as cantidad,
			sum(a.total) as total,
			sum(a.valida_hasta < curdate()) as vencidas,
			sum(a.valida_hasta between curdate() and curdate() + interval 3 day) as por_vencer", false)
		->from("cotizacion a")
		->join("cotizacion_estado b", "b.id = a.cotizacion_estado_id")
		->where("a.empresa_id", $this->empresa())
		->where("a.sucursal_id", $this->sucursal())
		->where_in("b.codigo", [
			"BORRADOR",
			"ENVIADA",
			"ACEPTADA"
		])
		->group_by("b.codigo")
		->get()
		->result();

		$datos = [
			"abiertas" => 0,
			"total" => 0,
			"vencidas" => 0,
			"por_vencer" => 0,
			"estados" => [
				"BORRADOR" => 0,
				"ENVIADA" => 0,
				"ACEPTADA" => 0
			]
		];

		foreach ($tmp as $reg) {
			$datos["abiertas"] += (int)$reg->cantidad;
			$datos["total"] += (float)$reg->total;
			$datos["vencidas"] += (int)$reg->vencidas;
			$datos["por_vencer"] += (int)$reg->por_vencer;
			$datos["estados"][$reg->codigo] = (int)$reg->cantidad;
		}

		return $datos;
	}

	# Últimas ventas de la sucursal (incluye anuladas, marcadas)
	public function ultimasVentas($limite = 6)
	{
		return $this->db
		->select("
			a.id,
			a.fecha,
			a.correlativo,
			a.total_precio,
			a.anulado,
			ifnull(b.nombre, 'Consumidor final') as ncliente,
			c.nombre as nforma_pago", false)
		->from("venta a")
		->join("cliente b", "b.id = a.cliente_id", "left")
		->join("forma_pago c", "c.id = a.forma_pago_id")
		->where("a.empresa_id", $this->empresa())
		->where("a.sucursal_id", $this->sucursal())
		->order_by("a.fecha", "desc")
		->limit($limite)
		->get()
		->result();
	}
}

/* End of file Dashboard_model.php */
/* Location: ./application/models/Dashboard_model.php */
