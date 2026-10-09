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
					maxlength="100"
					placeholder="Ej. Mayorista"
					required
				>
			</div>

			<div class="col-12">
				<label for="inputDescripcion" class="form-label">Descripción</label>
				<textarea
					id="inputDescripcion"
					v-model="form.descripcion"
					class="form-control"
					rows="2"
					maxlength="300"
					placeholder="Ej. Clientes que compran por caja"
				/>
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
					<label class="form-check-label" for="checkActivo">Activa</label>
				</div>
				<div class="form-text">Con la lista inactiva, sus clientes compran al precio general.</div>
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
		name: "FormListaPrecio",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			listaPrecio: {
				type: Object,
				required: false,
				default: null,
			},
		},
		mixins: [Accion],
		created() {
			this.url   = "mnt/lista_precio"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo = 1
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
					this.setDataForm(this.listaPrecio)
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
