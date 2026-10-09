<?php
defined('BASEPATH') OR exit('No direct script access allowed');

# Página temporal para probar Google Drive con la librería Drive y el helper archivo
# (copiados de la guía): cuenta de servicio con delegación de dominio sobre team@innovasys.com.gt.
# Los archivos van a <carpeta raíz>/<subcarpeta> ("varios" si no se indica). No guarda nada en la base.
# Se llama Gdrive porque la librería ya usa el nombre Drive.
class Gdrive extends CI_Controller {

	const MAX_ARCHIVO = 10485760; # 10 MB

	public function __construct()
	{
		parent::__construct();
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	# Corre después del hook de sesión (el constructor corre antes): solo un administrador
	public function _remap($metodo, $params = [])
	{
		if (!es_administrador()) {
			$this->output->set_status_header("403");
			$this->output->set_output(json_encode([
				"exito"   => false,
				"mensaje" => "Solo un administrador puede usar la prueba de Google Drive."
			]));
		} else if (in_array($metodo, ["estado", "listar", "subir", "eliminar"])) {
			$this->load->library("Drive");
			$this->load->helper("archivo");

			call_user_func_array([$this, $metodo], $params);
		} else {
			$this->output->set_status_header("404");
		}
	}

	# Cuenta de servicio, usuario suplantado y su cuota
	public function estado()
	{
		$data = ["exito" => 0];
		$llave = json_decode((string)@file_get_contents(APPPATH . "libraries/drive/drive.json"));

		if ($llave) {
			$data["cuenta"] = verPropiedad($llave, "client_email", "");
			$data["client_id"] = verPropiedad($llave, "client_id", "");
		}

		try {
			$about = $this->drive->service->about->get([
				"fields" => "user(emailAddress),storageQuota(limit,usage)"
			]);

			$data["usuario"] = $about->getUser() ? $about->getUser()->getEmailAddress() : "";
			$data["cuota"] = [
				"limite" => $about->getStorageQuota()->getLimit(),
				"uso"    => $about->getStorageQuota()->getUsage()
			];
			$data["carpeta"] = $this->rutaCarpeta();
			$data["exito"] = 1;
			$data["mensaje"] = "Conexión correcta.";
		} catch (Exception $e) {
			$data["mensaje"] = $this->mensajeGoogle($e);
		}

		$this->output->set_output(json_encode($data));
	}

	# Archivos de la carpeta de destino (la crea si no existe), del más reciente al más antiguo
	public function listar()
	{
		$data = ["exito" => 0, "lista" => []];

		try {
			$this->drive->set_subcarpeta($this->rutaCarpeta());

			$res = $this->drive->service->files->listFiles([
				"q"        => sprintf("'%s' in parents and trashed = false", $this->drive->getIdSubcarpeta()),
				"fields"   => "files(id,name,mimeType,size,createdTime,webViewLink)",
				"orderBy"  => "createdTime desc",
				"pageSize" => 50
			]);

			foreach ($res->getFiles() as $archivo) {
				$data["lista"][] = $this->archivoDatos($archivo);
			}

			$data["exito"] = 1;
		} catch (Exception $e) {
			$data["mensaje"] = $this->mensajeGoogle($e);
		}

		$this->output->set_output(json_encode($data));
	}

	# Sube "archivo" con subirArchivo() del helper (queda compartido con cualquiera que tenga el enlace)
	public function subir()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$archivo = $_FILES["archivo"] ?? null;

			if (!$archivo || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
				$data["mensaje"] = "Seleccione un archivo.";
			} else if ($archivo["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo["tmp_name"])) {
				$data["mensaje"] = "No se pudo recibir el archivo, intente nuevamente.";
			} else if ($archivo["size"] > self::MAX_ARCHIVO) {
				$data["mensaje"] = "El archivo pesa más de 10 MB.";
			} else {
				try {
					$datos = [
						"name"     => $archivo["name"],
						"type"     => $archivo["type"] ?: "application/octet-stream",
						"tmp_name" => $archivo["tmp_name"]
					];

					$subcarpeta = $this->subcarpeta();
					if ($subcarpeta !== "") {
						$datos["carpeta"] = $subcarpeta;
					}

					$res = subirArchivo($datos);

					if ($res->exito) {
						$data["exito"] = 1;
						$data["mensaje"] = "Archivo subido con éxito.";
						$data["linea"] = $this->archivoDatos($this->drive->getArchivo($res->key, [
							"fields" => "id,name,mimeType,size,createdTime,webViewLink"
						]));
					} else {
						$data["mensaje"] = $res->mensaje;
					}
				} catch (Exception $e) {
					$data["mensaje"] = $this->mensajeGoogle($e);
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Manda el archivo a la papelera (no lo borra para siempre)
	public function eliminar()
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));
			$id = trim((string)verPropiedad($datos, "id", ""));

			if ($id === "") {
				$data["mensaje"] = "Indique el archivo.";
			} else {
				try {
					$this->drive->service->files->update($id, new Google_Service_Drive_DriveFile([
						"trashed" => true
					]));

					$data["exito"] = 1;
					$data["mensaje"] = "Archivo enviado a la papelera.";
				} catch (Exception $e) {
					$data["mensaje"] = $this->mensajeGoogle($e);
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}

	# Subcarpeta opcional dentro de la carpeta de la base (ej. "productos"); sin "/" al inicio ni al final
	private function subcarpeta()
	{
		$valor = $this->input->get("carpeta");
		if ($valor === null) {
			$valor = $this->input->post("carpeta");
		}

		return trim(str_replace("'", "", (string)$valor), " /");
	}

	# La misma carpeta a la que sube subirArchivo(): la subcarpeta, o "varios" de la librería si no se indica
	private function rutaCarpeta()
	{
		$subcarpeta = $this->subcarpeta();

		return $subcarpeta !== "" ? $subcarpeta : "varios";
	}

	private function archivoDatos($archivo)
	{
		return [
			"id"     => $archivo->getId(),
			"nombre" => $archivo->getName(),
			"tipo"   => $archivo->getMimeType(),
			"tamano" => $archivo->getSize() !== null ? (int)$archivo->getSize() : null,
			"fecha"  => $archivo->getCreatedTime(),
			"enlace" => $archivo->getWebViewLink() ?: get_url_drive($archivo->getId()),
			"imagen" => strpos((string)$archivo->getMimeType(), "image/") === 0 ? "https://lh3.googleusercontent.com/d/" . $archivo->getId() : null
		];
	}

	# Mensaje legible de los errores de Google (API o autenticación)
	private function mensajeGoogle(Exception $e)
	{
		$mensaje = $e->getMessage();
		$json = json_decode(substr($mensaje, (int)strpos($mensaje, "{")));

		if ($json && verPropiedad($json, "error")) {
			if (is_object($json->error)) {
				return "Google respondió (" . $e->getCode() . "): " . verPropiedad($json->error, "message", $mensaje);
			}

			$mensaje = $json->error . (verPropiedad($json, "error_description") ? ": " . $json->error_description : "");

			if ($json->error === "unauthorized_client") {
				$mensaje .= " (falta autorizar el Client ID con el alcance de Drive en la delegación de todo el dominio del Workspace)";
			}

			return "Error de autenticación: " . $mensaje;
		}

		return "Google respondió (" . $e->getCode() . "): " . $mensaje;
	}
}

/* End of file Gdrive.php */
/* Location: ./application/controllers/prueba/Gdrive.php */
