<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Venta en tamaño carta (formato_impresion = 2 en Parámetros; lo convierte a PDF la librería mPDF).
 * Mismo diseño que la cotización: la maqueta usa solo tablas, y los bordes y fondos van en las celdas.
 */
$m = function ($valor) {
	return monto($valor);
};

# Cantidades sin decimales de sobra: 12 → "12", 1.5 → "1.5"
$q = function ($valor) {
	return rtrim(rtrim(number_format((float)$valor, 2), "0"), ".");
};

$v = $venta;
$simbolo = $v->smoneda;
$conDescuento = (float)$v->descuento > 0;
$columnas = $conDescuento ? 8 : 7;
?>
<style>
	body { font-family: sans-serif; font-size: 9pt; color: #1f2937; }
	table { border-collapse: collapse; width: 100%; }
	td { vertical-align: top; }
	.empresa { font-size: 16pt; font-weight: bold; color: #1e3a5f; }
	.tenue { color: #64748b; }
	.chico { font-size: 8pt; }
	.der { text-align: right; }
	.centro { text-align: center; }
	.mono { font-family: monospace; }
	.negrita { font-weight: bold; }

	.doc-titulo { font-size: 20pt; font-weight: bold; color: #1e3a5f; letter-spacing: 2px; text-align: right; }
	.doc-numero { font-size: 11pt; font-weight: bold; font-family: monospace; color: #334155; text-align: right; padding-bottom: 5px; }
	.doc-dato td { font-size: 8pt; padding: 3px 6px; border: 0.5px solid #cbd5e1; }
	.doc-dato .etq { background-color: #f1f5f9; color: #475569; font-weight: bold; }

	.regla td { border-bottom: 2px solid #1e3a5f; height: 1px; font-size: 1pt; }

	.panel-titulo { background-color: #1e3a5f; color: #ffffff; font-size: 7.5pt; font-weight: bold; letter-spacing: 1px; padding: 4px 8px; }
	.panel { background-color: #f8fafc; border: 0.5px solid #e2e8f0; padding: 6px 8px; }
	.dato td { padding: 1.5px 0; }
	.dato .etq { color: #64748b; width: 34%; }

	.items th { background-color: #1e3a5f; color: #ffffff; font-size: 8pt; font-weight: bold; padding: 6px 5px; text-align: left; }
	.items td { padding: 6px 5px; border-bottom: 0.5px solid #e2e8f0; }
	.items tr.par td { background-color: #f8fafc; }

	.totales td { padding: 4px 8px; }
	.totales .linea td { border-bottom: 0.5px solid #e2e8f0; }
	.total-final td { background-color: #1e3a5f; color: #ffffff; font-size: 12pt; font-weight: bold; padding: 7px 8px; }
</style>

<!-- Encabezado: empresa y datos del documento -->
<table>
	<tr>
		<?php if ($empresa && !empty($empresa->logo)) : ?>
		<td style="width: 22mm; padding-right: 8px;">
			<img src="https://lh3.googleusercontent.com/d/<?= html_escape($empresa->logo) ?>" style="width: 20mm; height: 20mm;">
		</td>
		<?php endif; ?>
		<td>
			<?php if ($empresa) : ?>
			<div class="empresa"><?= html_escape($empresa->nombre) ?></div>
			<?php if ($empresa->razon_social && $empresa->razon_social !== $empresa->nombre) : ?>
			<div><?= html_escape($empresa->razon_social) ?></div>
			<?php endif; ?>
			<div class="tenue">NIT <?= html_escape($empresa->identificacion) ?></div>
			<?php if ($empresa->direccion) : ?>
			<div class="tenue chico"><?= html_escape($empresa->direccion) ?></div>
			<?php endif; ?>
			<div class="tenue chico">
				<?= html_escape(implode(" · ", array_filter([$empresa->telefono ? "Tel. {$empresa->telefono}" : "", $empresa->correo]))) ?>
			</div>
			<?php endif; ?>
		</td>
		<td style="width: 64mm;">
			<div class="doc-titulo">VENTA</div>
			<div class="doc-numero">No. <?= html_escape($v->correlativo) ?></div>
			<table class="doc-dato">
				<tr>
					<td class="etq">Serie</td>
					<td class="der"><?= html_escape($v->nserie) ?></td>
				</tr>
				<tr>
					<td class="etq">Fecha</td>
					<td class="der"><?= date("d/m/Y H:i", strtotime($v->fecha)) ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table class="regla" style="margin-top: 4mm;"><tr><td>&nbsp;</td></tr></table>

<!-- Cliente y datos de la venta -->
<table style="margin-top: 5mm;">
	<tr>
		<td style="width: 50%; padding-right: 3mm;">
			<table>
				<tr><td class="panel-titulo">CLIENTE</td></tr>
				<tr>
					<td class="panel">
						<div class="negrita" style="font-size: 10.5pt; padding-bottom: 3px;"><?= html_escape($v->ncliente) ?></div>
						<table class="dato">
							<tr><td class="etq">NIT</td><td><?= html_escape($cliente && $cliente->identificacion ? $cliente->identificacion : "CF") ?></td></tr>
							<?php if ($cliente && $cliente->direccion) : ?>
							<tr><td class="etq">Dirección</td><td><?= html_escape($cliente->direccion) ?></td></tr>
							<?php endif; ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td style="width: 50%; padding-left: 3mm;">
			<table>
				<tr><td class="panel-titulo">DATOS DE LA VENTA</td></tr>
				<tr>
					<td class="panel">
						<table class="dato">
							<?php if ($sucursal) : ?>
							<tr><td class="etq">Sucursal</td><td><?= html_escape($sucursal->nombre) ?></td></tr>
							<?php endif; ?>
							<tr><td class="etq">Forma de pago</td><td><?= html_escape($v->nforma_pago) ?></td></tr>
							<tr><td class="etq">Moneda</td><td><?= html_escape($v->cmoneda) ?> (<?= html_escape($simbolo) ?>)</td></tr>
							<tr><td class="etq">Cajero</td><td><?= html_escape($v->nusuario) ?></td></tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<!-- Detalle de productos -->
<table class="items" style="margin-top: 6mm;">
	<thead>
		<tr>
			<th class="centro" style="width: 8mm;">#</th>
			<th style="width: 26mm;">CÓDIGO</th>
			<th>DESCRIPCIÓN</th>
			<th class="centro" style="width: 16mm;">UNIDAD</th>
			<th class="der" style="width: 18mm;">CANT.</th>
			<th class="der" style="width: 24mm;">PRECIO UNIT.</th>
			<?php if ($conDescuento) : ?>
			<th class="der" style="width: 16mm;">DESC.</th>
			<?php endif; ?>
			<th class="der" style="width: 26mm;">TOTAL</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($detalle as $i => $d) : ?>
		<tr class="<?= $i % 2 ? "par" : "" ?>">
			<td class="centro tenue"><?= $i + 1 ?></td>
			<td class="mono chico"><?= html_escape($d->cproducto) ?></td>
			<td><?= html_escape($d->nproducto) ?></td>
			<td class="centro"><?= html_escape($d->npresentacion ?: $d->cunidad) ?></td>
			<td class="der"><?= $q($d->cantidad) ?></td>
			<td class="der"><?= $m($d->precio) ?></td>
			<?php if ($conDescuento) : ?>
			<td class="der"><?= (float)$d->descuento > 0 ? $q($d->descuento) . " %" : "" ?></td>
			<?php endif; ?>
			<td class="der negrita"><?= $m($d->total_precio) ?></td>
		</tr>
		<?php endforeach; ?>

		<?php if (count($detalle) === 0) : ?>
		<tr><td colspan="<?= $columnas ?>" class="centro tenue" style="padding: 12px;">La venta no tiene productos.</td></tr>
		<?php endif; ?>
	</tbody>
</table>

<!-- Totales -->
<table style="margin-top: 4mm;">
	<tr>
		<td style="width: 55%; padding-right: 6mm;" class="chico tenue">
			<?= count($detalle) ?> <?= count($detalle) === 1 ? "producto" : "productos" ?> en esta venta.
			<?php if ((int)$v->anulado === 1) : ?>
			<div style="margin-top: 6px; color: #b91c1c; font-weight: bold;">VENTA ANULADA</div>
			<?php if ($v->anulado_motivo) : ?>
			<div><?= html_escape($v->anulado_motivo) ?></div>
			<?php endif; ?>
			<?php endif; ?>
		</td>
		<td style="width: 45%;">
			<table class="totales">
				<?php if ($conDescuento) : ?>
				<tr class="linea">
					<td class="tenue">Subtotal</td>
					<td class="der"><?= html_escape($simbolo) ?> <?= $m((float)$v->total_precio + (float)$v->descuento) ?></td>
				</tr>
				<tr class="linea">
					<td class="tenue">Descuento</td>
					<td class="der">- <?= html_escape($simbolo) ?> <?= $m($v->descuento) ?></td>
				</tr>
				<?php endif; ?>
				<tr class="total-final">
					<td>TOTAL</td>
					<td class="der"><?= html_escape($simbolo) ?> <?= $m($v->total_precio) ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<div class="centro tenue" style="margin-top: 8mm; color: #1e3a5f; font-weight: bold;">¡Gracias por su compra!</div>
