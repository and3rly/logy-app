<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Parametro extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Empresa_parametro_model"]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Parámetros de la empresa de la sesión y el catálogo de monedas
	public function get_datos()
	{
		$data = [
			"parametro" => $this->catalogo->verEmpresaParametro(),
			"cat" => [
				"monedas" => $this->catalogo->verMonedas()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "moneda_id")) {

				# Abreviaturas en mayúsculas; vacías se guardan como NULL
				$abreviaturas = [];

				foreach (["abr_producto", "abr_cotizacion", "abr_compra", "abr_venta", "abr_recepcion"] as $campo) {
					$valor = property_exists($datos, $campo) ? strtoupper(trim((string)$datos->$campo)) : "";
					$datos->$campo = $valor === "" ? null : $valor;

					if ($valor !== "") {
						$abreviaturas[] = $valor;
					}
				}

				# Decimales vacíos se guardan como NULL
				$decimalesValidos = true;

				foreach (["decimal_monto"] as $campo) {
					$valor = property_exists($datos, $campo) ? trim((string)$datos->$campo) : "";

					if ($valor === "") {
						$datos->$campo = null;
					} else if (ctype_digit($valor) && (int)$valor <= 2) {
						$datos->$campo = (int)$valor;
					} else {
						$decimalesValidos = false;
					}
				}

				# Formato de impresión de la venta: uno de los definidos en el modelo (sin dato = ticket)
				$formatos = [
					Empresa_parametro_model::IMPRESION_TICKET,
					Empresa_parametro_model::IMPRESION_CARTA
				];

				$formato = property_exists($datos, "formato_impresion") ? trim((string)$datos->formato_impresion) : "";
				$datos->formato_impresion = $formato === "" ? Empresa_parametro_model::IMPRESION_TICKET : (int)$formato;
				$formatoValido = ($formato === "" || ctype_digit($formato)) && in_array($datos->formato_impresion, $formatos, true);

				# Datos que no se toman del formulario (las cantidades no manejan decimales configurables)
				unset($datos->id, $datos->empresa_id, $datos->activo, $datos->fecha, $datos->decimal_cantidad);

				$parametro = new Empresa_parametro_model($id);

				$moneda = $this->catalogo->verMonedas([
					"id"   => $datos->moneda_id,
					"_uno" => true
				]);

				if (!empty($id) && !$parametro->esDeLaEmpresa()) {
					$data["mensaje"] = "Los parámetros no pertenecen a su empresa.";
				} else if (empty($id) && $this->catalogo->verEmpresaParametro()) {
					$data["mensaje"] = "La empresa ya tiene parámetros registrados; recargue la página.";
				} else if (!$moneda) {
					$data["mensaje"] = "Seleccione una moneda activa de su empresa.";
				} else if (!$decimalesValidos) {
					$data["mensaje"] = "Los decimales deben ser 0, 1 o 2.";
				} else if (!$formatoValido) {
					$data["mensaje"] = "Seleccione un formato de impresión válido.";
				} else if (count($abreviaturas) !== count(array_unique($abreviaturas))) {
					$data["mensaje"] = "Las abreviaturas no se pueden repetir.";
				} else {
					if ($parametro->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Parámetros guardados con éxito.";
						$data["linea"] = $parametro->buscar([
							"id"   => $parametro->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $parametro->getMensaje();
					}
				}
			} else {
				$data["mensaje"] = "Complete los campos marcados con *.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Parametro.php */
/* Location: ./application/controllers/mnt/Parametro.php */
