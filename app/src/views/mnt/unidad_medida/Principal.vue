<template>
	<PageHeader />

	<div class="row g-3 align-items-start">
		<div class="col-sm-4">
			<card>
				<card-header>Información de la unidad</card-header>
				<card-body>
					<Form
						:medida="medida"
						:pk="reg"
						@actualizar="actualizar"
						@cancelar="nuevo"
					/>
				</card-body>
			</card>

			<!-- Equivalencias de la unidad elegida: 1 Quintal = 100 Libras. Con ellas una unidad puede ser presentación de un producto -->
			<card v-if="reg !== '' && medida" class="mt-3">
				<card-header>Equivalencias de {{ medida.nombre }}</card-header>
				<card-body class="p-0">
					<table class="table table-sm mb-0">
						<tbody>
							<tr
								v-for="e in equivalencias"
								:key="e.id"
								class="align-middle"
								:class="{ 'table-active': String(e.id) === formEq.id, 'text-body-secondary': Number(e.activo) !== 1 }"
							>
								<td class="ps-3">
									<span :class="{ 'text-decoration-line-through': Number(e.activo) !== 1 }">
										1 {{ e.ngrande }} = {{ formatoEquivalencia(e.cantidad) }} {{ e.nmenor }}
									</span>
									<span v-if="Number(e.activo) !== 1" class="badge rounded-1 bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle ms-1">Inactiva</span>
								</td>
								<td class="text-end pe-3 text-nowrap">
									<button type="button" class="btn btn-sm btn-link" title="Editar" :disabled="guardandoEq" @click="editarEquivalencia(e)">
										<i class="fa-solid fa-pen" aria-hidden="true" />
									</button>
									<button
										type="button"
										class="btn btn-sm btn-link"
										:title="Number(e.activo) === 1 ? 'Desactivar' : 'Activar'"
										:disabled="guardandoEq"
										@click="cambiarActivoEquivalencia(e)"
									>
										<i class="fa-solid" :class="Number(e.activo) === 1 ? 'fa-toggle-on' : 'fa-toggle-off'" aria-hidden="true" />
									</button>
								</td>
							</tr>
							<tr v-if="cargandoEq">
								<td colspan="2" class="text-center text-body-secondary">
									<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
								</td>
							</tr>
							<tr v-else-if="equivalencias.length === 0">
								<td colspan="2" class="text-center text-body-secondary">Sin equivalencias</td>
							</tr>
						</tbody>
					</table>

					<!-- Captura: 1 [la grande] = [cantidad] [la pequeña]; el botón de flechas cambia de lado esta unidad -->
					<form class="p-3 border-top bg-body-tertiary" autocomplete="off" @submit.prevent="guardarEquivalencia">
						<div class="small fw-semibold text-body-secondary mb-2">
							{{ formEq.id === '' ? 'Nueva equivalencia' : 'Editar equivalencia' }}
						</div>
						<div class="input-group">
							<span class="input-group-text">1</span>
							<span v-if="formEq.esGrande" class="input-group-text fw-semibold">{{ medida.nombre }}</span>
							<select v-else v-model="formEq.otra_id" class="form-select" aria-label="Unidad grande" required>
								<option :value="null" disabled>Unidad...</option>
								<option v-for="u in otrasUnidades" :key="u.id" :value="String(u.id)">{{ u.nombre }}</option>
							</select>
							<span class="input-group-text">=</span>
							<input
								v-model.number="formEq.cantidad"
								type="number"
								class="form-control text-end"
								min="1.00001"
								step="any"
								placeholder="Cantidad"
								aria-label="Cantidad"
								required
							>
							<span v-if="!formEq.esGrande" class="input-group-text fw-semibold">{{ medida.nombre }}</span>
							<select v-else v-model="formEq.otra_id" class="form-select" aria-label="Unidad pequeña" required>
								<option :value="null" disabled>Unidad...</option>
								<option v-for="u in otrasUnidades" :key="u.id" :value="String(u.id)">{{ u.nombre }}</option>
							</select>
							<button type="button" class="btn btn-outline-secondary" title="Cambiar de lado" @click="formEq.esGrande = !formEq.esGrande">
								<i class="fa-solid fa-right-left" aria-hidden="true" />
							</button>
						</div>
						<div class="d-flex justify-content-end gap-2 mt-2">
							<button v-if="formEq.id !== ''" type="button" class="btn btn-sm btn-outline-secondary" :disabled="guardandoEq" @click="limpiarEquivalencia">
								<i class="fa-solid fa-xmark me-1" aria-hidden="true" />Cancelar
							</button>
							<button type="submit" class="btn btn-sm btn-primary" :disabled="guardandoEq">
								<span v-if="guardandoEq" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
								<template v-if="formEq.id === ''">
									<i v-if="!guardandoEq" class="fa-solid fa-plus me-1" aria-hidden="true" />Agregar
								</template>
								<template v-else>
									<i v-if="!guardandoEq" class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
								</template>
							</button>
						</div>
						<div class="form-text mb-0">Siempre desde la grande: 1 Quintal = 100 Libras. Con las flechas se cambia de lado {{ medida.nombre }}.</div>
					</form>
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
								placeholder="Buscar por nombre o código..."
								aria-label="Buscar unidades de medida"
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
											@click="editarMedida(i)"
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
									<td colspan="4" class="text-center text-body-secondary">{{ termino ? 'Sin resultados para la búsqueda' : 'No hay unidades de medida registradas' }}</td>
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
	import api, { mensajeError } from '@/services/api'

	// Captura de una equivalencia; esGrande: la unidad elegida va a la izquierda (1 Quintal = ...)
	const nuevaEquivalencia = () => ({
		id: "",
		esGrande: true,
		otra_id: null,
		cantidad: null,
		activo: 1
	})

	export default {
		name: "UnidadMedida",
		mixins: [Accion],
		data: () => ({
			medida: null,
			equivalencias: [],
			cargandoEq: false,
			guardandoEq: false,
			formEq: nuevaEquivalencia()
		}),
		created() {
			this.url = "mnt/umedida"
		},
		methods: {
			nuevo() {
				this.medida = null
				this.reg    = ""
				this.equivalencias = []
				this.limpiarEquivalencia()
			},
			editarMedida(obj) {
				this.medida = obj
				this.setDataForm(obj)
				this.limpiarEquivalencia()
				this.getEquivalencias()
			},
			actualizar(reg) {
				this.setDataRegistro("medida", reg)
			},
			getEquivalencias() {
				this.cargandoEq = true
				this.equivalencias = []

				api
				.get(`/${this.url}/get_equivalencias/${this.medida.id}`)
				.then(result => {
					this.equivalencias = result.data.lista ?? []
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.cargandoEq = false
				})
			},
			limpiarEquivalencia() {
				this.formEq = nuevaEquivalencia()
			},
			editarEquivalencia(e) {
				let esGrande = String(e.unidad_medida_id) === String(this.medida.id)

				this.formEq = {
					id: String(e.id),
					esGrande,
					otra_id: String(esGrande ? e.unidad_menor_id : e.unidad_medida_id),
					cantidad: Number(e.cantidad),
					activo: Number(e.activo)
				}
			},
			// La unidad elegida y la otra, según el lado en que va cada una
			datosEquivalencia(f) {
				let propia = String(this.medida.id)

				return {
					unidad_medida_id: f.esGrande ? propia : f.otra_id,
					unidad_menor_id: f.esGrande ? f.otra_id : propia,
					cantidad: f.cantidad,
					activo: f.activo
				}
			},
			guardarEquivalencia() {
				if (!(Number(this.formEq.cantidad) > 1)) {
					this.$toast.error("La cantidad debe ser mayor que 1: la unidad grande va a la izquierda (use las flechas).")
					return
				}

				this.enviarEquivalencia(this.formEq.id, this.datosEquivalencia(this.formEq))
				.then(linea => {
					if (linea) {
						this.limpiarEquivalencia()
					}
				})
			},
			cambiarActivoEquivalencia(e) {
				this.enviarEquivalencia(String(e.id), {
					unidad_medida_id: e.unidad_medida_id,
					unidad_menor_id: e.unidad_menor_id,
					cantidad: e.cantidad,
					activo: Number(e.activo) === 1 ? 0 : 1
				})
			},
			// Guarda y actualiza la lista; devuelve la fila guardada o null
			enviarEquivalencia(id, datos) {
				this.guardandoEq = true

				return api
				.post(`/${this.url}/guardar_equivalencia/${id}`, datos)
				.then(result => {
					let res = result.data

					if (!res.exito) {
						this.$toast.error(res.mensaje)
						return null
					}

					let i = this.equivalencias.findIndex(e => String(e.id) === String(res.linea.id))

					if (i >= 0) {
						this.equivalencias.splice(i, 1, res.linea)
					} else {
						this.equivalencias.push(res.linea)
					}

					this.$toast.success(res.mensaje)
					return res.linea
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
					return null
				})
				.finally(() => {
					this.guardandoEq = false
				})
			},
			formatoEquivalencia(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 5
				})
			}
		},
		computed: {
			// Para la otra unidad de la equivalencia: las activas menos la elegida
			otrasUnidades() {
				return this.lista.filter(u => Number(u.activo) === 1 && String(u.id) !== String(this.medida?.id))
			}
		},
		components: {
			PageHeader,
			Form
		}
	}
</script>
