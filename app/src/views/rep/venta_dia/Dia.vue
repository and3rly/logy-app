<template>
	<div class="p-3">
		<div v-if="cargando" class="text-center text-body-secondary py-3">
			<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando el día...
		</div>

		<div v-else class="row g-3">
			<!-- Productos del día: qué dejó más ganancia -->
			<div class="col-12 col-xxl-5">
				<div class="rounded-3 border bg-body h-100">
					<div class="px-3 py-2 border-bottom fw-semibold">
						<i class="fa-solid fa-boxes-stacked text-primary me-2" aria-hidden="true" />Productos vendidos
					</div>
					<div class="table-responsive">
						<table class="table table-sm mb-0">
							<thead>
								<tr>
									<th class="ps-3">Producto</th>
									<th class="text-end">Cantidad</th>
									<th class="text-end">Vendido</th>
									<th class="text-end pe-3">Ganancia</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="p in productos" :key="`${p.producto_id}-${p.producto_presentacion_id}`" class="align-middle">
									<td class="ps-3">
										<div class="lh-sm">
											<div class="fw-semibold text-body">{{ p.nproducto }}</div>
											<span class="small text-body-secondary font-monospace">{{ p.cproducto }}</span>
										</div>
									</td>
									<td class="text-end text-nowrap">
										{{ formatoCantidad(p.cantidad) }}
										<span class="small text-body-secondary">{{ p.npresentacion || p.nunidad }}</span>
									</td>
									<td class="text-end text-nowrap">{{ formatoMonto(p.total) }}</td>
									<td class="text-end text-nowrap pe-3 fw-semibold" :class="Number(p.ganancia) < 0 ? 'text-danger' : 'text-success-emphasis'">
										{{ formatoMonto(p.ganancia) }}
									</td>
								</tr>
								<tr v-if="productos.length === 0">
									<td colspan="4" class="text-center text-body-secondary py-3">Sin productos vendidos</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Ventas del día con su ganancia -->
			<div class="col-12 col-xxl-7">
				<div class="rounded-3 border bg-body h-100">
					<div class="px-3 py-2 border-bottom fw-semibold">
						<i class="fa-solid fa-receipt text-primary me-2" aria-hidden="true" />Ventas del día
					</div>
					<div class="table-responsive">
						<table class="table table-sm mb-0">
							<thead>
								<tr>
									<th class="ps-3">Hora</th>
									<th>Venta</th>
									<th>Forma de pago</th>
									<th class="text-end">Total</th>
									<th class="text-end">Ganancia</th>
									<th class="pe-3" style="width: 40px"><span class="visually-hidden">Ticket</span></th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="v in ventas" :key="v.id" class="align-middle" :class="{ 'opacity-50': esAnulada(v) }">
									<td class="ps-3 text-body-secondary text-nowrap">{{ String(v.fecha).slice(11, 16) }}</td>
									<td>
										<div class="lh-sm">
											<div class="fw-semibold text-body font-monospace">{{ v.correlativo }}</div>
											<div class="small text-body-secondary">
												{{ v.ncliente }} · <i class="fa-regular fa-user me-1" aria-hidden="true" />{{ v.nusuario }}
											</div>
										</div>
									</td>
									<td class="text-nowrap">
										{{ v.nforma_pago }}
										<span
											v-if="esAnulada(v) || Number(v.venta_estado_id) === 1"
											class="badge rounded-1 fw-semibold etiqueta-color ms-1"
											:style="estiloEtiqueta(v.eestado)"
										>{{ v.nestado }}</span>
									</td>
									<td class="text-end text-nowrap" :class="{ 'text-decoration-line-through': esAnulada(v) }">{{ formatoMonto(v.total_precio) }}</td>
									<td class="text-end text-nowrap fw-semibold" :class="esAnulada(v) ? 'text-body-secondary' : Number(v.ganancia) < 0 ? 'text-danger' : 'text-success-emphasis'">
										{{ esAnulada(v) ? '—' : formatoMonto(v.ganancia) }}
									</td>
									<td class="pe-3">
										<button
											type="button"
											class="btn btn-sm btn-outline-secondary"
											:title="`Ver el ticket ${v.correlativo}`"
											:disabled="imprimiendo !== null"
											@click="imprimir(v)"
										>
											<span v-if="imprimiendo === v.id" class="spinner-border spinner-border-sm" aria-hidden="true" />
											<i v-else class="fa-solid fa-print" aria-hidden="true" />
										</button>
									</td>
								</tr>
								<tr v-if="ventas.length === 0">
									<td colspan="6" class="text-center text-body-secondary py-3">Sin ventas</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import api, { mensajeError } from '@/services/api'
	import { imprimirPdf } from '@/utils/imprimir'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { formatoMonto, formatoCantidad } from '@/utils/numero'

	// Detalle de un día del reporte: se carga al abrirlo, con los mismos filtros de la pantalla
	export default {
		name: "VentaDiaDetalle",
		props: {
			dia: { type: String, required: true },
			filtros: { type: Object, default: () => ({}) }
		},
		data: () => ({
			cargando: true,
			imprimiendo: null,
			ventas: [],
			productos: []
		}),
		created() {
			this.cargar()
		},
		methods: {
			cargar() {
				this.cargando = true

				api
				.get(`/rep/venta_dia/get_dia/${this.dia}`, {
					params: {
						forma_pago: this.filtros.forma_pago,
						usuario: this.filtros.usuario
					}
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.ventas = res.ventas ?? []
						this.productos = res.productos ?? []
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.cargando = false
				})
			},
			esAnulada(v) {
				return Number(v.venta_estado_id) === 4
			},
			imprimir(v) {
				this.imprimiendo = v.id

				imprimirPdf(`/ven/venta/imprimir/${v.id}`)
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudo generar el ticket."))
				})
				.finally(() => {
					this.imprimiendo = null
				})
			},
			estiloEtiqueta,
			formatoMonto,
			formatoCantidad
		}
	}
</script>
