# 9 · Preguntas frecuentes y mensajes comunes

[← Índice](README.md)

## Acceso y sesión

**No puedo entrar: "Usuario o contraseña incorrectos."**
Revise mayúsculas y minúsculas. Si olvidó la contraseña, pídale a un administrador que le asigne otra en **Configuración › Usuario**.

**Me sacó del sistema: "Tu sesión caducó…"**
La sesión dura 12 horas. Vuelva a ingresar; el sistema lo regresa a la pantalla donde estaba.

**No veo una opción del menú, o al abrirla me regresa al inicio.**
Su rol no tiene acceso a esa pantalla. Un administrador puede dárselo en **Configuración › Rol › Accesos**.

**No veo las ventas (o compras, ajustes…) de mis compañeros.**
Es normal: un usuario que no es administrador solo ve los documentos que él registró.

**No veo la mercadería o los documentos que esperaba.**
Revise la **sucursal actual** en la barra superior. Todo lo que se muestra es de esa sucursal.

## Ventas

**"No hay existencia suficiente de …"**
En la sucursal no hay suficiente de ese producto o de esa presentación. Revise:
- si el producto está en otra presentación (ej. en cajas): ábrala con el botón **Abrir**;
- si está en otra sucursal: pida un [traslado](06-inventario.md#traslados);
- si la existencia del sistema no coincide con la física: corríjala con un [ajuste](06-inventario.md#ajustes).

**"Una venta al crédito necesita un cliente con crédito autorizado."**
Elija un cliente (no Consumidor final) que tenga **Otorgar crédito** marcado en **Catálogos › Clientes**.

**"La venta supera el crédito disponible del cliente (…)."**
Lo que el cliente ya debe más esta venta pasa su límite de crédito. Cobre abonos pendientes, aumente el límite o venda de contado.

**"El precio de … no puede ser menor al costo (…)."**
No se puede vender por debajo del costo. Si el costo está mal, revise la última compra del producto.

**"La cuenta por cobrar de esta venta ya tiene abonos; anúlelos antes de anular la venta."**
Vaya a **Finanzas › Cuentas por cobrar**, anule los abonos de esa cuenta y luego anule la venta.

**"La empresa no tiene moneda configurada. Elíjala en Parámetros."**
Un administrador debe elegir la moneda en **Configuración › Parámetros**.

**¿Puedo dar un descuento en el punto de venta?**
No directamente. Puede cambiar el precio de la línea (sin bajar del costo), usar una lista de precios o hacer una cotización con descuento y convertirla en venta.

**Vendí unidades sueltas pero el sistema solo tenía cajas.**
El sistema no abre cajas solo. Antes de vender, use **Abrir** en la tarjeta de la caja.

## Cotizaciones

**"La cotización está vencida; duplíquela para cotizar de nuevo."**
Pasó la fecha **Válida hasta**. Use **Duplicar**: se crea un borrador nuevo, válido 15 días.

**"Solo una cotización aceptada se puede convertir en venta."**
Primero **Enviar al cliente** y luego **Marcar aceptada**.

**"El precio con descuento no puede ser menor al costo (…)."**
Baje el porcentaje de descuento de esa línea.

## Compras

**No puedo recibir la compra al crédito.**
Las compras al crédito necesitan **No. de factura** y **Fecha de factura** en el encabezado.

**Recibí una compra con un error y no se puede anular.**
Una compra recibida no se anula. Corrija la existencia con un [ajuste](06-inventario.md#ajustes). Si el error fue en el costo, la próxima compra lo actualizará.

## Inventario

**"El producto tiene existencia: para cambiar la unidad de medida primero sáquela con un ajuste de salida."**
La unidad de un producto solo se cambia cuando no tiene existencia en ninguna sucursal.

**"La presentación ya tiene movimientos: el factor no se puede cambiar."**
Una presentación que ya se compró o vendió no puede cambiar lo que contiene. Desactívela y cree otra.

**"No se puede anular: parte de la existencia de … ya salió."**
El ajuste de entrada (o el inventario inicial) que intenta anular ya se vendió o se movió en parte. Haga un ajuste de salida por la diferencia.

**"Este archivo ya se importó en el inventario …; las cantidades se duplicarían."**
Ese mismo Excel ya está cargado en el borrador abierto. No hace falta subirlo otra vez; si quiere cambiar cantidades, quite los productos del borrador o corrija el archivo.

**La importación del Excel marca errores.**
Corrija en el Excel las filas indicadas (columnas obligatorias vacías, fechas con formato distinto a dd/mm/aaaa, cantidades en 0…) y vuelva a subirlo. Use siempre la plantilla del sistema.

**Envié un traslado y el producto no aparece en ninguna sucursal.**
Está **en camino**. Aparecerá en el destino cuando ahí pulsen **Recibir traslado**.

**¿Cómo deshago una conversión?**
Las conversiones no se anulan: haga la contraria (si abrió una caja, ármela).

## Configuración

**"No puede desactivar su propio usuario" / "el rol con el que está trabajando" / "la sucursal en la que está trabajando."**
Son protecciones para no quedar sin acceso. Hágalo desde otro usuario administrador o cambie primero de sucursal.

**"Las abreviaturas no se pueden repetir."**
En **Parámetros**, cada tipo de documento necesita un prefijo distinto.

**"Ya existe … con el mismo nombre / código / identificación."**
Ese registro ya existe (quizás inactivo). Búsquelo en la lista y edítelo en lugar de crear otro.
