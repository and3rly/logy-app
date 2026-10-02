// Colores de etiqueta (ej. categoria.etiqueta). Se guarda el `id`, que es el mismo nombre
// que usaba el proyecto guía ('primary', 'lime'...), y se pinta con los tonos del
// catálogo de colores del sistema (config/colores.ts).
import { catalogoColores } from './colores'

export interface ColorEtiqueta {
  /** Valor que se guarda en la base */
  id: string
  nombre: string
  /** Color del catálogo del sistema que lo pinta */
  color: string
}

export const coloresEtiqueta: ColorEtiqueta[] = [
  { id: 'primary', nombre: 'Azul', color: 'azul' },
  { id: 'indigo', nombre: 'Índigo', color: 'indigo' },
  { id: 'purple', nombre: 'Lila', color: 'violeta' },
  { id: 'pink', nombre: 'Rosado', color: 'rosa' },
  { id: 'danger', nombre: 'Rojo', color: 'coral' },
  { id: 'warning', nombre: 'Naranja', color: 'naranja' },
  { id: 'yellow', nombre: 'Amarillo', color: 'ambar' },
  { id: 'lime', nombre: 'Lima', color: 'lima' },
  { id: 'success', nombre: 'Verde', color: 'esmeralda' },
  { id: 'teal', nombre: 'Turquesa', color: 'turquesa' },
  { id: 'info', nombre: 'Celeste', color: 'oceano' },
  { id: 'secondary', nombre: 'Gris', color: 'grafito' },
  { id: 'light', nombre: 'Piedra', color: 'piedra' },
  { id: 'magenta', nombre: 'Magenta', color: 'magenta' },
]

/** Color para una etiqueta nueva */
export const ETIQUETA_POR_DEFECTO = 'primary'

function rgb(hex: string) {
  const n = parseInt(hex.slice(1), 16)
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255].join(', ')
}

// Los valores desconocidos ('default', 'dark' o vacíos) se muestran en gris
function colorDe(id: string | null | undefined) {
  const etiqueta = coloresEtiqueta.find((e) => e.id === id) ?? coloresEtiqueta.find((e) => e.id === 'secondary')!
  return catalogoColores.find((c) => c.id === etiqueta.color)!
}

/**
 * Variables para la clase .etiqueta-color (app.css): fondo y borde suaves del color,
 * texto oscuro en modo claro y claro en modo oscuro
 */
export function estiloEtiqueta(id: string | null | undefined) {
  const [, t300, , t500, , t700, t800] = colorDe(id).escala
  const calido = colorDe(id).calido

  return {
    '--etq-rgb': rgb(t500),
    '--etq-texto': calido ? t800 : t700,
    '--etq-texto-oscuro': t300,
  }
}

/** Muestra del color en el selector */
export function muestraEtiqueta(id: string) {
  return colorDe(id).escala[4]
}
