<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<!-- Datos generales -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-id-card text-primary" aria-hidden="true" />Datos generales
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12 col-md-6">
				<label for="inputNombre" class="form-label">Nombre <span class="text-danger">*</span></label>
				<input
					id="inputNombre"
					v-model="form.nombre"
					type="text"
					class="form-control"
					maxlength="150"
					placeholder="Ej. Juan Pérez"
					required
				>
			</div>

			<div class="col-12 col-md-6">
				<label for="inputRazon" class="form-label">Razón social</label>
				<input
					id="inputRazon"
					v-model="form.razon_social"
					type="text"
					class="form-control"
					maxlength="150"
					placeholder="Nombre para facturación"
				>
			</div>

			<div class="col-12 col-md-6">
				<label for="inputIdentificacion" class="form-label">NIT / Identificación</label>
				<input
					id="inputIdentificacion"
					v-model="form.identificacion"
					type="text"
					class="form-control text-uppercase"
					maxlength="20"
					placeholder="Ej. 1234567-8 o CF"
				>
			</div>

			<div class="col-12 col-md-6">
				<label for="inputCodigo" class="form-label">Código</label>
				<input
					id="inputCodigo"
					v-model="form.codigo"
					type="text"
					class="form-control"
					maxlength="10"
					placeholder="Ej. CLI001"
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
					inputmode="numeric"
					pattern="[0-9]*"
					class="form-control"
					maxlength="10"
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
					maxlength="70"
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
					maxlength="150"
					placeholder="Calle, número, zona"
				>
			</div>
		</div>

		<!-- Ubicación: el departamento solo filtra los municipios -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-location-dot text-primary" aria-hidden="true" />Ubicación
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12 col-md-6">
				<label for="selectDepartamento" class="form-label">Departamento</label>
				<select
					id="selectDepartamento"
					v-model="departamento"
					class="form-select"
					@change="form.municipio_id = null"
				>
					<option :value="null">Seleccionar...</option>
					<option v-for="d in departamentos" :key="d.id" :value="String(d.id)">{{ d.nombre }}</option>
				</select>
			</div>

			<div class="col-12 col-md-6">
				<label for="selectMunicipio" class="form-label">Municipio</label>
				<select
					id="selectMunicipio"
					v-model="form.municipio_id"
					class="form-select"
					:disabled="!departamento"
				>
					<option :value="null">Seleccionar...</option>
					<option v-for="m in municipiosDepartamento" :key="m.id" :value="String(m.id)">{{ m.nombre }}</option>
				</select>
			</div>
		</div>

		<!-- Crédito -->
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
					<label class="form-check-label" for="checkCredito">Otorgar crédito</label>
				</div>
			</div>

			<template v-if="Number(form.credito) === 1">
				<div class="col-6">
					<label for="inputLimite" class="form-label">Límite de crédito</label>
					<input
						id="inputLimite"
						v-model="form.credito_limite"
						type="number"
						class="form-control"
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
						class="form-control"
						min="0"
						step="1"
						placeholder="Ej. 30"
					>
				</div>
			</template>
		</div>

		<!-- Precios: sin lista paga el precio general (solo desde el mantenimiento de clientes) -->
		<template v-if="listasPrecio.length > 0">
			<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
				<i class="fa-solid fa-tags text-primary" aria-hidden="true" />Precios
			</h6>
			<div class="row g-2 mb-4">
				<div class="col-12 col-md-6">
					<label for="selectListaPrecio" class="form-label">Lista de precios</label>
					<select
						id="selectListaPrecio"
						v-model="form.lista_precio_id"
						class="form-select"
					>
						<option :value="null">Precio general</option>
						<option
							v-for="l in listasPrecio"
							:key="l.id"
							:value="String(l.id)"
							:disabled="Number(l.activo) !== 1 && String(l.id) !== String(form.lista_precio_id)"
						>{{ l.nombre }}{{ Number(l.activo) !== 1 ? ' (inactiva)' : '' }}</option>
					</select>
				</div>
				<div class="col-12 col-md-6 d-flex align-items-end">
					<div class="form-text mb-2">Lo que no está en la lista se le cobra al precio general.</div>
				</div>
			</div>
		</template>

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
		name: "FormCliente",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			cliente: {
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
			listasPrecio: {
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
			this.url   = "mnt/cliente"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo       = 1
				this.fbase.credito      = 0
				this.fbase.credito_dias = 0
				this.fbase.municipio_id = null
				this.fbase.lista_precio_id = null
			} else {
				this.setDataForm(this.cliente)
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
					this.setDataForm(this.cliente)
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
