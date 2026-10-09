<template>
	<PageHeader>
		<button
			v-if="listaPrecios"
			type="button"
			class="btn btn-outline-secondary"
			@click="cerrarPrecios"
		>
			<i class="fa-solid fa-arrow-left me-1" aria-hidden="true" />Volver
		</button>
	</PageHeader>

	<!-- Precios de una lista -->
	<Precios
		v-if="listaPrecios"
		ref="precios"
		:lista="listaPrecios"
		@actualizar="preciosGuardados"
	/>

	<div v-else class="row g-3 align-items-start">
		<div class="col-sm-4">
			<card>
				<card-header>Información de la lista</card-header>
				<card-body>
					<Form
						:lista-precio="listaPrecio"
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
								placeholder="Buscar por nombre o descripción..."
								aria-label="Buscar listas de precios"
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
									<th class="ps-3">Nombre</th>
									<th class="text-end">Precios</th>
									<th class="text-end">Clientes</th>
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
										<div class="text-body">{{ i.nombre }}</div>
										<div v-if="i.descripcion" class="small text-body-secondary">{{ i.descripcion }}</div>
									</td>
									<td class="text-end">{{ i.precios }}</td>
									<td class="text-end">{{ i.clientes }}</td>
									<td>
										<span
											class="badge border rounded-1 fw-semibold"
											:class="Number(i.activo) === 1
												? 'bg-success-subtle text-success-emphasis border-success-subtle'
												: 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
										>{{ Number(i.activo) === 1 ? 'Activa' : 'Inactiva' }}</span>
									</td>
									<td class="text-end pe-3 text-nowrap">
										<button
											type="button"
											class="btn btn-sm btn-link"
											title="Precios"
											@click="abrirPrecios(i)"
										>
											<i class="fa-solid fa-tags" aria-hidden="true" />
										</button>
										<button
											type="button"
											class="btn btn-sm btn-link"
											title="Editar"
											@click="editarLista(i)"
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
									<td colspan="5" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay listas de precios registradas' }}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</card-body>
			</card>
		</div>
	</div>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Form from './Form.vue'
	import Precios from './Precios.vue'
	import Accion from '@/mixins/Accion.js'

	export default {
		name: "ListaPrecio",
		mixins: [Accion],
		data: () => ({
			listaPrecio: null,
			// Lista cuyos precios se están editando
			listaPrecios: null
		}),
		created() {
			this.url = "mnt/lista_precio"
		},
		methods: {
			nuevo() {
				this.listaPrecio = null
				this.reg         = ""
			},
			editarLista(obj) {
				this.listaPrecio = obj
				this.setDataForm(obj)
			},
			actualizar(reg) {
				this.setDataRegistro("listaPrecio", reg)
			},
			abrirPrecios(obj) {
				this.listaPrecios = obj
				window.scrollTo(0, 0)
			},
			cerrarPrecios() {
				if (this.$refs.precios?.hayCambios && !confirm("Hay precios sin guardar. ¿Desea salir sin guardarlos?")) {
					return
				}

				this.listaPrecios = null
			},
			// El conteo de precios de la fila cambia al guardar
			preciosGuardados(reg) {
				for (let i in reg) {
					this.listaPrecios[i] = reg[i]
				}
			}
		},
		components: {
			PageHeader,
			Form,
			Precios
		}
	}
</script>
