<template>
	<!-- Cobro: contado pide el efectivo recibido y muestra el vuelto; crédito confirma el cliente -->
	<Teleport to="body">
		<div
			ref="modal"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloCobroVenta"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-dialog-centered">
				<form class="modal-content" autocomplete="off" @submit.prevent="confirmar">
					<div class="modal-header">
						<div>
							<h5 id="tituloCobroVenta" class="modal-title fw-semibold mb-0">Cobrar venta</h5>
							<div class="small text-body-secondary">{{ formaPago }}</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" :disabled="guardando" @click="cerrar" />
					</div>

					<div class="modal-body">
						<div class="rounded-3 border bg-body-tertiary p-3 mb-3 text-center">
							<div class="small text-body-secondary">Total a cobrar</div>
							<div class="display-6 fw-bold text-body text-nowrap">{{ simbolo }} {{ formatoMonto(total) }}</div>
						</div>

						<template v-if="credito">
							<ul v-if="cliente" class="list-unstyled small mb-0">
								<li class="d-flex gap-2 py-2 border-bottom">
									<span class="text-body-secondary">Cliente</span>
									<span class="ms-auto fw-semibold text-body">{{ cliente.nombre }}</span>
								</li>
								<li class="d-flex gap-2 py-2 border-bottom">
									<span class="text-body-secondary">Días de crédito</span>
									<span class="ms-auto fw-semibold text-body">{{ cliente.credito_dias }}</span>
								</li>
								<li class="d-flex gap-2 py-2">
									<span class="text-body-secondary">Límite de crédito</span>
									<span class="ms-auto fw-semibold text-body">
										{{ Number(cliente.credito_limite) > 0 ? `${simbolo} ${formatoMonto(cliente.credito_limite)}` : 'Sin límite' }}
									</span>
								</li>
							</ul>
							<div class="form-text">Se generará la cuenta por cobrar del cliente.</div>
						</template>

						<!-- Transferencia, cheque...: sin vuelto -->
						<div v-else-if="!efectivo" class="d-flex align-items-center gap-2 small text-body-secondary">
							<i class="fa-solid fa-circle-info text-primary" aria-hidden="true" />
							Confirme que recibió el pago por {{ formaPago.toLowerCase() }} antes de registrar la venta.
						</div>

						<template v-else>
							<label for="cobroRecibido" class="form-label">Efectivo recibido</label>
							<div class="input-group input-group-lg">
								<span class="input-group-text">{{ simbolo }}</span>
								<input
									id="cobroRecibido"
									ref="recibido"
									v-model="recibido"
									type="number"
									min="0"
									step="0.01"
									class="form-control text-end"
									:class="{ 'is-invalid': error }"
									placeholder="0.00"
									@input="error = ''"
								>
							</div>
							<div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
							<div v-else class="form-text">Déjelo vacío si recibe el monto exacto.</div>

							<div class="d-flex flex-wrap gap-2 mt-2">
								<button
									v-for="b in billetes"
									:key="b"
									type="button"
									class="btn btn-sm btn-outline-secondary"
									@click="recibido = b"
								>{{ simbolo }} {{ formatoMonto(b) }}</button>
							</div>

							<div class="d-flex justify-content-between align-items-center rounded-3 border p-3 mt-3">
								<span class="text-body-secondary">Vuelto</span>
								<span class="fs-4 fw-bold text-body">{{ simbolo }} {{ formatoMonto(vuelto) }}</span>
							</div>
						</template>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" :disabled="guardando" @click="cerrar">Cancelar</button>
						<button type="submit" class="btn btn-primary" :disabled="guardando">
							<span v-if="guardando" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
							<i v-else class="fa-solid fa-check me-1" aria-hidden="true" />{{ credito ? 'Registrar a crédito' : 'Cobrar' }}
						</button>
					</div>
				</form>
			</div>
		</div>
	</Teleport>
</template>

<script>
	import { Modal } from 'bootstrap'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "CobroVenta",
		props: {
			total: {
				type: Number,
				required: true,
			},
			simbolo: {
				type: String,
				required: false,
				default: "",
			},
			credito: {
				type: Boolean,
				required: false,
				default: false,
			},
			efectivo: {
				type: Boolean,
				required: false,
				default: true,
			},
			cliente: {
				type: Object,
				required: false,
				default: null,
			},
			formaPago: {
				type: String,
				required: false,
				default: "",
			},
			guardando: {
				type: Boolean,
				required: false,
				default: false,
			},
		},
		emits: ["confirmar"],
		data: () => ({
			recibido: "",
			error: ""
		}),
		mounted() {
			// Fondo estático: un clic fuera no cancela el cobro
			this.modal = new Modal(this.$refs.modal, { backdrop: "static" })
			this.$refs.modal.addEventListener("shown.bs.modal", () => this.$refs.recibido?.focus())
		},
		beforeUnmount() {
			this.modal?.dispose()
		},
		methods: {
			abrir() {
				this.recibido = ""
				this.error = ""
				this.modal?.show()
			},
			cerrar() {
				this.modal?.hide()
			},
			// Envía el efectivo recibido (null si no es en efectivo) para calcular el vuelto
			confirmar() {
				if (this.guardando) {
					return
				}

				if (this.credito || !this.efectivo) {
					this.$emit("confirmar", null)
					return
				}

				let recibido = this.recibido === "" ? this.total : Number(this.recibido)

				if (recibido < this.total) {
					this.error = "El efectivo recibido es menor que el total."
					return
				}

				this.$emit("confirmar", recibido)
			},
			formatoMonto
		},
		computed: {
			vuelto() {
				let recibido = Number(this.recibido || 0)
				return recibido > this.total ? Math.round((recibido - this.total) * 100) / 100 : 0
			},
			// Montos rápidos: exacto y los siguientes billetes redondos
			billetes() {
				let lista = [this.total]

				for (let b of [10, 20, 50, 100, 200]) {
					let monto = Math.ceil(this.total / b) * b

					if (monto > this.total && !lista.includes(monto)) {
						lista.push(monto)
					}
				}

				return lista.slice(0, 4)
			}
		}
	}
</script>
