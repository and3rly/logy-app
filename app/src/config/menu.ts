// Tipos del menú lateral y las opciones fijas.
// El resto del menú viene de la base de datos (tablas modulo y menu): ver src/stores/menu.ts.

/** Enlace directo a una página */
export interface EnlaceMenu {
  texto: string
  /** Clase de Font Awesome; solo en el primer nivel */
  icono?: string
  to: string
  /** Contador opcional a la derecha (ej. pendientes) */
  insignia?: string
}

/** Grupo desplegable con subopciones */
export interface GrupoMenu {
  texto: string
  icono: string
  hijos: EnlaceMenu[]
}

export type OpcionMenu = EnlaceMenu | GrupoMenu

export interface SeccionMenu {
  titulo: string
  opciones: OpcionMenu[]
}

/** Opciones que no están en la base de datos */
export const menuFijo: SeccionMenu[] = [
  {
    titulo: 'Principal',
    opciones: [
      { texto: 'Inicio', icono: 'fa-solid fa-gauge', to: '/' },
    ],
  },
]
