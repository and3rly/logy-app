import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import { catalogoColores, COLOR_POR_DEFECTO, variablesDeAcento } from '../config/colores'
import { useTemaStore } from './tema'

// Claves en localStorage; index.html lee VARIABLES antes de pintar para evitar un destello
const CLAVE = 'logy-acento'
const CLAVE_VARIABLES = 'logy-acento-vars'

function acentoInicial() {
  try {
    const guardado = localStorage.getItem(CLAVE)
    if (guardado && (guardado.startsWith('#') || catalogoColores.some((c) => c.id === guardado))) return guardado
  } catch {
    // Almacenamiento bloqueado: color por defecto
  }
  return COLOR_POR_DEFECTO
}

/** Color del sistema: un id del catálogo o un color libre '#rrggbb' */
export const useAcentoStore = defineStore('acento', () => {
  const tema = useTemaStore()
  const acento = ref(acentoInicial())

  const esPersonalizado = computed(() => acento.value.startsWith('#'))
  const nombre = computed(() =>
    esPersonalizado.value
      ? `Personalizado (${acento.value})`
      : catalogoColores.find((c) => c.id === acento.value)?.nombre ?? '',
  )

  // Aplica las variables del modo actual en <html>; se recalcula al cambiar color o tema
  watch(
    [acento, () => tema.tema],
    ([valor, modo]) => {
      const vars = variablesDeAcento(valor)
      const actuales = modo === 'oscuro' ? vars.oscuro : vars.claro
      for (const [nombreVar, v] of Object.entries(actuales)) {
        document.documentElement.style.setProperty(nombreVar, v)
      }
      try {
        localStorage.setItem(CLAVE, valor)
        localStorage.setItem(CLAVE_VARIABLES, JSON.stringify(vars))
      } catch {
        // Sin almacenamiento: el color dura solo esta visita
      }
    },
    { immediate: true },
  )

  function elegir(valor: string) {
    acento.value = valor
  }

  return { acento, esPersonalizado, nombre, elegir }
})
