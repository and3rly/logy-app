<template>
	<PageHeader>
		<button type="button" class="btn btn-primary" @click="nuevo">
			<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nueva sucursal
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
						placeholder="Buscar por nombre, dirección, teléfono o correo..."
						aria-label="Buscar sucursales"
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

			<div class="table-responsive tabla-pantalla">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th class="ps-3">Sucursal</th>
							<th>Contacto</th>
							<th>Dirección</th>
							<th>Ubicación</th>
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
										<div v-if="String(i.id) === sucursalActual" class="small text-primary">
											<i class="fa-solid fa-location-crosshairs me-1" aria-hidden="true" />Sucursal actual
										</div>
									</div>
								</div>
							</td>
							<td>
								<div v-if="i.telefono || i.correo" class="lh-sm">
									<div v-if="i.telefono"><i class="fa-solid fa-phone fa-fw text-body-secondary me-1" aria-hidden="true" />{{ i.telefono }}</div>
									<div v-if="i.correo" class="small text-body-secondary"><i class="fa-regular fa-envelope fa-fw me-1" aria-hidden="true" />{{ i.correo }}</div>
								</div>
								<span v-else class="text-body-secondary">—</span>
							</td>
							<td>
								<span
									v-if="i.direccion"
									class="d-inline-block text-truncate align-middle"
									style="max-width: 16rem"
									:title="i.direccion"
								>{{ i.direccion }}</span>
								<span v-else class="text-body-secondary">—</span>
							</td>
							<td>
								<div v-if="ubicacion(i.municipio_id)" class="lh-sm">
									<div>{{ ubicacion(i.municipio_id).municipio }}</div>
									<div class="small text-body-secondary">{{ ubicacion(i.municipio_id).departamento }}</div>
								</div>
								<span v-else class="text-body-secondary">—</span>
							</td>
							<td>
								<span
									class="badge border rounded-1 fw-semibold"
									:class="Number(i.activo) === 1
										? 'bg-success-subtle text-success-emphasis border-success-subtle'
										: 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
								>{{ Number(i.activo) === 1 ? 'Activa' : 'Inactiva' }}</span>
							</td>
							<td class="text-end pe-3">
								<button
									type="button"
									class="btn btn-sm btn-link"
									title="Editar"
									@click="editarSucursal(i)"
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
							<td colspan="6" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay sucursales registradas' }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</card-body>
	</card>

	<!-- Formulario en modal: grande, centrado y con scroll propio si no cabe -->
	<Teleport to="body">
		<div
			ref="modal"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloModalSucursal"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloModalSucursal" class="modal-title fw-semibold mb-0">
								{{ reg === '' ? 'Nueva sucursal' : 'Editar sucursal' }}
							</h5>
							<div class="small text-body-secondary">
								{{ reg === '' ? 'Los campos con * son obligatorios' : sucursal?.nombre }}
							</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarModal" />
					</div>
					<div class="modal-body">
						<Form
							v-if="modalAbierto"
							:key="apertura"
							:sucursal="sucursal"
							:pk="reg"
							:departamentos="departamentos"
							:municipios="municipios"
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
	import { useSesionStore } from '@/stores/sesion'

	let modal = null

	export default {
		name: "Sucursal",
		mixins: [Accion],
		data: () => ({
			sucursal: null,
			departamentos: [],
			municipios: [],
			modalAbierto: false,
			apertura: 0
		}),
		created() {
			this.url = "mnt/sucursal"
			this.getDatos()
		},
		mounted() {
			// Fondo estático: un clic fuera no cierra ni pierde lo escrito (Esc y Cancelar sí)
			modal = new Modal(this.$refs.modal, { backdrop: "static" })

			// Al cerrarse vuelve a modo nuevo
			this.$refs.modal.addEventListener("hidden.bs.modal", () => {
				this.modalAbierto = false
				this.sucursal = null
				this.reg      = ""
			})
		},
		beforeUnmount() {
			modal?.dispose()
			modal = null
		},
		computed: {
			// Sucursal de la sesión: se marca en la lista y no se puede desactivar
			sucursalActual() {
				let id = useSesionStore().usuario?.sucursal?.id
				return id ? String(id) : ""
			}
		},
		methods: {
			nuevo() {
				this.sucursal = null
				this.reg      = ""
				this.abrirModal()
			},
			editarSucursal(obj) {
				this.sucursal = obj
				this.setDataForm(obj)
				this.abrirModal()
			},
			actualizar(reg) {
				this.setDataRegistro("sucursal", reg)
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
			// Catálogos del formulario y de la columna Ubicación
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					this.departamentos = result.data.cat?.departamentos ?? []
					this.municipios    = result.data.cat?.municipios ?? []
				})
				.catch(() => {
					this.departamentos = []
					this.municipios    = []
				})
			},
			// Municipio y departamento de una sucursal, para la columna Ubicación
			ubicacion(municipioId) {
				let municipio = this.municipios.find(m => String(m.id) === String(municipioId))

				if (!municipio) {
					return null
				}

				let departamento = this.departamentos.find(d => String(d.id) === String(municipio.departamento_id))

				return {
					municipio: municipio.nombre,
					departamento: departamento ? departamento.nombre : ""
				}
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
