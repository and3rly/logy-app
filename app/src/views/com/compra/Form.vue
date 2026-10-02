<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<fieldset :disabled="!editable || btnGuardar">
			<!-- Dos filas: proveedor, forma de pago y moneda; factura y guardar -->
			<div class="row g-2 align-items-end">
				<div class="col-12 col-md-6">
					<label for="selectProveedor" class="form-label d-flex justify-content-between gap-2">
						<span>Proveedor <span class="text-danger">*</span></span>
						<span v-if="proveedor" class="text-truncate">
							NIT {{ proveedor.identificacion || 'CF' }} ·
							{{ Number(proveedor.credito) === 1 ? `Crédito a ${proveedor.credito_dias} días` : 'Sin crédito' }}
						</span>
					</label>
					<select id="selectProveedor" v-model="form.proveedor_id" class="form-select" required>
						<option :value="null" disabled>Seleccionar...</option>
						<option
							v-for="p in disponibles(catalogo.proveedores, form.proveedor_id)"
							:key="p.id"
							:value="String(p.id)"
						>{{ p.nombre }}</option>
					</select>
				</div>

				<div class="col-6 col-md-3">
					<label for="selectFormaPago" class="form-label">Forma de pago <span class="text-danger">*</span></label>
					<select id="selectFormaPago" v-model="form.forma_pago_id" class="form-select" required>
						<option :value="null" disabled>Seleccionar...</option>
						<option
							v-for="f in disponibles(catalogo.formas_pago, form.forma_pago_id)"
							:key="f.id"
							:value="String(f.id)"
						>{{ f.nombre }}</option>
					</select>
				</div>

				<div class="col-6 col-md-3">
					<label for="selectMoneda" class="form-label">Moneda <span class="text-danger">*</span></label>
					<select id="selectMoneda" v-model="form.moneda_id" class="form-select" required>
						<option :value="null" disabled>Seleccionar...</option>
						<option
							v-for="m in disponibles(catalogo.monedas, form.moneda_id)"
							:key="m.id"
							:value="String(m.id)"
						>{{ m.nombre }} ({{ m.simbolo }})</option>
					</select>
				</div>

				<div class="col-6 col-md-4">
					<label for="inputFactura" class="form-label">No. de factura</label>
					<input
						id="inputFactura"
						v-model="form.factura_numero"
						type="text"
						class="form-control"
						maxlength="50"
						placeholder="Serie y número"
					>
				</div>

				<div class="col-6 col-md-4">
					<label for="inputFacturaFecha" class="form-label">Fecha de factura</label>
					<input
						id="inputFacturaFecha"
						v-model="form.factura_fecha"
						type="date"
						class="form-control"
					>
				</div>

				<div v-if="editable" class="col-12 col-md-4 d-grid">
					<!-- En una compra nueva es la acción principal (sólido); después, secundaria (la principal es Recibir) -->
					<button type="submit" class="btn" :class="reg === '' ? 'btn-primary' : 'btn-outline-primary'">
						<template v-if="btnGuardar">
							<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Guardando
						</template>
						<template v-else>
							<i class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
						</template>
					</button>
				</div>
			</div>
		</fieldset>
	</form>
</template>

<script>
	import Accion from '@/mixins/Accion.js'

	export default {
		name: "FormCompra",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			compra: {
				type: Object,
				required: false,
				default: null,
			},
			catalogo: {
				type: Object,
				required: true,
			},
			monedaDefecto: {
				type: [String, Number],
				required: false,
				default: null,
			},
			editable: {
				type: Boolean,
				required: false,
				default: true,
			},
		},
		emits: ["actualizar", "cancelar"],
		mixins: [Accion],
		created() {
			this.url   = "com/compra"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.proveedor_id  = null
				this.fbase.forma_pago_id = null
				this.fbase.moneda_id     = this.monedaDefecto ? String(this.monedaDefecto) : null
			} else {
				this.setDataForm(this.compra)
			}
		},
		methods: {
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			},
			// Opciones activas, más la que ya tiene la compra aunque esté inactiva
			disponibles(lista, actual) {
				return (lista ?? []).filter(e => Number(e.activo) === 1 || String(e.id) === String(actual))
			}
		},
		computed: {
			proveedor() {
				return (this.catalogo.proveedores ?? []).find(p => String(p.id) === String(this.form.proveedor_id))
			}
		},
		watch: {
			pk(valor) {
				if (valor) {
					this.setDataForm(this.compra)
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
