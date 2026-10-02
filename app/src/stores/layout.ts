import { defineStore } from 'pinia'
import { ref } from 'vue'

// Mismo corte que el breakpoint "md" de Bootstrap
const MOVIL = '(max-width: 767.98px)'

export const useLayoutStore = defineStore('layout', () => {
  /** Escritorio: menú reducido a solo iconos */
  const colapsado = ref(false)
  /** Móvil: menú visible encima del contenido */
  const abierto = ref(false)

  function alternarMenu() {
    if (window.matchMedia(MOVIL).matches) {
      abierto.value = !abierto.value
    } else {
      colapsado.value = !colapsado.value
    }
  }

  function cerrarMenuMovil() {
    abierto.value = false
  }

  return { colapsado, abierto, alternarMenu, cerrarMenuMovil }
})
