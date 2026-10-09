<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Primera instalación: crea la empresa y carga los catálogos de config/instalacion.php
class Instalacion_model extends CI_Model {

	# Se instalan antes de la empresa; si la tabla ya tiene datos solo se agregan los ids que faltan
	# (ej. una opción de menú nueva cuando la base trae el menú de antes)
	private $globales = [
		"pais",
		"departamento",
		"municipio",
		"modulo",
		"menu",
		"venta_estado"
	];

	# Reciben el empresa_id de la empresa nueva (el orden respeta las llaves foráneas)
	private $porEmpresa = [
		"movimiento_tipo",
		"compra_estado",
		"cotizacion_estado",
		"inventario_ajuste_estado",
		"inventario_ajuste_tipo",
		"inventario_traslado_estado",
		"inventario_estado",
		"inventario_tipo",
		"forma_pago",
		"unidad_medida",
		"unidad_equivalencia"
	];

	private $mensaje = "";

	public function __construct()
	{
		parent::__construct();
		$this->config->load("instalacion", true);
	}

	public function getMensaje()
	{
		return $this->mensaje;
	}

	public function instalado()
	{
		return $this->db->count_all("empresa") > 0;
	}

	public function getCatalogo($tabla)
	{
		$datos = $this->config->item("instalacion", "instalacion");

		return isset($datos[$tabla]) ? $datos[$tabla] : [];
	}

	# Departamentos y municipios para el formulario (la tabla puede estar vacía todavía)
	public function getUbicaciones()
	{
		$departamentos = array_map(function ($d) {
			return [
				"id" => $d["id"],
				"nombre" => $d["nombre"]
			];
		}, $this->getCatalogo("departamento"));

		$municipios = array_map(function ($m) {
			return [
				"id" => $m["id"],
				"nombre" => $m["nombre"],
				"departamento_id" => $m["departamento_id"]
			];
		}, $this->getCatalogo("municipio"));

		usort($departamentos, function ($a, $b) {
			return strcmp($a["nombre"], $b["nombre"]);
		});

		usort($municipios, function ($a, $b) {
			return strcmp($a["nombre"], $b["nombre"]);
		});

		return [
			"departamentos" => $departamentos,
			"municipios" => $municipios
		];
	}

	public function existeMunicipio($id)
	{
		foreach ($this->getCatalogo("municipio") as $m) {
			if ((int)$m["id"] === (int)$id) {
				return true;
			}
		}

		return false;
	}

	/**
	 * $empresa: nombre, razon_social, identificacion, direccion, telefono, correo, municipio_id
	 * Crea todo en una transacción: si algo falla no queda nada a medias.
	 */
	public function instalar($empresa)
	{
		if ($this->instalado()) {
			$this->mensaje = "El sistema ya está instalado.";
			return false;
		}

		# Con db_debug CodeIgniter corta la petición en el primer error; aquí se reporta en JSON
		$debug = $this->db->db_debug;
		$this->db->db_debug = false;
		$this->db->trans_begin();

		try {
			foreach ($this->globales as $tabla) {
				$this->insertar($tabla, $this->faltantes($tabla));
			}

			$this->insertar("empresa", [$empresa]);
			$empresa_id = $this->db->insert_id();

			$this->insertar("rol", [[
				"nombre" => "Administrador",
				"administrador" => 1,
				"empresa_id" => $empresa_id
			]]);
			$rol_id = $this->db->insert_id();

			# Usuario por defecto: admin / admin (cambiar la clave en Mi perfil)
			$this->insertar("usuario", [[
				"nombre" => "Administrador",
				"alias" => "admin",
				"clave" => password_hash("admin", PASSWORD_DEFAULT),
				"correo" => $empresa["correo"],
				"empresa_id" => $empresa_id,
				"rol_id" => $rol_id
			]]);
			$usuario_id = $this->db->insert_id();

			$this->insertar("sucursal", [[
				"nombre" => "Principal",
				"direccion" => $empresa["direccion"],
				"telefono" => $empresa["telefono"],
				"correo" => $empresa["correo"],
				"empresa_id" => $empresa_id,
				"municipio_id" => $empresa["municipio_id"],
				"usuario_id" => $usuario_id
			]]);
			$sucursal_id = $this->db->insert_id();

			$this->insertar("usuario_sucursal", [[
				"sucursal_id" => $sucursal_id,
				"usuario_id" => $usuario_id,
				"principal" => 1
			]]);

			# La moneda la registra el usuario y luego la elige en Parámetros
			$this->insertar("empresa_parametro", [[
				"empresa_id" => $empresa_id,
				"abr_producto" => "PRD",
				"abr_cotizacion" => "COT",
				"abr_compra" => "COM",
				"abr_venta" => "VEN",
				"abr_recibo" => "RC",
				"decimal_cantidad" => 0,
				"decimal_monto" => 2,
				"formato_impresion" => 1
			]]);

			foreach ($this->porEmpresa as $tabla) {
				$this->insertar($tabla, $this->conEmpresa($this->getCatalogo($tabla), $empresa_id));
			}

			# Las series llevan el usuario que las creó
			$tablasSerie = [
				"venta_serie",
				"cotizacion_serie"
			];

			foreach ($tablasSerie as $tabla) {
				$series = $this->conEmpresa($this->getCatalogo($tabla), $empresa_id);

				foreach ($series as &$serie) {
					$serie["usuario_id"] = $usuario_id;
				}
				unset($serie);

				$this->insertar($tabla, $series);
			}

			# Cliente genérico de las ventas sin cliente
			$this->insertar("cliente", [[
				"nombre" => "Consumidor final",
				"identificacion" => "CF",
				"codigo" => "CF",
				"empresa_id" => $empresa_id,
				"usuario_id" => $usuario_id
			]]);

			$this->db->trans_commit();
			$exito = true;

		} catch (Exception $e) {
			$this->db->trans_rollback();
			$this->mensaje = $e->getMessage();
			$exito = false;
		}

		$this->db->db_debug = $debug;

		return $exito;
	}

	# Filas del catálogo cuyo id todavía no existe en la tabla (los existentes no se modifican)
	private function faltantes($tabla)
	{
		$existentes = array_map("intval", array_column($this->db->select("id")->get($tabla)->result_array(), "id"));

		return array_values(array_filter($this->getCatalogo($tabla), function ($fila) use ($existentes) {
			return !in_array((int)$fila["id"], $existentes, true);
		}));
	}

	private function conEmpresa($filas, $empresa_id)
	{
		return array_map(function ($fila) use ($empresa_id) {
			$fila["empresa_id"] = $empresa_id;
			return $fila;
		}, $filas);
	}

	private function insertar($tabla, $filas)
	{
		if (empty($filas)) {
			return;
		}

		if (!$this->db->insert_batch($tabla, $filas)) {
			$error = $this->db->error();
			throw new Exception("No se pudo cargar {$tabla}: {$error["message"]}");
		}
	}
}

/* End of file Instalacion_model.php */
/* Location: ./application/models/Instalacion_model.php */
