<template>
	<div v-if="Number(rol.administrador) === 1" class="text-body-secondary small">
		<i class="fa-solid fa-circle-info text-primary me-1" aria-hidden="true" />
		Este rol tiene acceso total: ve todas las opciones del menú.
	</div>

	<template v-else>
		<div v-if="cargando" class="text-center text-body-secondary py-2">
			<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
		</div>

		<form v-else @submit.prevent="guardar" autocomplete="off">
			<p class="text-body-secondary small mb-2">Marque las opciones del menú que puede abrir el rol.</p>

			<ul class="list-unstyled mb-3">
				<li
					v-for="m in modulos"
					:key="m.id"
					class="border-bottom py-2"
				>
					<div class="form-check">
						<input
							:id="`acceso-modulo-${m.id}`"
							class="form-check-input"
							type="checkbox"
							:checked="estadoModulo(m) === 'todo'"
							:indeterminate.prop="estadoModulo(m) === 'parte'"
							:disabled="m.detalle && m.menu.length === 0"
							@change="marcarModulo(m, $event.target.checked)"
						>
						<label class="form-check-label fw-semibold" :for="`acceso-modulo-${m.id}`">
							<i :class="[m.icono || 'fa-solid fa-circle', 'text-body-secondary me-1']" aria-hidden="true" />{{ m.nombre }}
						</label>
					</div>

					<div v-if="m.detalle" class="row row-cols-1 row-cols-sm-2 g-0 ps-4 pt-1">
						<div
							v-for="o in m.menu"
							:key="o.id"
							class="col form-check"
						>
							<input
								:id="`acceso-menu-${o.id}`"
								v-model="marcadas"
								class="form-check-input"
								type="checkbox"
								:value="clave(m.id, o.id)"
							>
							<label class="form-check-label" :for="`acceso-menu-${o.id}`">{{ o.nombre }}</label>
						</div>
						<div v-if="m.menu.length === 0" class="small text-body-secondary">Sin opciones activas</div>
					</div>
				</li>
			</ul>

			<div class="d-flex justify-content-end gap-2">
				<button
					type="button"
					class="btn btn-outline-secondary"
					:disabled="btnGuardar"
					@click="$emit('cancelar')"
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
</template>

<script>
	import api, { mensajeError } from '@/services/api'
	import { useMenuStore } from '@/stores/menu'

	export default {
		name: "AccesosRol",
		props: {
			rol: {
				type: Object,
				required: true,
			},
		},
		emits: ['guardado', 'cancelar'],
		data: () => ({
			modulos: [],
			// Claves "modulo_id-menu_id"; los módulos de enlace directo van como "modulo_id-"
			marcadas: [],
			cargando: false,
			btnGuardar: false
		}),
		mounted() {
			this.buscar()
		},
		methods: {
			clave(modulo, menu) {
				return `${modulo}-${menu ?? ''}`
			},
			// Claves de lo que se marca con la casilla del módulo
			clavesModulo(m) {
				return m.detalle
					? m.menu.map(o => this.clave(m.id, o.id))
					: [this.clave(m.id, null)]
			},
			estadoModulo(m) {
				const claves = this.clavesModulo(m)
				const cuantas = claves.filter(c => this.marcadas.includes(c)).length

				if (claves.length > 0 && cuantas === claves.length) return 'todo'
				return cuantas > 0 ? 'parte' : 'nada'
			},
			marcarModulo(m, marcar) {
				const claves = this.clavesModulo(m)
				const resto = this.marcadas.filter(c => !claves.includes(c))

				this.marcadas = marcar ? [...resto, ...claves] : resto
			},
			buscar() {
				if (Number(this.rol.administrador) === 1) return

				this.cargando = true

				api
				.get(`/mnt/rol/accesos/${this.rol.id}`)
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.modulos  = res.modulos
						this.marcadas = res.accesos.map(a => this.clave(a.modulo_id, a.menu_id))
					} else {
						this.$toast.error(res.mensaje)
					}

					this.cargando = false
				})
				.catch(e => {
					this.cargando = false
					this.$toast.error(mensajeError(e))
				})
			},
			guardar() {
				if (!confirm("¿Está seguro de guardar?")) return

				this.btnGuardar = true

				const accesos = this.marcadas.map(c => {
					const [modulo_id, menu_id] = c.split('-')
					return {
						modulo_id: Number(modulo_id),
						menu_id: menu_id ? Number(menu_id) : null
					}
				})

				api
				.post(`/mnt/rol/guardar_accesos/${this.rol.id}`, { accesos })
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.marcadas = res.accesos.map(a => this.clave(a.modulo_id, a.menu_id))
						this.$toast.success(res.mensaje)

						// Por si es el rol de quien está trabajando
						useMenuStore().cargar()
						this.$emit('guardado')
					} else {
						this.$toast.error(res.mensaje)
					}

					this.btnGuardar = false
				})
				.catch(e => {
					this.btnGuardar = false
					this.$toast.error(mensajeError(e))
				})
			}
		},
		watch: {
			'rol.id'() {
				this.buscar()
			},
			'rol.administrador'() {
				this.buscar()
			}
		}
	}
</script>
