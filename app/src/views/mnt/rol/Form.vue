<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<div class="row g-2 mb-3">
			<div class="col-12">
				<label for="inputNombre" class="form-label">Nombre <span class="text-danger">*</span></label>
				<input
					id="inputNombre"
					v-model="form.nombre"
					type="text"
					class="form-control"
					maxlength="45"
					placeholder="Ej. Vendedor"
					required
				>
			</div>

			<div class="col-12">
				<div class="form-check form-switch">
					<input
						id="checkAdministrador"
						v-model="form.administrador"
						class="form-check-input"
						type="checkbox"
						role="switch"
						:true-value="1"
						:false-value="0"
					>
					<label class="form-check-label" for="checkAdministrador">Acceso total</label>
				</div>
				<div class="form-text">Ve todas las opciones del menú sin asignarlas una por una.</div>
			</div>

			<div class="col-12" v-if="reg !== ''">
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
		</div>

		<div class="d-flex justify-content-end gap-2">
			<button
				v-if="reg !== ''"
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
		name: "FormRol",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			rol: {
				type: Object,
				required: false,
				default: null,
			},
		},
		mixins: [Accion],
		created() {
			this.url   = "mnt/rol"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo = 1
				this.fbase.administrador = 0
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
					this.setDataForm(this.rol)
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
