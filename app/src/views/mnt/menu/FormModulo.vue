<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<div class="row g-2 mb-3">
			<div class="col-12">
				<label for="inputModuloNombre" class="form-label">Nombre <span class="text-danger">*</span></label>
				<input
					id="inputModuloNombre"
					v-model="form.nombre"
					type="text"
					class="form-control"
					maxlength="100"
					placeholder="Ej. Inventario"
					required
				>
			</div>

			<div class="col-8">
				<label for="inputModuloIcono" class="form-label">Icono</label>
				<div class="input-group">
					<span class="input-group-text text-primary" style="width: 2.75rem" aria-hidden="true">
						<i :class="form.icono || 'fa-solid fa-circle'" class="fa-fw" />
					</span>
					<input
						id="inputModuloIcono"
						v-model="form.icono"
						type="text"
						class="form-control font-monospace"
						maxlength="50"
						placeholder="Ej. fa-solid fa-boxes-stacked"
					>
				</div>
				<div class="form-text">
					Clase de
					<a href="https://fontawesome.com/search?ic=free" target="_blank" rel="noopener noreferrer">Font Awesome</a>.
				</div>
			</div>

			<div class="col-4">
				<label for="inputModuloOrden" class="form-label">Orden</label>
				<input
					id="inputModuloOrden"
					v-model.number="form.orden"
					type="number"
					class="form-control"
					min="0"
					step="1"
				>
			</div>

			<div class="col-12">
				<span class="form-label d-block">Tipo</span>
				<div class="row g-2" role="radiogroup" aria-label="Tipo de módulo">
					<div class="col-sm-6">
						<div class="form-check">
							<input
								id="radioDetalleGrupo"
								v-model="form.detalle"
								type="radio"
								class="form-check-input"
								name="detalle"
								:value="1"
							>
							<label class="form-check-label" for="radioDetalleGrupo">Agrupa opciones</label>
							<div class="form-text mt-0">Se despliega y muestra sus opciones.</div>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-check">
							<input
								id="radioDetalleEnlace"
								v-model="form.detalle"
								type="radio"
								class="form-check-input"
								name="detalle"
								:value="0"
							>
							<label class="form-check-label" for="radioDetalleEnlace">Enlace directo</label>
							<div class="form-text mt-0">Lleva directo a una pantalla.</div>
						</div>
					</div>
				</div>
			</div>

			<div v-if="Number(form.detalle) === 0" class="col-12">
				<label for="inputModuloUrl" class="form-label">Ruta <span class="text-danger">*</span></label>
				<input
					id="inputModuloUrl"
					v-model="form.url"
					type="text"
					class="form-control font-monospace"
					maxlength="100"
					placeholder="Ej. /venta"
					required
				>
			</div>

			<div class="col-12" v-if="reg !== ''">
				<div class="form-check form-switch">
					<input
						id="checkModuloActivo"
						v-model="form.activo"
						class="form-check-input"
						type="checkbox"
						role="switch"
						:true-value="1"
						:false-value="0"
					>
					<label class="form-check-label" for="checkModuloActivo">Activo</label>
				</div>
			</div>
		</div>

		<div class="d-flex justify-content-end gap-2">
			<!-- Siempre visible: el formulario vive en un modal y Cancelar lo cierra -->
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
		name: "FormModulo",
		props: {
			pk: {
				type: String,
				required: false,
				default: ''
			},
			modulo: {
				type: Object,
				required: false,
				default: null
			},
			ordenSiguiente: {
				type: Number,
				required: false,
				default: 1
			}
		},
		mixins: [Accion],
		created() {
			this.url  = "mnt/menu"
			this.urlg = "guardar_modulo"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk !== "" && this.modulo) {
				this.setDataForm(this.modulo)
				this.form.detalle = Number(this.form.detalle)
				delete this.form.menu
			} else {
				this.fbase = {
					activo: 1,
					detalle: 1,
					orden: this.ordenSiguiente
				}
			}
		},
		methods: {
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			}
		}
	}
</script>
