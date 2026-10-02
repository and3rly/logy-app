<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<!-- Datos generales -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-truck text-primary" aria-hidden="true" />Datos generales
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12 col-md-8">
				<label for="inputNombre" class="form-label">Nombre <span class="text-danger">*</span></label>
				<input
					id="inputNombre"
					v-model="form.nombre"
					type="text"
					class="form-control"
					maxlength="150"
					placeholder="Ej. Distribuidora El Sol"
					required
				>
			</div>

			<div class="col-12 col-md-4">
				<label for="inputIdentificacion" class="form-label">NIT / Identificación</label>
				<input
					id="inputIdentificacion"
					v-model="form.identificacion"
					type="text"
					class="form-control text-uppercase"
					maxlength="100"
					placeholder="Ej. 1234567-8"
				>
			</div>
		</div>

		<!-- Contacto -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-address-book text-primary" aria-hidden="true" />Contacto
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12 col-md-4">
				<label for="inputTelefono" class="form-label">Teléfono</label>
				<input
					id="inputTelefono"
					v-model="form.telefono"
					type="tel"
					class="form-control"
					maxlength="15"
					placeholder="Ej. 55551234"
				>
			</div>

			<div class="col-12 col-md-8">
				<label for="inputCorreo" class="form-label">Correo</label>
				<input
					id="inputCorreo"
					v-model="form.correo"
					type="email"
					class="form-control"
					maxlength="250"
					placeholder="correo@ejemplo.com"
				>
			</div>

			<div class="col-12">
				<label for="inputDireccion" class="form-label">Dirección</label>
				<input
					id="inputDireccion"
					v-model="form.direccion"
					type="text"
					class="form-control"
					maxlength="200"
					placeholder="Calle, número, zona, municipio"
				>
			</div>
		</div>

		<!-- Crédito que nos otorga el proveedor -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-credit-card text-primary" aria-hidden="true" />Crédito
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12">
				<div class="form-check form-switch">
					<input
						id="checkCredito"
						v-model="form.credito"
						class="form-check-input"
						type="checkbox"
						role="switch"
						:true-value="1"
						:false-value="0"
					>
					<label class="form-check-label" for="checkCredito">El proveedor nos otorga crédito</label>
				</div>
			</div>

			<template v-if="Number(form.credito) === 1">
				<div class="col-6">
					<label for="inputLimite" class="form-label">Límite de crédito</label>
					<input
						id="inputLimite"
						v-model="form.credito_limite"
						type="number"
						class="form-control text-end"
						min="0"
						step="0.01"
						placeholder="0.00"
					>
				</div>

				<div class="col-6">
					<label for="inputDias" class="form-label">Días de crédito</label>
					<input
						id="inputDias"
						v-model="form.credito_dias"
						type="number"
						class="form-control text-end"
						min="0"
						step="1"
						placeholder="Ej. 30"
					>
				</div>
			</template>
		</div>

		<div class="mb-4" v-if="reg !== ''">
			<div class="form-check form-switch">
				<input
					id="checkActivo"
					v-model="form.activo"
					class="form-check-input"
					type="checkbox"
					role="switch"
					:true-value="1"
					:false-value="0"
				>
				<label class="form-check-label" for="checkActivo">Activo</label>
			</div>
		</div>

		<div class="d-flex justify-content-end gap-2 pt-3 border-top">
			<button
				type="button"
				class="btn btn-outline-secondary"
				:disabled="btnGuardar"
				@click="cancelar"
			>
				<i class="fa-solid fa-xmark me-1" aria-hidden="true" />Cancelar
			</button>

			<button
				type="submit"
				class="btn btn-primary"
				:disabled="btnGuardar"
			>
				<template v-if="btnGuardar">
					<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Guardando
				</template>
				<template v-else>
					<i class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
				</template>
			</button>
		</div>
	</form>
</template>

<script>
	import Accion from '@/mixins/Accion.js'

	export default {
		name: "FormProveedor",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			proveedor: {
				type: Object,
				required: false,
				default: null,
			},
		},
		mixins: [Accion],
		created() {
			this.url   = "mnt/proveedor"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo       = 1
				this.fbase.credito      = 0
				this.fbase.credito_dias = 0
			} else {
				this.setDataForm(this.proveedor)
			}
		},
		methods: {
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			}
		},
		watch: {
			pk(valor) {
				if (valor) {
					this.setDataForm(this.proveedor)
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
