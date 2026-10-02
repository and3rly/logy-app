<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<div class="row g-2 mb-3">
			<div class="col-md-6">
				<label for="inputOpcionNombre" class="form-label">Nombre <span class="text-danger">*</span></label>
				<input
					id="inputOpcionNombre"
					v-model="form.nombre"
					type="text"
					class="form-control"
					maxlength="100"
					placeholder="Ej. Existencias"
					required
				>
			</div>

			<div class="col-md-6">
				<label for="selectOpcionModulo" class="form-label">Módulo <span class="text-danger">*</span></label>
				<select
					id="selectOpcionModulo"
					v-model="form.modulo_id"
					class="form-select"
					required
				>
					<option v-for="m in modulos" :key="m.id" :value="String(m.id)">
						{{ m.nombre }}{{ Number(m.detalle) === 1 ? '' : ' (enlace directo)' }}
					</option>
				</select>
			</div>

			<div class="col-md-6">
				<label for="inputOpcionUrl" class="form-label">Ruta <span class="text-danger">*</span></label>
				<input
					id="inputOpcionUrl"
					v-model="form.url"
					type="text"
					class="form-control font-monospace"
					maxlength="100"
					placeholder="Ej. /existencia"
					required
				>
				<div v-if="form.url && !tienePantalla" class="form-text text-warning-emphasis">
					<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />La interfaz aún no tiene esta pantalla.
				</div>
			</div>

			<div class="col-8 col-md-4">
				<label for="inputOpcionIcono" class="form-label">Icono</label>
				<div class="input-group">
					<span class="input-group-text text-body-secondary" style="width: 2.75rem" aria-hidden="true">
						<i :class="form.icono || 'fa-regular fa-circle'" class="fa-fw" />
					</span>
					<input
						id="inputOpcionIcono"
						v-model="form.icono"
						type="text"
						class="form-control font-monospace"
						maxlength="50"
						placeholder="fa-solid fa-..."
					>
				</div>
			</div>

			<div class="col-4 col-md-2">
				<label for="inputOpcionOrden" class="form-label">Orden</label>
				<input
					id="inputOpcionOrden"
					v-model.number="form.orden"
					type="number"
					class="form-control"
					min="0"
					step="1"
				>
			</div>

			<div class="col-12" v-if="reg !== ''">
				<div class="form-check form-switch">
					<input
						id="checkOpcionActivo"
						v-model="form.activo"
						class="form-check-input"
						type="checkbox"
						role="switch"
						:true-value="1"
						:false-value="0"
					>
					<label class="form-check-label" for="checkOpcionActivo">Activa</label>
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

	// Ruta comodín del router: cualquier url sin pantalla cae ahí
	const RUTA_COMODIN = "/:pathMatch(.*)*"

	export default {
		name: "FormOpcion",
		props: {
			pk: {
				type: String,
				required: false,
				default: ''
			},
			opcion: {
				type: Object,
				required: false,
				default: null
			},
			modulos: {
				type: Array,
				required: false,
				default: () => []
			},
			moduloId: {
				type: String,
				required: true
			},
			ordenSiguiente: {
				type: Number,
				required: false,
				default: 1
			}
		},
		mixins: [Accion],
		created() {
			this.url   = "mnt/menu"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk !== "" && this.opcion) {
				this.setDataForm(this.opcion)
				this.form.modulo_id = String(this.form.modulo_id)
			} else {
				this.fbase = {
					activo: 1,
					modulo_id: this.moduloId,
					orden: this.ordenSiguiente
				}
			}
		},
		methods: {
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			}
		},
		computed: {
			tienePantalla() {
				let url = String(this.form.url ?? "").trim()
				let destino = this.$router.resolve(url.startsWith("/") ? url : `/${url}`)

				return !destino.matched.some(e => e.path === RUTA_COMODIN)
			}
		}
	}
</script>
