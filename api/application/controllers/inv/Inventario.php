<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Inventario inicial de la sucursal de la sesión: se carga desde Excel y se procesa una sola vez
class Inventario extends CI_Controller {

	const MAX_ARCHIVO = 5242880;

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"inv/Inventario_enc_model",
			"inv/Inventario_det_model",
			"inv/Inventario_importacion_model",
			"mnt/Producto_model",
			"Stock_model",
			"Movimiento_model"
		]);
		$this->load->model("Catalogo_model", "catalogo");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Inventario inicial vigente de la sucursal (o null) y los anulados
	public function get_datos()
	{
		$inventario = new Inventario_enc_model();

		$data = [
			"simbolo" => $this->simboloMoneda(),
			"inventario" => $inventario->cargarInicial() ? $inventario->getInfo() : null,
			"anulados" => array_values(array_filter($inventario->_buscar([
				"tipo" => Inventario_enc_model::INICIAL
			]), function ($i) {
				return (int)$i->inventario_estado_id === Inventario_enc_model::ANULADO;
			}))
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_detalle($id="")
	{
		$inventario = new Inventario_enc_model($id);

		$data = [
			"det" => $inventario->esDeLaSucursal() ? $inventario->getDetalle() : []
		];

		$this->output->set_output(json_encode($data));
	}

	# Plantilla de Excel con las unidades de la empresa y una hoja de ejemplo
	public function plantilla()
	{
		$libro = $this->Inventario_importacion_model->plantilla($this->simboloMoneda());
		$escritor = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro);

		ob_start();
		$escritor->save("php://output");
		$contenido = ob_get_clean();

		$this->output
		->set_content_type("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
		->set_header("Content-Disposition: attachment; filename=\"plantilla_inventario_inicial.xlsx\"")
		->set_header("Cache-Control: max-age=0")
		->set_output($contenido);
	}

	# Revisa el archivo sin guardar nada: filas con su acción (producto nuevo o existente) y errores
	public function validar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$archivo = $this->archivo();

			if (is_string($archivo)) {
				$data["mensaje"] = $archivo;
			} else {
				$validacion = $this->Inventario_importacion_model->validar($archivo["tmp_name"]);

				if ($validacion === false) {
					$data["mensaje"] = $this->Inventario_importacion_model->getMensaje();
				} else {
					$data["exito"] = 1;
					$data["validacion"] = $validacion;
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	/**
	 * Importa el archivo al inventario inicial de la sucursal (lo crea en borrador si no existe):
	 * crea los catálogos y productos que falten y suma cada fila a su lote. Si alguna fila tiene
	 * errores no se importa nada.
	 */
	public function importar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$archivo = $this->archivo();

			if (is_string($archivo)) {
				$data["mensaje"] = $archivo;
				$this->output->set_output(json_encode($data));
				return;
			}

			$validacion = $this->Inventario_importacion_model->validar($archivo["tmp_name"]);

			if ($validacion === false) {
				$data["mensaje"] = $this->Inventario_importacion_model->getMensaje();
			} else if ($validacion["resumen"]["errores"] > 0) {
				$errores = $validacion["resumen"]["errores"];
				$data["mensaje"] = $errores === 1 ? "Una fila tiene errores; corríjala y vuelva a subir el archivo." : "{$errores} filas tienen errores; corríjalas y vuelva a subir el archivo.";
				$data["validacion"] = $validacion;
			} else {
				$hash = hash_file("sha256", $archivo["tmp_name"]);

				$this->db->trans_begin();

				# Bloquea la sucursal: dos usuarios no crean dos inventarios iniciales a la vez
				$this->db->query("select id from sucursal where id = ? for update", [$this->_ses->sucursal_id]);

				$inventario = new Inventario_enc_model();
				$existe = $inventario->cargarInicial();

				if ($existe && !$inventario->editable()) {
					$data["mensaje"] = "La sucursal ya tiene su inventario inicial procesado ({$inventario->numero}); use ajustes para corregir existencias.";
				} else if ($existe && $inventario->archivo_hash === $hash) {
					$data["mensaje"] = "Este archivo ya se importó en el inventario {$inventario->numero}; las cantidades se duplicarían.";
				} else {
					$inventario->asignarNumero();
					$inventario->guardar([
						"archivo_nombre" => mb_substr($archivo["name"], 0, 255),
						"archivo_hash" => $hash
					]);

					$nuevos = $this->Inventario_importacion_model->importar($inventario->getPK(), $validacion);

					if ($this->db->trans_status() !== false) {
						$filas = $validacion["resumen"]["filas"];
						$data["exito"] = 1;
						$data["mensaje"] = ($filas === 1 ? "Se importó 1 fila" : "Se importaron {$filas} filas") .
							($nuevos > 0 ? " y se crearon {$nuevos} productos" : "") .
							" en el inventario {$inventario->numero}.";
						$data["inventario"] = $inventario->getInfo();
					} else {
						$data["mensaje"] = "No se pudo importar el archivo, intente nuevamente.";
					}
				}

				if ($data["exito"] === 1) {
					$this->db->trans_commit();
				} else {
					$this->db->trans_rollback();
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Quita una línea de un inventario en borrador
	public function quitar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "id")) {
				$det = new Inventario_det_model($datos->id);
				$inventario = new Inventario_enc_model($det->inventario_enc_id);

				if (!$inventario->esDeLaSucursal()) {
					$data["mensaje"] = "La línea no existe.";
				} else if (!$inventario->editable()) {
					$data["mensaje"] = "El inventario ya fue procesado o anulado, no se puede modificar.";
				} else if ($det->eliminar()) {
					$data["exito"] = 1;
					$data["mensaje"] = "Producto quitado del inventario.";
					$data["inventario"] = $inventario->getInfo();
				} else {
					$data["mensaje"] = "No se pudo quitar el producto, intente nuevamente.";
				}
			} else {
				$data["mensaje"] = "Seleccione el producto a quitar.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function procesar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$inventario = new Inventario_enc_model($id);

			if (!$inventario->esDeLaSucursal()) {
				$data["mensaje"] = "El inventario no existe.";
			} else if (!$inventario->editable()) {
				$data["mensaje"] = "El inventario ya fue procesado o anulado.";
			} else if ($inventario->procesar()) {
				$data["exito"] = 1;
				$data["mensaje"] = "Inventario {$inventario->numero} procesado: las existencias ya están en la sucursal.";
				$data["inventario"] = $inventario->getInfo();
			} else {
				$data["mensaje"] = $inventario->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	public function anular($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$motivo = trim((string)verPropiedad($datos, "motivo", ""));

			$inventario = new Inventario_enc_model($id);

			if (!$inventario->esDeLaSucursal()) {
				$data["mensaje"] = "El inventario no existe.";
			} else if ($motivo === "") {
				$data["mensaje"] = "Indique el motivo de la anulación.";
			} else if ($inventario->anular(mb_substr($motivo, 0, 500))) {
				$data["exito"] = 1;
				$data["mensaje"] = "Inventario {$inventario->numero} anulado.";
				$data["inventario"] = $inventario->getInfo();
			} else {
				$data["mensaje"] = $inventario->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Archivo subido en "archivo" o el mensaje de error
	private function archivo()
	{
		$archivo = $_FILES["archivo"] ?? null;

		if (!$archivo || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
			return "Seleccione el archivo de Excel.";
		}

		if ($archivo["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo["tmp_name"])) {
			return "No se pudo recibir el archivo, intente nuevamente.";
		}

		if ($archivo["size"] > self::MAX_ARCHIVO) {
			return "El archivo pesa más de 5 MB.";
		}

		if (!in_array(strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION)), ["xlsx", "xls"])) {
			return "El archivo debe ser de Excel (.xlsx).";
		}

		return $archivo;
	}

	# Símbolo de la moneda de los parámetros de la empresa
	private function simboloMoneda()
	{
		$param = $this->catalogo->verEmpresaParametro();
		$moneda = $param ? $this->catalogo->verMonedas([
			"id" => $param->moneda_id,
			"_todos" => true,
			"_uno" => true
		]) : null;

		return $moneda ? $moneda->simbolo : "";
	}
}

/* End of file Inventario.php */
/* Location: ./application/controllers/inv/Inventario.php */
