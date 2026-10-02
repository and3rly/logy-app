<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<fieldset :disabled="!editable || btnGuardar">
			<div class="row g-2 align-items-end">
				<!-- El sentido no se guarda: filtra los tipos (el tipo define si suma o resta) -->
				<div class="col-12 col-md-5">
					<span class="form-label d-block">Sentido</span>
					<div class="btn-group w-100" role="group" aria-label="Sentido del ajuste">
						<template v-for="s in sentidos" :key="s.valor">
							<input
								:id="`sentido${s.valor}`"
								v-model="sentido"
								type="radio"
								class="btn-check"
								name="sentido"
								:value="s.valor"
								:disabled="bloquearSentido && sentido !== s.valor"
							>
							<label class="btn" :class="sentido === s.valor ? s.activo : 'btn-outline-secondary'" :for="`sentido${s.valor}`">
								<i class="fa-solid me-1" :class="s.icono" aria-hidden="true" />{{ s.texto }}
							</label>
						</template>
					</div>
					<div v-if="bloquearSentido" class="form-text">Quite los productos para cambiar entre entrada y salida.</div>
				</div>

				<div class="col-12 col-md-7">
					<label for="selectTipoAjuste" class="form-label">Tipo de ajuste <span class="text-danger">*</span></label>
					<select id="selectTipoAjuste" v-model="form.inventario_ajuste_tipo_id" class="form-select" required>
						<option :value="null" disabled>Seleccionar...</option>
						<option v-for="t in tiposDisponibles" :key="t.id" :value="String(t.id)">{{ t.nombre }}</option>
					</select>
				</div>

				<div class="col-12 col-md-9">
					<label for="inputObservacionAjuste" class="form-label">
						Observación
						<span v-if="requiereObservacion" class="text-danger">*</span>
						<span v-if="requiereObservacion" class="small text-body-secondary">(requerida por el tipo)</span>
					</label>
					<input
						id="inputObservacionAjuste"
						v-model="form.observacion"
						type="text"
						class="form-control"
						maxlength="300"
						placeholder="Ej. Producto roto al descargar el camión"
						:required="requiereObservacion"
					>
				</div>

				<div v-if="editable" class="col-12 col-md-3 d-grid">
					<!-- En un ajuste nuevo es la acción principal; después, secundaria (la principal es Aplicar) -->
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
		name: "FormAjuste",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			ajuste: {
				type: Object,
				required: false,
				default: null,
			},
			tipos: {
				type: Array,
				required: true,
			},
			// Con productos agregados no se puede pasar de entrada a salida (ni al revés)
			tieneLineas: {
				type: Boolean,
				required: false,
				default: false,
			},
			editable: {
				type: Boolean,
				required: false,
				default: true,
			},
		},
		emits: ["actualizar", "cancelar"],
		mixins: [Accion],
		data: () => ({
			sentido: "SALIDA",
			sentidos: [
				{ valor: "ENTRADA", texto: "Entrada", icono: "fa-arrow-up", activo: "btn-success" },
				{ valor: "SALIDA", texto: "Salida", icono: "fa-arrow-down", activo: "btn-danger" }
			]
		}),
		created() {
			this.url   = "inv/ajuste"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.inventario_ajuste_tipo_id = null
				this.fbase.observacion = ""
			} else {
				this.cargar()
			}
		},
		methods: {
			cargar() {
				this.setDataForm(this.ajuste)
				this.form.inventario_ajuste_tipo_id = String(this.ajuste.inventario_ajuste_tipo_id)
				this.sentido = this.ajuste.sentido
			}
		},
		computed: {
			// Tipos activos del sentido elegido, más el que ya tiene el ajuste aunque esté inactivo
			tiposDisponibles() {
				return this.tipos.filter(t =>
					t.sentido === this.sentido &&
					(Number(t.activo) === 1 || String(t.id) === String(this.ajuste?.inventario_ajuste_tipo_id))
				)
			},
			tipo() {
				return this.tipos.find(t => String(t.id) === String(this.form.inventario_ajuste_tipo_id))
			},
			requiereObservacion() {
				return Number(this.tipo?.requiere_observacion) === 1
			},
			bloquearSentido() {
				return this.pk !== "" && this.tieneLineas
			}
		},
		watch: {
			// Al cambiar de sentido, el tipo elegido deja de valer
			sentido(valor) {
				if (this.tipo && this.tipo.sentido !== valor) {
					this.form.inventario_ajuste_tipo_id = null
				}
			},
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
