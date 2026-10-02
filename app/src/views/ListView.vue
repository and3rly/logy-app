<script setup lang="ts">
// Listado genérico: barra (búsqueda, filtros, exportar, paginación), filtros activos,
// tabla ordenable con selección y modal de eliminar.
// Por ahora trabaja sobre datos de ejemplo (src/data/ejemplos.ts).
import { computed, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PageHeader from '../components/layout/PageHeader.vue'
import DataTable from '../components/ui/DataTable.vue'
import TablePagination from '../components/ui/TablePagination.vue'
import ConfirmModal from '../components/ui/ConfirmModal.vue'
import { ejemploPara } from '../data/ejemplos'
import { normalizar } from '../utils/texto'
import type { Fila, Orden } from '../types/tabla'

withDefaults(defineProps<{
  /** Muestra el botón "Agregar nuevo" (lleva a <ruta>/nuevo) */
  crear?: boolean
}>(), {
  crear: true,
})

const route = useRoute()

// --- Datos ---------------------------------------------------------------
const ejemplo = ejemploPara(String(route.name))
const columnas = ejemplo.columnas
const filtrosDisponibles = ejemplo.filtros
const filas = ref<Fila[]>([...ejemplo.filas])

// --- Búsqueda y filtros --------------------------------------------------
const busqueda = ref('')
const filtros = reactive<Record<string, string | undefined>>({})

// Opciones de cada filtro: valores distintos de su columna
function opcionesDe(clave: string) {
  return [...new Set(filas.value.map((fila) => fila[clave] ?? ''))].sort()
}

const filasFiltradas = computed(() => {
  const q = normalizar(busqueda.value.trim())
  return filas.value.filter((fila) => {
    const coincideBusqueda = !q || Object.values(fila).some((valor) => normalizar(valor).includes(q))
    const coincideFiltros = filtrosDisponibles.every((f) => !filtros[f.clave] || fila[f.clave] === filtros[f.clave])
    return coincideBusqueda && coincideFiltros
  })
})

// Etiquetas de lo que está filtrando la tabla, cada una con su forma de quitarse
const filtrosActivos = computed(() => {
  const activos: { texto: string; quitar: () => void }[] = []
  if (busqueda.value.trim()) {
    activos.push({ texto: `Búsqueda: “${busqueda.value.trim()}”`, quitar: () => (busqueda.value = '') })
  }
  for (const f of filtrosDisponibles) {
    const valor = filtros[f.clave]
    if (valor) activos.push({ texto: `${f.titulo}: ${valor}`, quitar: () => (filtros[f.clave] = undefined) })
  }
  return activos
})

function limpiarFiltros() {
  busqueda.value = ''
  for (const f of filtrosDisponibles) filtros[f.clave] = undefined
}

// --- Orden ---------------------------------------------------------------
const orden = ref<Orden | null>(null)

// Clic en un encabezado: ascendente → descendente → sin orden
function ordenar(clave: string) {
  if (orden.value?.clave !== clave) orden.value = { clave, dir: 'asc' }
  else if (orden.value.dir === 'asc') orden.value = { clave, dir: 'desc' }
  else orden.value = null
}

// Fechas (dd/mm/aaaa [hh:mm]) y números ("1,250.00") se ordenan por su valor
function valorOrden(valor: string): number | string {
  const fecha = valor.match(/^(\d{2})\/(\d{2})\/(\d{4})(?: (\d{2}):(\d{2}))?$/)
  if (fecha) {
    const [, d, m, a, h = '00', min = '00'] = fecha
    return Number(`${a}${m}${d}${h}${min}`)
  }
  const numero = valor.replace(/,/g, '')
  if (/^-?\d+(\.\d+)?$/.test(numero)) return Number(numero)
  return normalizar(valor)
}

const filasOrdenadas = computed(() => {
  const o = orden.value
  if (!o) return filasFiltradas.value
  return [...filasFiltradas.value].sort((a, b) => {
    const va = valorOrden(a[o.clave] ?? '')
    const vb = valorOrden(b[o.clave] ?? '')
    const comparacion = typeof va === 'number' && typeof vb === 'number'
      ? va - vb
      : String(va).localeCompare(String(vb), 'es')
    return o.dir === 'asc' ? comparacion : -comparacion
  })
})

// --- Paginación ----------------------------------------------------------
const POR_PAGINA = 10
const pagina = ref(1)

const totalPaginas = computed(() => Math.max(1, Math.ceil(filasOrdenadas.value.length / POR_PAGINA)))

const filasPagina = computed(() => {
  const inicio = (pagina.value - 1) * POR_PAGINA
  return filasOrdenadas.value.slice(inicio, inicio + POR_PAGINA)
})

const contador = computed(() => {
  const total = filasOrdenadas.value.length
  if (!total) return '0 registros'
  const desde = (pagina.value - 1) * POR_PAGINA + 1
  const hasta = Math.min(pagina.value * POR_PAGINA, total)
  return `${desde}–${hasta} de ${total} ${total === 1 ? 'registro' : 'registros'}`
})

// Al cambiar la búsqueda, un filtro o el orden, volver a la primera página
watch([busqueda, filtros, orden], () => {
  pagina.value = 1
})

// Si se eliminan filas y la página queda vacía, retroceder
watch(totalPaginas, (total) => {
  if (pagina.value > total) pagina.value = total
})

// --- Selección -----------------------------------------------------------
const seleccion = ref<Fila[]>([])

// --- Exportar a CSV ------------------------------------------------------
// Exporta la selección o, si no hay, todo lo filtrado (en el orden actual)
function exportar() {
  const origen = seleccion.value.length ? seleccion.value : filasOrdenadas.value
  const celda = (valor: string) => `"${valor.replace(/"/g, '""')}"`
  const lineas = [
    columnas.map((c) => celda(c.titulo)).join(','),
    ...origen.map((fila) => columnas.map((c) => celda(fila[c.clave] ?? '')).join(',')),
  ]
  // BOM para que Excel respete las tildes
  const archivo = new Blob(['﻿' + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(archivo)
  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = `${String(route.name).replace(/\//g, '-')}.csv`
  enlace.click()
  URL.revokeObjectURL(url)
}

// --- Eliminar ------------------------------------------------------------
const modalEliminar = ref<InstanceType<typeof ConfirmModal>>()
const filasEliminar = ref<Fila[]>([])

// Una fila se nombra por su primera columna; varias, por la cantidad
const mensajeEliminar = computed(() => {
  if (filasEliminar.value.length > 1) {
    return `¿Deseas eliminar ${filasEliminar.value.length} registros? Esta acción no se puede deshacer.`
  }
  const nombre = filasEliminar.value[0]?.[columnas[0]?.clave ?? ''] ?? ''
  return `¿Deseas eliminar «${nombre}»? Esta acción no se puede deshacer.`
})

function pedirEliminar(filasAEliminar: Fila[]) {
  filasEliminar.value = filasAEliminar
  modalEliminar.value?.abrir()
}

function confirmarEliminar() {
  // Con la API, aquí irá la eliminación en el servidor
  filas.value = filas.value.filter((fila) => !filasEliminar.value.includes(fila))
  seleccion.value = seleccion.value.filter((fila) => !filasEliminar.value.includes(fila))
  modalEliminar.value?.cerrar()
}
</script>

<template>
  <PageHeader>
    <RouterLink v-if="crear" :to="`${route.path}/nuevo`" class="btn btn-primary">
      <i class="fa-solid fa-plus me-1" aria-hidden="true" />Agregar nuevo
    </RouterLink>
  </PageHeader>

  <div class="tabla-tarjeta">
    <!-- Barra de acciones en bloque (con filas seleccionadas) -->
    <div v-if="seleccion.length" class="barra-tabla barra-seleccion">
      <span class="contador-seleccion">
        {{ seleccion.length }} {{ seleccion.length === 1 ? 'seleccionado' : 'seleccionados' }}
      </span>
      <button type="button" class="btn btn-outline-secondary" @click="exportar">
        <i class="fa-solid fa-file-arrow-down me-1" aria-hidden="true" />Exportar
      </button>
      <button type="button" class="btn btn-outline-danger" @click="pedirEliminar(seleccion)">
        <i class="fa-regular fa-trash-can me-1" aria-hidden="true" />Eliminar
      </button>
      <button type="button" class="btn btn-link ms-auto" @click="seleccion = []">
        Quitar selección
      </button>
    </div>

    <!-- Barra compacta: búsqueda, filtros, exportar y paginación -->
    <div v-else class="barra-tabla">
      <div class="buscador">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
        <input v-model="busqueda" type="search" class="form-control" placeholder="Buscar..." aria-label="Buscar en la tabla">
      </div>

      <select
        v-for="filtro in filtrosDisponibles"
        :key="filtro.clave"
        v-model="filtros[filtro.clave]"
        class="form-select"
        :aria-label="filtro.titulo"
      >
        <option :value="undefined">{{ filtro.titulo }}: todos</option>
        <option v-for="opcion in opcionesDe(filtro.clave)" :key="opcion" :value="opcion">
          {{ opcion }}
        </option>
      </select>

      <button type="button" class="btn btn-outline-secondary ms-auto" title="Exportar a CSV" @click="exportar">
        <i class="fa-solid fa-file-arrow-down me-1" aria-hidden="true" />Exportar
      </button>
    </div>

    <!-- Filtros activos -->
    <div v-if="filtrosActivos.length" class="filtros-activos">
      <span v-for="filtro in filtrosActivos" :key="filtro.texto" class="chip-filtro">
        {{ filtro.texto }}
        <button type="button" :aria-label="`Quitar ${filtro.texto}`" @click="filtro.quitar()">
          <i class="fa-solid fa-xmark" aria-hidden="true" />
        </button>
      </span>
      <button type="button" class="btn btn-link p-0" @click="limpiarFiltros">Limpiar todo</button>
    </div>

    <DataTable
      v-model:seleccion="seleccion"
      :columnas="columnas"
      :filas="filasPagina"
      :orden="orden"
      seleccionable
      @ordenar="ordenar"
      @eliminar="(fila) => pedirEliminar([fila])"
    />

    <!-- Pie: contador y paginación -->
    <div class="pie-tabla">
      <span class="barra-contador">{{ contador }}</span>
      <TablePagination v-model:pagina="pagina" :total-paginas="totalPaginas" />
    </div>
  </div>

  <ConfirmModal ref="modalEliminar" :mensaje="mensajeEliminar" @confirmar="confirmarEliminar" />
</template>
