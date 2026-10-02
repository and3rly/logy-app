<template>
	<PageHeader>
		<button type="button" class="btn btn-primary" @click="nuevo">
			<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nuevo usuario
		</button>
	</PageHeader>

	<card>
		<card-body class="p-0">
			<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
				<div class="input-group flex-grow-1 w-auto">
					<span class="input-group-text">
						<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
					</span>
					<input
						v-model="termino"
						type="search"
						class="form-control"
						placeholder="Buscar por nombre, usuario, rol, teléfono o correo..."
						aria-label="Buscar usuarios"
					>
				</div>

				<span
					class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
					aria-live="polite"
				>
					<i class="fa-solid fa-layer-group text-primary" aria-hidden="true" />
					<span>
						<span class="fw-semibold text-body">{{ termino ? `${filtrada.length} de ${lista.length}` : lista.length }}</span>
						{{ lista.length === 1 ? 'registro' : 'registros' }}
					</span>
				</span>
			</div>

			<div class="table-responsive">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th class="ps-3">Usuario</th>
							<th>Rol</th>
							<th>Contacto</th>
							<th>Sucursales</th>
							<th>Estado</th>
							<th class="text-end pe-3">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="i in filtrada"
							:key="i.id"
							class="align-middle"
							:class="{ 'table-active': String(i.id) === reg }"
						>
							<td class="ps-3">
								<div class="d-flex align-items-center gap-2">
									<span class="avatar" aria-hidden="true">{{ iniciales(i.nombre) }}</span>
									<div class="lh-sm">
										<div class="fw-semibold text-body">{{ i.nombre }}</div>
										<div class="small text-body-secondary font-monospace">{{ i.alias }}</div>
									</div>
								</div>
							</td>
							<td>
								<span v-if="i.rol" class="badge border rounded-1 fw-semibold bg-body-tertiary text-body">{{ i.rol }}</span>
								<span v-else class="text-body-secondary">—</span>
							</td>
							<td>
								<div v-if="i.telefono || i.correo" class="lh-sm">
									<div v-if="i.telefono"><i class="fa-solid fa-phone fa-fw text-body-secondary me-1" aria-hidden="true" />{{ i.telefono }}</div>
									<div v-if="i.correo" class="small text-body-secondary"><i class="fa-regular fa-envelope fa-fw me-1" aria-hidden="true" />{{ i.correo }}</div>
								</div>
								<span v-else class="text-body-secondary">—</span>
							</td>
							<td>
								<div v-if="nombresSucursales(i).length" class="lh-sm">
									<div
										v-for="s in nombresSucursales(i)"
										:key="s.id"
										:class="{ 'small text-body-secondary': !s.principal }"
									>
										<i
											v-if="s.principal"
											class="fa-solid fa-star fa-fw text-warning me-1"
											title="Principal"
											aria-hidden="true"
										/>{{ s.nombre }}
									</div>
								</div>
								<span v-else class="text-body-secondary">—</span>
							</td>
							<td>
								<span
									class="badge border rounded-1 fw-semibold"
									:class="Number(i.activo) === 1
										? 'bg-success-subtle text-success-emphasis border-success-subtle'
										: 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
								>{{ Number(i.activo) === 1 ? 'Activo' : 'Inactivo' }}</span>
							</td>
							<td class="text-end pe-3">
								<button
									type="button"
									class="btn btn-sm btn-link"
									title="Editar"
									@click="editarUsuario(i)"
								>
									<i class="fa-solid fa-pen" aria-hidden="true" />
								</button>
							</td>
						</tr>

						<tr v-if="btnBuscar">
							<td colspan="6" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="filtrada.length === 0">
							<td colspan="6" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay usuarios registrados' }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</card-body>
	</card>

	<Teleport to="body">
		<div
			ref="modal"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloModalUsuario"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloModalUsuario" class="modal-title fw-semibold mb-0">
								{{ reg === '' ? 'Nuevo usuario' : 'Editar usuario' }}
							</h5>
							<div class="small text-body-secondary">
								{{ reg === '' ? 'Los campos con * son obligatorios' : usuario?.nombre }}
							</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarModal" />
					</div>
					<div class="modal-body">
						<Form
							v-if="modalAbierto"
							:key="apertura"
							:usuario="usuario"
							:pk="reg"
							:roles="roles"
							:sucursales="sucursales"
							@actualizar="actualizar"
							@cancelar="cerrarModal"
						/>
					</div>
				</div>
			</div>
		</div>
	</Teleport>
</template>

<script>
	import { Modal } from 'bootstrap'
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Form from './Form.vue'
	import Accion from '@/mixins/Accion.js'
	import api from '@/services/api'

	let modal = null

	export default {
		name: "Usuario",
		mixins: [Accion],
		data: () => ({
			usuario: null,
			roles: [],
			sucursales: [],
			modalAbierto: false,
			apertura: 0
		}),
		created() {
			this.url = "mnt/usuario"
			this.getDatos()
		},
		mounted() {
			modal = new Modal(this.$refs.modal, { backdrop: "static" })

			this.$refs.modal.addEventListener("hidden.bs.modal", () => {
				this.modalAbierto = false
				this.usuario = null
				this.reg     = ""
			})
		},
		beforeUnmount() {
			modal?.dispose()
			modal = null
		},
		methods: {
			nuevo() {
				this.usuario = null
				this.reg     = ""
				this.abrirModal()
			},
			editarUsuario(obj) {
				this.usuario = obj
				this.setDataForm(obj)
				this.abrirModal()
			},
			actualizar(reg) {
				this.setDataRegistro("usuario", reg)
				this.cerrarModal()
			},
			abrirModal() {
				this.apertura++
				this.modalAbierto = true
				modal?.show()
			},
			cerrarModal() {
				modal?.hide()
			},
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					this.roles      = result.data.cat?.roles ?? []
					this.sucursales = result.data.cat?.sucursales ?? []
				})
				.catch(() => {
					this.roles      = []
					this.sucursales = []
				})
			},
			// Sucursales asignadas con su nombre, la principal primero
			nombresSucursales(usuario) {
				return this.sucursales
				.filter(s => (usuario.sucursales ?? []).some(id => String(id) === String(s.id)))
				.map(s => ({
					id: s.id,
					nombre: s.nombre,
					principal: String(s.id) === String(usuario.sucursal_id)
				}))
				.sort((a, b) => Number(b.principal) - Number(a.principal))
			},
			iniciales(nombre) {
				return (nombre ?? "")
				.split(/\s+/)
				.filter(Boolean)
				.slice(0, 2)
				.map(p => p[0])
				.join("")
				.toUpperCase()
			}
		},
		components: {
			PageHeader,
			Form
		}
	}
</script>
