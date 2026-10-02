// Catálogo de colores del sistema (acento) y cálculo de sus variables CSS.
// Cada color trae su escala de tonos: [50, 300, 400, 500, 600, 700, 800].

export interface ColorCatalogo {
  id: string
  nombre: string
  escala: [string, string, string, string, string, string, string]
  /** Grises: en modo oscuro necesitan un tono más claro para destacar */
  neutro?: boolean
  /** Tonos claros (amarillos): en modo claro se usa un paso más oscuro */
  calido?: boolean
}

// Paleta moderna generada en OKLCH (brillo parejo entre colores); dos filas de 7 en el selector
export const catalogoColores: ColorCatalogo[] = [
  { id: 'indigo', nombre: 'Índigo', escala: ['#f3f5ff', '#b8befe', '#979cff', '#7b79fe', '#6453fc', '#5341d9', '#4132ae'] },
  { id: 'violeta', nombre: 'Violeta', escala: ['#f6f4fe', '#c8b8fe', '#b091fe', '#9b69ff', '#873ff5', '#712ed3', '#5a23a9'] },
  { id: 'magenta', nombre: 'Magenta', escala: ['#fef1fc', '#f6a2ee', '#ea71e2', '#d743d0', '#bf12b9', '#a1009c', '#81017d'] },
  { id: 'rosa', nombre: 'Rosa', escala: ['#fef2f4', '#fea8b7', '#ff7393', '#ef4375', '#d6115d', '#b5004c', '#90013b'] },
  { id: 'coral', nombre: 'Coral', escala: ['#fff2f0', '#feac9e', '#fc7c69', '#ea5341', '#d23020', '#b31f12', '#8f170c'] },
  { id: 'naranja', nombre: 'Naranja', escala: ['#fef3ee', '#ffae89', '#f8834a', '#e45f01', '#bf4e00', '#a04000', '#803200'] },
  { id: 'ambar', nombre: 'Ámbar', escala: ['#fefaf4', '#ffcd95', '#ffb75e', '#f69e00', '#cc8305', '#a16500', '#805001'], calido: true },
  { id: 'lima', nombre: 'Lima', escala: ['#f7fdf1', '#bee795', '#a3dc5e', '#89cc1d', '#6fa901', '#568500', '#436900'], calido: true },
  { id: 'esmeralda', nombre: 'Esmeralda', escala: ['#eff8f2', '#91d6b1', '#52c08c', '#00a86e', '#008c5b', '#03744b', '#015c3b'] },
  { id: 'turquesa', nombre: 'Turquesa', escala: ['#eef8f7', '#8dd4cd', '#49bdb4', '#05a39a', '#018881', '#02716b', '#005a54'] },
  { id: 'oceano', nombre: 'Océano', escala: ['#eef7fc', '#8ccef1', '#4ab4e6', '#069acf', '#0680ad', '#036b91', '#025473'] },
  { id: 'azul', nombre: 'Azul', escala: ['#f0f6ff', '#a5c5ff', '#77a6fe', '#4a87ff', '#2369f4', '#1655d2', '#0f43a8'] },
  { id: 'grafito', nombre: 'Grafito', escala: ['#f4f5f7', '#bdc4d1', '#9ea8ba', '#838ea3', '#6b768b', '#586275', '#454d5c'], neutro: true },
  { id: 'piedra', nombre: 'Piedra', escala: ['#f6f5f4', '#cac3bd', '#b0a69e', '#978b82', '#7f736a', '#6a6058', '#544b45'], neutro: true },
]

export const COLOR_POR_DEFECTO = 'indigo'

// --- Utilidades de color ---------------------------------------------------

function aRgb(hex: string): [number, number, number] {
  const n = parseInt(hex.slice(1), 16)
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255]
}

function aHex([r, g, b]: [number, number, number]) {
  return `#${[r, g, b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('')}`
}

/** Mezcla `hex` con `con` en la proporción `p` (0 = hex, 1 = con) */
function mezclar(hex: string, con: string, p: number) {
  const a = aRgb(hex)
  const b = aRgb(con)
  return aHex([0, 1, 2].map((i) => a[i]! + (b[i]! - a[i]!) * p) as [number, number, number])
}

function luminancia(hex: string) {
  const lineal = aRgb(hex).map((v) => {
    const c = v / 255
    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4
  })
  return 0.2126 * lineal[0]! + 0.7152 * lineal[1]! + 0.0722 * lineal[2]!
}

function contraste(a: string, b: string) {
  const [l1, l2] = [luminancia(a), luminancia(b)].sort((x, y) => y - x)
  return (l1! + 0.05) / (l2! + 0.05)
}

/** Texto legible sobre un fondo: blanco si alcanza 3:1, si no, casi negro */
export function textoSobre(fondo: string) {
  return contraste(fondo, '#ffffff') >= 3 ? '#ffffff' : '#0f172a'
}

/** Escala aproximada para un color libre (se toma como el tono 600) */
function escalaDesde(hex: string): ColorCatalogo['escala'] {
  return [
    mezclar(hex, '#ffffff', 0.92),
    mezclar(hex, '#ffffff', 0.55),
    mezclar(hex, '#ffffff', 0.35),
    mezclar(hex, '#ffffff', 0.15),
    hex,
    mezclar(hex, '#000000', 0.18),
    mezclar(hex, '#000000', 0.35),
  ]
}

// --- Variables CSS por modo ------------------------------------------------

export type VariablesAcento = Record<string, string>

function variables(
  base: string,
  hover: string,
  suave: string,
  texto: string,
  hoverEnlace: string,
): VariablesAcento {
  return {
    '--color-primario': base,
    '--color-primario-rgb': aRgb(base).join(', '),
    '--color-primario-hover': hover,
    '--color-primario-suave': suave,
    '--color-primario-texto': texto,
    '--color-primario-contraste': textoSobre(base),
    '--bs-link-color-rgb': aRgb(texto).join(', '),
    '--bs-link-hover-color': hoverEnlace,
  }
}

/** Variables del acento para modo claro y oscuro */
export function variablesDeAcento(acento: string): { claro: VariablesAcento; oscuro: VariablesAcento } {
  const color = catalogoColores.find((c) => c.id === acento)
  const escala = color?.escala ?? escalaDesde(acento)
  const [t50, t300, t400, t500, t600, t700, t800] = escala

  // Claro: tono 600 (700 en los cálidos) sobre fondos blancos
  const claro = color?.calido
    ? variables(t700, t800, t50, t800, t800)
    : variables(t600, t700, t50, t700, t800)

  // Oscuro: tono 500 (400 en los grises) y texto en 300 sobre fondos oscuros
  const baseOscuro = color?.neutro ? t400 : t500
  const hoverOscuro = color?.neutro ? t300 : t400
  const oscuro = variables(
    baseOscuro,
    hoverOscuro,
    `rgba(${aRgb(baseOscuro).join(', ')}, 0.16)`,
    t300,
    mezclar(t300, '#ffffff', 0.35),
  )

  return { claro, oscuro }
}

/** Muestra del color en el selector (el tono de modo claro) */
export function muestraDe(acento: string) {
  return variablesDeAcento(acento).claro['--color-primario']!
}
