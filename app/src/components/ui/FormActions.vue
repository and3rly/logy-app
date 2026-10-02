<script setup lang="ts">
// Botones al final del formulario. "Guardar" es submit: el formulario maneja el guardado.
const emit = defineEmits<{ cancelar: [] }>()
</script>

<template>
  <div class="d-flex justify-content-end gap-2 mt-2">
    <button type="button" class="btn btn-outline-secondary" @click="emit('cancelar')">
      Cancelar
    </button>
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
    </button>
  </div>
</template>
