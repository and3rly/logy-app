<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<!-- Datos generales -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-store text-primary" aria-hidden="true" />Datos generales
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12">
				<label for="inputNombre" class="form-label">Nombre <span class="text-danger">*</span></label>
				<input
					id="inputNombre"
					v-model="form.nombre"
					type="text"
					class="form-control"
					maxlength="200"
					placeholder="Ej. Sucursal Central"
					required
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
		</div>

		<!-- Ubicación: el departamento solo filtra los municipios -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-location-dot text-primary" aria-hidden="true" />Ubicación
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12 col-md-6">
				<label for="selectDepartamento" class="form-label">Departamento <span class="text-danger">*</span></label>
				<select
					id="selectDepartamento"
					v-model="departamento"
					class="form-select"
					required
					@change="form.municipio_id = null"
				>
					<option :value="null">Seleccionar...</option>
					<option v-for="d in departamentos" :key="d.id" :value="String(d.id)">{{ d.nombre }}</option>
				</select>
			</div>

			<div class="col-12 col-md-6">
				<label for="selectMunicipio" class="form-label">Municipio <span class="text-danger">*</span></label>
				<select
					id="selectMunicipio"
					v-model="form.municipio_id"
					class="form-select"
					:disabled="!departamento"
					required
				>
					<option :value="null">Seleccionar...</option>
					<option v-for="m in municipiosDepartamento" :key="m.id" :value="String(m.id)">{{ m.nombre }}</option>
				</select>
			</div>

			<div class="col-12">
				<label for="inputDireccion" class="form-label">Dirección</label>
				<input
					id="inputDireccion"
					v-model="form.direccion"
					type="text"
					class="form-control"
					maxlength="200"
					placeholder="Calle, número, zona"
				>
			</div>
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
				<label class="form-check-label" for="checkActivo">Activa</label>
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
		name: "FormSucursal",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			sucursal: {
				type: Object,
				required: false,
				default: null,
			},
			departamentos: {
				type: Array,
				required: false,
				default: () => [],
			},
			municipios: {
				type: Array,
				required: false,
				default: () => [],
			},
		},
		mixins: [Accion],
		data: () => ({
			departamento: null
		}),
		created() {
			this.url   = "mnt/sucursal"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo       = 1
				this.fbase.municipio_id = null
			} else {
				this.setDataForm(this.sucursal)
				this.ubicarDepartamento()
			}
		},
		methods: {
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			},
			ubicarDepartamento() {
				let municipio = this.municipios.find(m => String(m.id) === String(this.form.municipio_id))
				this.departamento = municipio ? String(municipio.departamento_id) : null
			}
		},
		computed: {
			municipiosDepartamento() {
				return this.municipios.filter(m => String(m.departamento_id) === this.departamento)
			}
		},
		watch: {
			pk(valor) {
				if (valor) {
					this.setDataForm(this.sucursal)
				} else {
					this.limpiar()
				}

				this.ubicarDepartamento()
			},
			municipios() {
				this.ubicarDepartamento()
			}
		}
	}
</script>
