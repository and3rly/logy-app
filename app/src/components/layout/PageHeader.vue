<script setup lang="ts">
// Encabezado de cada página: breadcrumb + icono y título (desde route.meta y el menú),
// y acciones en el slot
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useMenuStore } from '../../stores/menu'

const route = useRoute()
const menu = useMenuStore()

const titulo = computed(() => route.meta.titulo ?? '')
const descripcion = computed(() => route.meta.descripcion ?? '')
const migas = computed(() => route.meta.migas ?? [])

// Icono de la opción del menú que corresponde a esta página
// (las subopciones usan el icono de su grupo; /x/nuevo usa el de /x)
const icono = computed(() => {
  const ruta = route.path
  const coincide = (to: string) => (to === '/' ? ruta === '/' : ruta === to || ruta.startsWith(`${to}/`))

  for (const seccion of menu.secciones) {
    for (const opcion of seccion.opciones) {
      if ('hijos' in opcion) {
        if (opcion.hijos.some((hijo) => coincide(hijo.to))) return opcion.icono
      } else if (coincide(opcion.to)) {
        return opcion.icono
      }
    }
  }
  return undefined
})
</script>

<template>
  <div class="pagina-encabezado">
    <div>
      <!-- Ruta de navegación arriba del título; "Inicio" es un icono de casa -->
      <nav aria-label="Ruta de navegación">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <RouterLink v-if="migas.length" to="/" class="breadcrumb-inicio" aria-label="Inicio">
              <i class="fa-solid fa-house" aria-hidden="true" />
            </RouterLink>
            <span v-else class="breadcrumb-inicio" aria-current="page">
              <i class="fa-solid fa-house" aria-hidden="true" /> Inicio
            </span>
          </li>

          <li
            v-for="(miga, i) in migas"
            :key="i"
            class="breadcrumb-item"
            :class="{ active: !miga.to }"
            :aria-current="miga.to ? undefined : 'page'"
          >
            <RouterLink v-if="miga.to" :to="miga.to">{{ miga.texto }}</RouterLink>
            <template v-else>{{ miga.texto }}</template>
          </li>
        </ol>
      </nav>

      <!-- Icono (con el efecto de la opción activa del menú) + título y descripción -->
      <div class="pagina-titulo-fila">
        <span v-if="icono" class="pagina-icono" aria-hidden="true">
          <i :class="icono" />
        </span>
        <div>
          <h1 class="pagina-titulo">{{ titulo }}</h1>
          <p v-if="descripcion" class="pagina-descripcion">{{ descripcion }}</p>
        </div>
      </div>
    </div>

    <!-- Acciones de la página (ej. "Agregar nuevo") -->
    <div v-if="$slots.default" class="d-flex gap-2">
      <slot />
    </div>
  </div>
</template>
