// Datos de ejemplo de toda la plantilla, hasta conectar la API.
// Todos los nombres, empresas y valores son ficticios.

import type { Columna, Fila, FiltroTabla } from '../types/tabla'
import type { CampoFormulario, ValoresFormulario } from '../types/formulario'

export interface EjemploModulo {
  columnas: Columna[]
  filtros: FiltroTabla[]
  filas: Fila[]
  /** Campos del formulario "nuevo" (sin campos, el módulo no tiene formulario) */
  campos: CampoFormulario[]
}

// Estados reutilizados
const ESTADO_ACTIVO = { Activo: 'exito', Activa: 'exito', Inactivo: 'neutro', Inactiva: 'neutro', Bloqueado: 'peligro' } as const

// ===========================================================================
// Usuarios
// ===========================================================================
const usuarios: EjemploModulo = {
  columnas: [
    { clave: 'nombre', titulo: 'Nombre', formato: 'avatar' },
    { clave: 'correo', titulo: 'Correo' },
    { clave: 'rol', titulo: 'Rol' },
    { clave: 'estado', titulo: 'Estado', formato: 'etiqueta', etiquetas: ESTADO_ACTIVO },
    { clave: 'acceso', titulo: 'Último acceso', alinear: 'fin' },
  ],
  filtros: [
    { clave: 'rol', titulo: 'Rol' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { nombre: 'Ana López', correo: 'ana.lopez@ejemplo.com', rol: 'Administrador', estado: 'Activo', acceso: '25/09/2026 08:42' },
    { nombre: 'Carlos Méndez', correo: 'carlos.mendez@ejemplo.com', rol: 'Vendedor', estado: 'Activo', acceso: '25/09/2026 08:15' },
    { nombre: 'María Fernández', correo: 'maria.fernandez@ejemplo.com', rol: 'Almacén', estado: 'Activo', acceso: '24/09/2026 17:30' },
    { nombre: 'Jorge Ramírez', correo: 'jorge.ramirez@ejemplo.com', rol: 'Vendedor', estado: 'Inactivo', acceso: '12/08/2026 10:05' },
    { nombre: 'Lucía Torres', correo: 'lucia.torres@ejemplo.com', rol: 'Compras', estado: 'Activo', acceso: '24/09/2026 16:48' },
    { nombre: 'Pedro Castillo', correo: 'pedro.castillo@ejemplo.com', rol: 'Almacén', estado: 'Bloqueado', acceso: '03/09/2026 09:20' },
    { nombre: 'Sofía Herrera', correo: 'sofia.herrera@ejemplo.com', rol: 'Vendedor', estado: 'Activo', acceso: '25/09/2026 07:58' },
    { nombre: 'Diego Morales', correo: 'diego.morales@ejemplo.com', rol: 'Compras', estado: 'Activo', acceso: '23/09/2026 14:11' },
    { nombre: 'Valeria Ruiz', correo: 'valeria.ruiz@ejemplo.com', rol: 'Administrador', estado: 'Activo', acceso: '25/09/2026 09:03' },
    { nombre: 'Andrés Vargas', correo: 'andres.vargas@ejemplo.com', rol: 'Vendedor', estado: 'Inactivo', acceso: '28/07/2026 11:37' },
    { nombre: 'Camila Rojas', correo: 'camila.rojas@ejemplo.com', rol: 'Almacén', estado: 'Activo', acceso: '24/09/2026 12:26' },
    { nombre: 'Luis Gutiérrez', correo: 'luis.gutierrez@ejemplo.com', rol: 'Vendedor', estado: 'Activo', acceso: '25/09/2026 08:31' },
    { nombre: 'Paula Jiménez', correo: 'paula.jimenez@ejemplo.com', rol: 'Compras', estado: 'Activo', acceso: '22/09/2026 15:44' },
    { nombre: 'Martín Silva', correo: 'martin.silva@ejemplo.com', rol: 'Almacén', estado: 'Inactivo', acceso: '19/06/2026 08:09' },
  ],
  campos: [
    { clave: 'nombre', etiqueta: 'Nombre completo', tipo: 'texto', requerido: true, placeholder: 'Ej. Ana López' },
    { clave: 'correo', etiqueta: 'Correo', tipo: 'correo', requerido: true, placeholder: 'nombre@empresa.com' },
    { clave: 'telefono', etiqueta: 'Teléfono', tipo: 'texto', placeholder: 'Ej. 987 654 321' },
    { clave: 'rol', etiqueta: 'Rol', tipo: 'seleccion', requerido: true, opciones: ['Administrador', 'Vendedor', 'Almacén', 'Compras'] },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Activo', 'Inactivo', 'Bloqueado'] },
    { clave: 'alta', etiqueta: 'Fecha de alta', tipo: 'fecha' },
    { clave: 'notas', etiqueta: 'Notas', tipo: 'area', placeholder: 'Información adicional del usuario' },
  ],
}

// ===========================================================================
// Inventario
// ===========================================================================
const CATEGORIAS = ['Almacenaje', 'Embalaje', 'Equipos', 'Etiquetado', 'Seguridad']
const MARCAS = ['CargoLift', 'EcoBox', 'LabelTech', 'Metalix', 'PackPro', 'SafeWork', 'StoreMax']

const productos: EjemploModulo = {
  columnas: [
    { clave: 'codigo', titulo: 'Código' },
    { clave: 'nombre', titulo: 'Producto' },
    { clave: 'categoria', titulo: 'Categoría' },
    { clave: 'marca', titulo: 'Marca' },
    {
      clave: 'estado',
      titulo: 'Estado',
      formato: 'etiqueta',
      etiquetas: { Disponible: 'exito', 'Stock bajo': 'aviso', Agotado: 'peligro' },
    },
    { clave: 'stock', titulo: 'Stock', alinear: 'fin' },
    { clave: 'precio', titulo: 'Precio', alinear: 'fin' },
  ],
  filtros: [
    { clave: 'categoria', titulo: 'Categoría' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { codigo: 'PRD-0001', nombre: 'Caja de cartón 40×30×30', categoria: 'Embalaje', marca: 'EcoBox', estado: 'Disponible', stock: '1,250', precio: '2.40' },
    { codigo: 'PRD-0002', nombre: 'Cinta adhesiva transparente 48 mm', categoria: 'Embalaje', marca: 'PackPro', estado: 'Disponible', stock: '860', precio: '1.15' },
    { codigo: 'PRD-0003', nombre: 'Film stretch 18"', categoria: 'Embalaje', marca: 'PackPro', estado: 'Stock bajo', stock: '42', precio: '12.90' },
    { codigo: 'PRD-0004', nombre: 'Pallet de madera estándar', categoria: 'Almacenaje', marca: 'StoreMax', estado: 'Disponible', stock: '310', precio: '18.50' },
    { codigo: 'PRD-0005', nombre: 'Estante metálico 5 niveles', categoria: 'Almacenaje', marca: 'Metalix', estado: 'Agotado', stock: '0', precio: '145.00' },
    { codigo: 'PRD-0006', nombre: 'Etiqueta térmica 100×150', categoria: 'Etiquetado', marca: 'LabelTech', estado: 'Disponible', stock: '5,400', precio: '0.08' },
    { codigo: 'PRD-0007', nombre: 'Impresora de etiquetas', categoria: 'Equipos', marca: 'LabelTech', estado: 'Stock bajo', stock: '6', precio: '389.00' },
    { codigo: 'PRD-0008', nombre: 'Lector de código de barras', categoria: 'Equipos', marca: 'LabelTech', estado: 'Disponible', stock: '24', precio: '75.00' },
    { codigo: 'PRD-0009', nombre: 'Carretilla hidráulica 2.5 t', categoria: 'Equipos', marca: 'CargoLift', estado: 'Disponible', stock: '8', precio: '520.00' },
    { codigo: 'PRD-0010', nombre: 'Bolsa de burbujas 30×40', categoria: 'Embalaje', marca: 'EcoBox', estado: 'Disponible', stock: '2,100', precio: '0.35' },
    { codigo: 'PRD-0011', nombre: 'Contenedor plástico 60 L', categoria: 'Almacenaje', marca: 'StoreMax', estado: 'Stock bajo', stock: '15', precio: '22.80' },
    { codigo: 'PRD-0012', nombre: 'Guantes de trabajo (par)', categoria: 'Seguridad', marca: 'SafeWork', estado: 'Disponible', stock: '480', precio: '3.60' },
    { codigo: 'PRD-0013', nombre: 'Chaleco reflectivo', categoria: 'Seguridad', marca: 'SafeWork', estado: 'Disponible', stock: '120', precio: '6.90' },
    { codigo: 'PRD-0014', nombre: 'Casco de seguridad', categoria: 'Seguridad', marca: 'SafeWork', estado: 'Agotado', stock: '0', precio: '14.50' },
    { codigo: 'PRD-0015', nombre: 'Etiqueta "Frágil" (rollo)', categoria: 'Etiquetado', marca: 'LabelTech', estado: 'Disponible', stock: '95', precio: '4.20' },
    { codigo: 'PRD-0016', nombre: 'Precinto de seguridad', categoria: 'Embalaje', marca: 'PackPro', estado: 'Disponible', stock: '3,000', precio: '0.12' },
    { codigo: 'PRD-0017', nombre: 'Balanza de piso 500 kg', categoria: 'Equipos', marca: 'Metalix', estado: 'Stock bajo', stock: '3', precio: '610.00' },
    { codigo: 'PRD-0018', nombre: 'Esquinero de cartón', categoria: 'Embalaje', marca: 'EcoBox', estado: 'Disponible', stock: '1,780', precio: '0.25' },
  ],
  campos: [
    { clave: 'codigo', etiqueta: 'Código', tipo: 'texto', requerido: true, placeholder: 'Ej. PRD-0019' },
    { clave: 'nombre', etiqueta: 'Nombre del producto', tipo: 'texto', requerido: true, placeholder: 'Ej. Caja de cartón 50×40×40' },
    { clave: 'categoria', etiqueta: 'Categoría', tipo: 'seleccion', requerido: true, opciones: CATEGORIAS },
    { clave: 'marca', etiqueta: 'Marca', tipo: 'seleccion', opciones: MARCAS },
    { clave: 'precio', etiqueta: 'Precio', tipo: 'numero', requerido: true, placeholder: '0.00' },
    { clave: 'stockMinimo', etiqueta: 'Stock mínimo', tipo: 'numero', placeholder: '0' },
    { clave: 'unidad', etiqueta: 'Unidad de medida', tipo: 'seleccion', opciones: ['Unidad', 'Caja', 'Rollo', 'Par', 'Paquete'] },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Disponible', 'Stock bajo', 'Agotado'] },
    { clave: 'descripcion', etiqueta: 'Descripción', tipo: 'area', placeholder: 'Medidas, material, uso recomendado…' },
  ],
}

const categorias: EjemploModulo = {
  columnas: [
    { clave: 'nombre', titulo: 'Categoría' },
    { clave: 'descripcion', titulo: 'Descripción' },
    { clave: 'estado', titulo: 'Estado', formato: 'etiqueta', etiquetas: ESTADO_ACTIVO },
    { clave: 'productos', titulo: 'Productos', alinear: 'fin' },
  ],
  filtros: [{ clave: 'estado', titulo: 'Estado' }],
  filas: [
    { nombre: 'Embalaje', descripcion: 'Cajas, cintas, film y material de protección', estado: 'Activa', productos: '7' },
    { nombre: 'Almacenaje', descripcion: 'Pallets, estantes y contenedores', estado: 'Activa', productos: '3' },
    { nombre: 'Equipos', descripcion: 'Maquinaria y equipos de manipulación', estado: 'Activa', productos: '4' },
    { nombre: 'Etiquetado', descripcion: 'Etiquetas e insumos de impresión', estado: 'Activa', productos: '2' },
    { nombre: 'Seguridad', descripcion: 'Equipos de protección personal', estado: 'Activa', productos: '3' },
    { nombre: 'Limpieza', descripcion: 'Productos de limpieza para almacén', estado: 'Inactiva', productos: '0' },
  ],
  campos: [
    { clave: 'nombre', etiqueta: 'Nombre', tipo: 'texto', requerido: true, placeholder: 'Ej. Embalaje' },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Activa', 'Inactiva'] },
    { clave: 'descripcion', etiqueta: 'Descripción', tipo: 'area', placeholder: 'Qué productos agrupa esta categoría' },
  ],
}

const marcas: EjemploModulo = {
  columnas: [
    { clave: 'nombre', titulo: 'Marca' },
    { clave: 'pais', titulo: 'País de origen' },
    { clave: 'estado', titulo: 'Estado', formato: 'etiqueta', etiquetas: ESTADO_ACTIVO },
    { clave: 'productos', titulo: 'Productos', alinear: 'fin' },
  ],
  filtros: [
    { clave: 'pais', titulo: 'País' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { nombre: 'PackPro', pais: 'México', estado: 'Activa', productos: '3' },
    { nombre: 'EcoBox', pais: 'Chile', estado: 'Activa', productos: '3' },
    { nombre: 'LabelTech', pais: 'Estados Unidos', estado: 'Activa', productos: '4' },
    { nombre: 'StoreMax', pais: 'Brasil', estado: 'Activa', productos: '2' },
    { nombre: 'Metalix', pais: 'Argentina', estado: 'Activa', productos: '2' },
    { nombre: 'SafeWork', pais: 'Colombia', estado: 'Activa', productos: '3' },
    { nombre: 'CargoLift', pais: 'España', estado: 'Activa', productos: '1' },
    { nombre: 'BoxLine', pais: 'Perú', estado: 'Inactiva', productos: '0' },
  ],
  campos: [
    { clave: 'nombre', etiqueta: 'Nombre', tipo: 'texto', requerido: true, placeholder: 'Ej. PackPro' },
    { clave: 'pais', etiqueta: 'País de origen', tipo: 'seleccion', opciones: ['Argentina', 'Brasil', 'Chile', 'Colombia', 'España', 'Estados Unidos', 'México', 'Perú'] },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Activa', 'Inactiva'] },
    { clave: 'sitio', etiqueta: 'Sitio web', tipo: 'texto', placeholder: 'https://' },
    { clave: 'notas', etiqueta: 'Notas', tipo: 'area' },
  ],
}

// ===========================================================================
// Compras
// ===========================================================================
const PROVEEDORES = [
  'Cartones del Sur', 'Equipos Logísticos SAC', 'Etiquetas & Más', 'Maderas Unidas',
  'Metalúrgica Andes', 'Plásticos Industriales', 'Seguridad Total',
]

const ordenes: EjemploModulo = {
  columnas: [
    { clave: 'numero', titulo: 'N.º de orden' },
    { clave: 'proveedor', titulo: 'Proveedor' },
    { clave: 'fecha', titulo: 'Emisión' },
    { clave: 'entrega', titulo: 'Entrega' },
    {
      clave: 'estado',
      titulo: 'Estado',
      formato: 'etiqueta',
      etiquetas: { Recibida: 'exito', Pendiente: 'aviso', 'En tránsito': 'neutro', Cancelada: 'peligro' },
    },
    { clave: 'total', titulo: 'Total', alinear: 'fin' },
  ],
  filtros: [
    { clave: 'proveedor', titulo: 'Proveedor' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { numero: 'OC-2026-0142', proveedor: 'Cartones del Sur', fecha: '22/09/2026', entrega: '29/09/2026', estado: 'Pendiente', total: '3,840.00' },
    { numero: 'OC-2026-0141', proveedor: 'Etiquetas & Más', fecha: '20/09/2026', entrega: '25/09/2026', estado: 'En tránsito', total: '1,275.50' },
    { numero: 'OC-2026-0140', proveedor: 'Equipos Logísticos SAC', fecha: '18/09/2026', entrega: '24/09/2026', estado: 'Recibida', total: '4,160.00' },
    { numero: 'OC-2026-0139', proveedor: 'Seguridad Total', fecha: '15/09/2026', entrega: '19/09/2026', estado: 'Recibida', total: '890.40' },
    { numero: 'OC-2026-0138', proveedor: 'Plásticos Industriales', fecha: '12/09/2026', entrega: '18/09/2026', estado: 'Recibida', total: '2,318.00' },
    { numero: 'OC-2026-0137', proveedor: 'Maderas Unidas', fecha: '10/09/2026', entrega: '17/09/2026', estado: 'Cancelada', total: '5,550.00' },
    { numero: 'OC-2026-0136', proveedor: 'Metalúrgica Andes', fecha: '05/09/2026', entrega: '15/09/2026', estado: 'Recibida', total: '7,250.00' },
    { numero: 'OC-2026-0135', proveedor: 'Cartones del Sur', fecha: '02/09/2026', entrega: '08/09/2026', estado: 'Recibida', total: '3,120.00' },
    { numero: 'OC-2026-0134', proveedor: 'Etiquetas & Más', fecha: '28/08/2026', entrega: '02/09/2026', estado: 'Recibida', total: '960.00' },
    { numero: 'OC-2026-0133', proveedor: 'Plásticos Industriales', fecha: '25/08/2026', entrega: '01/09/2026', estado: 'Recibida', total: '1,845.60' },
    { numero: 'OC-2026-0132', proveedor: 'Seguridad Total', fecha: '20/08/2026', entrega: '26/08/2026', estado: 'Recibida', total: '1,104.00' },
    { numero: 'OC-2026-0131', proveedor: 'Maderas Unidas', fecha: '18/08/2026', entrega: '25/08/2026', estado: 'Recibida', total: '4,625.00' },
  ],
  campos: [
    { clave: 'proveedor', etiqueta: 'Proveedor', tipo: 'seleccion', requerido: true, opciones: PROVEEDORES },
    { clave: 'fecha', etiqueta: 'Fecha de emisión', tipo: 'fecha', requerido: true },
    { clave: 'entrega', etiqueta: 'Fecha de entrega', tipo: 'fecha' },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Pendiente', 'En tránsito', 'Recibida', 'Cancelada'] },
    { clave: 'observaciones', etiqueta: 'Observaciones', tipo: 'area', placeholder: 'Condiciones de pago, lugar de entrega…' },
  ],
}

const proveedores: EjemploModulo = {
  columnas: [
    { clave: 'nombre', titulo: 'Razón social' },
    { clave: 'contacto', titulo: 'Contacto', formato: 'avatar' },
    { clave: 'telefono', titulo: 'Teléfono' },
    { clave: 'ciudad', titulo: 'Ciudad' },
    { clave: 'estado', titulo: 'Estado', formato: 'etiqueta', etiquetas: ESTADO_ACTIVO },
  ],
  filtros: [
    { clave: 'ciudad', titulo: 'Ciudad' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { nombre: 'Cartones del Sur', contacto: 'Rosa Paredes', telefono: '(01) 452-3310', ciudad: 'Lima', estado: 'Activo' },
    { nombre: 'Etiquetas & Más', contacto: 'Hugo Salazar', telefono: '(01) 471-8820', ciudad: 'Lima', estado: 'Activo' },
    { nombre: 'Equipos Logísticos SAC', contacto: 'Elena Quispe', telefono: '(054) 22-4415', ciudad: 'Arequipa', estado: 'Activo' },
    { nombre: 'Plásticos Industriales', contacto: 'Raúl Medina', telefono: '(044) 29-1187', ciudad: 'Trujillo', estado: 'Activo' },
    { nombre: 'Maderas Unidas', contacto: 'Gloria Campos', telefono: '(064) 21-5530', ciudad: 'Huancayo', estado: 'Inactivo' },
    { nombre: 'Metalúrgica Andes', contacto: 'Fernando Rivas', telefono: '(054) 25-7702', ciudad: 'Arequipa', estado: 'Activo' },
    { nombre: 'Seguridad Total', contacto: 'Patricia Luna', telefono: '(01) 438-9051', ciudad: 'Lima', estado: 'Activo' },
  ],
  campos: [
    { clave: 'nombre', etiqueta: 'Razón social', tipo: 'texto', requerido: true },
    { clave: 'documento', etiqueta: 'N.º de identificación fiscal', tipo: 'texto', requerido: true },
    { clave: 'contacto', etiqueta: 'Persona de contacto', tipo: 'texto' },
    { clave: 'correo', etiqueta: 'Correo', tipo: 'correo', placeholder: 'contacto@proveedor.com' },
    { clave: 'telefono', etiqueta: 'Teléfono', tipo: 'texto' },
    { clave: 'ciudad', etiqueta: 'Ciudad', tipo: 'seleccion', opciones: ['Arequipa', 'Huancayo', 'Lima', 'Trujillo'] },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Activo', 'Inactivo'] },
    { clave: 'direccion', etiqueta: 'Dirección', tipo: 'area' },
  ],
}

// ===========================================================================
// Ventas
// ===========================================================================
const CLIENTES = [
  'Agroinsumos del Valle', 'Comercial del Norte', 'Distribuidora San Martín', 'Farmacia Vida',
  'Ferretería El Tornillo', 'Importadora Pacífico', 'Librería Central', 'Supermercados La Económica',
  'Textiles Andinos', 'Tienda Mía',
]

const pedidos: EjemploModulo = {
  columnas: [
    { clave: 'numero', titulo: 'N.º de pedido' },
    { clave: 'cliente', titulo: 'Cliente' },
    { clave: 'fecha', titulo: 'Fecha' },
    { clave: 'vendedor', titulo: 'Vendedor' },
    {
      clave: 'estado',
      titulo: 'Estado',
      formato: 'etiqueta',
      etiquetas: { Entregado: 'exito', 'En camino': 'aviso', Preparando: 'neutro', Cancelado: 'peligro' },
    },
    { clave: 'total', titulo: 'Total', alinear: 'fin' },
  ],
  filtros: [
    { clave: 'vendedor', titulo: 'Vendedor' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { numero: 'PED-2026-1245', cliente: 'Comercial del Norte', fecha: '25/09/2026', vendedor: 'Carlos Méndez', estado: 'Preparando', total: '1,420.00' },
    { numero: 'PED-2026-1244', cliente: 'Farmacia Vida', fecha: '25/09/2026', vendedor: 'Sofía Herrera', estado: 'Preparando', total: '386.50' },
    { numero: 'PED-2026-1243', cliente: 'Ferretería El Tornillo', fecha: '24/09/2026', vendedor: 'Luis Gutiérrez', estado: 'En camino', total: '2,115.80' },
    { numero: 'PED-2026-1242', cliente: 'Supermercados La Económica', fecha: '24/09/2026', vendedor: 'Carlos Méndez', estado: 'En camino', total: '5,960.00' },
    { numero: 'PED-2026-1241', cliente: 'Librería Central', fecha: '23/09/2026', vendedor: 'Sofía Herrera', estado: 'Entregado', total: '742.30' },
    { numero: 'PED-2026-1240', cliente: 'Textiles Andinos', fecha: '23/09/2026', vendedor: 'Luis Gutiérrez', estado: 'Entregado', total: '1,088.00' },
    { numero: 'PED-2026-1239', cliente: 'Importadora Pacífico', fecha: '22/09/2026', vendedor: 'Carlos Méndez', estado: 'Cancelado', total: '3,250.00' },
    { numero: 'PED-2026-1238', cliente: 'Agroinsumos del Valle', fecha: '22/09/2026', vendedor: 'Sofía Herrera', estado: 'Entregado', total: '964.70' },
    { numero: 'PED-2026-1237', cliente: 'Tienda Mía', fecha: '21/09/2026', vendedor: 'Luis Gutiérrez', estado: 'Entregado', total: '215.40' },
    { numero: 'PED-2026-1236', cliente: 'Distribuidora San Martín', fecha: '21/09/2026', vendedor: 'Carlos Méndez', estado: 'Entregado', total: '4,380.00' },
    { numero: 'PED-2026-1235', cliente: 'Comercial del Norte', fecha: '20/09/2026', vendedor: 'Sofía Herrera', estado: 'Entregado', total: '1,730.25' },
    { numero: 'PED-2026-1234', cliente: 'Farmacia Vida', fecha: '19/09/2026', vendedor: 'Luis Gutiérrez', estado: 'Entregado', total: '512.00' },
    { numero: 'PED-2026-1233', cliente: 'Ferretería El Tornillo', fecha: '19/09/2026', vendedor: 'Carlos Méndez', estado: 'Entregado', total: '1,294.90' },
    { numero: 'PED-2026-1232', cliente: 'Supermercados La Económica', fecha: '18/09/2026', vendedor: 'Sofía Herrera', estado: 'Entregado', total: '6,120.00' },
  ],
  campos: [
    { clave: 'cliente', etiqueta: 'Cliente', tipo: 'seleccion', requerido: true, opciones: CLIENTES },
    { clave: 'fecha', etiqueta: 'Fecha del pedido', tipo: 'fecha', requerido: true },
    { clave: 'vendedor', etiqueta: 'Vendedor', tipo: 'seleccion', opciones: ['Carlos Méndez', 'Luis Gutiérrez', 'Sofía Herrera'] },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Preparando', 'En camino', 'Entregado', 'Cancelado'] },
    { clave: 'direccion', etiqueta: 'Dirección de entrega', tipo: 'area', placeholder: 'Calle, número, distrito, referencia' },
  ],
}

const clientes: EjemploModulo = {
  columnas: [
    { clave: 'nombre', titulo: 'Cliente' },
    { clave: 'contacto', titulo: 'Contacto', formato: 'avatar' },
    { clave: 'correo', titulo: 'Correo' },
    { clave: 'ciudad', titulo: 'Ciudad' },
    { clave: 'estado', titulo: 'Estado', formato: 'etiqueta', etiquetas: ESTADO_ACTIVO },
    { clave: 'pedidos', titulo: 'Pedidos', alinear: 'fin' },
  ],
  filtros: [
    { clave: 'ciudad', titulo: 'Ciudad' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { nombre: 'Supermercados La Económica', contacto: 'Julio Benavides', correo: 'compras@laeconomica.ejemplo.com', ciudad: 'Lima', estado: 'Activo', pedidos: '48' },
    { nombre: 'Comercial del Norte', contacto: 'Mónica Aguilar', correo: 'logistica@delnorte.ejemplo.com', ciudad: 'Trujillo', estado: 'Activo', pedidos: '36' },
    { nombre: 'Ferretería El Tornillo', contacto: 'Óscar Delgado', correo: 'pedidos@eltornillo.ejemplo.com', ciudad: 'Lima', estado: 'Activo', pedidos: '31' },
    { nombre: 'Distribuidora San Martín', contacto: 'Irene Cáceres', correo: 'almacen@sanmartin.ejemplo.com', ciudad: 'Tarapoto', estado: 'Activo', pedidos: '27' },
    { nombre: 'Farmacia Vida', contacto: 'Tomás Villanueva', correo: 'compras@farmaciavida.ejemplo.com', ciudad: 'Arequipa', estado: 'Activo', pedidos: '22' },
    { nombre: 'Textiles Andinos', contacto: 'Nora Huamán', correo: 'contacto@textilesandinos.ejemplo.com', ciudad: 'Cusco', estado: 'Activo', pedidos: '15' },
    { nombre: 'Librería Central', contacto: 'Ricardo Peña', correo: 'ventas@libreriacentral.ejemplo.com', ciudad: 'Lima', estado: 'Activo', pedidos: '12' },
    { nombre: 'Agroinsumos del Valle', contacto: 'Silvia Rosales', correo: 'compras@agrovalle.ejemplo.com', ciudad: 'Ica', estado: 'Activo', pedidos: '9' },
    { nombre: 'Importadora Pacífico', contacto: 'Gabriel Soto', correo: 'importaciones@pacifico.ejemplo.com', ciudad: 'Callao', estado: 'Inactivo', pedidos: '6' },
    { nombre: 'Tienda Mía', contacto: 'Daniela Flores', correo: 'hola@tiendamia.ejemplo.com', ciudad: 'Arequipa', estado: 'Activo', pedidos: '4' },
  ],
  campos: [
    { clave: 'nombre', etiqueta: 'Razón social', tipo: 'texto', requerido: true },
    { clave: 'documento', etiqueta: 'N.º de identificación fiscal', tipo: 'texto', requerido: true },
    { clave: 'contacto', etiqueta: 'Persona de contacto', tipo: 'texto' },
    { clave: 'correo', etiqueta: 'Correo', tipo: 'correo', placeholder: 'contacto@cliente.com' },
    { clave: 'telefono', etiqueta: 'Teléfono', tipo: 'texto' },
    { clave: 'ciudad', etiqueta: 'Ciudad', tipo: 'seleccion', opciones: ['Arequipa', 'Callao', 'Cusco', 'Ica', 'Lima', 'Tarapoto', 'Trujillo'] },
    { clave: 'estado', etiqueta: 'Estado', tipo: 'seleccion', opciones: ['Activo', 'Inactivo'] },
    { clave: 'direccion', etiqueta: 'Dirección', tipo: 'area' },
  ],
}

// ===========================================================================
// Reportes (sin formulario)
// ===========================================================================
const reportes: EjemploModulo = {
  columnas: [
    { clave: 'nombre', titulo: 'Reporte' },
    { clave: 'tipo', titulo: 'Tipo' },
    { clave: 'periodo', titulo: 'Periodo' },
    { clave: 'autor', titulo: 'Generado por' },
    {
      clave: 'estado',
      titulo: 'Estado',
      formato: 'etiqueta',
      etiquetas: { Listo: 'exito', 'En proceso': 'aviso', 'Con error': 'peligro' },
    },
    { clave: 'fecha', titulo: 'Fecha', alinear: 'fin' },
  ],
  filtros: [
    { clave: 'tipo', titulo: 'Tipo' },
    { clave: 'estado', titulo: 'Estado' },
  ],
  filas: [
    { nombre: 'Inventario valorizado', tipo: 'Inventario', periodo: 'Septiembre 2026', autor: 'Ana López', estado: 'Listo', fecha: '25/09/2026' },
    { nombre: 'Ventas por vendedor', tipo: 'Ventas', periodo: 'Septiembre 2026', autor: 'Valeria Ruiz', estado: 'En proceso', fecha: '25/09/2026' },
    { nombre: 'Compras por proveedor', tipo: 'Compras', periodo: 'Agosto 2026', autor: 'Lucía Torres', estado: 'Listo', fecha: '02/09/2026' },
    { nombre: 'Rotación de productos', tipo: 'Inventario', periodo: 'Q3 2026', autor: 'María Fernández', estado: 'Listo', fecha: '20/09/2026' },
    { nombre: 'Ventas diarias', tipo: 'Ventas', periodo: '24/09/2026', autor: 'Carlos Méndez', estado: 'Listo', fecha: '24/09/2026' },
    { nombre: 'Productos sin movimiento', tipo: 'Inventario', periodo: 'Últimos 90 días', autor: 'Camila Rojas', estado: 'Con error', fecha: '18/09/2026' },
    { nombre: 'Órdenes de compra pendientes', tipo: 'Compras', periodo: 'Septiembre 2026', autor: 'Diego Morales', estado: 'Listo', fecha: '23/09/2026' },
    { nombre: 'Comparativo mensual de ventas', tipo: 'Ventas', periodo: '2026', autor: 'Ana López', estado: 'Listo', fecha: '01/09/2026' },
  ],
  campos: [],
}

// ===========================================================================
// Configuración (formulario con valores actuales)
// ===========================================================================
export const configuracion: { campos: CampoFormulario[]; valores: ValoresFormulario } = {
  campos: [
    { clave: 'empresa', etiqueta: 'Nombre de la empresa', tipo: 'texto', requerido: true },
    { clave: 'documento', etiqueta: 'N.º de identificación fiscal', tipo: 'texto', requerido: true },
    { clave: 'correo', etiqueta: 'Correo de contacto', tipo: 'correo' },
    { clave: 'telefono', etiqueta: 'Teléfono', tipo: 'texto' },
    { clave: 'moneda', etiqueta: 'Moneda', tipo: 'seleccion', opciones: ['Dólar (USD)', 'Euro (EUR)', 'Sol (PEN)'] },
    { clave: 'zona', etiqueta: 'Zona horaria', tipo: 'seleccion', opciones: ['América/Bogotá (UTC−5)', 'América/Lima (UTC−5)', 'América/México (UTC−6)'] },
    { clave: 'formatoFecha', etiqueta: 'Formato de fecha', tipo: 'seleccion', opciones: ['DD/MM/AAAA', 'MM/DD/AAAA', 'AAAA-MM-DD'] },
    { clave: 'stockMinimo', etiqueta: 'Stock mínimo por defecto', tipo: 'numero' },
    { clave: 'direccion', etiqueta: 'Dirección fiscal', tipo: 'area' },
  ],
  valores: {
    empresa: 'Logy Logística',
    documento: '20601234567',
    correo: 'contacto@logy.ejemplo.com',
    telefono: '(01) 555-0142',
    moneda: 'Sol (PEN)',
    zona: 'América/Lima (UTC−5)',
    formatoFecha: 'DD/MM/AAAA',
    stockMinimo: '10',
    direccion: 'Av. Industrial 1450, Ate, Lima',
  },
}

// ===========================================================================
// Barra superior: usuario conectado (las notificaciones ya son reales: API notificacion)
// ===========================================================================
export const usuarioActual = {
  nombre: 'Ana López',
  rol: 'Administrador',
  correo: 'ana.lopez@ejemplo.com',
}

// ===========================================================================
// Búsqueda por ruta
// ===========================================================================
const modulos: Record<string, EjemploModulo> = {
  usuarios,
  'inventario/productos': productos,
  'inventario/categorias': categorias,
  'inventario/marcas': marcas,
  'compras/ordenes': ordenes,
  'compras/proveedores': proveedores,
  'ventas/pedidos': pedidos,
  'ventas/clientes': clientes,
  reportes,
}

/** Ejemplo según el nombre de la ruta del listado (ej. 'inventario/productos') */
export function ejemploPara(ruta: string): EjemploModulo {
  return modulos[ruta] ?? productos
}
