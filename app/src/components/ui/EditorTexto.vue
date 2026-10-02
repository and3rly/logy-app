<script setup lang="ts">
// Editor de texto con formato básico (Tiptap). v-model en HTML; vacío = ''.
// La API limpia el HTML al guardar (helper limpiarHtml).
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Placeholder } from '@tiptap/extensions'
import TextAlign from '@tiptap/extension-text-align'

const props = withDefaults(defineProps<{
  modelValue?: string | null
  id?: string
  placeholder?: string
  /** Solo lectura: oculta la barra y no permite escribir (ej. dentro de un fieldset deshabilitado) */
  disabled?: boolean
}>(), {
  modelValue: '',
  id: undefined,
  placeholder: '',
  disabled: false,
})

const emit = defineEmits<{ 'update:modelValue': [valor: string] }>()

const valorActual = () => (editor.value?.isEmpty ? '' : editor.value?.getHTML() ?? '')

// Los textos guardados antes del editor son texto plano: cada línea pasa a ser un párrafo
function aHtml(valor: string | null | undefined): string {
  const texto = valor ?? ''
  if (texto.trim() === '' || /^\s*</.test(texto)) return texto

  const escapar = (linea: string) =>
    linea.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  return texto.split(/\r?\n/).map((linea) => `<p>${escapar(linea)}</p>`).join('')
}

const editor = useEditor({
  content: aHtml(props.modelValue),
  editable: !props.disabled,
  extensions: [
    // Solo el formato que la API permite guardar
    StarterKit.configure({
      heading: false,
      blockquote: false,
      code: false,
      codeBlock: false,
      horizontalRule: false,
      link: {
        openOnClick: false,
        autolink: true,
        defaultProtocol: 'https',
      },
    }),
    TextAlign.configure({ types: ['paragraph'] }),
    Placeholder.configure({ placeholder: () => props.placeholder }),
  ],
  editorProps: {
    attributes: {
      class: 'editor-texto-contenido',
      ...(props.id ? { id: props.id } : {}),
    },
  },
  onUpdate: () => emit('update:modelValue', valorActual()),
})

// Cambios desde fuera (cargar un registro, limpiar el formulario)
watch(
  () => props.modelValue,
  (valor) => {
    if (!editor.value || (valor ?? '') === valorActual()) return
    editor.value.commands.setContent(aHtml(valor), { emitUpdate: false })
  },
)

watch(
  () => props.disabled,
  (disabled) => {
    editor.value?.setEditable(!disabled, false)
    if (disabled) enlaceAbierto.value = false
  },
)

onBeforeUnmount(() => editor.value?.destroy())

// --- Barra de herramientas ------------------------------------------------
const botones = [
  { titulo: 'Negrita (Ctrl+B)', icono: 'fa-bold', activo: 'bold', accion: () => editor.value?.chain().focus().toggleBold().run() },
  { titulo: 'Cursiva (Ctrl+I)', icono: 'fa-italic', activo: 'italic', accion: () => editor.value?.chain().focus().toggleItalic().run() },
  { titulo: 'Subrayado (Ctrl+U)', icono: 'fa-underline', activo: 'underline', accion: () => editor.value?.chain().focus().toggleUnderline().run() },
  { titulo: 'Tachado', icono: 'fa-strikethrough', activo: 'strike', accion: () => editor.value?.chain().focus().toggleStrike().run() },
  null,
  { titulo: 'Lista con viñetas', icono: 'fa-list-ul', activo: 'bulletList', accion: () => editor.value?.chain().focus().toggleBulletList().run() },
  { titulo: 'Lista numerada', icono: 'fa-list-ol', activo: 'orderedList', accion: () => editor.value?.chain().focus().toggleOrderedList().run() },
  null,
  { titulo: 'Alinear a la izquierda', icono: 'fa-align-left', activo: () => !['center', 'right', 'justify'].some((a) => editor.value?.isActive({ textAlign: a })), accion: () => editor.value?.chain().focus().unsetTextAlign().run() },
  { titulo: 'Centrar', icono: 'fa-align-center', activo: { textAlign: 'center' }, accion: () => editor.value?.chain().focus().setTextAlign('center').run() },
  { titulo: 'Alinear a la derecha', icono: 'fa-align-right', activo: { textAlign: 'right' }, accion: () => editor.value?.chain().focus().setTextAlign('right').run() },
  { titulo: 'Justificar', icono: 'fa-align-justify', activo: { textAlign: 'justify' }, accion: () => editor.value?.chain().focus().setTextAlign('justify').run() },
]

// Izquierda es la alineación normal: se guarda sin estilo y se marca si no hay otra
function estaActivo(b: { activo: string | Record<string, string> | (() => boolean) }) {
  if (typeof b.activo === 'function') return b.activo()
  if (typeof b.activo === 'string') return !!editor.value?.isActive(b.activo)
  return !!editor.value?.isActive(b.activo)
}

// Enlace: se escribe la dirección en una fila bajo la barra
const enlaceAbierto = ref(false)
const enlaceUrl = ref('')
const enlaceInput = ref<HTMLInputElement | null>(null)

async function abrirEnlace() {
  enlaceUrl.value = editor.value?.getAttributes('link').href ?? ''
  enlaceAbierto.value = true
  await nextTick()
  enlaceInput.value?.focus()
}

function aplicarEnlace() {
  const url = enlaceUrl.value.trim()
  const cadena = editor.value?.chain().focus().extendMarkRange('link')

  if (url) cadena?.setLink({ href: /^(https?:\/\/|mailto:)/i.test(url) ? url : `https://${url}` }).run()
  else cadena?.unsetLink().run()

  enlaceAbierto.value = false
}

function cancelarEnlace() {
  enlaceAbierto.value = false
  editor.value?.commands.focus()
}
</script>

<template>
  <div class="editor-texto" :class="{ 'editor-texto-deshabilitado': disabled }">
    <div v-if="editor && !disabled" class="editor-texto-barra" role="toolbar" aria-label="Formato del texto">
      <template v-for="(b, i) in botones" :key="i">
        <span v-if="!b" class="editor-texto-separador" aria-hidden="true" />
        <button
          v-else
          type="button"
          class="editor-texto-boton"
          :class="{ activo: estaActivo(b) }"
          :title="b.titulo"
          :aria-label="b.titulo"
          :aria-pressed="estaActivo(b)"
          @click="b.accion"
        >
          <i class="fa-solid" :class="b.icono" aria-hidden="true" />
        </button>
      </template>

      <span class="editor-texto-separador" aria-hidden="true" />
      <button
        type="button"
        class="editor-texto-boton"
        :class="{ activo: editor.isActive('link') || enlaceAbierto }"
        title="Enlace"
        aria-label="Enlace"
        @click="abrirEnlace"
      >
        <i class="fa-solid fa-link" aria-hidden="true" />
      </button>

      <span class="ms-auto" />
      <button
        type="button"
        class="editor-texto-boton"
        title="Deshacer (Ctrl+Z)"
        aria-label="Deshacer"
        :disabled="!editor.can().undo()"
        @click="editor.chain().focus().undo().run()"
      >
        <i class="fa-solid fa-rotate-left" aria-hidden="true" />
      </button>
      <button
        type="button"
        class="editor-texto-boton"
        title="Rehacer (Ctrl+Y)"
        aria-label="Rehacer"
        :disabled="!editor.can().redo()"
        @click="editor.chain().focus().redo().run()"
      >
        <i class="fa-solid fa-rotate-right" aria-hidden="true" />
      </button>
    </div>

    <div v-if="enlaceAbierto && !disabled" class="editor-texto-enlace">
      <i class="fa-solid fa-link text-body-secondary" aria-hidden="true" />
      <input
        ref="enlaceInput"
        v-model="enlaceUrl"
        type="text"
        class="form-control form-control-sm"
        placeholder="https://… (vacío para quitar el enlace)"
        aria-label="Dirección del enlace"
        @keydown.enter.prevent="aplicarEnlace"
        @keydown.esc.prevent="cancelarEnlace"
      >
      <button type="button" class="btn btn-sm btn-primary" @click="aplicarEnlace">Aplicar</button>
      <button type="button" class="btn btn-sm btn-outline-secondary" @click="cancelarEnlace">Cancelar</button>
    </div>

    <EditorContent :editor="editor" />
  </div>
</template>
