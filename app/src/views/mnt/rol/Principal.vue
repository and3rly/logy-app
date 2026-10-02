<template>
	<PageHeader />

	<div class="row g-3 align-items-start">
		<div class="col-sm-4">
			<card>
				<card-header>Información del rol</card-header>
				<card-body>
					<Form
						:rol="rol"
						:pk="reg"
						@actualizar="actualizar"
						@cancelar="nuevo"
					/>
				</card-body>
			</card>
		</div>

		<div class="col-sm-8">
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
								placeholder="Buscar por nombre..."
								aria-label="Buscar roles"
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
									<th class="ps-3">Nombre</th>
									<th>Acceso</th>
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
									<td class="ps-3">{{ i.nombre }}</td>
									<td>{{ Number(i.administrador) === 1 ? 'Total' : 'Por opciones' }}</td>
									<td>
										<span
											class="badge border rounded-1 fw-semibold"
											:class="Number(i.activo) === 1
												? 'bg-success-subtle text-success-emphasis border-success-subtle'
												: 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
										>{{ Number(i.activo) === 1 ? 'Activo' : 'Inactivo' }}</span>
									</td>
									<td class="text-end pe-3 text-nowrap">
										<button
											type="button"
											class="btn btn-sm btn-link"
											:title="Number(i.administrador) === 1 ? 'Acceso total: ve todo el menú' : 'Accesos'"
											:disabled="Number(i.administrador) === 1"
											@click="abrirAccesos(i)"
										>
											<i class="fa-solid fa-key" aria-hidden="true" />
										</button>
										<button
											type="button"
											class="btn btn-sm btn-link"
											title="Editar"
											@click="editarRol(i)"
										>
											<i class="fa-solid fa-pen" aria-hidden="true" />
										</button>
									</td>
								</tr>

								<tr v-if="btnBuscar">
									<td colspan="4" class="text-center text-body-secondary">
										<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
									</td>
								</tr>
								<tr v-else-if="filtrada.length === 0">
									<td colspan="4" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay roles registrados' }}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</card-body>
			</card>
		</div>
	</div>

	<!-- Accesos del rol en modal: arriba (sin centrar) y con scroll propio si no cabe -->
	<Teleport to="body">
		<div
			ref="modal"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloModalAccesos"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloModalAccesos" class="modal-title fw-semibold mb-0">Accesos</h5>
							<div class="small text-body-secondary">{{ rolAccesos?.nombre }}</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarAccesos" />
					</div>
					<div class="modal-body">
						<Accesos
							v-if="rolAccesos"
							:key="apertura"
							:rol="rolAccesos"
							@guardado="cerrarAccesos"
							@cancelar="cerrarAccesos"
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
	import Accesos from './Accesos.vue'
	import Accion from '@/mixins/Accion.js'

	let modal = null

	export default {
		name: "Rol",
		mixins: [Accion],
		data: () => ({
			rol: null,
			rolAccesos: null,
			apertura: 0
		}),
		created() {
			this.url = "mnt/rol"
		},
		mounted() {
			// Fondo estático: un clic fuera no pierde lo marcado (Esc y Cancelar sí cierran)
			modal = new Modal(this.$refs.modal, { backdrop: "static" })

			this.$refs.modal.addEventListener("hidden.bs.modal", () => {
				this.rolAccesos = null
			})
		},
		beforeUnmount() {
			modal?.dispose()
			modal = null
		},
		methods: {
			nuevo() {
				this.rol = null
				this.reg = ""
			},
			editarRol(obj) {
				this.rol = obj
				this.setDataForm(obj)
			},
			actualizar(reg) {
				this.setDataRegistro("rol", reg)
			},
			abrirAccesos(obj) {
				this.apertura++
				this.rolAccesos = obj
				modal?.show()
			},
			cerrarAccesos() {
				modal?.hide()
			}
		},
		components: {
			PageHeader,
			Form,
			Accesos
		}
	}
</script>
