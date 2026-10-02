<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ticket de venta de 80 mm (lo convierte a PDF la librería mPDF).
 * mPDF entiende un subconjunto de HTML/CSS: la maqueta usa tablas, sin flexbox.
 */
$m = function ($valor) {
	return monto($valor);
};

$v = $venta;
$simbolo = $v->smoneda;
?>
<style>
	body { font-family: sans-serif; font-size: 8pt; color: #111827; }
	table { border-collapse: collapse; width: 100%; }
	.centro { text-align: center; }
	.der { text-align: right; }
	.tenue { color: #4b5563; }
	.empresa { font-size: 11pt; font-weight: bold; }
	.numero { font-size: 10pt; font-weight: bold; font-family: monospace; }
	.linea { border-top: 1px dashed #6b7280; margin: 4px 0; }
	.items td { padding: 2px 0; vertical-align: top; }
	.total td { font-size: 11pt; font-weight: bold; padding-top: 3px; }
</style>

<div class="centro">
	<div class="empresa"><?= html_escape($empresa ? $empresa->nombre : "") ?></div>
	<?php if ($empresa && $empresa->identificacion) : ?>
	<div>NIT <?= html_escape($empresa->identificacion) ?></div>
	<?php endif; ?>
	<?php if ($sucursal) : ?>
	<div class="tenue"><?= html_escape($sucursal->nombre) ?></div>
	<?php if ($sucursal->direccion) : ?>
	<div class="tenue"><?= html_escape($sucursal->direccion) ?></div>
	<?php endif; ?>
	<?php endif; ?>
</div>

<div class="linea"></div>

<div class="centro">
	<div><?= html_escape($v->nserie) ?></div>
	<div class="numero"><?= html_escape($v->correlativo) ?></div>
	<div class="tenue"><?= date("d/m/Y H:i", strtotime($v->fecha)) ?></div>
</div>

<div class="linea"></div>

<table>
	<tr>
		<td class="tenue" style="width: 22mm;">Cliente</td>
		<td><?= html_escape($v->ncliente) ?></td>
	</tr>
	<tr>
		<td class="tenue">NIT</td>
		<td><?= html_escape($cliente && $cliente->identificacion ? $cliente->identificacion : "CF") ?></td>
	</tr>
	<?php if ($cliente && $cliente->direccion) : ?>
	<tr>
		<td class="tenue">Dirección</td>
		<td><?= html_escape($cliente->direccion) ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<td class="tenue">Pago</td>
		<td><?= html_escape($v->nforma_pago) ?></td>
	</tr>
	<tr>
		<td class="tenue">Cajero</td>
		<td><?= html_escape($v->nusuario) ?></td>
	</tr>
</table>

<div class="linea"></div>

<table class="items">
	<?php foreach ($detalle as $d) : ?>
	<tr>
		<td colspan="2"><?= html_escape($d->nproducto) ?><?= $d->npresentacion ? " · " . html_escape($d->npresentacion) : "" ?></td>
	</tr>
	<tr>
		<td class="tenue">
			<?= (float)$d->cantidad ?> x <?= $m($d->precio) ?>
			<?php if ((float)$d->descuento_total > 0) : ?>
			· Desc. <?= rtrim(rtrim(number_format((float)$d->descuento, 2), "0"), ".") ?> %
			<?php endif; ?>
		</td>
		<td class="der"><?= $m($d->total_precio) ?></td>
	</tr>
	<?php endforeach; ?>
</table>

<div class="linea"></div>

<table>
	<tr class="total">
		<td>Total</td>
		<td class="der"><?= html_escape($simbolo) ?> <?= $m($v->total_precio) ?></td>
	</tr>
</table>

<?php if ((int)$v->anulado === 1) : ?>
<div class="linea"></div>
<div class="centro"><b>VENTA ANULADA</b></div>
<?php if ($v->anulado_motivo) : ?>
<div class="centro tenue"><?= html_escape($v->anulado_motivo) ?></div>
<?php endif; ?>
<?php endif; ?>

<div class="linea"></div>
<div class="centro tenue">Gracias por su compra</div>
