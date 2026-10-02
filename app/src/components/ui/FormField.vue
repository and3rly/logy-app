<script setup lang="ts">
// Campo de formulario: etiqueta de 12px encima + control en el slot
defineProps<{
  id: string
  etiqueta: string
  requerido?: boolean
}>()
</script>

<template>
  <div>
    <label :for="id" class="form-label">
      {{ etiqueta }}<span v-if="requerido" class="text-danger"> *</span>
    </label>
    <slot />
  </div>
</template>
