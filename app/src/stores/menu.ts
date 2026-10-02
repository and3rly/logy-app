import { defineStore } from 'pinia'
import { ref } from 'vue'
import api, { mensajeError } from '../services/api'
import { useSesionStore } from './sesion'
import { menuFijo, type EnlaceMenu, type OpcionMenu, type SeccionMenu } from '../config/menu'

// Respuesta de GET /menu (api/controllers/Menu.php)
interface OpcionApi {
  id: number
  nombre: string
  icono: string | null
  url: string | null
}

interface ModuloApi extends OpcionApi {
  /** true: agrupa opciones; false: es un enlace directo */
  detalle: boolean
  menu: OpcionApi[]
}

interface RespuestaMenu {
  exito: boolean
  modulos: ModuloApi[]
  /** Rutas de todo el menú: las que dependen de los accesos del rol */
  rutas: string[]
}

const ICONO_POR_DEFECTO = 'fa-solid fa-circle'

// Url de la base como ruta de la interfaz ("moneda" → "/moneda"); "/" o vacío no es destino
function ruta(url: string | null): string | null {
  const limpia = (url ?? '').trim()
  if (!limpia || limpia === '/') return null
  return limpia.startsWith('/') ? limpia : `/${limpia}`
}

// Un módulo de la base como opción del menú lateral:
//  - con detalle y opciones → grupo desplegable
//  - sin detalle, o sin opciones pero con url → enlace directo (ej. Venta, Cotización)
//  - sin opciones ni url → no se muestra
function aOpcion(modulo: ModuloApi): OpcionMenu | null {
  const icono = modulo.icono?.trim() || ICONO_POR_DEFECTO

  const hijos = modulo.menu.flatMap((opcion): EnlaceMenu[] => {
    const to = ruta(opcion.url)
    return to ? [{ texto: opcion.nombre, to }] : []
  })

  if (modulo.detalle && hijos.length) return { texto: modulo.nombre, icono, hijos }

  const to = ruta(modulo.url)
  return to ? { texto: modulo.nombre, icono, to } : null
}

/** Menú lateral: las opciones fijas más los módulos de la base de datos */
export const useMenuStore = defineStore('menu', () => {
  const secciones = ref<SeccionMenu[]>(menuFijo)
  const cargando = ref(false)
  const error = ref('')
  // Rutas del menú en la base (controladas) y las que el rol puede abrir
  const controladas = ref<string[]>([])
  const permitidas = ref<string[]>([])
  // Token con el que se cargó: al cambiar de usuario se vuelve a pedir
  const cargadoPara = ref<string | null>(null)

  async function cargar() {
    const sesion = useSesionStore()
    cargando.value = true
    error.value = ''

    try {
      const { data } = await api.get<RespuestaMenu>('/menu')
      controladas.value = data.rutas.map(ruta).filter((r): r is string => r !== null)
      permitidas.value = data.modulos.flatMap((modulo) => [modulo.url, ...modulo.menu.map((o) => o.url)])
        .map(ruta)
        .filter((r): r is string => r !== null)
      cargadoPara.value = sesion.token

      const opciones = data.modulos
        .map(aOpcion)
        .filter((opcion): opcion is OpcionMenu => opcion !== null)

      secciones.value = [...menuFijo, { titulo: 'Módulos', opciones }]
    } catch (e) {
      error.value = mensajeError(e, 'No se pudo cargar el menú.')
    } finally {
      cargando.value = false
    }
  }

  // Carga el menú una vez por sesión (lo usa el router antes de cada ruta)
  async function asegurar() {
    const sesion = useSesionStore()
    if (cargadoPara.value !== sesion.token) await cargar()
  }

  // Las rutas que no están en el menú de la base (inicio, perfil...) siempre se pueden abrir
  function puedeVer(path: string): boolean {
    const dentro = (url: string) => path === url || path.startsWith(`${url}/`)
    return !controladas.value.some(dentro) || permitidas.value.some(dentro)
  }

  return { secciones, cargando, error, cargar, asegurar, puedeVer }
})
