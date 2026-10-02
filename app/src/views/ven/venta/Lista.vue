<template>
	<PageHeader>
		<button type="button" class="btn btn-outline-secondary" @click="$emit('volver')">
			<i class="fa-solid fa-arrow-left me-1" aria-hidden="true" />Volver al punto de venta
		</button>
	</PageHeader>

	<card>
		<card-body class="p-0">
			<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
				<div class="input-group w-auto">
					<span class="input-group-text">Del</span>
					<input v-model="bform.fdel" type="date" class="form-control" aria-label="Desde" @change="buscar">
					<span class="input-group-text">al</span>
					<input v-model="bform.fal" type="date" class="form-control" aria-label="Hasta" @change="buscar">
				</div>

				<select v-model="bform.estado" class="form-select w-auto" aria-label="Estado" @change="buscar">
					<option :value="null">Todos los estados</option>
					<option v-for="e in estados" :key="e.id" :value="String(e.id)">{{ e.nombre }}</option>
				</select>

				<div class="input-group flex-grow-1 w-auto">
					<span class="input-group-text">
						<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
					</span>
					<input
						v-model="termino"
						type="search"
						class="form-control"
						placeholder="Buscar por número, cliente o cajero..."
						aria-label="Buscar ventas"
					>
				</div>

				<span class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap">
					<i class="fa-solid fa-sack-dollar text-primary" aria-hidden="true" />
					<span>Vendido <span class="fw-semibold text-body">{{ formatoMonto(totalVendido) }}</span></span>
				</span>

				<span class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap" aria-live="polite">
					<i class="fa-solid fa-layer-group text-primary" aria-hidden="true" />
					<span>
						<span class="fw-semibold text-body">{{ termino ? `${filtrada.length} de ${lista.length}` : lista.length }}</span>
						{{ lista.length === 1 ? 'venta' : 'ventas' }}
					</span>
				</span>
			</div>

			<div class="table-responsive">
				<table class="table table-sm table-hover mb-0">
					<thead>
						<tr>
							<th class="ps-3">Número</th>
							<th>Fecha</th>
							<th>Cliente</th>
							<th>Forma de pago</th>
							<th>Cajero</th>
							<th class="text-end">Total</th>
							<th>Estado</th>
							<th class="text-end pe-3">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<template v-for="i in filtrada" :key="i.id">
							<tr class="align-middle" role="button" @click="verDetalle(i)">
								<td class="ps-3 fw-semibold font-monospace text-body text-nowrap">
									<i
										class="fa-solid fa-fw small text-body-secondary me-1"
										:class="abierta === i.id ? 'fa-chevron-down' : 'fa-chevron-right'"
										aria-hidden="true"
									/>{{ i.correlativo }}
								</td>
								<td class="text-nowrap">{{ formatoFecha(i.fecha) }}</td>
								<td>
									<div class="lh-sm">
										<div class="text-body">{{ i.ncliente }}</div>
										<div v-if="i.nit_cliente" class="small text-body-secondary">NIT {{ i.nit_cliente }}</div>
									</div>
								</td>
								<td>{{ i.nforma_pago }}</td>
								<td>{{ i.nusuario }}</td>
								<td class="text-end fw-semibold text-nowrap">{{ i.smoneda }} {{ formatoMonto(i.total_precio) }}</td>
								<td>
									<span
										class="badge rounded-1 fw-semibold etiqueta-color"
										:style="estiloEtiqueta(etiquetaEstado(i.eestado))"
										:title="Number(i.anulado) === 1 && i.anulado_motivo ? i.anulado_motivo : null"
									>{{ i.nestado }}</span>
								</td>
								<td class="text-end pe-3 text-nowrap">
									<button type="button" class="btn btn-sm btn-link" title="Imprimir ticket" :disabled="imprimiendo !== null" @click.stop="imprimir(i)">
										<span v-if="imprimiendo === i.id" class="spinner-border spinner-border-sm" aria-hidden="true" />
										<i v-else class="fa-solid fa-print" aria-hidden="true" />
									</button>
									<button
										v-if="Number(i.anulado) === 0"
										type="button"
										class="btn btn-sm btn-link text-danger"
										title="Anular venta"
										@click.stop="pedirAnular(i)"
									>
										<i class="fa-solid fa-ban" aria-hidden="true" />
									</button>
								</td>
							</tr>

							<!-- Productos de la venta -->
							<tr v-if="abierta === i.id">
								<td colspan="8" class="bg-body-tertiary px-3 py-2">
									<div v-if="cargandoDetalle" class="small text-body-secondary">
										<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando productos...
									</div>
									<table v-else class="table table-sm mb-0 small bg-transparent">
										<thead>
											<tr>
												<th>Código</th>
												<th>Producto</th>
												<th class="text-end">Cantidad</th>
												<th class="text-end">Precio</th>
												<th class="text-end">Total</th>
											</tr>
										</thead>
										<tbody>
											<tr v-for="d in detalle" :key="d.id">
												<td class="font-monospace">{{ d.cproducto }}</td>
												<td>{{ d.nproducto }}</td>
												<td class="text-end">{{ formatoCantidad(d.cantidad) }} {{ d.npresentacion || d.cunidad }}</td>
												<td class="text-end">{{ formatoMonto(d.precio) }}</td>
												<td class="text-end fw-semibold">{{ formatoMonto(d.total_precio) }}</td>
											</tr>
											<tr v-if="detalle.length === 0">
												<td colspan="5" class="text-center text-body-secondary">Sin productos</td>
											</tr>
										</tbody>
									</table>
									<div v-if="Number(i.anulado) === 1 && i.anulado_motivo" class="small text-danger-emphasis mt-1">
										<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anulada el {{ formatoFecha(i.anulado_fecha) }}: {{ i.anulado_motivo }}
									</div>
								</td>
							</tr>
						</template>

						<tr v-if="btnBuscar">
							<td colspan="8" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="filtrada.length === 0">
							<td colspan="8" class="text-center text-body-secondary py-4">
								{{ termino ? 'Sin resultados para la búsqueda' : 'No hay ventas en el período seleccionado' }}
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</card-body>
	</card>

	<!-- Anular: pide el motivo -->
	<Teleport to="body">
		<div ref="modalAnular" class="modal fade" tabindex="-1" aria-labelledby="tituloAnularVenta" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<form class="modal-content" @submit.prevent="anular">
					<div class="modal-header py-2">
						<h2 id="tituloAnularVenta" class="modal-title h3">Anular venta {{ venta?.correlativo }}</h2>
						<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnGuardar" @click="modal?.hide()" />
					</div>
					<div class="modal-body">
						<p class="mb-2">
							Los productos regresarán al inventario de la sucursal
							<template v-if="venta && venta.nforma_pago && /cr[eé]dito/i.test(venta.nforma_pago)"> y se anulará la cuenta por cobrar</template>.
							Esta acción no se puede deshacer.
						</p>
						<label for="anularMotivo" class="form-label">Motivo *</label>
						<textarea
							id="anularMotivo"
							ref="motivo"
							v-model="motivo"
							class="form-control"
							:class="{ 'is-invalid': errorMotivo }"
							rows="2"
							maxlength="500"
							@input="errorMotivo = ''"
						/>
						<div v-if="errorMotivo" class="invalid-feedback">{{ errorMotivo }}</div>
					</div>
					<div class="modal-footer py-2">
						<button type="button" class="btn btn-outline-secondary" :disabled="btnGuardar" @click="modal?.hide()">Cancelar</button>
						<button type="submit" class="btn btn-danger" :disabled="btnGuardar">
							<span v-if="btnGuardar" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Anular
						</button>
					</div>
				</form>
			</div>
		</div>
	</Teleport>
</template>

<script>
	import { Modal } from 'bootstrap'
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { imprimirPdf } from '@/utils/imprimir'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "ListaVenta",
		mixins: [Accion],
		props: {
			estados: {
				type: Array,
				required: false,
				default: () => [],
			},
			fecha: {
				type: String,
				required: false,
				default: null,
			},
		},
		emits: ["volver"],
		data: () => ({
			imprimiendo: null,
			abierta: null,
			detalle: [],
			cargandoDetalle: false,
			venta: null,
			motivo: "",
			errorMotivo: ""
		}),
		created() {
			this.url = "ven/venta"
			this.bform = {
				fdel: this.fecha,
				fal: this.fecha,
				estado: null
			}
		},
		mounted() {
			this.modal = new Modal(this.$refs.modalAnular)
			this.$refs.modalAnular.addEventListener("shown.bs.modal", () => this.$refs.motivo?.focus())
		},
		beforeUnmount() {
			this.modal?.dispose()
		},
		methods: {
			verDetalle(i) {
				if (this.abierta === i.id) {
					this.abierta = null
					return
				}

				this.abierta = i.id
				this.detalle = []
				this.cargandoDetalle = true

				api
				.get(`/${this.url}/get_detalle/${i.id}`)
				.then(result => {
					this.detalle = result.data.det ?? []
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.cargandoDetalle = false
				})
			},
			imprimir(i) {
				this.imprimiendo = i.id

				imprimirPdf(`/${this.url}/imprimir/${i.id}`)
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudo generar el ticket."))
				})
				.finally(() => {
					this.imprimiendo = null
				})
			},
			pedirAnular(i) {
				this.venta = i
				this.motivo = ""
				this.errorMotivo = ""
				this.modal?.show()
			},
			anular() {
				if (!this.motivo.trim()) {
					this.errorMotivo = "Indique el motivo de la anulación."
					return
				}

				this.btnGuardar = true

				api
				.post(`/${this.url}/anular/${this.venta.id}`, {
					motivo: this.motivo.trim()
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(this.venta, res.linea)
						this.modal?.hide()
						this.$toast.success(res.mensaje)
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnGuardar = false
				})
			},
			// venta_estado.etiqueta viene como "badge bg-warning"; el color es lo que va después de "bg-"
			etiquetaEstado(etiqueta) {
				let color = String(etiqueta ?? "").split("bg-").pop().trim()
				return color === "green" ? "success" : color
			},
			formatoMonto,
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 2
				})
			},
			// "2026-09-17 03:45:53" → "17/09/2026 03:45"
			formatoFecha(fecha) {
				if (!fecha) {
					return ""
				}

				let [dia, hora] = String(fecha).split(" ")
				let [a, m, d] = dia.split("-")

				return `${d}/${m}/${a}` + (hora ? ` ${hora.slice(0, 5)}` : "")
			},
			estiloEtiqueta
		},
		computed: {
			// Solo ventas vigentes
			totalVendido() {
				return this.filtrada
				.filter(e => Number(e.anulado) === 0)
				.reduce((s, e) => s + Number(e.total_precio), 0)
			}
		},
		components: {
			PageHeader
		}
	}
</script>
