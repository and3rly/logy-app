# Manual de usuario de Logy

Logy es un sistema de ventas, compras, inventario y cuentas por cobrar y por pagar para empresas con una o varias sucursales. Este manual explica **cómo usar cada pantalla**, paso a paso, con los mismos nombres que aparecen en el sistema.

> Última revisión: 2026-10-08.
>
> **Versión en Word** (con capturas de pantalla numeradas): carpeta [`word/`](word/), un documento por área. Las capturas están en [`capturas/`](capturas/).

## Contenido

| # | Manual | Para quién | Qué cubre |
|---|---|---|---|
| 1 | [Primeros pasos](01-primeros-pasos.md) | Todos | Instalación, ingreso, inicio (dashboard), barra superior, menú, Mi perfil, cómo funcionan las listas y los formularios |
| 2 | [Configuración](02-configuracion.md) | Administrador | Parámetros, sucursales, usuarios, roles y accesos, menú |
| 3 | [Catálogos](03-catalogos.md) | Administrador / encargado | Monedas, unidades de medida y equivalencias, categorías, marcas, productos y presentaciones, clientes, listas de precios |
| 4 | [Compras](04-compras.md) | Encargado de compras | Proveedores, órdenes de compra, recibir mercadería |
| 5 | [Ventas](05-ventas.md) | Cajero / vendedor | Punto de venta, cobro, ventas del día, anular, cotizaciones y cómo convertirlas en venta |
| 6 | [Inventario](06-inventario.md) | Bodega / encargado | Existencias, kardex, ajustes, inventario inicial desde Excel, conversiones, traslados entre sucursales |
| 7 | [Finanzas](07-finanzas.md) | Caja / contabilidad | Cuentas por cobrar (abonos y recibos) y cuentas por pagar (pagos y comprobantes) |
| 8 | [Reportes](08-reportes.md) | Administrador / encargado | Ventas por día con costo, ganancia y margen |
| 9 | [Preguntas frecuentes](09-preguntas-frecuentes.md) | Todos | Mensajes comunes y cómo resolverlos |

## Orden recomendado para empezar a usar el sistema

Si la empresa es nueva en Logy, siga este orden; cada paso necesita del anterior:

1. **Instalar** el sistema y entrar con el usuario `admin` ([Primeros pasos](01-primeros-pasos.md#instalación)). Cambie la contraseña de inmediato.
2. Revisar **Parámetros**: moneda, decimales, prefijos y formato de impresión ([Configuración](02-configuracion.md#parámetros)).
3. Crear las **sucursales**, los **roles** y los **usuarios** ([Configuración](02-configuracion.md)).
4. Completar los **catálogos**: unidades, categorías, marcas y productos ([Catálogos](03-catalogos.md)).
5. Cargar la **existencia inicial** con el inventario inicial desde Excel ([Inventario](06-inventario.md#inventario-inicial)). Este paso también puede crear los productos, categorías y marcas que falten.
6. Registrar **clientes**, **proveedores** y, si se usan, **listas de precios**.
7. Empezar a **vender** y **comprar**.

## Conceptos que se repiten en todo el sistema

| Concepto | Qué significa |
|---|---|
| **Sucursal de trabajo** | Todo lo que usted ve y registra (ventas, compras, existencias, ajustes…) es de la sucursal que aparece en la barra superior. Para trabajar en otra, cámbiela ahí. |
| **Unidad de medida** | Cómo se cuenta un producto: unidad, libra, galón, quintal… |
| **Presentación** | Otra forma de vender o comprar el mismo producto: una caja de 12, un fardo, una libra de un producto que se compra por quintal. **Cada presentación tiene su propia existencia**: 5 cajas son 5 cajas, no 60 unidades. Para pasar de una a otra se usa una [conversión](06-inventario.md#conversiones). |
| **Lote** | Existencia de un producto con la misma fecha de vencimiento. Los productos que no controlan vencimiento tienen un solo lote "sin vencimiento". |
| **Vence primero, sale primero** | Al vender, ajustar o trasladar, el sistema saca primero lo que vence antes. Lo que no vence sale al final. |
| **Consumidor final (CF)** | Cliente que se usa cuando la venta no lleva cliente. Nunca compra al crédito. |
| **Al crédito** | Una venta al crédito crea una **cuenta por cobrar**; una compra al crédito, una **cuenta por pagar**. |
| **Anular** | Los documentos no se borran: se anulan con un motivo. Al anular, el sistema devuelve la existencia o el saldo que había movido. |
| **Administrador** | Un usuario con rol de **acceso total** ve todo el menú y los documentos de todos. Los demás usuarios ven solo las opciones que su rol tiene permitidas y solo los documentos que ellos mismos registraron (ver [Roles](02-configuracion.md#roles-y-accesos)). |

## Convenciones de este manual

- Los nombres de **botones, campos y opciones del menú** van en negrita, tal como aparecen en pantalla.
- La ruta del menú se escribe así: **Inventario › Ajustes**.
- Las notas marcadas con **Importante** evitan errores que no se pueden deshacer.
- Las secciones marcadas con **Solo administrador** las ve únicamente un usuario con acceso total.
