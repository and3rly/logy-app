<template>
	<PageHeader />

	<div class="row g-3 align-items-start">
		<div class="col-sm-4">
			<card>
				<card-header>Información de la moneda</card-header>
				<card-body>
					<Form
						:moneda="moneda"
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
								placeholder="Buscar por nombre, código o símbolo..."
								aria-label="Buscar monedas"
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
									<th>Código</th>
									<th>Símbolo</th>
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
									<td>{{ i.codigo }}</td>
									<td>{{ i.simbolo }}</td>
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
											@click="editarMoneda(i)"
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
									<td colspan="5" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay monedas registradas' }}</td>
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
	import Accion from '@/mixins/Accion.js'

	export default {
		name: "Moneda",
		mixins: [Accion],
		data: () => ({
			moneda: null
		}),
		created() {
			this.url = "mnt/moneda"
		},
		methods: {
			nuevo() {
				this.moneda = null
				this.reg    = ""
			},
			editarMoneda(obj) {
				this.moneda = obj
				this.setDataForm(obj)
			},
			actualizar(reg) {
				this.setDataRegistro("moneda", reg)
			}
		},
		components: {
			PageHeader,
			Form
		}
	}
</script>