<script setup lang="ts">
// Barra superior: botón de menú, buscador, notificaciones y perfil
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Dropdown } from 'bootstrap'
import { useLayoutStore } from '../../stores/layout'
import { useTemaStore } from '../../stores/tema'
import ColorPicker from './ColorPicker.vue'
import { useSesionStore } from '../../stores/sesion'
import api, { mensajeError } from '../../services/api'
import toaster from '../../helpers/toaster'

const layout = useLayoutStore()
const tema = useTemaStore()
const sesion = useSesionStore()
const router = useRouter()

// --- Usuario -------------------------------------------------------------
const usuario = computed(() => ({
  nombre: sesion.usuario?.nombre ?? '',
  rol: sesion.usuario?.rol ?? '',
  correo: sesion.usuario?.correo || sesion.usuario?.alias || '',
}))

// Iniciales del nombre para el avatar (ej. "Ana López" → "AL")
const iniciales = computed(() =>
  usuario.value.nombre
    .split(' ')
    .slice(0, 2)
    .map((parte) => parte[0])
    .join('')
    .toUpperCase(),
)

// --- Sucursal ------------------------------------------------------------
// Al cambiarla, AdminLayout recrea la vista y cada pantalla vuelve a cargar sus datos
// con el token nuevo (las listas por sucursal se filtran en la API con ese token)
const sucursales = computed(() => sesion.usuario?.sucursales ?? [])
const sucursalActual = computed(() => sesion.usuario?.sucursal ?? null)
const cambiandoSucursal = ref(false)

async function cambiarSucursal(id: number) {
  if (id === sucursalActual.value?.id || cambiandoSucursal.value) return

  cambiandoSucursal.value = true
  try {
    const res = await sesion.cambiarSucursal(id)
    if (res.exito) toaster.success(res.mensaje ?? 'Sucursal cambiada.')
    else toaster.error(res.mensaje ?? 'No se pudo cambiar la sucursal.')
  } catch (e) {
    toaster.error(mensajeError(e))
  } finally {
    cambiandoSucursal.value = false
  }
}

function cerrarSesion() {
  sesion.cerrar()
  router.push({ name: 'login' })
}

// --- Notificaciones ------------------------------------------------------
// Las de la sucursal de la sesión (API notificacion/buscar). Se recargan al cambiar de sucursal
// y cada minuto; leer una es por usuario.
interface Notificacion {
  id: number
  texto: string
  icono: string
  /** Color del icono */
  tipo: 'aviso' | 'info' | 'exito'
  ruta: string | null
  documento_id: number | null
  fecha: string
  /** Antigüedad según el reloj del servidor */
  segundos: number
  leida: boolean
}

const notificaciones = ref<Notificacion[]>([])
const sinLeer = ref(0)
const botonNotificaciones = ref<HTMLElement | null>(null)
let recarga: number | undefined

async function cargarNotificaciones() {
  try {
    const { data } = await api.get('/notificacion/buscar')
    notificaciones.value = (data.lista ?? []).map((n: Notificacion) => ({
      ...n,
      segundos: Number(n.segundos),
      leida: Number(n.leida) === 1,
    }))
    sinLeer.value = Number(data.sin_leer ?? 0)
  } catch {
    // Sin aviso: se vuelve a intentar en la siguiente recarga
  }
}

async function marcarTodasLeidas() {
  try {
    const { data } = await api.post('/notificacion/marcar_todas')
    if (data.exito) {
      notificaciones.value.forEach((n) => (n.leida = true))
      sinLeer.value = Number(data.sin_leer ?? 0)
    }
  } catch (e) {
    toaster.error(mensajeError(e))
  }
}

// Marca la notificación como leída y abre su documento (ej. /traslado?id=3)
async function abrirNotificacion(n: Notificacion) {
  Dropdown.getOrCreateInstance(botonNotificaciones.value as HTMLElement).hide()

  if (!n.leida) {
    n.leida = true
    api
      .post(`/notificacion/marcar_leida/${n.id}`)
      .then(({ data }) => (sinLeer.value = Number(data.sin_leer ?? 0)))
      .catch(() => undefined)
  }

  if (n.ruta) {
    router.push({ path: n.ruta, query: n.documento_id ? { id: String(n.documento_id) } : {} })
  }
}

// "Hace un momento", "Hace 10 min", "Hace 2 h", "Ayer" o la fecha
function hace(segundos: number, fecha: string) {
  if (segundos < 60) return 'Hace un momento'
  if (segundos < 3600) return `Hace ${Math.floor(segundos / 60)} min`
  if (segundos < 86400) return `Hace ${Math.floor(segundos / 3600)} h`
  if (segundos < 172800) return 'Ayer'

  const [a, m, d] = String(fecha).slice(0, 10).split('-')
  return `${d}/${m}/${a}`
}

watch(() => sucursalActual.value?.id, cargarNotificaciones)

onMounted(() => {
  cargarNotificaciones()
  recarga = window.setInterval(cargarNotificaciones, 60000)
})

onBeforeUnmount(() => window.clearInterval(recarga))
</script>

<template>
  <header class="app-navbar">
    <!-- Mostrar / ocultar menú -->
    <button
      type="button"
      class="btn btn-icono"
      aria-label="Mostrar u ocultar menú"
      @click="layout.alternarMenu()"
    >
      <i class="fa-solid fa-bars" aria-hidden="true" />
    </button>

    <!-- Buscador -->
    <form class="app-buscador" role="search" @submit.prevent>
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
      <input type="search" class="form-control" placeholder="Buscar pedidos, clientes…" aria-label="Buscar">
    </form>

    <div class="ms-auto d-flex align-items-center gap-1">
      <!-- Sucursal de la sesión: se puede cambiar entre las asignadas al usuario -->
      <div v-if="sucursalActual" class="dropdown">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2 me-1"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          :disabled="cambiandoSucursal"
          title="Sucursal actual"
          :aria-label="`Sucursal actual: ${sucursalActual.nombre}`"
        >
          <span v-if="cambiandoSucursal" class="spinner-border spinner-border-sm" aria-hidden="true" />
          <i v-else class="fa-solid fa-store" aria-hidden="true" />
          <span class="d-none d-md-inline text-truncate" style="max-width: 160px">{{ sucursalActual.nombre }}</span>
          <i v-if="sucursales.length > 1" class="fa-solid fa-chevron-down small" aria-hidden="true" />
        </button>

        <div class="dropdown-menu dropdown-menu-end">
          <h6 class="dropdown-header">Cambiar sucursal</h6>
          <button
            v-for="s in sucursales"
            :key="s.id"
            type="button"
            class="dropdown-item d-flex align-items-center gap-2"
            :class="{ active: s.id === sucursalActual.id }"
            :aria-current="s.id === sucursalActual.id"
            @click="cambiarSucursal(s.id)"
          >
            <i class="fa-solid fa-store fa-fw" aria-hidden="true" />
            <span class="me-auto">{{ s.nombre }}</span>
            <i v-if="s.id === sucursalActual.id" class="fa-solid fa-check" aria-hidden="true" />
          </button>
        </div>
      </div>

      <!-- Color del sistema -->
      <ColorPicker />

      <!-- Modo claro / oscuro -->
      <button
        type="button"
        class="btn btn-icono"
        :title="tema.tema === 'oscuro' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        :aria-label="tema.tema === 'oscuro' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        @click="tema.alternarTema()"
      >
        <i class="fa-regular" :class="tema.tema === 'oscuro' ? 'fa-sun' : 'fa-moon'" aria-hidden="true" />
      </button>

      <!-- Notificaciones -->
      <div class="dropdown">
        <button
          ref="botonNotificaciones"
          type="button"
          class="btn btn-icono position-relative"
          data-bs-toggle="dropdown"
          data-bs-auto-close="outside"
          aria-expanded="false"
          :aria-label="sinLeer ? `Notificaciones (${sinLeer} sin leer)` : 'Notificaciones'"
        >
          <i class="fa-regular fa-bell" aria-hidden="true" />
          <span v-if="sinLeer" class="notificacion-punto" aria-hidden="true">{{ sinLeer > 99 ? '99+' : sinLeer }}</span>
        </button>

        <div class="dropdown-menu dropdown-menu-end p-0 notificaciones">
          <div class="notificaciones-cabecera">
            <span class="fw-semibold">Notificaciones</span>
            <button v-if="sinLeer" type="button" class="btn btn-link p-0" @click="marcarTodasLeidas">
              Marcar todas como leídas
            </button>
          </div>

          <!-- Las de la sucursal de la sesión; el clic abre el documento -->
          <ul class="list-unstyled mb-0">
            <li
              v-for="n in notificaciones"
              :key="n.id"
              class="notificacion"
              :class="{ 'notificacion--nueva': !n.leida }"
              role="button"
              tabindex="0"
              @click="abrirNotificacion(n)"
              @keydown.enter="abrirNotificacion(n)"
            >
              <span class="notificacion-icono" :class="`notificacion-icono--${n.tipo}`">
                <i :class="n.icono" aria-hidden="true" />
              </span>
              <span>
                <span class="d-block">{{ n.texto }}</span>
                <span class="notificacion-hace">{{ hace(n.segundos, n.fecha) }}</span>
              </span>
            </li>
            <li v-if="notificaciones.length === 0" class="notificacion justify-content-center text-body-secondary">
              Sin notificaciones
            </li>
          </ul>
        </div>
      </div>

      <span class="navbar-separador" aria-hidden="true" />

      <!-- Perfil de usuario -->
      <div class="dropdown">
        <button
          type="button"
          class="btn btn-perfil"
          data-bs-toggle="dropdown"
          aria-expanded="false"
        >
          <span class="avatar" aria-hidden="true">{{ iniciales }}</span>
          <span class="d-none d-sm-block">
            <span class="perfil-nombre d-block">{{ usuario.nombre }}</span>
            <span class="perfil-rol d-block">{{ usuario.rol }}</span>
          </span>
          <i class="fa-solid fa-chevron-down" aria-hidden="true" />
        </button>
        <div class="dropdown-menu dropdown-menu-end menu-perfil">
          <!-- Cuenta: el nombre y el rol ya se ven en el botón (en móvil el botón solo muestra el avatar) -->
          <div class="menu-perfil-cabecera">
            <div class="perfil-nombre d-sm-none">{{ usuario.nombre }} · {{ usuario.rol }}</div>
            <div class="menu-perfil-etiqueta">Sesión iniciada como</div>
            <div class="menu-perfil-correo">{{ usuario.correo }}</div>
          </div>

          <!-- Opciones -->
          <ul class="menu-perfil-opciones">
            <li>
              <RouterLink class="dropdown-item" to="/perfil">
                <i class="fa-regular fa-user fa-fw" aria-hidden="true" />Mi perfil
              </RouterLink>
            </li>
            <li>
              <RouterLink class="dropdown-item" to="/configuracion">
                <i class="fa-solid fa-gear fa-fw" aria-hidden="true" />Configuración
              </RouterLink>
            </li>
          </ul>

          <div class="menu-perfil-pie">
            <button type="button" class="dropdown-item menu-perfil-salir" @click="cerrarSesion">
              <i class="fa-solid fa-right-from-bracket fa-fw" aria-hidden="true" />Cerrar sesión
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
