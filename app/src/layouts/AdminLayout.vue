<script setup lang="ts">
// Layout principal: sidebar + navbar + contenido + pie de página
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from '../components/layout/AppSidebar.vue'
import AppNavbar from '../components/layout/AppNavbar.vue'
import AppFooter from '../components/layout/AppFooter.vue'
import { useLayoutStore } from '../stores/layout'
import { useSesionStore } from '../stores/sesion'

const layout = useLayoutStore()
const sesion = useSesionStore()
const route = useRoute()

// En móvil, cerrar el menú al cambiar de página
watch(() => route.fullPath, () => layout.cerrarMenuMovil())

// Datos del usuario al día (sucursal actual y asignadas) al abrir el sistema
onMounted(() => {
  sesion.refrescar().catch(() => {
    // Sin conexión: se queda con los datos guardados; si la sesión caducó, api.ts lleva al login
  })
})

// Clave de la vista: la recrea al pasar entre módulos que comparten componente
// y al cambiar de sucursal, para que cada pantalla recargue sus datos filtrados
const claveVista = computed(() => `${route.path}|${sesion.usuario?.sucursal?.id ?? ''}`)
</script>

<template>
  <div
    class="app"
    :class="{ 'app--colapsado': layout.colapsado, 'app--menu-abierto': layout.abierto }"
  >
    <AppSidebar />
    <div class="app-backdrop" @click="layout.cerrarMenuMovil()" />

    <div class="app-main">
      <AppNavbar />

      <main class="app-content">
        <!-- Sin usuario (al cerrar sesión) no se recrea la vista: pediría datos sin token -->
        <RouterView v-if="sesion.usuario" :key="claveVista" />
      </main>

      <AppFooter />
    </div>
  </div>
</template>
