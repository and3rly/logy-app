import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import AdminLayout from '../layouts/AdminLayout.vue'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import ListView from '../views/ListView.vue'
import FormView from '../views/FormView.vue'
import MonedaPrincipal from '../views/mnt/moneda/Principal.vue'
import UnidadMedidaPrincipal from '../views/mnt/unidad_medida/Principal.vue'
import MarcaPrincipal from '../views/mnt/marca/Principal.vue'
import CategoriaPrincipal from '../views/mnt/categoria/Principal.vue'
import ClientePrincipal from '../views/mnt/cliente/Principal.vue'
import ProductoPrincipal from '../views/mnt/producto/Principal.vue'
import ListaPrecioPrincipal from '../views/mnt/lista_precio/Principal.vue'
import ProveedorPrincipal from '../views/mnt/proveedor/Principal.vue'
import UsuarioPrincipal from '../views/mnt/usuario/Principal.vue'
import SucursalPrincipal from '../views/mnt/sucursal/Principal.vue'
import RolPrincipal from '../views/mnt/rol/Principal.vue'
import MenuPrincipal from '../views/mnt/menu/Principal.vue'
import ParametrosPrincipal from '../views/mnt/parametros/Principal.vue'
import CompraPrincipal from '../views/com/compra/Principal.vue'
import VentaPrincipal from '../views/ven/venta/Principal.vue'
import CotizacionPrincipal from '../views/ven/cotizacion/Principal.vue'
import CuentaCobrarPrincipal from '../views/fin/cuenta_cobrar/Principal.vue'
import CuentaPagarPrincipal from '../views/fin/cuenta_pagar/Principal.vue'
import ExistenciaPrincipal from '../views/inv/existencia/Principal.vue'
import AjustePrincipal from '../views/inv/ajuste/Principal.vue'
import KardexPrincipal from '../views/inv/kardex/Principal.vue'
import InventarioPrincipal from '../views/inv/inventario/Principal.vue'
import ConversionPrincipal from '../views/inv/conversion/Principal.vue'
import TrasladoPrincipal from '../views/inv/traslado/Principal.vue'
import PerfilPrincipal from '../views/perfil/Principal.vue'
import VentaDiaPrincipal from '../views/rep/venta_dia/Principal.vue'
import { useSesionStore } from '../stores/sesion'
import { useMenuStore } from '../stores/menu'
import toaster from '../helpers/toaster'
import api from '../services/api'

// Rutas de un módulo con listado + formulario de nuevo registro.
// `tituloNuevo` es el título del formulario (ej. 'Nuevo producto').
// `descripcion` es la frase bajo el título del listado.
// `grupo` es el grupo del menú al que pertenece (se muestra en el breadcrumb).
function modulo(
  ruta: string,
  titulo: string,
  tituloNuevo: string,
  descripcion: string,
  grupo?: string,
): RouteRecordRaw[] {
  const base = grupo ? [{ texto: grupo }] : []
  return [
    {
      path: ruta,
      name: ruta,
      component: ListView,
      meta: { titulo, descripcion, migas: [...base, { texto: titulo }] },
    },
    {
      path: `${ruta}/nuevo`,
      name: `${ruta}-nuevo`,
      component: FormView,
      props: { modulo: ruta },
      meta: {
        titulo: tituloNuevo,
        descripcion: 'Completa los datos y guarda. Los campos con * son obligatorios.',
        migas: [...base, { texto: titulo, to: `/${ruta}` }, { texto: 'Nuevo' }],
      },
    },
  ]
}

const routes: RouteRecordRaw[] = [
  // --- Público ---
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { titulo: 'Iniciar sesión', publica: true },
  },
  {
    path: '/instalacion',
    name: 'instalacion',
    component: () => import('../views/InstalacionView.vue'),
    meta: { titulo: 'Instalación', publica: true },
  },

  // --- Panel administrativo ---
  {
    path: '/',
    component: AdminLayout,
    children: [
      {
        path: '',
        name: 'dashboard',
        component: DashboardView,
        meta: { titulo: 'Inicio', descripcion: 'Resumen de la operación del mes' },
      },
      {
        path: 'perfil',
        name: 'perfil',
        component: PerfilPrincipal,
        meta: {
          titulo: 'Mi perfil',
          descripcion: 'Sus datos, su actividad y la seguridad de su cuenta',
          migas: [{ texto: 'Mi perfil' }],
        },
      },
      // --- Mantenimientos (views/mnt) ---
      // Formulario y lista en la misma página: editar no cambia de ruta
      {
        path: 'moneda',
        name: 'moneda',
        component: MonedaPrincipal,
        meta: { titulo: 'Monedas', migas: [{ texto: 'Catálogos' }, { texto: 'Monedas' }] },
      },
      {
        path: 'unidad_medida',
        name: 'unidad_medida',
        component: UnidadMedidaPrincipal,
        meta: { titulo: 'Unidades de medida', migas: [{ texto: 'Catálogos' }, { texto: 'Unidades de medida' }] },
      },
      {
        path: 'marca',
        name: 'marca',
        component: MarcaPrincipal,
        meta: { titulo: 'Marcas', migas: [{ texto: 'Catálogos' }, { texto: 'Marcas' }] },
      },
      {
        path: 'categoria',
        name: 'categoria',
        component: CategoriaPrincipal,
        meta: { titulo: 'Categorías', migas: [{ texto: 'Catálogos' }, { texto: 'Categorías' }] },
      },
      {
        path: 'cliente',
        name: 'cliente',
        component: ClientePrincipal,
        meta: { titulo: 'Clientes', migas: [{ texto: 'Catálogos' }, { texto: 'Clientes' }] },
      },
      {
        path: 'producto',
        name: 'producto',
        component: ProductoPrincipal,
        meta: { titulo: 'Productos', migas: [{ texto: 'Catálogos' }, { texto: 'Productos' }] },
      },
      {
        path: 'lista-precio',
        name: 'lista-precio',
        component: ListaPrecioPrincipal,
        meta: { titulo: 'Listas de precios', migas: [{ texto: 'Catálogos' }, { texto: 'Listas de precios' }] },
      },
      {
        path: 'proveedor',
        name: 'proveedor',
        component: ProveedorPrincipal,
        meta: { titulo: 'Proveedores', migas: [{ texto: 'Compra' }, { texto: 'Proveedores' }] },
      },
      // --- Compras (views/com) ---
      {
        path: 'compra',
        name: 'compra',
        component: CompraPrincipal,
        meta: { titulo: 'Órdenes de compra', migas: [{ texto: 'Compra' }, { texto: 'Órdenes de compra' }] },
      },
      // --- Ventas (views/ven) ---
      {
        path: 'venta',
        name: 'venta',
        component: VentaPrincipal,
        meta: { titulo: 'Punto de venta', migas: [{ texto: 'Venta' }] },
      },
      {
        path: 'cotizacion',
        name: 'cotizacion',
        component: CotizacionPrincipal,
        meta: {
          titulo: 'Cotizaciones',
          descripcion: 'Propuestas de precio para los clientes de la sucursal',
          migas: [{ texto: 'Cotización' }],
        },
      },
      // --- Finanzas (views/fin) ---
      {
        path: 'cuenta-cobrar',
        name: 'cuenta-cobrar',
        component: CuentaCobrarPrincipal,
        meta: {
          titulo: 'Cuentas por cobrar',
          descripcion: 'Saldos de las ventas al crédito de la sucursal y sus abonos',
          migas: [{ texto: 'Finanzas' }, { texto: 'Cuentas por cobrar' }],
        },
      },
      {
        path: 'cuenta-pagar',
        name: 'cuenta-pagar',
        component: CuentaPagarPrincipal,
        meta: {
          titulo: 'Cuentas por pagar',
          descripcion: 'Saldos de las compras al crédito de la sucursal y sus pagos',
          migas: [{ texto: 'Finanzas' }, { texto: 'Cuentas por pagar' }],
        },
      },
      // --- Inventario (views/inv) ---
      {
        path: 'existencia',
        name: 'existencia',
        component: ExistenciaPrincipal,
        meta: {
          titulo: 'Existencias',
          descripcion: 'Inventario de la sucursal por producto, con su valor al costo',
          migas: [{ texto: 'Inventario' }, { texto: 'Existencias' }],
        },
      },
      {
        path: 'kardex',
        name: 'kardex',
        component: KardexPrincipal,
        meta: {
          titulo: 'Kardex',
          descripcion: 'Historial de entradas y salidas de un producto en la sucursal, con su saldo',
          migas: [{ texto: 'Inventario' }, { texto: 'Kardex' }],
        },
      },
      {
        path: 'ajuste',
        name: 'ajuste',
        component: AjustePrincipal,
        meta: {
          titulo: 'Ajustes de inventario',
          descripcion: 'Entradas y salidas por mermas, daños, consumo y otros motivos',
          migas: [{ texto: 'Inventario' }, { texto: 'Ajustes' }],
        },
      },
      {
        path: 'inventario',
        name: 'inventario',
        component: InventarioPrincipal,
        meta: {
          titulo: 'Inventario inicial',
          descripcion: 'Existencias con las que arranca la sucursal, cargadas desde Excel',
          migas: [{ texto: 'Inventario' }, { texto: 'Inventario inicial' }],
        },
      },
      {
        path: 'conversion',
        name: 'conversion',
        component: ConversionPrincipal,
        meta: {
          titulo: 'Conversiones',
          descripcion: 'Pasar de una medida a otra: abrir un quintal en libras o armar quintales con libras',
          migas: [{ texto: 'Inventario' }, { texto: 'Conversiones' }],
        },
      },
      {
        path: 'traslado',
        name: 'traslado',
        component: TrasladoPrincipal,
        meta: {
          titulo: 'Traslados',
          descripcion: 'Envío de producto a otra sucursal y recepción de lo que llega',
          migas: [{ texto: 'Inventario' }, { texto: 'Traslados' }],
        },
      },
      {
        path: 'usuario',
        name: 'usuario',
        component: UsuarioPrincipal,
        meta: { titulo: 'Usuarios', migas: [{ texto: 'Configuración' }, { texto: 'Usuarios' }] },
      },
      {
        path: 'parametros',
        name: 'parametros',
        component: ParametrosPrincipal,
        meta: {
          titulo: 'Parámetros',
          descripcion: 'Datos de la empresa y preferencias generales del sistema',
          migas: [{ texto: 'Configuración' }, { texto: 'Parámetros' }],
        },
      },
      {
        path: 'sucursal',
        name: 'sucursal',
        component: SucursalPrincipal,
        meta: { titulo: 'Sucursales', migas: [{ texto: 'Configuración' }, { texto: 'Sucursales' }] },
      },
      {
        path: 'rol',
        name: 'rol',
        component: RolPrincipal,
        meta: { titulo: 'Roles', migas: [{ texto: 'Configuración' }, { texto: 'Roles' }] },
      },
      {
        path: 'menu',
        name: 'menu',
        component: MenuPrincipal,
        meta: {
          titulo: 'Menú',
          descripcion: 'Módulos y opciones del menú lateral',
          migas: [{ texto: 'Configuración' }, { texto: 'Menú' }],
        },
      },
      ...modulo('inventario/productos', 'Productos', 'Nuevo producto', 'Catálogo de productos, stock y precios', 'Inventario'),
      ...modulo('inventario/categorias', 'Categorías', 'Nueva categoría', 'Agrupación de los productos del inventario', 'Inventario'),
      ...modulo('inventario/marcas', 'Marcas', 'Nueva marca', 'Marcas de los productos y su país de origen', 'Inventario'),
      ...modulo('compras/ordenes', 'Órdenes de compra', 'Nueva orden de compra', 'Pedidos a proveedores y su estado de entrega', 'Compras'),
      ...modulo('compras/proveedores', 'Proveedores', 'Nuevo proveedor', 'Empresas que abastecen el inventario', 'Compras'),
      ...modulo('ventas/pedidos', 'Pedidos', 'Nuevo pedido', 'Pedidos de clientes y su estado de despacho', 'Ventas'),
      ...modulo('ventas/clientes', 'Clientes', 'Nuevo cliente', 'Empresas que compran y sus contactos', 'Ventas'),
      {
        path: 'ventas-dia',
        name: 'reporte-ventas-dia',
        component: VentaDiaPrincipal,
        meta: {
          titulo: 'Ventas por día',
          descripcion: 'Vendido, costo y ganancia de cada día, con sus ventas y productos',
          migas: [{ texto: 'Reportes' }, { texto: 'Ventas por día' }],
        },
      },
      {
        path: 'reportes',
        name: 'reportes',
        component: ListView,
        props: { crear: false },
        meta: { titulo: 'Reportes', descripcion: 'Informes generados por el sistema', migas: [{ texto: 'Reportes' }] },
      },
      {
        path: 'configuracion',
        name: 'configuracion',
        component: FormView,
        meta: { titulo: 'Configuración', descripcion: 'Datos de la empresa y preferencias generales', migas: [{ texto: 'Configuración' }] },
      },
    ],
  },

  // Cualquier otra ruta vuelve al inicio
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// Sin empresa registrada todo lleva a la instalación (la pantalla siempre es pública); se pregunta una vez por carga de la página
let instalado: boolean | null = null

async function estaInstalado() {
  if (instalado === null) {
    try {
      const { data } = await api.get('/instalacion/estado')
      instalado = Boolean(data.instalado)
    } catch {
      instalado = true // Sin respuesta no se bloquea el login
    }
  }
  return instalado
}

// La pantalla de instalación avisa al terminar
export function marcarInstalado() {
  instalado = true
}

// Sin sesión solo se ven las rutas públicas; con sesión el login redirige al inicio.
// Las rutas del menú de la base solo se abren si el rol tiene acceso (ver stores/menu.ts)
router.beforeEach(async (to) => {
  const sesion = useSesionStore()

  if (!sesion.autenticado) {
    const listo = await estaInstalado()
    if (!listo && to.name !== 'instalacion') return { name: 'instalacion' }
  }

  if (!to.meta.publica && !sesion.autenticado) {
    sesion.cerrar()
    return { name: 'login', query: to.fullPath !== '/' ? { volver: to.fullPath } : {} }
  }

  if (to.name === 'login' && sesion.autenticado) {
    return { name: 'dashboard' }
  }

  if (!to.meta.publica) {
    const menu = useMenuStore()
    await menu.asegurar()

    if (!menu.puedeVer(to.path)) {
      toaster.error('No tiene acceso a esta opción.')
      return { name: 'dashboard' }
    }
  }
})

// Título de la pestaña del navegador
router.afterEach((to) => {
  document.title = to.meta.titulo ? `${to.meta.titulo} | Logy` : 'Logy'
})

export default router
