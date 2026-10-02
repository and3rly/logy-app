<script setup lang="ts">
// Dashboard: resumen de la sucursal de la sesión con datos reales (API dashboard/resumen)
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api, { mensajeError } from '../services/api'
import toaster from '../helpers/toaster'
import { useSesionStore } from '../stores/sesion'
import PageHeader from '../components/layout/PageHeader.vue'
import StatCard from '../components/dashboard/StatCard.vue'
import VentasChart, { type PuntoVenta } from '../components/dashboard/VentasChart.vue'
import { formatoMonto } from '../utils/numero'

interface Totales { ventas: number; total: number; ganancia: number }
interface Periodo { fecha: string; ventas: number; total: number }
interface Resumen {
  fecha: string
  simbolo: string
  ventas: { hoy: Totales; ayer: Totales; mes: Totales; mes_anterior: Totales }
  por_hora: { hora: number; ventas: number; total: number }[]
  diario: Periodo[]
  mensual: Periodo[]
  top_productos: { id: string; nombre: string; nunidad: string; cantidad: string; total: string; ganancia: string }[]
  formas_pago: { nombre: string; ventas: string; total: string }[]
  cobrar: {
    cuentas: number; saldo: number; vencidas: number; vencido: number
    proximas: { id: string; saldo: string; fecha_vence: string; dias: string; correlativo: string; ncliente: string }[]
  }
  pagar: { cuentas: number; saldo: number; vencidas: number; vencido: number }
  cotizaciones: {
    abiertas: number; total: number; vencidas: number; por_vencer: number
    estados: { BORRADOR: number; ENVIADA: number; ACEPTADA: number }
  }
  inventario: {
    productos: number; valor: number; disponible: number; minimo: number; agotado: number; por_vencer: number
    alertas: {
      producto_id: string; nombre: string; nunidad: string; existencia: number
      existencia_minima: number; proximo_vence: string | null; estado: 'agotado' | 'minimo' | 'disponible'
    }[]
  }
  ultimas_ventas: {
    id: string; fecha: string; correlativo: string; total_precio: string
    anulado: string; ncliente: string; nforma_pago: string
  }[]
}

const sesion = useSesionStore()
const datos = ref<Resumen | null>(null)
const cargando = ref(false)
const periodo = ref<'dias' | 'meses'>('dias')

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get<Resumen>('/dashboard/resumen')
    datos.value = data
  } catch (error) {
    toaster.error(mensajeError(error, 'No se pudo cargar el resumen.'))
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)
// Al cambiar de sucursal, el resumen es otro
watch(() => sesion.usuario?.sucursal?.id, cargar)

// --- Formatos -------------------------------------------------------------
const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']
const MESES_LARGO = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']
const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado']

const simbolo = computed(() => datos.value?.simbolo ?? '')
const monto = (n: number | string) => formatoMonto(n)
const dinero = (n: number | string) => `${simbolo.value} ${monto(n)}`.trim()
const cantidad = (n: number | string) => Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 2 })
// Unidad en plural si no es 1: unidad → unidades, metro → metros
function unidades(nombre: string, n: number | string) {
  const texto = nombre.toLowerCase()
  if (Number(n) === 1 || /s$/.test(texto)) return texto
  return /[aeiouáéó]$/.test(texto) ? `${texto}s` : `${texto}es`
}

function fechaLocal(texto: string) {
  const [a, m, d] = texto.slice(0, 10).split('-').map(Number)
  return new Date(a!, (m ?? 1) - 1, d ?? 1)
}
const fechaCorta = (texto: string) => {
  const f = fechaLocal(texto)
  return `${f.getDate()} ${MESES[f.getMonth()]}`
}
const hora = (texto: string) => texto.slice(11, 16)

// "Hoy 17:55", "Ayer 16:05" o "17 sep 03:23"
function cuando(texto: string) {
  if (!datos.value) return texto
  const dias = Math.round((fechaLocal(datos.value.fecha).getTime() - fechaLocal(texto).getTime()) / 86400000)
  const dia = dias === 0 ? 'Hoy' : dias === 1 ? 'Ayer' : fechaCorta(texto)
  return `${dia} ${hora(texto)}`
}

// Variación en %; null si no hay base para comparar
function variacion(actual: number, anterior: number) {
  if (!anterior) return actual ? null : 0
  return ((actual - anterior) / anterior) * 100
}

// --- Encabezado -----------------------------------------------------------
// Reloj de la pantalla: avanza cada minuto para que el saludo y la franja cambien solos
const ahora = ref(new Date())
const reloj = window.setInterval(() => (ahora.value = new Date()), 60000)
onBeforeUnmount(() => window.clearInterval(reloj))

// Momento del día: define los colores y la decoración de la franja de bienvenida
type Momento = 'manana' | 'tarde' | 'atardecer' | 'noche'
const momento = computed<Momento>(() => {
  const h = ahora.value.getHours()
  if (h >= 5 && h < 12) return 'manana'
  if (h >= 12 && h < 18) return 'tarde'
  if (h >= 18 && h < 20) return 'atardecer'
  return 'noche'
})
const esNoche = computed(() => momento.value === 'atardecer' || momento.value === 'noche')

const saludo = computed(() => {
  const h = ahora.value.getHours()
  return h >= 5 && h < 12 ? 'Buenos días' : h >= 12 && h < 19 ? 'Buenas tardes' : 'Buenas noches'
})
const iconoSaludo = computed(() => ({ manana: '☀️', tarde: '👋', atardecer: '🌇', noche: '🌙' })[momento.value])

// Estrellas de la noche: [izquierda %, arriba %, tamaño px], posiciones fijas
const estrellas = [
  [38, 12, 2], [44, 70, 1.5], [49, 30, 2.5], [53, 85, 1.5], [57, 14, 2], [61, 52, 1.5],
  [64, 24, 3], [67, 78, 2], [70, 40, 1.5], [74, 10, 2], [78, 66, 2.5], [82, 20, 1.5],
  [86, 88, 2], [90, 44, 1.5], [94, 16, 2.5], [97, 72, 1.5], [33, 50, 1.5], [41, 90, 2],
]
const nombre = computed(() => (sesion.usuario?.nombre ?? '').split(' ')[0])
const hoyTexto = computed(() => {
  const f = datos.value ? fechaLocal(datos.value.fecha) : new Date()
  const texto = `${DIAS[f.getDay()]} ${f.getDate()} de ${MESES_LARGO[f.getMonth()]} de ${f.getFullYear()}`
  return texto.charAt(0).toUpperCase() + texto.slice(1)
})
const mesTexto = computed(() => {
  const f = datos.value ? fechaLocal(datos.value.fecha) : new Date()
  return MESES_LARGO[f.getMonth()]
})

const porcentaje = (n: number) => `${Math.round(Math.abs(n))}%`

// Frase del día: compara lo de hoy con el promedio diario de los días anteriores del mes
const mensajeDia = computed(() => {
  const d = datos.value
  if (!d) return ''
  const { hoy, mes } = d.ventas
  const dia = fechaLocal(d.fecha).getDate()

  if (!hoy.ventas) {
    return mes.ventas
      ? `Aún no hay ventas hoy. En ${mesTexto.value} llevas ${dinero(mes.total)} en ${mes.ventas} ${mes.ventas === 1 ? 'venta' : 'ventas'}.`
      : 'Aún no hay ventas este mes. Registra la primera desde el punto de venta.'
  }

  const texto = `Llevas ${hoy.ventas} ${hoy.ventas === 1 ? 'venta' : 'ventas'} hoy`
  const anteriores = mes.total - hoy.total
  if (dia <= 1 || anteriores <= 0) return `${texto}. ¡Buen comienzo de ${mesTexto.value}!`

  // ¿Es el mejor día del mes? (días del mes actual en la serie diaria)
  const mesActual = d.fecha.slice(0, 7)
  const mejorAnterior = Math.max(0, ...d.diario.filter((p) => p.fecha.startsWith(mesActual) && p.fecha !== d.fecha).map((p) => p.total))
  if (hoy.total > mejorAnterior) return `${texto}: ¡ya es tu mejor día de ${mesTexto.value}! 🎉`

  const veces = hoy.total / (anteriores / (dia - 1))
  const cambio = (veces - 1) * 100
  if (Math.abs(cambio) < 5) return `${texto}, en línea con tu promedio diario del mes.`
  if (veces >= 2) return `${texto}, ${veces.toFixed(1)} veces tu promedio diario del mes.`
  return `${texto}, ${porcentaje(cambio)} ${cambio > 0 ? 'sobre' : 'bajo'} tu promedio diario del mes.`
})

// Ventas por hora de hoy: de 7:00 a 20:00, ampliado si hubo ventas fuera de ese rango
const horaActual = computed(() => ahora.value.getHours())
const horaActiva = ref<number | null>(null)

const horas = computed(() => {
  const lista = datos.value?.por_hora ?? []
  const conVentas = lista.filter((h) => h.ventas > 0).map((h) => h.hora)
  const desde = Math.min(7, ...conVentas)
  const hasta = Math.max(20, ...conVentas)
  const tramo = lista.filter((h) => h.hora >= desde && h.hora <= hasta)
  const maximo = Math.max(...tramo.map((h) => h.total), 1)
  return tramo.map((h) => ({ ...h, alto: (h.total / maximo) * 100 }))
})

const horaMostrada = computed(() => {
  const lista = horas.value
  if (horaActiva.value !== null) return lista.find((h) => h.hora === horaActiva.value) ?? null
  // Sin cursor: la hora más fuerte del día
  return lista.reduce<(typeof lista)[number] | null>((m, h) => (h.total > (m?.total ?? 0) ? h : m), null)
})

const rangoHora = (h: number) => `${String(h).padStart(2, '0')}:00 – ${String(h + 1).padStart(2, '0')}:00`

// Accesos rápidos de la franja de bienvenida
const accesos = computed(() => {
  const d = datos.value
  return [
    { to: '/venta', icono: 'fa-solid fa-cash-register', texto: 'Nueva venta', aviso: 0 },
    { to: '/cotizacion', icono: 'fa-solid fa-file-signature', texto: 'Cotizar', aviso: d?.cotizaciones.estados.ACEPTADA ?? 0 },
    { to: '/cuenta-cobrar', icono: 'fa-solid fa-hand-holding-dollar', texto: 'Cobrar', aviso: d?.cobrar.vencidas ?? 0 },
    { to: '/existencia', icono: 'fa-solid fa-boxes-stacked', texto: 'Existencias', aviso: (d?.inventario.agotado ?? 0) + (d?.inventario.minimo ?? 0) },
  ]
})

// --- Indicadores ----------------------------------------------------------
const indicadores = computed(() => {
  const d = datos.value
  if (!d) return []
  const { hoy, ayer, mes, mes_anterior } = d.ventas
  const margen = mes.total ? (mes.ganancia / mes.total) * 100 : 0
  const margenAnterior = mes_anterior.total ? (mes_anterior.ganancia / mes_anterior.total) * 100 : 0

  return [
    {
      titulo: 'Ventas de hoy',
      valor: dinero(hoy.total),
      icono: 'fa-solid fa-cash-register',
      color: 'primario' as const,
      variacion: variacion(hoy.total, ayer.total),
      comparado: 'vs. ayer',
      detalle: `${hoy.ventas} ${hoy.ventas === 1 ? 'venta' : 'ventas'}`,
    },
    {
      titulo: `Ventas de ${mesTexto.value}`,
      valor: dinero(mes.total),
      icono: 'fa-solid fa-chart-line',
      color: 'info' as const,
      variacion: variacion(mes.total, mes_anterior.total),
      comparado: 'vs. mismo tramo del mes anterior',
      detalle: mes.ventas ? `Ticket promedio ${dinero(mes.total / mes.ventas)}` : 'Sin ventas aún',
    },
    {
      titulo: 'Ganancia del mes',
      valor: dinero(mes.ganancia),
      icono: 'fa-solid fa-sack-dollar',
      color: 'success' as const,
      variacion: variacion(mes.ganancia, mes_anterior.ganancia),
      comparado: 'vs. mes anterior',
      detalle: `Margen ${margen.toFixed(1)}%${mes_anterior.total ? ` (antes ${margenAnterior.toFixed(1)}%)` : ''}`,
    },
    {
      titulo: 'Inventario al costo',
      valor: dinero(d.inventario.valor),
      icono: 'fa-solid fa-boxes-stacked',
      color: 'warning' as const,
      detalle: `${d.inventario.productos} productos · ${d.inventario.agotado} sin existencia`,
    },
  ]
})

// --- Gráfica de ventas ----------------------------------------------------
const serie = computed<PuntoVenta[]>(() => {
  const d = datos.value
  if (!d) return []
  if (periodo.value === 'dias') {
    return d.diario.map((p) => {
      const f = fechaLocal(p.fecha)
      return {
        etiqueta: fechaCorta(p.fecha),
        detalle: `${DIAS[f.getDay()]} ${f.getDate()} de ${MESES_LARGO[f.getMonth()]}`,
        total: p.total,
        ventas: p.ventas,
      }
    })
  }
  return d.mensual.map((p) => {
    const [a, m] = p.fecha.split('-').map(Number)
    return {
      etiqueta: `${MESES[(m ?? 1) - 1]} ${String(a).slice(2)}`,
      detalle: `${MESES_LARGO[(m ?? 1) - 1]} de ${a}`,
      total: p.total,
      ventas: p.ventas,
    }
  })
})

const resumenSerie = computed(() => {
  const puntos = serie.value
  const total = puntos.reduce((s, p) => s + p.total, 0)
  const ventas = puntos.reduce((s, p) => s + p.ventas, 0)
  const mejor = puntos.reduce<PuntoVenta | null>((m, p) => (p.total > (m?.total ?? 0) ? p : m), null)
  return { total, ventas, mejor, conVentas: puntos.filter((p) => p.ventas > 0).length }
})

// --- Productos y formas de pago ------------------------------------------
const topProductos = computed(() => {
  const lista = datos.value?.top_productos ?? []
  const maximo = Math.max(...lista.map((p) => Number(p.total)), 1)
  return lista.map((p) => ({ ...p, ancho: (Number(p.total) / maximo) * 100 }))
})

const formasPago = computed(() => {
  const lista = datos.value?.formas_pago ?? []
  const total = lista.reduce((s, f) => s + Number(f.total), 0) || 1
  return lista.map((f) => ({ ...f, parte: (Number(f.total) / total) * 100 }))
})

// --- Atención: lo que requiere acción hoy --------------------------------
interface Aviso { icono: string; tono: 'danger' | 'warning' | 'info'; titulo: string; texto: string; to: string }

const avisos = computed<Aviso[]>(() => {
  const d = datos.value
  if (!d) return []
  const lista: Aviso[] = []

  if (d.cobrar.vencidas) {
    lista.push({
      icono: 'fa-solid fa-hand-holding-dollar',
      tono: 'danger',
      titulo: `${d.cobrar.vencidas} ${d.cobrar.vencidas === 1 ? 'cuenta vencida' : 'cuentas vencidas'} por cobrar`,
      texto: `${dinero(d.cobrar.vencido)} pendientes de cobro`,
      to: '/cuenta-cobrar',
    })
  }
  if (d.pagar.vencidas) {
    lista.push({
      icono: 'fa-solid fa-file-invoice-dollar',
      tono: 'danger',
      titulo: `${d.pagar.vencidas} ${d.pagar.vencidas === 1 ? 'pago vencido' : 'pagos vencidos'} a proveedores`,
      texto: `${dinero(d.pagar.vencido)} por pagar`,
      to: '/cuenta-pagar',
    })
  }
  if (d.cotizaciones.estados.ACEPTADA) {
    lista.push({
      icono: 'fa-solid fa-file-signature',
      tono: 'info',
      titulo: `${d.cotizaciones.estados.ACEPTADA} ${d.cotizaciones.estados.ACEPTADA === 1 ? 'cotización aceptada' : 'cotizaciones aceptadas'}`,
      texto: 'Lista para convertir en venta',
      to: '/cotizacion',
    })
  }
  if (d.cotizaciones.por_vencer) {
    lista.push({
      icono: 'fa-solid fa-hourglass-half',
      tono: 'warning',
      titulo: `${d.cotizaciones.por_vencer} ${d.cotizaciones.por_vencer === 1 ? 'cotización vence' : 'cotizaciones vencen'} pronto`,
      texto: 'En los próximos 3 días',
      to: '/cotizacion',
    })
  }
  return lista
})

function textoAlerta(a: Resumen['inventario']['alertas'][number]) {
  if (a.estado === 'agotado') return 'Sin existencia'
  if (a.proximo_vence) {
    const dias = Math.round((fechaLocal(a.proximo_vence).getTime() - fechaLocal(datos.value!.fecha).getTime()) / 86400000)
    return dias < 0 ? `Vencido hace ${-dias} d` : dias === 0 ? 'Vence hoy' : `Vence en ${dias} d`
  }
  return `Quedan ${cantidad(a.existencia)} de ${cantidad(a.existencia_minima)} mín.`
}

const tonoAlerta = (a: Resumen['inventario']['alertas'][number]) =>
  a.estado === 'agotado' ? 'danger' : 'warning'

const inventarioEstados = computed(() => {
  const i = datos.value?.inventario
  if (!i) return []
  const total = i.productos || 1
  return [
    { id: 'disponible', nombre: 'Con existencia', valor: i.disponible, barra: 'bg-success', punto: 'text-success', parte: (i.disponible / total) * 100 },
    { id: 'minimo', nombre: 'Bajo mínimo', valor: i.minimo, barra: 'bg-warning', punto: 'text-warning', parte: (i.minimo / total) * 100 },
    { id: 'agotado', nombre: 'Sin existencia', valor: i.agotado, barra: 'bg-danger', punto: 'text-danger', parte: (i.agotado / total) * 100 },
  ]
})

const embudo = computed(() => {
  const e = datos.value?.cotizaciones.estados
  if (!e) return []
  return [
    { nombre: 'Borrador', valor: e.BORRADOR, icono: 'fa-solid fa-pen' },
    { nombre: 'Enviada', valor: e.ENVIADA, icono: 'fa-solid fa-paper-plane' },
    { nombre: 'Aceptada', valor: e.ACEPTADA, icono: 'fa-solid fa-circle-check' },
  ]
})

function diasVence(dias: string | number) {
  const n = Number(dias)
  if (n < 0) return { texto: `Vencida hace ${-n} d`, clase: 'text-danger' }
  if (n === 0) return { texto: 'Vence hoy', clase: 'text-warning-emphasis' }
  return { texto: `Vence en ${n} d`, clase: n <= 7 ? 'text-warning-emphasis' : 'text-body-secondary' }
}
</script>

<template>
  <PageHeader>
    <button type="button" class="btn btn-outline-primary" :disabled="cargando" @click="cargar">
      <i class="fa-solid fa-rotate me-1" :class="{ 'fa-spin': cargando }" aria-hidden="true" />Actualizar
    </button>
  </PageHeader>

  <!-- Bienvenida: saludo con la frase del día, accesos rápidos y el pulso de hoy -->
  <section class="dash-hero mb-3" :class="[`dash-hero-${momento}`, { 'dash-hero-oscura': esNoche }]">
    <!-- Cielo del momento: sol (mañana, tarde, atardecer) o luna y estrellas (noche) -->
    <div class="dash-hero-cielo" aria-hidden="true">
      <template v-if="momento === 'noche'">
        <span
          v-for="(e, i) in estrellas"
          :key="i"
          class="dash-hero-estrella"
          :style="{ left: `${e[0]}%`, top: `${e[1]}%`, width: `${e[2]}px`, height: `${e[2]}px`, animationDelay: `${(i % 5) * 0.7}s` }"
        />
        <span class="dash-hero-luna" />
      </template>
      <span v-else class="dash-hero-sol" />
    </div>

    <!-- Decoración: puntos y anillos -->
    <svg class="dash-hero-deco" viewBox="0 0 400 200" aria-hidden="true" preserveAspectRatio="xMaxYMid slice">
      <defs>
        <pattern id="dash-puntos" width="16" height="16" patternUnits="userSpaceOnUse">
          <circle cx="2" cy="2" r="1.4" />
        </pattern>
        <!-- Los puntos aparecen poco a poco de izquierda a derecha -->
        <linearGradient id="dash-desvanecer" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#fff" stop-opacity="0" />
          <stop offset="100%" stop-color="#fff" stop-opacity="1" />
        </linearGradient>
        <mask id="dash-mascara">
          <rect x="120" y="0" width="280" height="200" fill="url(#dash-desvanecer)" />
        </mask>
      </defs>
      <rect x="120" y="0" width="280" height="200" fill="url(#dash-puntos)" mask="url(#dash-mascara)" class="dash-hero-puntos" />
      <circle cx="360" cy="30" r="90" class="dash-hero-anillo" />
      <circle cx="360" cy="30" r="140" class="dash-hero-anillo" />
      <circle cx="40" cy="210" r="70" class="dash-hero-anillo" />
    </svg>

    <div class="dash-hero-texto">
      <div class="dash-hero-chips">
        <span class="dash-hero-chip"><i class="fa-regular fa-calendar me-1" aria-hidden="true" />{{ hoyTexto }}</span>
        <span v-if="sesion.usuario?.sucursal" class="dash-hero-chip">
          <i class="fa-solid fa-store me-1" aria-hidden="true" />{{ sesion.usuario.sucursal.nombre }}
        </span>
      </div>
      <h2 class="dash-hero-saludo">{{ saludo }}{{ nombre ? `, ${nombre}` : '' }} <span aria-hidden="true">{{ iconoSaludo }}</span></h2>
      <p class="dash-hero-mensaje">
        <template v-if="datos">{{ mensajeDia }}</template>
        <span v-else class="placeholder col-8 rounded" />
      </p>

      <nav class="dash-hero-accesos" aria-label="Accesos rápidos">
        <RouterLink v-for="a in accesos" :key="a.to" :to="a.to" class="dash-hero-acceso">
          <i :class="a.icono" aria-hidden="true" />
          <span>{{ a.texto }}</span>
          <span v-if="a.aviso" class="dash-hero-aviso" :title="`${a.aviso} pendiente(s)`">{{ a.aviso }}</span>
        </RouterLink>
      </nav>
    </div>

    <!-- Pulso de hoy -->
    <div v-if="datos" class="dash-hero-hoy">
      <div class="d-flex align-items-center justify-content-between gap-2">
        <span class="dash-hero-hoy-titulo"><span class="dash-hero-vivo" aria-hidden="true" />Hoy</span>
        <span class="dash-hero-hoy-hora">
          <template v-if="horaMostrada && horaMostrada.ventas">
            {{ rangoHora(horaMostrada.hora) }} · {{ dinero(horaMostrada.total) }}
          </template>
          <template v-else-if="horaActiva !== null">{{ rangoHora(horaActiva) }} · sin ventas</template>
          <template v-else>Ventas por hora</template>
        </span>
      </div>

      <div class="dash-hero-hoy-cifras">
        <div>
          <span>Vendido</span>
          <strong>{{ dinero(datos.ventas.hoy.total) }}</strong>
        </div>
        <div>
          <span>Ganancia</span>
          <strong>{{ dinero(datos.ventas.hoy.ganancia) }}</strong>
        </div>
        <div>
          <span>Ventas</span>
          <strong>{{ datos.ventas.hoy.ventas }}</strong>
        </div>
      </div>

      <div class="dash-hero-horas" role="img" aria-label="Ventas de hoy por hora" @mouseleave="horaActiva = null">
        <div
          v-for="h in horas"
          :key="h.hora"
          class="dash-hero-hora"
          :class="{ activa: horaActiva === h.hora, actual: h.hora === horaActual, futura: h.hora > horaActual }"
          @mouseenter="horaActiva = h.hora"
        >
          <span :style="{ height: h.total ? `${Math.max(h.alto, 8)}%` : '3px' }" />
        </div>
      </div>
      <div class="dash-hero-horas-eje">
        <span>{{ horas[0]?.hora }}:00</span>
        <span>{{ (horas[horas.length - 1]?.hora ?? 0) + 1 }}:00</span>
      </div>
    </div>
  </section>

  <!-- Carga inicial -->
  <div v-if="!datos && cargando" class="row g-3 mb-3" aria-busy="true">
    <div v-for="n in 4" :key="n" class="col-12 col-sm-6 col-xl-3">
      <div class="indicador-tarjeta placeholder-glow">
        <span class="placeholder col-6" />
        <span class="placeholder placeholder-lg col-8 mt-2" />
        <span class="placeholder col-4 mt-auto" />
      </div>
    </div>
  </div>

  <template v-if="datos">
    <!-- Indicadores -->
    <div class="row g-3 mb-3">
      <div v-for="ind in indicadores" :key="ind.titulo" class="col-12 col-sm-6 col-xl-3">
        <StatCard v-bind="ind" />
      </div>
    </div>

    <div class="row g-3 mb-3">
      <!-- Tendencia de ventas -->
      <div class="col-12 col-xl-8">
        <section class="dash-panel h-100">
          <header class="dash-panel-cabecera">
            <div>
              <h2 class="dash-panel-titulo">Tendencia de ventas</h2>
              <p class="dash-panel-sub">
                {{ periodo === 'dias' ? 'Últimos 30 días' : 'Últimos 12 meses' }} · pasa el cursor para ver cada {{ periodo === 'dias' ? 'día' : 'mes' }}
              </p>
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Periodo de la gráfica">
              <input id="per-dias" v-model="periodo" type="radio" class="btn-check" value="dias">
              <label class="btn btn-outline-primary" for="per-dias">30 días</label>
              <input id="per-meses" v-model="periodo" type="radio" class="btn-check" value="meses">
              <label class="btn btn-outline-primary" for="per-meses">12 meses</label>
            </div>
          </header>

          <div class="dash-mini-stats">
            <div>
              <span>Total del periodo</span>
              <strong>{{ dinero(resumenSerie.total) }}</strong>
            </div>
            <div>
              <span>Ventas</span>
              <strong>{{ resumenSerie.ventas }}</strong>
            </div>
            <div>
              <span>{{ periodo === 'dias' ? 'Días con ventas' : 'Meses con ventas' }}</span>
              <strong>{{ resumenSerie.conVentas }} de {{ serie.length }}</strong>
            </div>
            <div v-if="resumenSerie.mejor">
              <span>Mejor {{ periodo === 'dias' ? 'día' : 'mes' }}</span>
              <strong>{{ resumenSerie.mejor.etiqueta }}</strong>
            </div>
          </div>

          <VentasChart :datos="serie" :simbolo="simbolo" />
        </section>
      </div>

      <!-- Requiere atención -->
      <div class="col-12 col-xl-4">
        <section class="dash-panel h-100">
          <header class="dash-panel-cabecera">
            <div>
              <h2 class="dash-panel-titulo">Requiere atención</h2>
              <p class="dash-panel-sub">Lo que conviene revisar hoy</p>
            </div>
            <span
              v-if="avisos.length + datos.inventario.alertas.length"
              class="badge rounded-pill text-bg-danger"
            >{{ avisos.length + datos.inventario.alertas.length }}</span>
          </header>

          <ul class="dash-lista">
            <li v-for="a in avisos" :key="a.titulo">
              <RouterLink :to="a.to" class="dash-lista-item">
                <span class="icono-suave" :class="`icono-suave-${a.tono}`" aria-hidden="true"><i :class="a.icono" /></span>
                <span class="flex-grow-1 min-w-0">
                  <span class="d-block fw-semibold text-body text-truncate">{{ a.titulo }}</span>
                  <span class="d-block small text-body-secondary text-truncate">{{ a.texto }}</span>
                </span>
                <i class="fa-solid fa-chevron-right small text-body-tertiary" aria-hidden="true" />
              </RouterLink>
            </li>
            <li v-for="al in datos.inventario.alertas" :key="al.producto_id">
              <RouterLink to="/existencia" class="dash-lista-item">
                <span class="icono-suave" :class="`icono-suave-${tonoAlerta(al)}`" aria-hidden="true">
                  <i :class="al.estado === 'agotado' ? 'fa-solid fa-box-open' : al.proximo_vence ? 'fa-solid fa-calendar-xmark' : 'fa-solid fa-arrow-down-short-wide'" />
                </span>
                <span class="flex-grow-1 min-w-0">
                  <span class="d-block fw-semibold text-body text-truncate">{{ al.nombre }}</span>
                  <span class="d-block small text-truncate" :class="al.estado === 'agotado' ? 'text-danger' : 'text-warning-emphasis'">
                    {{ textoAlerta(al) }}
                  </span>
                </span>
                <i class="fa-solid fa-chevron-right small text-body-tertiary" aria-hidden="true" />
              </RouterLink>
            </li>
          </ul>

          <div v-if="!avisos.length && !datos.inventario.alertas.length" class="dash-vacio">
            <span class="icono-suave icono-suave-success" aria-hidden="true"><i class="fa-solid fa-check" /></span>
            <div>
              <div class="fw-semibold">Todo en orden</div>
              <div class="small text-body-secondary">Sin cuentas vencidas ni productos por reponer.</div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <!-- Productos más vendidos -->
      <div class="col-12 col-lg-6 col-xl-4">
        <section class="dash-panel h-100">
          <header class="dash-panel-cabecera">
            <div>
              <h2 class="dash-panel-titulo">Más vendidos</h2>
              <p class="dash-panel-sub">Por monto, en {{ mesTexto }}</p>
            </div>
          </header>

          <ol v-if="topProductos.length" class="dash-ranking">
            <li v-for="(p, i) in topProductos" :key="p.id">
              <span class="dash-ranking-pos">{{ i + 1 }}</span>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between gap-2">
                  <span class="text-truncate fw-medium" :title="p.nombre">{{ p.nombre }}</span>
                  <span class="fw-semibold text-nowrap tabular">{{ dinero(p.total) }}</span>
                </div>
                <div class="dash-pista"><span :style="{ width: `${p.ancho}%` }" /></div>
                <div class="small text-body-secondary">{{ cantidad(p.cantidad) }} {{ unidades(p.nunidad, p.cantidad) }} · ganancia {{ dinero(p.ganancia) }}</div>
              </div>
            </li>
          </ol>
          <p v-else class="dash-vacio-texto">Aún no hay ventas este mes.</p>
        </section>
      </div>

      <!-- Formas de pago -->
      <div class="col-12 col-lg-6 col-xl-4">
        <section class="dash-panel h-100">
          <header class="dash-panel-cabecera">
            <div>
              <h2 class="dash-panel-titulo">Formas de pago</h2>
              <p class="dash-panel-sub">Cómo pagaron los clientes en {{ mesTexto }}</p>
            </div>
          </header>

          <ul v-if="formasPago.length" class="dash-barras">
            <li v-for="f in formasPago" :key="f.nombre">
              <div class="d-flex justify-content-between gap-2 mb-1">
                <span class="fw-medium">{{ f.nombre }}</span>
                <span class="text-nowrap">
                  <span class="fw-semibold tabular">{{ dinero(f.total) }}</span>
                  <span class="text-body-secondary small ms-1">{{ Math.round(f.parte) }}%</span>
                </span>
              </div>
              <div class="dash-pista dash-pista-alta"><span :style="{ width: `${f.parte}%` }" /></div>
              <div class="small text-body-secondary mt-1">{{ f.ventas }} {{ Number(f.ventas) === 1 ? 'venta' : 'ventas' }}</div>
            </li>
          </ul>
          <p v-else class="dash-vacio-texto">Aún no hay ventas este mes.</p>
        </section>
      </div>

      <!-- Cuentas y cotizaciones -->
      <div class="col-12 col-xl-4">
        <section class="dash-panel h-100">
          <header class="dash-panel-cabecera">
            <div>
              <h2 class="dash-panel-titulo">Cuentas</h2>
              <p class="dash-panel-sub">Saldos pendientes de la sucursal</p>
            </div>
          </header>

          <div class="dash-cuentas">
            <RouterLink to="/cuenta-cobrar" class="dash-cuenta">
              <span class="small text-body-secondary"><i class="fa-solid fa-arrow-down me-1 text-success" aria-hidden="true" />Por cobrar</span>
              <strong class="tabular">{{ dinero(datos.cobrar.saldo) }}</strong>
              <span class="small" :class="datos.cobrar.vencidas ? 'text-danger' : 'text-body-secondary'">
                {{ datos.cobrar.cuentas }} {{ datos.cobrar.cuentas === 1 ? 'cuenta' : 'cuentas' }}
                <template v-if="datos.cobrar.vencidas"> · {{ datos.cobrar.vencidas }} vencida{{ datos.cobrar.vencidas === 1 ? '' : 's' }}</template>
              </span>
            </RouterLink>
            <RouterLink to="/cuenta-pagar" class="dash-cuenta">
              <span class="small text-body-secondary"><i class="fa-solid fa-arrow-up me-1 text-danger" aria-hidden="true" />Por pagar</span>
              <strong class="tabular">{{ dinero(datos.pagar.saldo) }}</strong>
              <span class="small" :class="datos.pagar.vencidas ? 'text-danger' : 'text-body-secondary'">
                {{ datos.pagar.cuentas }} {{ datos.pagar.cuentas === 1 ? 'cuenta' : 'cuentas' }}
                <template v-if="datos.pagar.vencidas"> · {{ datos.pagar.vencidas }} vencida{{ datos.pagar.vencidas === 1 ? '' : 's' }}</template>
              </span>
            </RouterLink>
          </div>

          <h3 class="dash-subtitulo">Próximos cobros</h3>
          <ul v-if="datos.cobrar.proximas.length" class="dash-mini-lista">
            <li v-for="c in datos.cobrar.proximas" :key="c.id">
              <div class="min-w-0">
                <div class="text-truncate fw-medium">{{ c.ncliente }}</div>
                <div class="small" :class="diasVence(c.dias).clase">{{ c.correlativo }} · {{ diasVence(c.dias).texto }}</div>
              </div>
              <span class="fw-semibold text-nowrap tabular">{{ dinero(c.saldo) }}</span>
            </li>
          </ul>
          <p v-else class="dash-vacio-texto">No hay cuentas por cobrar pendientes.</p>
        </section>
      </div>
    </div>

    <div class="row g-3">
      <!-- Últimas ventas -->
      <div class="col-12 col-xl-8">
        <section class="dash-panel h-100 p-0">
          <header class="dash-panel-cabecera px-3 pt-3">
            <div>
              <h2 class="dash-panel-titulo">Últimas ventas</h2>
              <p class="dash-panel-sub">Los movimientos más recientes del punto de venta</p>
            </div>
            <RouterLink to="/venta" class="btn btn-sm btn-suave-info">Ver punto de venta</RouterLink>
          </header>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 dash-tabla">
              <thead>
                <tr>
                  <th scope="col" class="ps-3">Venta</th>
                  <th scope="col">Cliente</th>
                  <th scope="col">Pago</th>
                  <th scope="col">Fecha</th>
                  <th scope="col" class="text-end pe-3">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="v in datos.ultimas_ventas" :key="v.id" :class="{ 'text-decoration-line-through text-body-secondary': v.anulado === '1' }">
                  <td class="ps-3 fw-semibold text-nowrap">{{ v.correlativo }}</td>
                  <td class="text-truncate" style="max-width: 220px">{{ v.ncliente }}</td>
                  <td><span class="badge rounded-pill bg-body-tertiary text-body border fw-normal">{{ v.nforma_pago }}</span></td>
                  <td class="text-nowrap text-body-secondary">{{ cuando(v.fecha) }}</td>
                  <td class="text-end pe-3 fw-semibold text-nowrap tabular">{{ dinero(v.total_precio) }}</td>
                </tr>
                <tr v-if="!datos.ultimas_ventas.length">
                  <td colspan="5" class="text-center text-body-secondary py-4">Aún no hay ventas en esta sucursal.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- Inventario y cotizaciones -->
      <div class="col-12 col-xl-4">
        <section class="dash-panel h-100">
          <header class="dash-panel-cabecera">
            <div>
              <h2 class="dash-panel-titulo">Inventario</h2>
              <p class="dash-panel-sub">{{ datos.inventario.productos }} productos en la sucursal</p>
            </div>
            <RouterLink to="/existencia" class="btn btn-sm btn-suave-info">Existencias</RouterLink>
          </header>

          <div class="progress-stacked mb-2" style="height: 8px">
            <div
              v-for="e in inventarioEstados"
              :key="e.id"
              class="progress"
              role="progressbar"
              :aria-label="e.nombre"
              :aria-valuenow="Math.round(e.parte)"
              aria-valuemin="0"
              aria-valuemax="100"
              :style="{ width: `${e.parte}%` }"
            >
              <div class="progress-bar" :class="e.barra" />
            </div>
          </div>
          <ul class="dash-leyenda">
            <li v-for="e in inventarioEstados" :key="e.id">
              <i class="fa-solid fa-circle" :class="e.punto" aria-hidden="true" />
              <span class="text-body-secondary">{{ e.nombre }}</span>
              <strong>{{ e.valor }}</strong>
            </li>
          </ul>

          <h3 class="dash-subtitulo">Cotizaciones abiertas</h3>
          <div class="dash-embudo">
            <RouterLink v-for="e in embudo" :key="e.nombre" to="/cotizacion" class="dash-embudo-paso">
              <i :class="e.icono" aria-hidden="true" />
              <strong>{{ e.valor }}</strong>
              <span>{{ e.nombre }}</span>
            </RouterLink>
          </div>
          <p class="small text-body-secondary mb-0 mt-2">
            <template v-if="datos.cotizaciones.abiertas">
              {{ dinero(datos.cotizaciones.total) }} en propuestas
              <template v-if="datos.cotizaciones.vencidas"> · <span class="text-danger">{{ datos.cotizaciones.vencidas }} vencida{{ datos.cotizaciones.vencidas === 1 ? '' : 's' }}</span></template>
            </template>
            <template v-else>Sin cotizaciones abiertas.</template>
          </p>
        </section>
      </div>
    </div>
  </template>
</template>
