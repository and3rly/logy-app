<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<fieldset :disabled="!editable || btnGuardar">
			<div class="row g-2 align-items-end">
				<div class="col-12 col-md-5">
					<label for="selectDestinoTraslado" class="form-label">Sucursal destino <span class="text-danger">*</span></label>
					<select id="selectDestinoTraslado" v-model="form.sucursal_destino_id" class="form-select" required>
						<option :value="null" disabled>Seleccionar...</option>
						<option v-for="s in destinos" :key="s.id" :value="String(s.id)">{{ s.nombre }}</option>
					</select>
					<div v-if="editable && destinos.length === 0" class="form-text text-danger">No hay otra sucursal activa a la cual enviar.</div>
				</div>

				<div class="col-12" :class="editable ? 'col-md-4' : 'col-md-7'">
					<label for="inputObservacionTraslado" class="form-label">Observación</label>
					<input
						id="inputObservacionTraslado"
						v-model="form.observacion"
						type="text"
						class="form-control"
						maxlength="300"
						placeholder="Ej. Reposición semanal"
					>
				</div>

				<div v-if="editable" class="col-12 col-md-3 d-grid">
					<!-- En un traslado nuevo es la acción principal; después, secundaria (la principal es Enviar) -->
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
		name: "FormTraslado",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			traslado: {
				type: Object,
				required: false,
				default: null,
			},
			sucursales: {
				type: Array,
				required: true,
			},
			// Sucursal de la sesión (el origen): no puede ser el destino
			sucursalId: {
				type: [Number, String],
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
			this.url   = "inv/traslado"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.sucursal_destino_id = null
				this.fbase.observacion = ""
			} else {
				this.cargar()
			}
		},
		methods: {
			cargar() {
				this.setDataForm(this.traslado)
				this.form.sucursal_destino_id = String(this.traslado.sucursal_destino_id)
			}
		},
		computed: {
			// Sucursales activas distintas del origen, más la que ya tiene el traslado aunque esté inactiva
			destinos() {
				return this.sucursales.filter(s =>
					String(s.id) !== String(this.traslado?.sucursal_id ?? this.sucursalId) &&
					(Number(s.activo) === 1 || String(s.id) === String(this.traslado?.sucursal_destino_id))
				)
			}
		},
		watch: {
			pk(valor) {
				if (valor) {
					this.cargar()
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
