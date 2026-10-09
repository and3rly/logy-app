<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Notificaciones de la campana. Van a una sucursal (usuario_id NULL = todos sus usuarios) o a un
 * usuario; la lectura es por usuario (notificacion_leida) y activo = 0 la retira para todos.
 * ruta + documento_id: lo que se abre con el clic (ej. /traslado?id=3).
 */
class Notificacion_model extends Centro_model {

	# Cuántas se muestran en la campana
	const LIMITE = 20;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Crea una notificación. Se llama dentro de la transacción del documento que la genera.
	 * $args: sucursal_id, texto, icono, tipo (aviso, info o exito), ruta, documento_id, usuario_id (opcional)
	 * No usa guardar(): pondría el usuario de la sesión en usuario_id, que aquí es el destinatario.
	 */
	public function crear($args)
	{
		return $this->db->insert($this->_tabla, [
			"tipo" => elemento($args, "tipo", "info"),
			"icono" => $args["icono"],
			"texto" => mb_substr($args["texto"], 0, 300),
			"ruta" => elemento($args, "ruta", null),
			"documento_id" => elemento($args, "documento_id", null),
			"empresa_id" => $this->_ses->empresa_id,
			"sucursal_id" => $args["sucursal_id"],
			"usuario_id" => elemento($args, "usuario_id", null),
			"usuario_origen_id" => $this->_ses->id
		]);
	}

	# Retira para todos las notificaciones de un documento (ej. el traslado ya se recibió)
	public function cerrarDocumento($ruta, $documentoId)
	{
		return $this->db
		->set("activo", 0)
		->where("empresa_id", $this->_ses->empresa_id)
		->where("ruta", $ruta)
		->where("documento_id", $documentoId)
		->where("activo", 1)
		->update($this->_tabla);
	}

	/**
	 * Las del usuario de la sesión en la sucursal de la sesión, de la más reciente a la más antigua.
	 * segundos: antigüedad según el reloj de MySQL (la interfaz arma el "Hace 10 min").
	 */
	public function buscar($args=[])
	{
		$this->visibles();

		return $this->db
		->select("
			a.id,
			a.tipo,
			a.icono,
			a.texto,
			a.ruta,
			a.documento_id,
			a.fecha,
			timestampdiff(second, a.fecha, now()) as segundos,
			if(b.notificacion_id is null, 0, 1) as leida", false)
		->order_by("a.fecha", "desc")
		->order_by("a.id", "desc")
		->limit(self::LIMITE)
		->get()
		->result();
	}

	public function sinLeer()
	{
		$this->visibles();

		return $this->db
		->where("b.notificacion_id IS NULL", null, false)
		->count_all_results();
	}

	# Marca una (o, sin id, todas las visibles) como leída para el usuario de la sesión
	public function marcarLeida($id=null)
	{
		$this->visibles();

		if ($id) {
			$this->db->where("a.id", $id);
		}

		$pendientes = $this->db
		->select("a.id")
		->where("b.notificacion_id IS NULL", null, false)
		->get()
		->result();

		foreach ($pendientes as $fila) {
			$this->db->query("
				insert ignore into notificacion_leida (notificacion_id, usuario_id)
				values (?, ?)", [
					$fila->id,
					$this->_ses->id
				]);
		}

		return true;
	}

	# Tablas y filtros comunes: activas, de la sucursal de la sesión y para todos o para el usuario
	private function visibles()
	{
		$this->db
		->from("{$this->_tabla} a")
		->join("notificacion_leida b", "b.notificacion_id = a.id and b.usuario_id = " . (int)$this->_ses->id, "left", false)
		->where("a.empresa_id", $this->_ses->empresa_id)
		->where("a.sucursal_id", $this->_ses->sucursal_id)
		->where("a.activo", 1)
		->group_start()
			->where("a.usuario_id IS NULL", null, false)
			->or_where("a.usuario_id", $this->_ses->id)
		->group_end();
	}
}

/* End of file Notificacion_model.php */
/* Location: ./application/models/Notificacion_model.php */
