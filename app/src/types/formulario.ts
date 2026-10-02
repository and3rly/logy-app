// Tipos de los formularios generados a partir de una lista de campos

export interface CampoFormulario {
  clave: string
  etiqueta: string
  tipo: 'texto' | 'correo' | 'numero' | 'fecha' | 'seleccion' | 'area'
  /** Opciones de un campo 'seleccion' */
  opciones?: string[]
  requerido?: boolean
  placeholder?: string
  /** 'completo' ocupa toda la fila (por defecto, las áreas de texto) */
  ancho?: 'normal' | 'completo'
}

export type ValoresFormulario = Record<string, string>
