<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario_model extends Centro_model {
	public $nombre;
	public $alias;
	public $clave;
	public $correo;
	public $telefono;
	public $foto;
	public $activo = 1;
	public $empresa_id;
	public $rol_id;

	public function __construct($id = null)
	{
		parent::__construct();

		if ($id !== null) {
			$this->cargar($id);
		}
	}

	public function iniciarSesion($alias, $clave)
	{
		$tmp = $this->db
		->select("u.id, u.clave, e.activo as empresa_activa")
		->from("usuario u")
		->join("empresa e", "e.id = u.empresa_id")
		->where("u.alias", $alias)
		->where("u.activo", 1)
		->get()
		->row();

		if (!$tmp || !password_verify($clave, $tmp->clave)) {
			$this->setMensaje("Usuario o contraseña incorrectos.");
			return false;
		}

		if (!$tmp->empresa_activa) {
			$this->setMensaje("La empresa del usuario está inactiva.");
			return false;
		}

		$this->cargar($tmp->id);

		if ($this->getSucursalPrincipal() === null) {
			$this->setMensaje("El usuario no tiene una sucursal asignada.");
			return false;
		}

		return true;
	}

	public function getSucursales()
	{
		return $this->db
		->select("s.id, s.nombre, us.principal")
		->from("usuario_sucursal us")
		->join("sucursal s", "s.id = us.sucursal_id")
		->where("us.usuario_id", $this->getPK())
		->where("us.activo", 1)
		->where("s.activo", 1)
		->order_by("us.principal", "desc")
		->order_by("s.nombre", "asc")
		->get()
		->result();
	}

	public function getSucursalPrincipal()
	{
		$sucursales = $this->getSucursales();

		return count($sucursales) > 0 ? $sucursales[0] : null;
	}

	# Sucursal activa asignada al usuario con ese id, o null si no la tiene
	public function getSucursal($id)
	{
		foreach ($this->getSucursales() as $sucursal) {
			if ((int)$sucursal->id === (int)$id) {
				return $sucursal;
			}
		}

		return null;
	}

	# La elegida por el usuario si aún la tiene asignada; si no, la principal
	public function getSucursalSesion($id = null)
	{
		$sucursal = $id ? $this->getSucursal($id) : null;

		return $sucursal ? $sucursal : $this->getSucursalPrincipal();
	}

	public function getRol()
	{
		return $this->db
		->select("id, nombre")
		->where("id", $this->rol_id)
		->get("rol")
		->row();
	}

	# El alias es el usuario para iniciar sesión: único en todo el sistema
	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("alias", $args->alias)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}

	public function esDeLaEmpresa($id)
	{
		return $this->db
		->where("id", $id)
		->where("empresa_id", $this->_ses->empresa_id)
		->count_all_results("$this->_tabla") > 0;
	}

	# Usuarios de la empresa de la sesión con su rol y sucursales (sin la clave)
	public function getLista($args=[])
	{
		if (isset($args["id"])) {
			$this->db->where("u.id", $args["id"]);
		}

		$usuarios = $this->db
		->select("u.id, u.nombre, u.alias, u.correo, u.telefono, u.activo, u.rol_id, r.nombre as rol")
		->from("usuario u")
		->join("rol r", "r.id = u.rol_id", "left")
		->where("u.empresa_id", $this->_ses->empresa_id)
		->order_by("u.nombre", "asc")
		->get()
		->result();

		if (isset($args["id"])) {
			$this->db->where("u.id", $args["id"]);
		}

		$asignadas = $this->db
		->select("us.usuario_id, us.sucursal_id, us.principal")
		->from("usuario_sucursal us")
		->join("usuario u", "u.id = us.usuario_id")
		->where("u.empresa_id", $this->_ses->empresa_id)
		->where("us.activo", 1)
		->get()
		->result();

		foreach ($usuarios as $usuario) {
			$usuario->sucursales = [];
			$usuario->sucursal_id = null;

			foreach ($asignadas as $fila) {
				if ($fila->usuario_id == $usuario->id) {
					$usuario->sucursales[] = $fila->sucursal_id;

					if ((int)$fila->principal === 1) {
						$usuario->sucursal_id = $fila->sucursal_id;
					}
				}
			}
		}

		if (isset($args["_uno"])) {
			return count($usuarios) > 0 ? $usuarios[0] : null;
		}

		return $usuarios;
	}

	# Deja asignadas solo $sucursales, con $principal como principal; true si algo cambió
	public function setSucursales($sucursales, $principal)
	{
		$cambio = false;
		$actuales = [];

		$filas = $this->db
		->where("usuario_id", $this->getPK())
		->get("usuario_sucursal")
		->result();

		foreach ($filas as $fila) {
			$actuales[$fila->sucursal_id] = $fila;
		}

		foreach ($sucursales as $sucursal) {
			$datos = [
				"activo"    => 1,
				"principal" => $sucursal == $principal ? 1 : 0
			];

			if (isset($actuales[$sucursal])) {
				$fila = $actuales[$sucursal];

				if ((int)$fila->activo !== $datos["activo"] || (int)$fila->principal !== $datos["principal"]) {
					$this->db
					->where("usuario_id", $this->getPK())
					->where("sucursal_id", $sucursal)
					->update("usuario_sucursal", $datos);

					$cambio = true;
				}
			} else {
				$this->db->insert("usuario_sucursal", array_merge($datos, [
					"usuario_id"  => $this->getPK(),
					"sucursal_id" => $sucursal
				]));

				$cambio = true;
			}
		}

		foreach ($actuales as $sucursal => $fila) {
			if (!in_array($sucursal, $sucursales) && (int)$fila->activo === 1) {
				$this->db
				->where("usuario_id", $this->getPK())
				->where("sucursal_id", $sucursal)
				->update("usuario_sucursal", [
					"activo"    => 0,
					"principal" => 0
				]);

				$cambio = true;
			}
		}

		return $cambio;
	}

	# $sucursal_id: la sucursal elegida en la barra superior; sin ella, la principal
	public function getSesion($sucursal_id = null)
	{
		$sucursal = $this->getSucursalSesion($sucursal_id);

		return (object)[
			"id"          => (int)$this->getPK(),
			"empresa_id"  => (int)$this->empresa_id,
			"sucursal_id" => $sucursal ? (int)$sucursal->id : null,
			"rol_id"      => (int)$this->rol_id
		];
	}

	# Datos de la pantalla Mi perfil: cuenta, rol, empresa y sucursales asignadas
	public function getPerfil()
	{
		$tmp = $this->db
		->select("
			u.id,
			u.nombre,
			u.alias,
			u.correo,
			u.telefono,
			u.foto,
			u.fecha,
			r.nombre as rol,
			ifnull(r.administrador, 0) as administrador,
			e.nombre as empresa", false)
		->from("usuario u")
		->join("rol r", "r.id = u.rol_id", "left")
		->join("empresa e", "e.id = u.empresa_id")
		->where("u.id", $this->getPK())
		->get()
		->row();

		$tmp->administrador = (int)$tmp->administrador === 1;
		$tmp->sucursales = array_map(function ($s) {
			return [
				"id"        => (int)$s->id,
				"nombre"    => $s->nombre,
				"principal" => (int)$s->principal === 1
			];
		}, $this->getSucursales());

		return $tmp;
	}

	# Documentos registrados por el usuario en el mes actual (sin anulados), en todas sus sucursales
	public function getActividad()
	{
		$desde = date("Y-m-01 00:00:00");

		$ventas = $this->db
		->select("count(*) as cantidad, ifnull(sum(total_precio), 0) as total", false)
		->where("usuario_id", $this->getPK())
		->where("anulado", 0)
		->where("fecha >=", $desde)
		->get("venta")
		->row();

		$contar = function ($tabla) use ($desde) {
			return $this->db
			->where("usuario_id", $this->getPK())
			->where("anulado", 0)
			->where("fecha >=", $desde)
			->count_all_results($tabla);
		};

		return [
			"ventas"       => (int)$ventas->cantidad,
			"total_ventas" => (float)$ventas->total,
			"cotizaciones" => $contar("cotizacion"),
			"compras"      => $contar("compra")
		];
	}

	public function getPublico($sucursal_id = null)
	{
		$rol = $this->getRol();
		$sucursal = $this->getSucursalSesion($sucursal_id);

		# Decimales de montos de los parámetros de la empresa (la interfaz formatea con ellos)
		$param = $this->db
		->select("decimal_monto")
		->where("empresa_id", $this->empresa_id)
		->where("activo", 1)
		->get("empresa_parametro")
		->row();

		return [
			"id"          => (int)$this->getPK(),
			"nombre"      => $this->nombre,
			"alias"       => $this->alias,
			"correo"      => $this->correo,
			"foto"        => $this->foto,
			"empresa_id"  => (int)$this->empresa_id,
			"rol"         => $rol ? $rol->nombre : null,
			"decimal_monto" => ($param && $param->decimal_monto !== null) ? (int)$param->decimal_monto : null,
			"sucursal"    => $sucursal ? [
				"id" => (int)$sucursal->id,
				"nombre" => $sucursal->nombre
			] : null,
			"sucursales"  => array_map(function ($s) {
				return [
					"id" => (int)$s->id,
					"nombre" => $s->nombre
				];
			}, $this->getSucursales())
		];
	}
}

/* End of file Usuario_model.php */
/* Location: ./application/models/Usuario_model.php */
