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
					placeholder="Ej. Quetzal"
					required
				>
			</div>

			<div class="col-6">
				<label for="inputCodigo" class="form-label">Código</label>
				<input
					id="inputCodigo"
					v-model="form.codigo"
					type="text"
					class="form-control text-uppercase"
					maxlength="45"
					placeholder="Ej. GTQ"
				>
			</div>

			<div class="col-6">
				<label for="inputSimbolo" class="form-label">Símbolo</label>
				<input
					id="inputSimbolo"
					v-model="form.simbolo"
					type="text"
					class="form-control"
					maxlength="5"
					placeholder="Ej. Q"
				>
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

	export default {
		name: "FormUm",
		props: {
      pk: {
        type: String,
        required: false,
        default: '',
      },
      moneda: {
        type: Object,
        required: false,
        default: null,
      },
    },
    mixins: [Accion],
    created() {
    	this.url   = "mnt/moneda"
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
          this.setDataForm(this.moneda)
        } else {
          this.limpiar()
        }
      }
    }
	}
</script>