// Impresión de documentos PDF generados por la API (ej. formato de una orden de compra).
// El PDF se pide con `api` (lleva el token de sesión) y se carga en un iframe que flota sobre la
// pantalla actual con el visor de PDF del navegador (imprimir, descargar, zoom).
import api from '../services/api'

// Un solo iframe reutilizable: se reemplaza su contenido en cada impresión
let fondo: HTMLDivElement | null = null
let iframe: HTMLIFrameElement | null = null
let cerrar: HTMLButtonElement | null = null
let urlAnterior = ''

function ocultar() {
  if (fondo) fondo.style.display = 'none'
  if (iframe) iframe.style.display = 'none'
  if (cerrar) cerrar.style.display = 'none'
  document.removeEventListener('keydown', alPresionarTecla)
}

function alPresionarTecla(e: KeyboardEvent) {
  if (e.key === 'Escape') ocultar()
}

function iframeVisor() {
  if (!iframe) {
    // Fondo tenue: un clic fuera del documento lo cierra
    fondo = document.createElement('div')
    fondo.style.cssText = 'position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.55)'
    fondo.addEventListener('click', ocultar)

    iframe = document.createElement('iframe')
    iframe.title = 'Documento'
    iframe.style.cssText = 'position:fixed;z-index:2001;top:3vh;left:50%;transform:translateX(-50%);width:min(1000px,94vw);height:94vh;border:0;border-radius:6px;box-shadow:0 10px 40px rgba(0,0,0,.4);background:#525659'

    cerrar = document.createElement('button')
    cerrar.type = 'button'
    cerrar.title = 'Cerrar'
    cerrar.setAttribute('aria-label', 'Cerrar')
    cerrar.className = 'btn btn-light rounded-circle shadow'
    cerrar.innerHTML = '<i class="fa-solid fa-xmark"></i>'
    cerrar.style.cssText = 'position:fixed;z-index:2002;top:calc(3vh - 14px);right:calc(50% - min(500px,47vw) - 14px);width:32px;height:32px;padding:0'
    cerrar.addEventListener('click', ocultar)

    document.body.append(fondo, iframe, cerrar)
  }
  return iframe
}

/** Descarga el PDF de la ruta de la API y lo muestra en el visor del navegador */
export async function imprimirPdf(ruta: string) {
  const { data } = await api.get<Blob>(ruta, { responseType: 'blob' })
  const url = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
  const marco = iframeVisor()

  marco.src = url
  fondo!.style.display = ''
  marco.style.display = ''
  cerrar!.style.display = ''
  document.addEventListener('keydown', alPresionarTecla)

  // El PDF anterior ya no se necesita
  if (urlAnterior) URL.revokeObjectURL(urlAnterior)
  urlAnterior = url
}
