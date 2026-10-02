// Tipos compartidos por las tablas de listado

/** Color de la píldora de estado */
export type VarianteEtiqueta = 'exito' | 'aviso' | 'peligro' | 'neutro'

export interface Columna {
  clave: string
  titulo: string
  /** 'fin' para números: se alinean a la derecha */
  alinear?: 'inicio' | 'fin'
  /**
   * 'etiqueta': píldora de estado
   * 'avatar': iniciales en un círculo antes del texto (personas)
   */
  formato?: 'texto' | 'etiqueta' | 'avatar'
  /** Color de la píldora según el valor (ej. { Activo: 'exito' }) */
  etiquetas?: Record<string, VarianteEtiqueta>
}

export type Fila = Record<string, string>

/** Filtro desplegable: sus opciones salen de los valores de la columna */
export interface FiltroTabla {
  clave: string
  titulo: string
}

/** Orden actual de la tabla */
export interface Orden {
  clave: string
  dir: 'asc' | 'desc'
}
