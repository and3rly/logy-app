<template>
	<card>
		<card-header>
			<span class="font-monospace">{{ cuenta.factura_numero }}</span>
			<button type="button" class="btn-close ms-auto" aria-label="Cerrar" @click="$emit('cerrar')" />
		</card-header>
		<card-body>
			<div class="small text-body-secondary mb-2">
				{{ cuenta.nproveedor }}<template v-if="cuenta.nit_proveedor"> · NIT {{ cuenta.nit_proveedor }}</template> · Compra {{ cuenta.compra_numero }}
			</div>

			<!-- Total, abonado y saldo -->
			<div class="row g-2 mb-3">
				<div class="col-4">
					<div class="rounded-3 border px-2 py-2 h-100">
						<div class="small text-body-secondary">Total</div>
						<div class="fw-semibold text-body text-nowrap">{{ formatoMonto(cuenta.total) }}</div>
					</div>
				</div>
				<div class="col-4">
					<div class="rounded-3 border px-2 py-2 h-100">
						<div class="small text-body-secondary">Pagado</div>
						<div class="fw-semibold text-success-emphasis text-nowrap">{{ formatoMonto(cuenta.abono) }}</div>
					</div>
				</div>
				<div class="col-4">
					<div class="rounded-3 border bg-body-tertiary px-2 py-2 h-100">
						<div class="small text-body-secondary">Saldo</div>
						<div class="fw-bold text-body text-nowrap">{{ formatoMonto(cuenta.saldo) }}</div>
					</div>
				</div>
			</div>

			<!-- Último comprobante registrado -->
			<div v-if="ultimo" class="alert alert-success d-flex align-items-center gap-2 py-2 small">
				<i class="fa-solid fa-circle-check" aria-hidden="true" />
				<span>Comprobante <span class="fw-semibold font-monospace">{{ ultimo.comprobante_numero }}</span></span>
				<button type="button" class="btn btn-sm btn-primary ms-auto" :disabled="imprimiendo !== null" @click="imprimir(ultimo)">
					<i class="fa-solid fa-print me-1" aria-hidden="true" />Imprimir
				</button>
			</div>

			<!-- Nuevo pago -->
			<form v-if="abonable" class="mb-3" autocomplete="off" @submit.prevent="pedirConfirmacion">
				<label for="pagoMonto" class="form-label small mb-1">Monto *</label>
				<div class="input-group mb-2">
					<span class="input-group-text">{{ cuenta.smoneda }}</span>
					<input
						id="pagoMonto"
						v-model="form.total"
						type="number"
						min="0.01"
						step="0.01"
						:max="Number(cuenta.saldo)"
						class="form-control text-end"
						:class="{ 'is-invalid': error }"
						placeholder="0.00"
						@input="error = ''"
					>
					<button type="button" class="btn btn-outline-secondary" title="Liquidar la cuenta" @click="saldoTotal">Saldo total</button>
				</div>
				<div v-if="error" class="small text-danger-emphasis mb-2">{{ error }}</div>

				<label for="pagoForma" class="form-label small mb-1">Forma de pago *</label>
				<select id="pagoForma" v-model="form.forma_pago_id" class="form-select mb-2">
					<option v-for="f in formasPago" :key="f.id" :value="String(f.id)">{{ f.nombre }}</option>
				</select>

				<!-- Transferencia o cheque: su número y fecha -->
				<div v-if="!esEfectivo" class="row g-2 mb-2">
					<div class="col-7">
						<label for="pagoDocumento" class="form-label small mb-1">No. de documento</label>
						<input id="pagoDocumento" v-model="form.documento_numero" type="text" class="form-control" maxlength="30" placeholder="Transferencia o cheque">
					</div>
					<div class="col-5">
						<label for="pagoFechaDoc" class="form-label small mb-1">Fecha</label>
						<input id="pagoFechaDoc" v-model="form.documento_fecha" type="date" class="form-control">
					</div>
				</div>

				<button type="submit" class="btn btn-primary w-100 mt-1" :disabled="btnGuardar">
					<span v-if="btnGuardar" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
					<i v-else class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
				</button>
			</form>

			<div v-else-if="Number(cuenta.anulado) === 1" class="alert alert-secondary small py-2">
				<i class="fa-solid fa-ban me-1" aria-hidden="true" />Cuenta anulada.
			</div>
			<div v-else class="alert alert-success small py-2">
				<i class="fa-solid fa-circle-check me-1" aria-hidden="true" />Cuenta pagada en su totalidad.
			</div>

			<!-- Historial de pagos -->
			<h6 class="fw-semibold text-body-secondary small mb-2">Pagos</h6>
			<div v-if="cargando" class="small text-body-secondary">
				<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
			</div>
			<div v-else-if="pagos.length === 0" class="small text-body-secondary">Sin pagos todavía.</div>
			<ul v-else class="list-group list-group-flush small">
				<li v-for="p in pagos" :key="p.id" class="list-group-item px-0 d-flex align-items-center gap-2">
					<div class="flex-grow-1 min-w-0 lh-sm" :class="{ 'opacity-50': Number(p.anulado) === 1 }">
						<div class="d-flex gap-2">
							<span class="font-monospace fw-semibold text-body">{{ p.comprobante_numero }}</span>
							<span v-if="Number(p.anulado) === 1" class="badge rounded-1 text-bg-secondary" :title="p.anulado_motivo">Anulado</span>
						</div>
						<div class="text-body-secondary text-truncate">
							{{ formatoFecha(p.fecha) }} · {{ p.nforma_pago }}<template v-if="p.documento_numero"> · {{ p.documento_numero }}</template>
						</div>
					</div>
					<span class="fw-semibold text-nowrap" :class="Number(p.anulado) === 1 ? 'text-decoration-line-through text-body-secondary' : 'text-body'">
						{{ formatoMonto(p.total) }}
					</span>
					<button type="button" class="btn btn-sm btn-link px-1" title="Imprimir comprobante" :disabled="imprimiendo !== null" @click="imprimir(p)">
						<span v-if="imprimiendo === p.id" class="spinner-border spinner-border-sm" aria-hidden="true" />
						<i v-else class="fa-solid fa-print" aria-hidden="true" />
					</button>
					<button
						v-if="Number(p.anulado) === 0 && Number(cuenta.anulado) === 0"
						type="button"
						class="btn btn-sm btn-link text-danger px-1"
						title="Anular pago"
						@click="pedirAnular(p)"
					>
						<i class="fa-solid fa-ban" aria-hidden="true" />
					</button>
				</li>
			</ul>
		</card-body>
	</card>

	<ConfirmModal
		ref="confirmar"
		titulo="Registrar pago"
		:mensaje="`Se registrará un pago de ${cuenta.smoneda} ${formatoMonto(form.total)} a la factura ${cuenta.factura_numero} de ${cuenta.nproveedor}.`"
		texto-confirmar="Registrar"
		variante="primary"
		@confirmar="guardar"
	/>

	<!-- Anular pago: pide el motivo -->
	<Teleport to="body">
		<div ref="modalAnular" class="modal fade" tabindex="-1" aria-labelledby="tituloAnularPago" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<form class="modal-content" @submit.prevent="anular">
					<div class="modal-header py-2">
						<h2 id="tituloAnularPago" class="modal-title h3">Anular pago {{ pago?.comprobante_numero }}</h2>
						<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnGuardar" @click="modalAnular?.hide()" />
					</div>
					<div class="modal-body">
						<p class="mb-2">El monto regresará al saldo de la cuenta por pagar.</p>
						<label for="pagoMotivo" class="form-label">Motivo *</label>
						<textarea
							id="pagoMotivo"
							ref="motivo"
							v-model="motivo"
							class="form-control"
							:class="{ 'is-invalid': errorMotivo }"
							rows="2"
							maxlength="200"
							@input="errorMotivo = ''"
						/>
						<div v-if="errorMotivo" class="invalid-feedback">{{ errorMotivo }}</div>
					</div>
					<div class="modal-footer py-2">
						<button type="button" class="btn btn-outline-secondary" :disabled="btnGuardar" @click="modalAnular?.hide()">Cancelar</button>
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
	import ConfirmModal from '../../../components/ui/ConfirmModal.vue'
	import api, { mensajeError } from '@/services/api'
	import { imprimirPdf } from '@/utils/imprimir'
	import { normalizar } from '@/utils/texto'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "PagoCuentaPagar",
		props: {
			cuenta: {
				type: Object,
				required: true,
			},
			formasPago: {
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
		emits: ["actualizar", "cerrar"],
		components: {
			ConfirmModal
		},
		data: () => ({
			pagos: [],
			cargando: false,
			btnGuardar: false,
			imprimiendo: null,
			ultimo: null,
			error: "",
			form: {
				total: "",
				forma_pago_id: null,
				documento_numero: "",
				documento_fecha: null
			},
			pago: null,
			motivo: "",
			errorMotivo: ""
		}),
		created() {
			this.limpiar()
			this.cargarPagos()
		},
		mounted() {
			this.modalAnular = new Modal(this.$refs.modalAnular)
			this.$refs.modalAnular.addEventListener("shown.bs.modal", () => this.$refs.motivo?.focus())
		},
		beforeUnmount() {
			this.modalAnular?.dispose()
		},
		methods: {
			cargarPagos() {
				this.cargando = true

				api
				.get(`/fin/cuenta_pagar/get_pagos/${this.cuenta.id}`)
				.then(result => {
					this.pagos = result.data.pagos ?? []
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.cargando = false
				})
			},
			// Efectivo por defecto
			limpiar() {
				let efectivo = this.formasPago.find(f => normalizar(f.nombre).includes("efectivo")) ?? this.formasPago[0]

				this.form = {
					total: "",
					forma_pago_id: efectivo ? String(efectivo.id) : null,
					documento_numero: "",
					documento_fecha: this.fecha
				}
				this.error = ""
			},
			saldoTotal() {
				this.form.total = Number(this.cuenta.saldo).toFixed(2)
				this.error = ""
			},
			pedirConfirmacion() {
				let monto = Math.round(Number(this.form.total) * 100) / 100

				if (!(monto > 0)) {
					this.error = "Indique un monto mayor a cero."
					return
				}

				if (monto > Number(this.cuenta.saldo)) {
					this.error = "El pago no puede superar el saldo."
					return
				}

				if (!this.form.forma_pago_id) {
					this.error = "Seleccione la forma de pago."
					return
				}

				this.$refs.confirmar?.abrir()
			},
			guardar() {
				this.$refs.confirmar?.cerrar()
				this.btnGuardar = true

				api
				.post(`/fin/cuenta_pagar/pagar/${this.cuenta.id}`, {
					...this.form,
					documento_numero: this.esEfectivo ? null : this.form.documento_numero,
					documento_fecha: this.esEfectivo ? null : this.form.documento_fecha
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.$toast.success(res.mensaje)
						this.pagos.push(res.pago)
						this.ultimo = res.pago
						this.$emit("actualizar", res.linea)
						this.limpiar()
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
			pedirAnular(p) {
				this.pago = p
				this.motivo = ""
				this.errorMotivo = ""
				this.modalAnular?.show()
			},
			anular() {
				if (!this.motivo.trim()) {
					this.errorMotivo = "Indique el motivo de la anulación."
					return
				}

				this.btnGuardar = true

				api
				.post(`/fin/cuenta_pagar/anular_pago/${this.pago.id}`, {
					motivo: this.motivo.trim()
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(this.pago, res.pago)
						this.$emit("actualizar", res.linea)
						this.modalAnular?.hide()
						this.$toast.success(res.mensaje)

						if (this.ultimo && this.ultimo.id === this.pago.id) {
							this.ultimo = null
						}
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
			imprimir(p) {
				this.imprimiendo = p.id

				imprimirPdf(`/fin/cuenta_pagar/imprimir_comprobante/${p.id}`)
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudo generar el comprobante."))
				})
				.finally(() => {
					this.imprimiendo = null
				})
			},
			formatoMonto,
			// "2026-09-28 10:15:00" → "28/09/2026"
			formatoFecha(fecha) {
				if (!fecha) {
					return ""
				}

				let [a, m, d] = String(fecha).slice(0, 10).split("-")
				return `${d}/${m}/${a}`
			}
		},
		computed: {
			abonable() {
				return Number(this.cuenta.anulado) === 0 && Number(this.cuenta.saldo) > 0
			},
			esEfectivo() {
				let f = this.formasPago.find(e => String(e.id) === String(this.form.forma_pago_id))
				return f ? normalizar(f.nombre).includes("efectivo") : true
			}
		},
		watch: {
			// Los catálogos pueden llegar después de abrir la cuenta
			formasPago() {
				if (!this.form.forma_pago_id) {
					this.limpiar()
				}
			}
		}
	}
</script>
