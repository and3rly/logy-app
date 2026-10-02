<script setup lang="ts">
// Selector del color del sistema: catálogo + color libre
import { catalogoColores, muestraDe, textoSobre } from '../../config/colores'
import { useAcentoStore } from '../../stores/acento'

const acento = useAcentoStore()

function elegirLibre(evento: Event) {
  acento.elegir((evento.target as HTMLInputElement).value)
}
</script>

<template>
  <div class="dropdown">
    <button
      type="button"
      class="btn btn-icono"
      data-bs-toggle="dropdown"
      data-bs-auto-close="outside"
      aria-expanded="false"
      title="Color del sistema"
      aria-label="Color del sistema"
    >
      <i class="fa-solid fa-palette" aria-hidden="true" />
    </button>

    <div class="dropdown-menu dropdown-menu-end selector-color">
      <div class="selector-color-cabecera">
        <span class="fw-semibold">Color del sistema</span>
        <span class="selector-color-actual">{{ acento.nombre }}</span>
      </div>

      <!-- Catálogo -->
      <div class="selector-color-grilla" role="radiogroup" aria-label="Color del sistema">
        <button
          v-for="color in catalogoColores"
          :key="color.id"
          type="button"
          class="muestra-color"
          role="radio"
          :aria-checked="acento.acento === color.id"
          :aria-label="color.nombre"
          :title="color.nombre"
          :style="{ background: muestraDe(color.id) }"
          @click="acento.elegir(color.id)"
        >
          <i
            v-if="acento.acento === color.id"
            class="fa-solid fa-check"
            :style="{ color: textoSobre(muestraDe(color.id)) }"
            aria-hidden="true"
          />
        </button>
      </div>

      <!-- Color libre -->
      <label class="selector-color-libre">
        <input
          type="color"
          :value="acento.esPersonalizado ? acento.acento : muestraDe(acento.acento)"
          aria-label="Elegir un color personalizado"
          @change="elegirLibre"
        >
        <span>Personalizado…</span>
        <i v-if="acento.esPersonalizado" class="fa-solid fa-check ms-auto" aria-hidden="true" />
      </label>
    </div>
  </div>
</template>
