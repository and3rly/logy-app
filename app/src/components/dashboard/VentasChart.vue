<script setup lang="ts">
// Gráfica de área de una sola serie (ventas en el tiempo), en SVG y sin librerías.
// Se dibuja en píxeles reales (mide su ancho) para que el texto no se deforme.
// Al pasar el cursor: línea guía, punto y texto emergente con el monto y el número de ventas.
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { formatoMonto } from '../../utils/numero'

export interface PuntoVenta {
  etiqueta: string
  /** Texto largo para el emergente (ej. 'lunes 28 de septiembre') */
  detalle: string
  total: number
  ventas: number
}

const props = defineProps<{
  datos: PuntoVenta[]
  simbolo?: string
}>()

const ALTO = 220
const MARGEN = { arriba: 12, derecha: 12, abajo: 26, izquierda: 56 }

const contenedor = ref<HTMLDivElement | null>(null)
const ancho = ref(600)
let observador: ResizeObserver | null = null

onMounted(() => {
  if (!contenedor.value) return
  ancho.value = contenedor.value.clientWidth || 600
  observador = new ResizeObserver(([e]) => {
    if (e) ancho.value = Math.max(240, e.contentRect.width)
  })
  observador.observe(contenedor.value)
})

onBeforeUnmount(() => observador?.disconnect())

const compacto = (n: number) =>
  n.toLocaleString('en-US', { notation: 'compact', maximumFractionDigits: 1 })
const monto = (n: number) => formatoMonto(n)

// Escala con valores redondos (0, 50k, 100k…)
const escala = computed(() => {
  const maximo = Math.max(...props.datos.map((d) => d.total), 1)
  const bruto = maximo / 4
  const magnitud = 10 ** Math.floor(Math.log10(bruto))
  const paso = [1, 2, 2.5, 5, 10].map((m) => m * magnitud).find((p) => p >= bruto) ?? bruto
  const tope = Math.ceil(maximo / paso) * paso
  const marcas = Array.from({ length: Math.round(tope / paso) + 1 }, (_, i) => i * paso)
  return { tope, marcas }
})

const plotAncho = computed(() => ancho.value - MARGEN.izquierda - MARGEN.derecha)
const plotAlto = ALTO - MARGEN.arriba - MARGEN.abajo

const x = (i: number) =>
  MARGEN.izquierda + (props.datos.length <= 1 ? plotAncho.value / 2 : (i / (props.datos.length - 1)) * plotAncho.value)
const y = (v: number) => MARGEN.arriba + plotAlto - (v / escala.value.tope) * plotAlto

const linea = computed(() => props.datos.map((d, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(d.total).toFixed(1)}`).join(' '))
const area = computed(() => {
  if (!props.datos.length) return ''
  const base = y(0).toFixed(1)
  return `${linea.value} L${x(props.datos.length - 1).toFixed(1)},${base} L${x(0).toFixed(1)},${base} Z`
})

// Etiquetas del eje X: como máximo ~7 para que no se encimen
const pasoEtiqueta = computed(() => Math.max(1, Math.ceil(props.datos.length / Math.max(2, Math.floor(plotAncho.value / 90)))))
const etiquetasX = computed(() =>
  props.datos
    .map((d, i) => ({ i, texto: d.etiqueta }))
    .filter(({ i }) => (props.datos.length - 1 - i) % pasoEtiqueta.value === 0),
)

// --- Texto emergente ------------------------------------------------------
const activo = ref<number | null>(null)

function mover(evento: MouseEvent) {
  const svg = evento.currentTarget as SVGSVGElement
  const px = evento.clientX - svg.getBoundingClientRect().left
  const n = props.datos.length
  if (n === 0) return
  const i = n === 1 ? 0 : Math.round(((px - MARGEN.izquierda) / plotAncho.value) * (n - 1))
  activo.value = Math.min(n - 1, Math.max(0, i))
}

const punto = computed(() => (activo.value === null ? null : props.datos[activo.value] ?? null))
const tooltipIzquierda = computed(() => {
  if (activo.value === null) return 0
  const px = x(activo.value)
  return Math.min(Math.max(px, 90), ancho.value - 90)
})
const idGradiente = `area-${Math.random().toString(36).slice(2, 8)}`
</script>

<template>
  <div ref="contenedor" class="ventas-chart">
    <svg
      :width="ancho"
      :height="ALTO"
      :viewBox="`0 0 ${ancho} ${ALTO}`"
      role="img"
      aria-label="Ventas en el tiempo"
      @mousemove="mover"
      @mouseleave="activo = null"
    >
      <defs>
        <linearGradient :id="idGradiente" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="var(--color-primario)" stop-opacity="0.28" />
          <stop offset="100%" stop-color="var(--color-primario)" stop-opacity="0" />
        </linearGradient>
      </defs>

      <!-- Líneas guía con su valor -->
      <g>
        <template v-for="marca in escala.marcas" :key="marca">
          <line
            :x1="MARGEN.izquierda"
            :x2="ancho - MARGEN.derecha"
            :y1="y(marca)"
            :y2="y(marca)"
            :class="marca === 0 ? 'ventas-chart-base' : 'ventas-chart-guia'"
          />
          <text :x="MARGEN.izquierda - 8" :y="y(marca)" class="ventas-chart-eje" text-anchor="end" dominant-baseline="middle">
            {{ compacto(marca) }}
          </text>
        </template>
      </g>

      <!-- Eje X -->
      <text
        v-for="e in etiquetasX"
        :key="e.i"
        :x="x(e.i)"
        :y="ALTO - 6"
        class="ventas-chart-eje"
        :text-anchor="e.i === 0 ? 'start' : e.i === datos.length - 1 ? 'end' : 'middle'"
      >
        {{ e.texto }}
      </text>

      <!-- Serie -->
      <path :d="area" :fill="`url(#${idGradiente})`" />
      <path :d="linea" class="ventas-chart-linea" />

      <!-- Último punto siempre marcado -->
      <circle
        v-if="datos.length && activo === null"
        :cx="x(datos.length - 1)"
        :cy="y(datos[datos.length - 1]!.total)"
        r="4.5"
        class="ventas-chart-punto"
      />

      <!-- Guía del cursor -->
      <g v-if="punto && activo !== null">
        <line :x1="x(activo)" :x2="x(activo)" :y1="MARGEN.arriba" :y2="y(0)" class="ventas-chart-cursor" />
        <circle :cx="x(activo)" :cy="y(punto.total)" r="5" class="ventas-chart-punto" />
      </g>

      <!-- Zona de hover: todo el plot -->
      <rect :x="MARGEN.izquierda" :y="MARGEN.arriba" :width="plotAncho" :height="plotAlto" fill="transparent" />
    </svg>

    <div
      v-if="punto && activo !== null"
      class="ventas-chart-tooltip"
      :style="{ left: `${tooltipIzquierda}px`, top: `${Math.max(0, y(punto.total) - 64)}px` }"
    >
      <span>{{ punto.detalle }}</span>
      <strong>{{ simbolo }} {{ monto(punto.total) }}</strong>
      <span>{{ punto.ventas }} {{ punto.ventas === 1 ? 'venta' : 'ventas' }}</span>
    </div>

    <!-- Tabla equivalente para lectores de pantalla -->
    <table class="visually-hidden">
      <caption>Ventas en el tiempo</caption>
      <thead>
        <tr><th scope="col">Periodo</th><th scope="col">Total</th><th scope="col">Ventas</th></tr>
      </thead>
      <tbody>
        <tr v-for="d in datos" :key="d.detalle">
          <th scope="row">{{ d.detalle }}</th>
          <td>{{ simbolo }} {{ monto(d.total) }}</td>
          <td>{{ d.ventas }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
