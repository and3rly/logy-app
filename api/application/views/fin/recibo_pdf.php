<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Recibo de abono a una cuenta por cobrar, media carta (lo convierte a PDF la librería mPDF).
 * mPDF entiende un subconjunto de HTML/CSS: la maqueta usa tablas, sin flexbox.
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

$s = $cuenta->smoneda;
?>
<style>
	body { font-family: sans-serif; font-size: 9pt; color: #1f2937; }
	table { border-collapse: collapse; width: 100%; }
	.empresa { font-size: 13pt; font-weight: bold; color: #0f172a; }
	.tenue { color: #64748b; }
	.documento { border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; }
	.documento .titulo { font-size: 11pt; font-weight: bold; letter-spacing: 1px; }
	.documento .numero { font-size: 11pt; font-weight: bold; font-family: monospace; }
	.dato td { padding: 4px 0; border-bottom: 1px solid #e2e8f0; }
	.dato .etq { color: #64748b; width: 30%; }
	.monto { border: 1px solid #cbd5e1; background-color: #f8fafc; padding: 8px 10px; }
	.monto .valor { font-size: 16pt; font-weight: bold; color: #0f172a; }
	.firma { border-top: 1px solid #64748b; padding-top: 4px; text-align: center; font-size: 8pt; color: #475569; }
</style>

<table>
	<tr>
		<td style="vertical-align: top;">
			<div class="empresa"><?= html_escape($empresa ? $empresa->nombre : "") ?></div>
			<?php if ($empresa && $empresa->identificacion) : ?>
			<div class="tenue">NIT <?= html_escape($empresa->identificacion) ?></div>
			<?php endif; ?>
			<?php if ($sucursal) : ?>
			<div class="tenue"><?= html_escape($sucursal->nombre) ?><?= $sucursal->direccion ? " · " . html_escape($sucursal->direccion) : "" ?></div>
			<?php endif; ?>
		</td>
		<td style="width: 60mm; vertical-align: top;">
			<div class="documento">
				<div class="titulo">RECIBO DE CAJA</div>
				<div class="numero"><?= html_escape($pago->recibo_numero) ?></div>
				<div class="tenue"><?= $f($pago->fecha, true) ?></div>
			</div>
		</td>
	</tr>
</table>

<table style="margin-top: 10px;">
	<tr>
		<td style="vertical-align: top; padding-right: 12px;">
			<table class="dato">
				<tr>
					<td class="etq">Recibimos de</td>
					<td><b><?= html_escape($cuenta->ncliente) ?></b> · NIT <?= html_escape($cuenta->nit_cliente ?: "CF") ?></td>
				</tr>
				<tr>
					<td class="etq">Por concepto de</td>
					<td>Abono a la venta <?= html_escape($cuenta->factura_numero) ?> del <?= $f($cuenta->factura_fecha) ?></td>
				</tr>
				<tr>
					<td class="etq">Forma de pago</td>
					<td>
						<?= html_escape($pago->nforma_pago) ?>
						<?php if ($pago->documento_numero) : ?>
						· Doc. <?= html_escape($pago->documento_numero) ?><?= $pago->documento_fecha ? " del " . $f($pago->documento_fecha) : "" ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td class="etq">Total de la venta</td>
					<td><?= html_escape($s) ?> <?= $m($cuenta->total) ?></td>
				</tr>
				<tr>
					<td class="etq">Saldo pendiente</td>
					<td><?= html_escape($s) ?> <?= $m($saldoDespues) ?></td>
				</tr>
			</table>
		</td>
		<td style="width: 60mm; vertical-align: top;">
			<div class="monto">
				<div class="tenue">Monto recibido</div>
				<div class="valor"><?= html_escape($s) ?> <?= $m($pago->total) ?></div>
			</div>
			<?php if ((int)$pago->anulado === 1) : ?>
			<div style="margin-top: 6px; color: #b91c1c;">
				<b>ANULADO</b> el <?= $f($pago->anulado_fecha, true) ?><br>
				<?= html_escape($pago->anulado_motivo) ?>
			</div>
			<?php endif; ?>
		</td>
	</tr>
</table>

<table style="margin-top: 28mm;">
	<tr>
		<td style="width: 45%;"><div class="firma">Recibido por: <?= html_escape($pago->nusuario) ?></div></td>
		<td style="width: 10%;"></td>
		<td style="width: 45%;"><div class="firma">Firma del cliente</div></td>
	</tr>
</table>
