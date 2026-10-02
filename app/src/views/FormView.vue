<script setup lang="ts">
// Formulario generado desde una lista de campos (src/data/ejemplos.ts).
// Con `modulo`, es el formulario "nuevo" de ese módulo; sin él, la configuración general.
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/layout/PageHeader.vue'
import FormField from '../components/ui/FormField.vue'
import FormActions from '../components/ui/FormActions.vue'
import { configuracion, ejemploPara } from '../data/ejemplos'
import type { CampoFormulario, ValoresFormulario } from '../types/formulario'

const props = defineProps<{
  /** Nombre de la ruta del listado (ej. 'inventario/productos') */
  modulo?: string
}>()

const router = useRouter()

const campos: CampoFormulario[] = props.modulo ? ejemploPara(props.modulo).campos : configuracion.campos
const valores = reactive<ValoresFormulario>(props.modulo ? {} : { ...configuracion.valores })

// Las áreas de texto ocupan toda la fila salvo que se indique otra cosa
function clasesColumna(campo: CampoFormulario) {
  const completo = campo.ancho === 'completo' || (campo.tipo === 'area' && campo.ancho !== 'normal')
  return completo ? 'col-12' : 'col-12 col-md-6 col-xl-3'
}

const tiposInput = { texto: 'text', correo: 'email', numero: 'number', fecha: 'date' } as const

// --- Guardar -------------------------------------------------------------
const guardado = ref(false)

function guardar() {
  // Con la API, aquí irá el envío al servidor
  guardado.value = true
}

function cancelar() {
  router.back()
}
</script>

<template>
  <PageHeader />

  <form class="panel" @submit.prevent="guardar">
    <!-- Aviso de guardado (de ejemplo: aún no se envía a ningún servidor) -->
    <div v-if="guardado" class="alert alert-success py-1 px-2 small mb-2" role="status">
      <i class="fa-solid fa-circle-check me-1" aria-hidden="true" />{{ modulo ? 'Registro guardado' : 'Cambios guardados' }}
    </div>

    <!-- 4 columnas en escritorio, 2 en tablet, 1 en móvil -->
    <div class="row g-2">
      <FormField
        v-for="campo in campos"
        :id="`campo-${campo.clave}`"
        :key="campo.clave"
        :etiqueta="campo.etiqueta"
        :requerido="campo.requerido"
        :class="clasesColumna(campo)"
      >
        <select
          v-if="campo.tipo === 'seleccion'"
          :id="`campo-${campo.clave}`"
          v-model="valores[campo.clave]"
          class="form-select"
          :required="campo.requerido"
        >
          <option :value="undefined" disabled>Seleccionar…</option>
          <option v-for="opcion in campo.opciones" :key="opcion" :value="opcion">{{ opcion }}</option>
        </select>

        <textarea
          v-else-if="campo.tipo === 'area'"
          :id="`campo-${campo.clave}`"
          v-model="valores[campo.clave]"
          rows="3"
          class="form-control"
          :placeholder="campo.placeholder"
          :required="campo.requerido"
        />

        <input
          v-else
          :id="`campo-${campo.clave}`"
          v-model="valores[campo.clave]"
          :type="tiposInput[campo.tipo]"
          class="form-control"
          :placeholder="campo.placeholder"
          :required="campo.requerido"
          :step="campo.tipo === 'numero' ? 'any' : undefined"
        >
      </FormField>
    </div>

    <FormActions @cancelar="cancelar" />
  </form>
</template>
