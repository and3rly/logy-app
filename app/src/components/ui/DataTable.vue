<script setup lang="ts">
// Tabla genérica: columnas ordenables, selección de filas y acciones por fila.
// El orden y la paginación los decide quien la usa (ver ListView.vue).
import { computed } from 'vue'
import type { Columna, Fila, Orden } from '../../types/tabla'

const props = withDefaults(defineProps<{
  columnas: Columna[]
  filas: Fila[]
  /** Muestra casillas para seleccionar filas */
  seleccionable?: boolean
  orden?: Orden | null
  /** Botones de cada fila */
  acciones?: ('ver' | 'editar' | 'eliminar')[]
}>(), {
  seleccionable: false,
  orden: null,
  acciones: () => ['ver', 'editar', 'eliminar'],
})

/** Filas marcadas (puede incluir filas de otras páginas) */
const seleccion = defineModel<Fila[]>('seleccion', { default: () => [] })

const emit = defineEmits<{
  ver: [fila: Fila]
  editar: [fila: Fila]
  eliminar: [fila: Fila]
  ordenar: [clave: string]
}>()

// --- Selección -----------------------------------------------------------
const todasMarcadas = computed(() => props.filas.length > 0 && props.filas.every((f) => seleccion.value.includes(f)))
const algunaMarcada = computed(() => props.filas.some((f) => seleccion.value.includes(f)))

// La casilla del encabezado marca o desmarca las filas de esta página
function alternarTodas() {
  seleccion.value = todasMarcadas.value
    ? seleccion.value.filter((f) => !props.filas.includes(f))
    : [...seleccion.value, ...props.filas.filter((f) => !seleccion.value.includes(f))]
}

function alternarFila(fila: Fila) {
  seleccion.value = seleccion.value.includes(fila)
    ? seleccion.value.filter((f) => f !== fila)
    : [...seleccion.value, fila]
}

// --- Orden ---------------------------------------------------------------
function ariaSort(col: Columna) {
  if (props.orden?.clave !== col.clave) return 'none'
  return props.orden.dir === 'asc' ? 'ascending' : 'descending'
}

function iconoOrden(col: Columna) {
  if (props.orden?.clave !== col.clave) return 'fa-sort'
  return props.orden.dir === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down'
}

// --- Formatos ------------------------------------------------------------
function iniciales(texto: string) {
  return texto
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0])
    .join('')
    .toUpperCase()
}
</script>

<template>
  <div class="tabla-contenedor">
    <table class="table table-hover tabla-datos">
      <thead>
        <tr>
          <th v-if="seleccionable" scope="col" class="tabla-check">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="todasMarcadas"
              :indeterminate="algunaMarcada && !todasMarcadas"
              aria-label="Seleccionar las filas de esta página"
              @change="alternarTodas"
            >
          </th>
          <th
            v-for="col in columnas"
            :key="col.clave"
            scope="col"
            :class="{ 'text-end': col.alinear === 'fin' }"
            :aria-sort="ariaSort(col)"
          >
            <button type="button" class="tabla-orden" @click="emit('ordenar', col.clave)">
              {{ col.titulo }}
              <i class="fa-solid" :class="iconoOrden(col)" aria-hidden="true" />
            </button>
          </th>
          <th scope="col" class="tabla-acciones text-end">Acciones</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(fila, i) in filas"
          :key="i"
          :class="{ seleccionada: seleccion.includes(fila) }"
        >
          <td v-if="seleccionable" class="tabla-check">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="seleccion.includes(fila)"
              :aria-label="`Seleccionar ${fila[columnas[0]?.clave ?? ''] ?? 'fila'}`"
              @change="alternarFila(fila)"
            >
          </td>
          <td
            v-for="col in columnas"
            :key="col.clave"
            :class="{ 'text-end': col.alinear === 'fin' }"
          >
            <span
              v-if="col.formato === 'etiqueta'"
              class="etiqueta"
              :class="`etiqueta-${col.etiquetas?.[fila[col.clave] ?? ''] ?? 'neutro'}`"
            >{{ fila[col.clave] }}</span>
            <span v-else-if="col.formato === 'avatar'" class="celda-avatar">
              <span class="avatar avatar-chico" aria-hidden="true">{{ iniciales(fila[col.clave] ?? '') }}</span>
              {{ fila[col.clave] }}
            </span>
            <template v-else>{{ fila[col.clave] }}</template>
          </td>
          <td class="tabla-acciones text-end">
            <button v-if="acciones.includes('ver')" type="button" class="btn accion-ver" title="Ver" aria-label="Ver" @click="emit('ver', fila)">
              <i class="fa-regular fa-eye" aria-hidden="true" />
            </button>
            <button v-if="acciones.includes('editar')" type="button" class="btn accion-editar" title="Editar" aria-label="Editar" @click="emit('editar', fila)">
              <i class="fa-solid fa-pen" aria-hidden="true" />
            </button>
            <button v-if="acciones.includes('eliminar')" type="button" class="btn accion-eliminar" title="Eliminar" aria-label="Eliminar" @click="emit('eliminar', fila)">
              <i class="fa-regular fa-trash-can" aria-hidden="true" />
            </button>
          </td>
        </tr>

        <!-- Sin registros -->
        <tr v-if="!filas.length">
          <td :colspan="columnas.length + (seleccionable ? 2 : 1)" class="tabla-vacia">
            <i class="fa-regular fa-folder-open" aria-hidden="true" />
            No se encontraron registros
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
