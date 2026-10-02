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
					maxlength="150"
					placeholder="Ej. Herramientas"
					required
				>
			</div>

			<div class="col-12">
				<span id="etiquetaColor" class="form-label d-block">Color de la etiqueta</span>
				<div class="d-flex flex-wrap gap-2" role="radiogroup" aria-labelledby="etiquetaColor">
					<button
						v-for="c in coloresEtiqueta"
						:key="c.id"
						type="button"
						class="muestra-color"
						role="radio"
						:aria-checked="form.etiqueta === c.id"
						:aria-label="c.nombre"
						:title="c.nombre"
						:style="{ background: muestraEtiqueta(c.id) }"
						@click="form.etiqueta = c.id"
					>
						<i v-if="form.etiqueta === c.id" class="fa-solid fa-check text-white" aria-hidden="true" />
					</button>
				</div>
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
	import { coloresEtiqueta, muestraEtiqueta, ETIQUETA_POR_DEFECTO } from '@/config/etiquetas'

	export default {
		name: "FormCategoria",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			categoria: {
				type: Object,
				required: false,
				default: null,
			},
		},
		mixins: [Accion],
		data: () => ({
			coloresEtiqueta
		}),
		created() {
			this.url   = "mnt/categoria"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo   = 1
				this.fbase.etiqueta = ETIQUETA_POR_DEFECTO
			}
		},
		methods: {
			muestraEtiqueta,
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			}
		},
		watch: {
			pk(valor) {
				if (valor) {
					this.setDataForm(this.categoria)
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
