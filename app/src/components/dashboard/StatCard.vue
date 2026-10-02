<script setup lang="ts">
// Tarjeta de indicador del dashboard: icono pastel, valor grande, detalle y variación
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  titulo: string
  valor: string
  icono?: string
  /** Tono del icono: primario, info, success, warning o danger */
  color?: 'primario' | 'info' | 'success' | 'warning' | 'danger'
  /** Texto pequeño bajo el valor (ej. '4 ventas · ticket Q 46,201.88') */
  detalle?: string
  /** Variación en % frente al periodo anterior; null = sin base para comparar */
  variacion?: number | null
  /** Con qué se compara (ej. 'vs. ayer') */
  comparado?: string
  /** Invierte el color de la variación (subir es malo, ej. cuentas vencidas) */
  invertir?: boolean
}>(), {
  color: 'primario',
  variacion: undefined,
})

const tono = computed(() => {
  if (props.variacion === undefined || props.variacion === null || props.variacion === 0) return 'neutro'
  const sube = props.variacion > 0
  return sube !== props.invertir ? 'sube' : 'baja'
})

const textoVariacion = computed(() => {
  const v = props.variacion
  if (v === undefined) return ''
  if (v === null) return 'Sin datos previos'
  const abs = Math.abs(v)
  return `${v > 0 ? '+' : v < 0 ? '−' : ''}${abs >= 100 ? Math.round(abs) : abs.toFixed(1)}%`
})
</script>

<template>
  <div class="indicador-tarjeta">
    <div class="d-flex align-items-start justify-content-between gap-2">
      <div class="indicador-titulo">{{ titulo }}</div>
      <span v-if="icono" class="icono-suave" :class="`icono-suave-${color}`" aria-hidden="true">
        <i :class="icono" />
      </span>
    </div>

    <div class="indicador-valor">{{ valor }}</div>

    <div class="d-flex flex-wrap align-items-center gap-2 mt-auto">
      <span v-if="variacion !== undefined && variacion !== null" class="indicador-variacion" :class="`indicador-variacion-${tono}`">
        <i
          v-if="tono !== 'neutro'"
          class="fa-solid"
          :class="(variacion ?? 0) > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'"
          aria-hidden="true"
        />
        {{ textoVariacion }}
      </span>
      <span v-if="comparado && variacion !== undefined && variacion !== null" class="indicador-detalle">{{ comparado }}</span>
      <span v-if="detalle" class="indicador-detalle text-truncate">{{ detalle }}</span>
    </div>
  </div>
</template>
