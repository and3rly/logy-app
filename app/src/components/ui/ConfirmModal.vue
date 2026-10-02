<script setup lang="ts">
// Modal de confirmación (por defecto, para eliminar). Se abre con abrir() vía ref.
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Modal } from 'bootstrap'

withDefaults(defineProps<{
  titulo?: string
  mensaje?: string
  textoConfirmar?: string
  /** Color del botón de confirmar (clase btn-*) */
  variante?: string
}>(), {
  titulo: 'Eliminar registro',
  mensaje: '¿Deseas eliminar este registro? Esta acción no se puede deshacer.',
  textoConfirmar: 'Eliminar',
  variante: 'danger',
})

const emit = defineEmits<{ confirmar: [] }>()

const elemento = ref<HTMLElement>()
let modal: Modal | null = null

onMounted(() => {
  if (elemento.value) modal = new Modal(elemento.value)
})

onBeforeUnmount(() => modal?.dispose())

function abrir() {
  modal?.show()
}

function cerrar() {
  modal?.hide()
}

defineExpose({ abrir, cerrar })
</script>

<template>
  <Teleport to="body">
    <div ref="elemento" class="modal fade" tabindex="-1" aria-labelledby="confirmar-titulo" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h2 id="confirmar-titulo" class="modal-title h3">{{ titulo }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar" />
          </div>
          <div class="modal-body py-2">
            {{ mensaje }}
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>
            <button type="button" class="btn" :class="`btn-${variante}`" @click="emit('confirmar')">
              {{ textoConfirmar }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
