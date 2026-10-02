/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

// @meforma/vue-toaster no trae tipos (ver src/helpers/toaster.ts)
declare module '@meforma/vue-toaster' {
  interface OpcionesToast {
    position?: 'top' | 'bottom' | 'top-right' | 'bottom-right' | 'top-left' | 'bottom-left'
    duration?: number | false
    dismissible?: boolean
    maxToasts?: number | false
  }

  type Mostrar = (mensaje: string, opciones?: OpcionesToast) => void

  export interface Toaster {
    show: Mostrar
    success: Mostrar
    error: Mostrar
    info: Mostrar
    warning: Mostrar
    clear: () => void
  }

  export function createToaster(opciones?: OpcionesToast): Toaster
}
