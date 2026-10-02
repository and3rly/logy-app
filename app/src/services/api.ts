import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Envía el token de sesión en cada petición
api.interceptors.request.use((config) => {
  try {
    const token = localStorage.getItem('logy-token')
    if (token) config.headers.Authorization = `Bearer ${token}`
  } catch {
    // Sin almacenamiento: la petición sale sin token
  }
  return config
})

// --- Sesión caducada ---------------------------------------------------------
// El hook de la API (hooks/Inicio.php) responde 401 con { sesion: true } cuando falta
// el token o ya no es válido. Qué hacer entonces lo registra main.ts, porque este
// archivo no puede importar el store de sesión (ese store importa este archivo).
let alCaducar: (() => void) | null = null

export function alCaducarSesion(accion: () => void) {
  alCaducar = accion
}

api.interceptors.response.use(
  (respuesta) => respuesta,
  (error) => {
    const datos = axios.isAxiosError(error) ? (error.response?.data as { sesion?: boolean } | undefined) : undefined
    if (error?.response?.status === 401 && datos?.sesion === true) {
      alCaducar?.()
    }
    return Promise.reject(error)
  },
)

// Mensaje de error de la API ({ mensaje }) o uno genérico
export function mensajeError(error: unknown, porDefecto = 'No se pudo conectar con el servidor.'): string {
  if (axios.isAxiosError(error)) {
    const mensaje = (error.response?.data as { mensaje?: string } | undefined)?.mensaje
    if (mensaje) return mensaje
  }
  return porDefecto
}

export default api
