<script setup lang="ts">
// Paginación compacta (se usa dentro de la barra de la tabla)
const props = defineProps<{
  totalPaginas: number
}>()

const pagina = defineModel<number>('pagina', { required: true })

function ir(n: number) {
  if (n >= 1 && n <= props.totalPaginas) pagina.value = n
}
</script>

<template>
  <nav aria-label="Paginación">
    <ul class="pagination">
      <li class="page-item" :class="{ disabled: pagina === 1 }">
        <button type="button" class="page-link" aria-label="Anterior" @click="ir(pagina - 1)">
          <i class="fa-solid fa-chevron-left" aria-hidden="true" />
        </button>
      </li>
      <li v-for="n in totalPaginas" :key="n" class="page-item" :class="{ active: n === pagina }">
        <button type="button" class="page-link" @click="ir(n)">{{ n }}</button>
      </li>
      <li class="page-item" :class="{ disabled: pagina === totalPaginas }">
        <button type="button" class="page-link" aria-label="Siguiente" @click="ir(pagina + 1)">
          <i class="fa-solid fa-chevron-right" aria-hidden="true" />
        </button>
      </li>
    </ul>
  </nav>
</template>
