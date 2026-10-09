<template>
	<PageHeader>
		<button type="button" class="btn btn-primary" @click="nuevoModulo">
			<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nuevo módulo
		</button>
	</PageHeader>

	<div class="row g-3 align-items-start">
		<!-- ============================== Módulos ============================== -->
		<div class="col-12 col-xl-5">
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
								placeholder="Buscar módulo..."
								aria-label="Buscar módulos"
							>
						</div>

						<span
							class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
							aria-live="polite"
						>
							<i class="fa-solid fa-layer-group text-primary" aria-hidden="true" />
							<span>
								<span class="fw-semibold text-body">{{ termino ? `${filtrada.length} de ${lista.length}` : lista.length }}</span>
								{{ lista.length === 1 ? 'módulo' : 'módulos' }}
							</span>
						</span>
					</div>

					<div class="table-responsive tabla-pantalla">
						<table class="table table-sm table-hover mb-0">
							<thead>
								<tr>
									<th class="ps-3 text-center">Orden</th>
									<th>Módulo</th>
									<th>Tipo</th>
									<th>Estado</th>
									<th class="text-end pe-3">Acciones</th>
								</tr>
							</thead>
							<tbody>
								<tr
									v-for="i in filtrada"
									:key="i.id"
									class="align-middle"
									:class="{ 'table-active': modulo && String(i.id) === String(modulo.id) }"
									role="button"
									@click="seleccionarModulo(i)"
								>
									<td class="ps-3 text-center text-body-secondary">{{ i.orden }}</td>
									<td>
										<div class="d-flex align-items-center gap-2">
											<i :class="i.icono || 'fa-solid fa-circle'" class="fa-fw text-primary" aria-hidden="true" />
											<span class="fw-semibold text-body">{{ i.nombre }}</span>
										</div>
									</td>
									<td>
										<span v-if="Number(i.detalle) === 1" class="small text-body-secondary text-nowrap">
											<i class="fa-solid fa-folder-tree me-1" aria-hidden="true" />{{ i.menu.length }} {{ i.menu.length === 1 ? 'opción' : 'opciones' }}
										</span>
										<span v-else class="small text-body-secondary text-nowrap">
											<i class="fa-solid fa-link me-1" aria-hidden="true" /><span class="font-monospace">{{ i.url }}</span>
										</span>
									</td>
									<td>
										<span
											class="badge border rounded-1 fw-semibold"
											:class="claseActivo(i.activo)"
										>{{ Number(i.activo) === 1 ? 'Activo' : 'Inactivo' }}</span>
									</td>
									<td class="text-end pe-3">
										<button
											type="button"
											class="btn btn-sm btn-link"
											title="Editar módulo"
											@click.stop="editarModulo(i)"
										>
											<i class="fa-solid fa-pen" aria-hidden="true" />
										</button>
									</td>
								</tr>

								<tr v-if="btnBuscar">
									<td colspan="5" class="text-center text-body-secondary">
										<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
									</td>
								</tr>
								<tr v-else-if="filtrada.length === 0">
									<td colspan="5" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay módulos registrados' }}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</card-body>
			</card>
		</div>

		<!-- ====================== Opciones del módulo seleccionado ====================== -->
		<div class="col-12 col-xl-7 d-flex flex-column gap-3">
			<template v-if="modulo">
				<card>
					<card-header>
						<span>{{ regOpcion === '' ? 'Nueva opción' : 'Editar opción' }}</span>
						<span class="small fw-normal text-body-secondary">en {{ modulo.nombre }}</span>
						<button
							v-if="regOpcion !== ''"
							type="button"
							class="btn btn-sm btn-suave-success ms-auto"
							@click="nuevaOpcion"
						>
							<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nueva opción
						</button>
					</card-header>
					<card-body>
						<div v-if="Number(modulo.detalle) !== 1" class="alert alert-warning small py-2">
							<i class="fa-solid fa-circle-info me-1" aria-hidden="true" />
							Este módulo es un <strong>enlace directo</strong>: sus opciones no se muestran en el menú lateral.
						</div>

						<Form
							:key="`op-${modulo.id}-${regOpcion}-${aperturaOpcion}`"
							:opcion="opcion"
							:pk="regOpcion"
							:modulos="lista"
							:modulo-id="String(modulo.id)"
							:orden-siguiente="siguienteOrden(modulo.menu)"
							@actualizar="actualizarOpcion"
							@cancelar="nuevaOpcion"
						/>
					</card-body>
				</card>

				<card>
					<card-header>
						<i :class="modulo.icono || 'fa-solid fa-circle'" class="fa-fw text-primary" aria-hidden="true" />
						Opciones de {{ modulo.nombre }}
						<span class="ms-auto small fw-normal text-body-secondary">
							{{ modulo.menu.length }} {{ modulo.menu.length === 1 ? 'opción' : 'opciones' }}
						</span>
					</card-header>
					<card-body class="p-0">
						<div class="table-responsive tabla-pantalla">
							<table class="table table-sm mb-0">
								<thead>
									<tr>
										<th class="ps-3 text-center">Orden</th>
										<th>Opción</th>
										<th>Ruta</th>
										<th>Estado</th>
										<th class="text-end pe-3">Acciones</th>
									</tr>
								</thead>
								<tbody>
									<tr
										v-for="i in modulo.menu"
										:key="i.id"
										class="align-middle"
										:class="{ 'table-active': String(i.id) === regOpcion }"
									>
										<td class="ps-3 text-center text-body-secondary">{{ i.orden }}</td>
										<td>
											<div class="d-flex align-items-center gap-2">
												<i :class="i.icono || 'fa-regular fa-circle'" class="fa-fw text-body-secondary" aria-hidden="true" />
												<span class="text-body">{{ i.nombre }}</span>
											</div>
										</td>
										<td>
											<span class="font-monospace small">{{ i.url }}</span>
											<span
												v-if="!tienePantalla(i.url)"
												class="badge rounded-1 fw-semibold bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1"
												title="La interfaz aún no tiene una pantalla para esta ruta: el menú lleva al inicio"
											>Sin pantalla</span>
										</td>
										<td>
											<span
												class="badge border rounded-1 fw-semibold"
												:class="claseActivo(i.activo)"
											>{{ Number(i.activo) === 1 ? 'Activa' : 'Inactiva' }}</span>
										</td>
										<td class="text-end pe-3">
											<button
												type="button"
												class="btn btn-sm btn-link"
												title="Editar"
												@click="editarOpcion(i)"
											>
												<i class="fa-solid fa-pen" aria-hidden="true" />
											</button>
										</td>
									</tr>

									<tr v-if="modulo.menu.length === 0">
										<td colspan="5" class="text-center text-body-secondary py-4">
											Este módulo aún no tiene opciones.
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</card-body>
				</card>
			</template>

			<card v-else>
				<card-body>
					<div class="text-center text-body-secondary p-5">
						<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-sitemap" aria-hidden="true" /></div>
						Seleccione un módulo para ver y editar sus opciones.
					</div>
				</card-body>
			</card>
		</div>
	</div>

	<!-- Formulario del módulo en modal: la lista de módulos queda arriba, a la vista -->
	<Teleport to="body">
		<div
			ref="modal"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloModalModulo"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloModalModulo" class="modal-title fw-semibold mb-0">
								{{ reg === '' ? 'Nuevo módulo' : 'Editar módulo' }}
							</h5>
							<div class="small text-body-secondary">
								{{ reg === '' ? 'Los campos con * son obligatorios' : moduloEdit?.nombre }}
							</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarModal" />
					</div>
					<div class="modal-body">
						<FormModulo
							v-if="modalAbierto"
							:key="aperturaModulo"
							:modulo="moduloEdit"
							:pk="String(reg)"
							:orden-siguiente="siguienteOrden(lista)"
							@actualizar="actualizarModulo"
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
	import FormModulo from './FormModulo.vue'
	import Form from './Form.vue'
	import Accion from '@/mixins/Accion.js'
	import { useMenuStore } from '@/stores/menu'

	// Ruta comodín del router: cualquier url sin pantalla cae ahí
	const RUTA_COMODIN = "/:pathMatch(.*)*"

	let modal = null

	export default {
		name: "Menu",
		mixins: [Accion],
		data: () => ({
			// modulo: el seleccionado (sus opciones a la derecha); moduloEdit: el del modal
			modulo: null,
			moduloEdit: null,
			modalAbierto: false,
			opcion: null,
			regOpcion: "",
			aperturaModulo: 0,
			aperturaOpcion: 0
		}),
		created() {
			this.url = "mnt/menu"
		},
		mounted() {
			// Fondo estático: un clic fuera no cierra ni pierde lo escrito (Esc y Cancelar sí)
			modal = new Modal(this.$refs.modal, { backdrop: "static" })

			// Al cerrarse vuelve a modo nuevo
			this.$refs.modal.addEventListener("hidden.bs.modal", () => {
				this.modalAbierto = false
				this.moduloEdit   = null
				this.reg          = ""
			})
		},
		beforeUnmount() {
			modal?.dispose()
			modal = null
		},
		methods: {
			nuevoModulo() {
				this.moduloEdit = null
				this.reg        = ""
				this.abrirModal()
			},
			editarModulo(obj) {
				this.moduloEdit = obj
				this.setDataForm(obj)
				this.abrirModal()
			},
			// Seleccionar un módulo muestra sus opciones a la derecha
			seleccionarModulo(obj) {
				this.modulo = obj
				this.nuevaOpcion()
			},
			// Un módulo nuevo queda seleccionado para agregarle opciones
			actualizarModulo(linea) {
				if (this.reg === "") {
					this.lista.push(linea)
					this.seleccionarModulo(linea)
				} else {
					Object.assign(this.moduloEdit, linea)
				}

				this.ordenar(this.lista)
				this.refrescarMenu()
				this.cerrarModal()
			},
			abrirModal() {
				this.aperturaModulo++
				this.modalAbierto = true
				modal?.show()
			},
			cerrarModal() {
				modal?.hide()
			},
			nuevaOpcion() {
				this.opcion    = null
				this.regOpcion = ""
				this.aperturaOpcion++
			},
			editarOpcion(obj) {
				this.opcion    = obj
				this.regOpcion = String(obj.id)
			},
			// La opción puede haber cambiado de módulo: se quita de donde esté y se pone en el suyo
			actualizarOpcion(linea) {
				for (let m of this.lista) {
					m.menu = m.menu.filter(e => String(e.id) !== String(linea.id))
				}

				let destino = this.lista.find(m => String(m.id) === String(linea.modulo_id))

				if (destino) {
					destino.menu.push(linea)
					this.ordenar(destino.menu)
				}

				if (this.regOpcion === "") {
					this.aperturaOpcion++
				} else if (String(linea.modulo_id) === String(this.modulo.id)) {
					this.opcion = linea
				} else {
					this.nuevaOpcion()
				}

				this.refrescarMenu()
			},
			ordenar(arreglo) {
				arreglo.sort((a, b) => (Number(a.orden) - Number(b.orden)) || (Number(a.id) - Number(b.id)))
			},
			siguienteOrden(arreglo) {
				return arreglo.reduce((max, e) => Math.max(max, Number(e.orden)), 0) + 1
			},
			tienePantalla(url) {
				let destino = this.$router.resolve(url || "/")
				return !destino.matched.some(e => e.path === RUTA_COMODIN)
			},
			claseActivo(activo) {
				return Number(activo) === 1
					? "bg-success-subtle text-success-emphasis border-success-subtle"
					: "bg-secondary-subtle text-secondary-emphasis border-secondary-subtle"
			},
			// El menú lateral se vuelve a leer para reflejar lo guardado
			refrescarMenu() {
				useMenuStore().cargar()
			}
		},
		components: {
			PageHeader,
			FormModulo,
			Form
		}
	}
</script>
