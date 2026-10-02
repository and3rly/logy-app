<script setup lang="ts">
// Barra superior: botón de menú, buscador, notificaciones y perfil
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useLayoutStore } from '../../stores/layout'
import { useTemaStore } from '../../stores/tema'
import ColorPicker from './ColorPicker.vue'
import { notificaciones as notificacionesEjemplo } from '../../data/ejemplos'
import { useSesionStore } from '../../stores/sesion'
import { mensajeError } from '../../services/api'
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
const notificaciones = ref(notificacionesEjemplo.map((n) => ({ ...n })))
const sinLeer = computed(() => notificaciones.value.filter((n) => !n.leida).length)

function marcarTodasLeidas() {
  notificaciones.value.forEach((n) => (n.leida = true))
}
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
          type="button"
          class="btn btn-icono position-relative"
          data-bs-toggle="dropdown"
          data-bs-auto-close="outside"
          aria-expanded="false"
          :aria-label="sinLeer ? `Notificaciones (${sinLeer} sin leer)` : 'Notificaciones'"
        >
          <i class="fa-regular fa-bell" aria-hidden="true" />
          <span v-if="sinLeer" class="notificacion-punto" />
        </button>

        <div class="dropdown-menu dropdown-menu-end p-0 notificaciones">
          <div class="notificaciones-cabecera">
            <span class="fw-semibold">Notificaciones</span>
            <button v-if="sinLeer" type="button" class="btn btn-link p-0" @click="marcarTodasLeidas">
              Marcar todas como leídas
            </button>
          </div>

          <ul class="list-unstyled mb-0">
            <li
              v-for="(n, i) in notificaciones"
              :key="i"
              class="notificacion"
              :class="{ 'notificacion--nueva': !n.leida }"
            >
              <span class="notificacion-icono" :class="`notificacion-icono--${n.tipo}`">
                <i :class="n.icono" aria-hidden="true" />
              </span>
              <span>
                <span class="d-block">{{ n.texto }}</span>
                <span class="notificacion-hace">{{ n.hace }}</span>
              </span>
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
