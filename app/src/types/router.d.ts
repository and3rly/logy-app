import 'vue-router'

export {}

// Datos propios que cada ruta puede declarar en `meta`
declare module 'vue-router' {
  interface RouteMeta {
    /** Título de la página (encabezado y pestaña del navegador) */
    titulo?: string
    /** Frase corta bajo el título */
    descripcion?: string
    /** Ruta de navegación después de "Inicio" */
    migas?: { texto: string; to?: string }[]
    /** Se puede ver sin iniciar sesión */
    publica?: boolean
  }
}
