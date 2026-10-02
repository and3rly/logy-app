<template>
	<form @submit.prevent="enviar" autocomplete="off">
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
					placeholder="Ej. Ana López"
					required
				>
			</div>

			<div class="col-12 col-md-6">
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

			<div class="col-12 col-md-6">
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
		</div>

		<!-- Acceso: el alias es el usuario con el que inicia sesión -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-key text-primary" aria-hidden="true" />Acceso
		</h6>
		<div class="row g-2 mb-4">
			<div class="col-12 col-md-6">
				<label for="inputAlias" class="form-label">Usuario <span class="text-danger">*</span></label>
				<input
					id="inputAlias"
					v-model.trim="form.alias"
					type="text"
					class="form-control"
					maxlength="45"
					placeholder="Ej. alopez"
					autocomplete="off"
					required
				>
			</div>

			<div class="col-12 col-md-6">
				<label for="selectRol" class="form-label">Rol <span class="text-danger">*</span></label>
				<select
					id="selectRol"
					v-model="form.rol_id"
					class="form-select"
					required
				>
					<option :value="null">Seleccionar...</option>
					<option v-for="r in roles" :key="r.id" :value="String(r.id)">{{ r.nombre }}</option>
				</select>
			</div>

			<div class="col-12 col-md-6">
				<label for="inputClave" class="form-label">
					Contraseña <span v-if="reg === ''" class="text-danger">*</span>
				</label>
				<input
					id="inputClave"
					v-model="form.clave"
					type="password"
					class="form-control"
					minlength="6"
					autocomplete="new-password"
					:placeholder="reg === '' ? 'Mínimo 6 caracteres' : 'Dejar en blanco para no cambiarla'"
					:required="reg === ''"
				>
			</div>

			<div class="col-12 col-md-6">
				<label for="inputClave2" class="form-label">
					Confirmar contraseña <span v-if="reg === ''" class="text-danger">*</span>
				</label>
				<input
					id="inputClave2"
					v-model="form.clave2"
					type="password"
					class="form-control"
					autocomplete="new-password"
					placeholder="Repita la contraseña"
					:required="reg === '' || !!form.clave"
				>
			</div>
		</div>

		<!-- Sucursales: al menos una, y una de ellas principal -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-store text-primary" aria-hidden="true" />Sucursales <span class="text-danger">*</span>
		</h6>
		<div class="mb-4">
			<ul v-if="sucursales.length" class="list-group">
				<li
					v-for="s in sucursales"
					:key="s.id"
					class="list-group-item d-flex align-items-center justify-content-between gap-2"
				>
					<div class="form-check mb-0">
						<input
							:id="`checkSucursal${s.id}`"
							v-model="form.sucursales"
							class="form-check-input"
							type="checkbox"
							:value="String(s.id)"
							@change="revisarPrincipal"
						>
						<label class="form-check-label" :for="`checkSucursal${s.id}`">{{ s.nombre }}</label>
					</div>

					<div class="form-check mb-0">
						<input
							:id="`radioPrincipal${s.id}`"
							v-model="form.sucursal_id"
							class="form-check-input"
							type="radio"
							name="sucursalPrincipal"
							:value="String(s.id)"
							:disabled="!form.sucursales.includes(String(s.id))"
						>
						<label class="form-check-label small text-body-secondary" :for="`radioPrincipal${s.id}`">Principal</label>
					</div>
				</li>
			</ul>
			<div v-else class="text-body-secondary small">No hay sucursales activas en la empresa.</div>
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
		name: "FormUsuario",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			usuario: {
				type: Object,
				required: false,
				default: null,
			},
			roles: {
				type: Array,
				required: false,
				default: () => [],
			},
			sucursales: {
				type: Array,
				required: false,
				default: () => [],
			},
		},
		mixins: [Accion],
		created() {
			this.url   = "mnt/usuario"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo      = 1
				this.fbase.rol_id      = null
				this.fbase.sucursales  = []
				this.fbase.sucursal_id = null

				// El mixin copia fbase en mounted(), después del primer render
				this.form = {...this.fbase}
			} else {
				this.setDataForm(this.usuario)
				this.prepararForm()
			}
		},
		methods: {
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			},
			// Copia propia de las sucursales para no tocar la fila de la lista antes de guardar
			prepararForm() {
				this.form.rol_id      = this.form.rol_id ? String(this.form.rol_id) : null
				this.form.sucursales  = (this.form.sucursales ?? []).map(String)
				this.form.sucursal_id = this.form.sucursal_id ? String(this.form.sucursal_id) : null
				this.form.clave       = ""
				this.form.clave2      = ""
			},
			// La principal siempre es una de las marcadas; si no hay, toma la primera
			revisarPrincipal() {
				if (!this.form.sucursales.includes(this.form.sucursal_id)) {
					this.form.sucursal_id = this.form.sucursales[0] ?? null
				}
			},
			enviar() {
				if (this.form.clave && this.form.clave !== this.form.clave2) {
					this.$toast.error("Las contraseñas no coinciden.")
					return
				}

				if (this.form.sucursales.length === 0) {
					this.$toast.error("Asigne al menos una sucursal.")
					return
				}

				this.guardar()
			}
		},
		watch: {
			pk(valor) {
				if (valor) {
					this.setDataForm(this.usuario)
					this.prepararForm()
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
