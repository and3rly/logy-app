<template>
	<PageHeader>
		<button type="button" class="btn btn-primary" @click="nuevo">
			<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nuevo cliente
		</button>
	</PageHeader>

	<!-- Lista a todo el ancho: con tantos campos, la tabla es la que necesita espacio -->
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
						placeholder="Buscar por nombre, NIT, código, teléfono o correo..."
						aria-label="Buscar clientes"
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
							<th class="ps-3">Cliente</th>
							<th>NIT</th>
							<th>Contacto</th>
							<th>Ubicación</th>
							<th>Crédito</th>
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
										<div class="small text-body-secondary">
											{{ i.razon_social && i.razon_social !== i.nombre ? i.razon_social : '' }}
											<span v-if="i.codigo" class="font-monospace">{{ i.razon_social && i.razon_social !== i.nombre ? '· ' : '' }}{{ i.codigo }}</span>
										</div>
									</div>
								</div>
							</td>
							<td>{{ i.identificacion || 'CF' }}</td>
							<td>
								<div v-if="i.telefono || i.correo" class="lh-sm">
									<div v-if="i.telefono"><i class="fa-solid fa-phone fa-fw text-body-secondary me-1" aria-hidden="true" />{{ i.telefono }}</div>
									<div v-if="i.correo" class="small text-body-secondary"><i class="fa-regular fa-envelope fa-fw me-1" aria-hidden="true" />{{ i.correo }}</div>
								</div>
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
								<div v-if="Number(i.credito) === 1" class="lh-sm">
									<div class="fw-semibold">{{ formatoMonto(i.credito_limite) }}</div>
									<div class="small text-body-secondary">{{ i.credito_dias }} {{ Number(i.credito_dias) === 1 ? 'día' : 'días' }}</div>
								</div>
								<span v-else class="badge border rounded-1 fw-semibold bg-body-tertiary text-body-secondary">Contado</span>
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
									@click="editarCliente(i)"
								>
									<i class="fa-solid fa-pen" aria-hidden="true" />
								</button>
							</td>
						</tr>

						<tr v-if="btnBuscar">
							<td colspan="7" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="filtrada.length === 0">
							<td colspan="7" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay clientes registrados' }}</td>
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
			aria-labelledby="tituloModalCliente"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloModalCliente" class="modal-title fw-semibold mb-0">
								{{ reg === '' ? 'Nuevo cliente' : 'Editar cliente' }}
							</h5>
							<div class="small text-body-secondary">
								{{ reg === '' ? 'Los campos con * son obligatorios' : cliente?.nombre }}
							</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarModal" />
					</div>
					<div class="modal-body">
						<Form
							v-if="modalAbierto"
							:key="apertura"
							:cliente="cliente"
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
	import { formatoMonto } from '@/utils/numero'

	let modal = null

	export default {
		name: "Cliente",
		mixins: [Accion],
		data: () => ({
			cliente: null,
			departamentos: [],
			municipios: [],
			modalAbierto: false,
			apertura: 0
		}),
		created() {
			this.url = "mnt/cliente"
			this.getDatos()
		},
		mounted() {
			// Fondo estático: un clic fuera no cierra ni pierde lo escrito (Esc y Cancelar sí)
			modal = new Modal(this.$refs.modal, { backdrop: "static" })

			// Al cerrarse vuelve a modo nuevo
			this.$refs.modal.addEventListener("hidden.bs.modal", () => {
				this.modalAbierto = false
				this.cliente = null
				this.reg     = ""
			})
		},
		beforeUnmount() {
			modal?.dispose()
			modal = null
		},
		methods: {
			nuevo() {
				this.cliente = null
				this.reg     = ""
				this.abrirModal()
			},
			editarCliente(obj) {
				this.cliente = obj
				this.setDataForm(obj)
				this.abrirModal()
			},
			actualizar(reg) {
				this.setDataRegistro("cliente", reg)
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
			// Municipio y departamento de un cliente, para la columna Ubicación
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
			},
			formatoMonto
		},
		components: {
			PageHeader,
			Form
		}
	}
</script>
