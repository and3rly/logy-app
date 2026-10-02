<script setup lang="ts">
// Menú lateral: logo y navegación por secciones, con grupos desplegables.
// Las opciones vienen de la base de datos (store de menú); "Inicio" es fija.
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import type { GrupoMenu } from '../../config/menu'
import { useLayoutStore } from '../../stores/layout'
import { useMenuStore } from '../../stores/menu'
import logo from '../../assets/logo.png'
import logoMarca from '../../assets/logo-marca.png'

const route = useRoute()
const layout = useLayoutStore()
const menu = useMenuStore()

// El router ya lo carga antes de entrar; aquí solo si aún no está (una vez por sesión)
onMounted(() => menu.asegurar())

// --- Estado activo -------------------------------------------------------

// Marca la opción también en sus subpáginas (ej. /usuarios/nuevo)
function esActivo(to: string) {
  if (to === '/') return route.path === '/'
  return route.path === to || route.path.startsWith(`${to}/`)
}

function grupoActivo(grupo: GrupoMenu) {
  return grupo.hijos.some((hijo) => esActivo(hijo.to))
}

// --- Grupos desplegados --------------------------------------------------
const abiertos = ref(new Set<string>())

// Al navegar (o al llegar el menú de la base), abrir el grupo de la página actual
watch(
  [() => route.path, () => menu.secciones],
  () => {
    for (const seccion of menu.secciones) {
      for (const opcion of seccion.opciones) {
        if ('hijos' in opcion && grupoActivo(opcion)) abiertos.value.add(opcion.texto)
      }
    }
  },
  { immediate: true },
)

function alternarGrupo(grupo: GrupoMenu) {
  // Con el menú reducido a iconos, primero se expande
  if (layout.colapsado) {
    layout.colapsado = false
    abiertos.value.add(grupo.texto)
    return
  }
  if (abiertos.value.has(grupo.texto)) abiertos.value.delete(grupo.texto)
  else abiertos.value.add(grupo.texto)
}
</script>

<template>
  <aside class="app-sidebar">
    <!-- Logo: completo con el menú abierto; solo el martillo con el menú colapsado -->
    <RouterLink to="/" class="sidebar-logo" title="Ir al inicio">
      <img :src="logo" alt="FerroAgro San Bernardino" class="sidebar-logo-completo">
      <img :src="logoMarca" alt="FerroAgro San Bernardino" class="sidebar-logo-marca">
    </RouterLink>

    <!-- Navegación por secciones -->
    <nav class="sidebar-menu" aria-label="Menú principal" :aria-busy="menu.cargando">
      <template v-for="seccion in menu.secciones" :key="seccion.titulo">
        <div class="sidebar-seccion">{{ seccion.titulo }}</div>

        <template v-for="opcion in seccion.opciones" :key="opcion.texto">
          <!-- Grupo desplegable -->
          <div v-if="'hijos' in opcion">
            <button
              type="button"
              class="sidebar-enlace"
              :class="{ 'grupo-activo': grupoActivo(opcion) }"
              :title="opcion.texto"
              :aria-expanded="abiertos.has(opcion.texto)"
              @click="alternarGrupo(opcion)"
            >
              <i class="fa-fw" :class="opcion.icono" aria-hidden="true" />
              <span class="sidebar-texto">{{ opcion.texto }}</span>
              <i class="fa-solid fa-chevron-down sidebar-flecha" aria-hidden="true" />
            </button>

            <div v-show="abiertos.has(opcion.texto)" class="sidebar-submenu">
              <RouterLink
                v-for="hijo in opcion.hijos"
                :key="hijo.to"
                :to="hijo.to"
                class="sidebar-subenlace"
                :class="{ activo: esActivo(hijo.to) }"
              >
                {{ hijo.texto }}
                <span v-if="hijo.insignia" class="sidebar-insignia">{{ hijo.insignia }}</span>
              </RouterLink>
            </div>
          </div>

          <!-- Enlace directo -->
          <RouterLink
            v-else
            :to="opcion.to"
            class="sidebar-enlace"
            :class="{ activo: esActivo(opcion.to) }"
            :title="opcion.texto"
          >
            <i class="fa-fw" :class="opcion.icono" aria-hidden="true" />
            <span class="sidebar-texto">{{ opcion.texto }}</span>
            <span v-if="opcion.insignia" class="sidebar-insignia sidebar-texto">{{ opcion.insignia }}</span>
          </RouterLink>
        </template>
      </template>

      <!-- Cargando el menú de la base: marcadores grises -->
      <template v-if="menu.cargando && menu.secciones.length === 1">
        <div class="sidebar-seccion">Módulos</div>
        <span v-for="n in 5" :key="n" class="sidebar-cargando" aria-hidden="true" />
      </template>

      <!-- Error al cargar -->
      <div v-if="menu.error" class="sidebar-error sidebar-texto">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true" />
        {{ menu.error }}
        <button type="button" class="btn btn-link p-0" @click="menu.cargar()">Reintentar</button>
      </div>
    </nav>
  </aside>
</template>
