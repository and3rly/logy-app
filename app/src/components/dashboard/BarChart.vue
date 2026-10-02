<script setup lang="ts">
// Gráfica de barras de una sola serie, sin librerías.
//  - 'columnas': vertical, con eje de valores (ej. evolución por mes)
//  - 'barras': horizontal, con el valor al final de cada barra (ej. comparar categorías)
// Una sola serie = un solo color (el principal) y sin leyenda: el título dice qué se grafica.
import { computed, ref } from 'vue'

export interface DatoGrafica {
  etiqueta: string
  valor: number
}

const props = withDefaults(defineProps<{
  titulo: string
  subtitulo?: string
  datos: DatoGrafica[]
  orientacion?: 'columnas' | 'barras'
  /** Unidad para el texto emergente (ej. 'pedidos') */
  unidad?: string
}>(), {
  orientacion: 'columnas',
  unidad: '',
})

const formato = (n: number) => n.toLocaleString('en-US')

// --- Escala con valores redondos (0, 500, 1,000…) -------------------------
const escala = computed(() => {
  const maximo = Math.max(...props.datos.map((d) => d.valor), 1)
  const bruto = maximo / 4
  const magnitud = 10 ** Math.floor(Math.log10(bruto))
  const paso = [1, 2, 2.5, 5, 10].map((m) => m * magnitud).find((p) => p >= bruto) ?? bruto
  const tope = Math.ceil(maximo / paso) * paso
  const marcas = Array.from({ length: Math.round(tope / paso) + 1 }, (_, i) => i * paso)
  return { tope, marcas, maximo }
})

const total = computed(() => props.datos.reduce((suma, d) => suma + d.valor, 0))

// En columnas la escala va hasta el tope redondo; en barras, hasta el máximo
function porcentaje(valor: number) {
  const base = props.orientacion === 'columnas' ? escala.value.tope : escala.value.maximo
  return `${(valor / base) * 100}%`
}

// --- Texto emergente -----------------------------------------------------
const activo = ref<number | null>(null)
</script>

<template>
  <figure class="panel grafica mb-0">
    <figcaption class="grafica-cabecera">
      <h2 class="h3 mb-0">{{ titulo }}</h2>
      <span v-if="subtitulo" class="grafica-subtitulo">{{ subtitulo }}</span>
    </figcaption>

    <!-- ===== Columnas (vertical) ===== -->
    <div v-if="orientacion === 'columnas'" class="grafica-columnas" aria-hidden="true">
      <div class="grafica-plot">
        <!-- Líneas guía con su valor -->
        <div
          v-for="marca in escala.marcas"
          :key="marca"
          class="grafica-guia"
          :style="{ bottom: porcentaje(marca) }"
        >
          <span>{{ formato(marca) }}</span>
        </div>

        <!-- Columnas: toda la franja es zona de hover -->
        <div class="grafica-franjas">
          <div
            v-for="(dato, i) in datos"
            :key="dato.etiqueta"
            class="grafica-franja"
            :class="{ activa: activo === i }"
            @mouseenter="activo = i"
            @mouseleave="activo = null"
          >
            <div class="grafica-columna" :style="{ height: porcentaje(dato.valor) }">
              <!-- Solo se rotula el último valor; el resto va en el texto emergente -->
              <span v-if="i === datos.length - 1 && activo !== i" class="grafica-valor">
                {{ formato(dato.valor) }}
              </span>
            </div>
            <div v-if="activo === i" class="grafica-tooltip" :style="{ bottom: porcentaje(dato.valor) }">
              <strong>{{ formato(dato.valor) }}</strong> {{ unidad }}
              <span>{{ dato.etiqueta }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="grafica-eje-x">
        <span v-for="dato in datos" :key="dato.etiqueta">{{ dato.etiqueta }}</span>
      </div>
    </div>

    <!-- ===== Barras (horizontal) ===== -->
    <ul v-else class="grafica-barras" aria-hidden="true">
      <li
        v-for="(dato, i) in datos"
        :key="dato.etiqueta"
        class="grafica-fila"
        :class="{ activa: activo === i }"
        @mouseenter="activo = i"
        @mouseleave="activo = null"
      >
        <span class="grafica-fila-etiqueta">{{ dato.etiqueta }}</span>
        <span class="grafica-pista">
          <span class="grafica-barra" :style="{ width: porcentaje(dato.valor) }" />
        </span>
        <span class="grafica-fila-valor">
          {{ formato(dato.valor) }}
          <span v-if="activo === i" class="grafica-fila-parte">
            · {{ Math.round((dato.valor / total) * 100) }}%
          </span>
        </span>
      </li>
    </ul>

    <!-- Tabla equivalente para lectores de pantalla -->
    <table class="visually-hidden">
      <caption>{{ titulo }}</caption>
      <thead>
        <tr><th scope="col">Dato</th><th scope="col">Valor</th></tr>
      </thead>
      <tbody>
        <tr v-for="dato in datos" :key="dato.etiqueta">
          <th scope="row">{{ dato.etiqueta }}</th>
          <td>{{ formato(dato.valor) }} {{ unidad }}</td>
        </tr>
      </tbody>
    </table>
  </figure>
</template>
