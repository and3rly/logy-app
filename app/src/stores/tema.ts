import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export type Tema = 'claro' | 'oscuro'

// Clave en localStorage; index.html la lee antes de pintar para evitar un destello
const CLAVE = 'logy-tema'

function temaInicial(): Tema {
  try {
    const guardado = localStorage.getItem(CLAVE)
    if (guardado === 'claro' || guardado === 'oscuro') return guardado
  } catch {
    // Almacenamiento bloqueado: se usa la preferencia del sistema
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oscuro' : 'claro'
}

export const useTemaStore = defineStore('tema', () => {
  const tema = ref<Tema>(temaInicial())

  // Bootstrap 5.3 cambia sus componentes con data-bs-theme en <html>
  watch(
    tema,
    (valor) => {
      document.documentElement.setAttribute('data-bs-theme', valor === 'oscuro' ? 'dark' : 'light')
      try {
        localStorage.setItem(CLAVE, valor)
      } catch {
        // Sin almacenamiento: el tema dura solo esta visita
      }
    },
    { immediate: true },
  )

  function alternarTema() {
    tema.value = tema.value === 'oscuro' ? 'claro' : 'oscuro'
  }

  return { tema, alternarTema }
})
