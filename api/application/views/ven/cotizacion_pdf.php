<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Formato de la cotización para el cliente (lo convierte a PDF la librería mPDF).
 * mPDF entiende un subconjunto de HTML/CSS: la maqueta usa solo tablas, y los bordes
 * y fondos van en las celdas (en un <div> dentro de una celda los dibuja línea por línea).
 */
$f = function ($fecha, $hora = false) {
	if (empty($fecha)) {
		return "";
	}

	return date($hora ? "d/m/Y H:i" : "d/m/Y", strtotime($fecha));
};

$m = function ($valor) {
	return monto($valor);
};

# Cantidades sin decimales de sobra: 12 → "12", 1.5 → "1.5"
$q = function ($valor) {
	return rtrim(rtrim(number_format((float)$valor, 2), "0"), ".");
};

$c = $cotizacion;
$simbolo = $c->smoneda;
$conDescuento = (float)$c->descuento > 0;
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
	.doc-dato .valida { background-color: #1e3a5f; color: #ffffff; font-weight: bold; }

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

	.texto-html p { margin: 0 0 3px 0; }
	.texto-html ul, .texto-html ol { margin: 0 0 3px 0; padding-left: 16px; }
	.firma td { border-top: 0.5px solid #64748b; padding-top: 4px; text-align: center; font-size: 8pt; }
</style>

<!-- Encabezado: empresa y datos del documento -->
<table>
	<tr>
		<?php if (!empty($empresa->logo)) : ?>
		<td style="width: 22mm; padding-right: 8px;">
			<img src="https://lh3.googleusercontent.com/d/<?= html_escape($empresa->logo) ?>" style="width: 20mm; height: 20mm;">
		</td>
		<?php endif; ?>
		<td>
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
		</td>
		<td style="width: 64mm;">
			<div class="doc-titulo">COTIZACIÓN</div>
			<div class="doc-numero">No. <?= html_escape($c->numero) ?></div>
			<table class="doc-dato">
				<tr>
					<td class="etq">Fecha de emisión</td>
					<td class="der"><?= $f($c->fecha) ?></td>
				</tr>
				<tr>
					<td class="valida">Válida hasta</td>
					<td class="der valida"><?= $f($c->valida_hasta) ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table class="regla" style="margin-top: 4mm;"><tr><td>&nbsp;</td></tr></table>

<!-- Cliente y datos de la cotización -->
<table style="margin-top: 5mm;">
	<tr>
		<td style="width: 50%; padding-right: 3mm;">
			<table>
				<tr><td class="panel-titulo">CLIENTE</td></tr>
				<tr>
					<td class="panel">
						<div class="negrita" style="font-size: 10.5pt; padding-bottom: 3px;"><?= html_escape($c->cliente_nombre) ?></div>
						<table class="dato">
							<?php if ($c->cliente_razon_social && $c->cliente_razon_social !== $c->cliente_nombre) : ?>
							<tr><td colspan="2"><?= html_escape($c->cliente_razon_social) ?></td></tr>
							<?php endif; ?>
							<tr><td class="etq">NIT</td><td><?= html_escape($c->cliente_identificacion ?: "CF") ?></td></tr>
							<?php if ($c->cliente_direccion) : ?>
							<tr><td class="etq">Dirección</td><td><?= html_escape($c->cliente_direccion) ?></td></tr>
							<?php endif; ?>
							<?php if ($c->cliente_telefono) : ?>
							<tr><td class="etq">Teléfono</td><td><?= html_escape($c->cliente_telefono) ?></td></tr>
							<?php endif; ?>
							<?php if ($c->cliente_correo) : ?>
							<tr><td class="etq">Correo</td><td><?= html_escape($c->cliente_correo) ?></td></tr>
							<?php endif; ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td style="width: 50%; padding-left: 3mm;">
			<table>
				<tr><td class="panel-titulo">DATOS DE LA COTIZACIÓN</td></tr>
				<tr>
					<td class="panel">
						<table class="dato">
							<tr><td class="etq">Sucursal</td><td><?= html_escape($sucursal->nombre) ?></td></tr>
							<tr><td class="etq">Forma de pago</td><td><?= html_escape($c->nforma_pago ?: "—") ?></td></tr>
							<tr><td class="etq">Moneda</td><td><?= html_escape($c->cmoneda) ?> (<?= html_escape($simbolo) ?>)</td></tr>
							<?php if ($c->referencia) : ?>
							<tr><td class="etq">Referencia</td><td><?= html_escape($c->referencia) ?></td></tr>
							<?php endif; ?>
							<tr><td class="etq">Atendido por</td><td><?= html_escape($creado_por) ?></td></tr>
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
			<td class="mono chico"><?= html_escape($d->producto_codigo) ?></td>
			<td>
				<?= html_escape($d->producto_nombre) ?>
				<?php if ($d->observacion) : ?>
				<div class="chico tenue"><?= html_escape($d->observacion) ?></div>
				<?php endif; ?>
			</td>
			<td class="centro"><?= html_escape($d->presentacion_nombre ?: $d->unidad_codigo) ?></td>
			<td class="der"><?= $q($d->cantidad) ?></td>
			<td class="der"><?= $m($d->precio) ?></td>
			<?php if ($conDescuento) : ?>
			<td class="der"><?= (float)$d->descuento_porcentaje > 0 ? rtrim(rtrim(number_format((float)$d->descuento_porcentaje, 2), "0"), ".") . " %" : "" ?></td>
			<?php endif; ?>
			<td class="der negrita"><?= $m($d->total) ?></td>
		</tr>
		<?php endforeach; ?>

		<?php if (count($detalle) === 0) : ?>
		<tr><td colspan="<?= $columnas ?>" class="centro tenue" style="padding: 12px;">La cotización no tiene productos.</td></tr>
		<?php endif; ?>
	</tbody>
</table>

<!-- Totales -->
<table style="margin-top: 4mm;">
	<tr>
		<td style="width: 55%; padding-right: 6mm;" class="chico tenue">
			<?= count($detalle) ?> <?= count($detalle) === 1 ? "producto" : "productos" ?> en esta cotización.
		</td>
		<td style="width: 45%;">
			<table class="totales">
				<?php if ($conDescuento) : ?>
				<tr class="linea">
					<td class="tenue">Subtotal</td>
					<td class="der"><?= html_escape($simbolo) ?> <?= $m($c->subtotal) ?></td>
				</tr>
				<tr class="linea">
					<td class="tenue">Descuento</td>
					<td class="der">- <?= html_escape($simbolo) ?> <?= $m($c->descuento) ?></td>
				</tr>
				<?php endif; ?>
				<tr class="total-final">
					<td>TOTAL</td>
					<td class="der"><?= html_escape($simbolo) ?> <?= $m($c->total) ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php if ($c->condiciones || $c->observaciones) : ?>
<table style="margin-top: 7mm;">
	<tr>
		<?php if ($c->condiciones) : ?>
		<td style="<?= $c->observaciones ? "width: 50%; padding-right: 3mm;" : "" ?>">
			<table>
				<tr><td class="panel-titulo">CONDICIONES</td></tr>
				<tr><td class="panel texto-html"><?= htmlDocumento($c->condiciones) ?></td></tr>
			</table>
		</td>
		<?php endif; ?>
		<?php if ($c->observaciones) : ?>
		<td style="<?= $c->condiciones ? "width: 50%; padding-left: 3mm;" : "" ?>">
			<table>
				<tr><td class="panel-titulo">OBSERVACIONES</td></tr>
				<tr><td class="panel texto-html"><?= htmlDocumento($c->observaciones) ?></td></tr>
			</table>
		</td>
		<?php endif; ?>
	</tr>
</table>
<?php endif; ?>

<!-- Nota y firma -->
<table style="margin-top: 8mm;">
	<tr>
		<td style="width: 55%; padding-right: 6mm;" class="chico tenue">
			Precios en <?= html_escape($c->cmoneda) ?>, válidos hasta el <?= $f($c->valida_hasta) ?>
			y sujetos a disponibilidad de inventario.
			<div style="margin-top: 4px; color: #1e3a5f; font-weight: bold;">¡Gracias por su preferencia!</div>
		</td>
		<td style="width: 45%; padding-top: 14mm;">
			<table class="firma">
				<tr>
					<td>
						<?= html_escape($creado_por) ?><br>
						<span class="tenue"><?= html_escape($empresa->nombre) ?></span>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
