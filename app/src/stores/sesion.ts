import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '../services/api'

export interface SucursalSesion {
  id: number
  nombre: string
}

export interface UsuarioSesion {
  id: number
  nombre: string
  alias: string
  correo: string | null
  foto: string | null
  empresa_id: number
  rol: string | null
  sucursal: SucursalSesion | null
  sucursales: SucursalSesion[]
  // Decimales de montos de los parámetros de la empresa (null = sin definir, se usan 2)
  decimal_monto?: number | null
}

interface RespuestaSesion {
  exito: boolean
  mensaje?: string
  token?: string
  usuario?: UsuarioSesion
}

// Claves en localStorage
const CLAVE_TOKEN = 'logy-token'
const CLAVE_USUARIO = 'logy-usuario'

function leer(clave: string): string | null {
  try {
    return localStorage.getItem(clave)
  } catch {
    return null
  }
}

function escribir(clave: string, valor: string | null) {
  try {
    if (valor === null) localStorage.removeItem(clave)
    else localStorage.setItem(clave, valor)
  } catch {
    // Sin almacenamiento: la sesión dura solo esta visita
  }
}

// El token es un JWT; se lee su vencimiento (exp, en segundos) sin validar la firma
function tokenVigente(token: string | null): boolean {
  if (!token) return false
  try {
    const cuerpo = token.split('.')[1]!.replace(/-/g, '+').replace(/_/g, '/')
    const { exp } = JSON.parse(atob(cuerpo)) as { exp?: number }
    return typeof exp === 'number' && exp * 1000 > Date.now()
  } catch {
    return false
  }
}

function usuarioGuardado(): UsuarioSesion | null {
  try {
    return JSON.parse(leer(CLAVE_USUARIO) ?? 'null') as UsuarioSesion | null
  } catch {
    return null
  }
}

export const useSesionStore = defineStore('sesion', () => {
  const guardado = leer(CLAVE_TOKEN)
  const token = ref<string | null>(tokenVigente(guardado) ? guardado : null)
  const usuario = ref<UsuarioSesion | null>(token.value ? usuarioGuardado() : null)

  const autenticado = computed(() => tokenVigente(token.value) && usuario.value !== null)

  async function iniciar(alias: string, clave: string) {
    const { data } = await api.post<RespuestaSesion>('/sesion/iniciar', { usuario: alias, clave })

    token.value = data.token ?? null
    usuario.value = data.usuario ?? null
    escribir(CLAVE_TOKEN, token.value)
    escribir(CLAVE_USUARIO, usuario.value ? JSON.stringify(usuario.value) : null)
  }

  // Vuelve a leer el usuario de la API (sucursales, rol...) por si cambió desde que inició sesión
  async function refrescar() {
    const { data } = await api.get<RespuestaSesion>('/sesion/actual')

    if (data.usuario) {
      usuario.value = data.usuario
      escribir(CLAVE_USUARIO, JSON.stringify(usuario.value))
    }
  }

  // La API emite un token nuevo con la sucursal elegida (el filtro por sucursal va en el token)
  async function cambiarSucursal(sucursalId: number) {
    const { data } = await api.post<RespuestaSesion>('/sesion/cambiar_sucursal', { sucursal_id: sucursalId })

    if (data.token && data.usuario) {
      token.value = data.token
      usuario.value = data.usuario
      escribir(CLAVE_TOKEN, token.value)
      escribir(CLAVE_USUARIO, JSON.stringify(usuario.value))
    }

    return data
  }

  function cerrar() {
    token.value = null
    usuario.value = null
    escribir(CLAVE_TOKEN, null)
    escribir(CLAVE_USUARIO, null)
  }

  return { token, usuario, autenticado, iniciar, refrescar, cambiarSucursal, cerrar }
})
