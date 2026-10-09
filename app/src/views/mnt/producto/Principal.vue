<template>
	<!-- ============================ Lista de productos ============================ -->
	<template v-if="!verFicha">
		<PageHeader>
			<button type="button" class="btn btn-primary" @click="nuevo">
				<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nuevo producto
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
							placeholder="Buscar por nombre, código o código de barras..."
							aria-label="Buscar productos"
						>
					</div>

					<select v-model="categoria" class="form-select w-auto" aria-label="Filtrar por categoría">
						<option :value="null">Todas las categorías</option>
						<option v-for="c in categorias" :key="c.id" :value="String(c.id)">{{ c.nombre }}</option>
					</select>

					<span
						class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
						aria-live="polite"
					>
						<i class="fa-solid fa-layer-group text-primary" aria-hidden="true" />
						<span>
							<span class="fw-semibold text-body">{{ termino || categoria ? `${visibles.length} de ${lista.length}` : lista.length }}</span>
							{{ lista.length === 1 ? 'registro' : 'registros' }}
						</span>
					</span>
				</div>

				<div class="table-responsive tabla-pantalla">
					<table class="table table-sm table-hover mb-0">
						<thead>
							<tr>
								<th class="ps-3">Producto</th>
								<th>Categoría</th>
								<th>Marca</th>
								<th>Unidad</th>
								<th class="text-end">Costo</th>
								<th class="text-end">Precio</th>
								<th>Estado</th>
								<th class="text-end pe-3">Acciones</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="i in visibles"
								:key="i.id"
								class="align-middle"
								role="button"
								@click="abrir(i)"
							>
								<td class="ps-3">
									<div class="d-flex align-items-center gap-2">
										<img
											v-if="i.foto"
											:src="urlImagen(i.foto)"
											:alt="i.nombre"
											width="36"
											height="36"
											class="rounded-2 border object-fit-cover flex-shrink-0"
											loading="lazy"
											referrerpolicy="no-referrer"
										>
										<span
											v-else
											class="d-inline-flex align-items-center justify-content-center rounded-2 border bg-body-tertiary text-body-secondary flex-shrink-0"
											style="width: 36px; height: 36px"
											aria-hidden="true"
										>
											<i class="fa-solid" :class="i.tipo_producto === 'S' ? 'fa-screwdriver-wrench' : 'fa-box'" />
										</span>
										<div class="lh-sm">
											<div class="fw-semibold text-body">{{ i.nombre }}</div>
											<div class="small text-body-secondary">
												<span class="font-monospace">{{ i.codigo }}</span>
												<span v-if="i.tipo_producto === 'S'"> · Servicio</span>
											</div>
										</div>
									</div>
								</td>
								<td>
									<span
										v-if="nombreDe(categorias, i.categoria_id)"
										class="badge rounded-1 fw-semibold etiqueta-color"
										:style="estiloEtiqueta(etiquetaDe(i.categoria_id))"
									>{{ nombreDe(categorias, i.categoria_id) }}</span>
								</td>
								<td>{{ nombreDe(marcas, i.marca_id) }}</td>
								<td>{{ nombreDe(unidades, i.unidad_medida_id) }}</td>
								<td class="text-end text-body-secondary">{{ formatoMonto(i.costo) }}</td>
								<td class="text-end fw-semibold">{{ formatoMonto(i.precio) }}</td>
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
										title="Abrir producto"
										@click.stop="abrir(i)"
									>
										<i class="fa-solid fa-arrow-right" aria-hidden="true" />
									</button>
								</td>
							</tr>

							<tr v-if="btnBuscar">
								<td colspan="8" class="text-center text-body-secondary">
									<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
								</td>
							</tr>
							<tr v-else-if="visibles.length === 0">
								<td colspan="8" class="text-center text-body-secondary">{{ termino || categoria ? 'Sin resultados para la búsqueda' : 'No hay productos registrados' }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</card-body>
		</card>
	</template>

	<!-- ============================ Ficha del producto ============================ -->
	<template v-else>
		<PageHeader>
			<button type="button" class="btn btn-outline-secondary" @click="regresar">
				<i class="fa-solid fa-arrow-left me-1" aria-hidden="true" />Volver a productos
			</button>
		</PageHeader>

		<!-- A la izquierda los datos; a la derecha presentaciones y existencias -->
		<div class="row g-3 align-items-start">
			<div class="col-12 col-xl-8">
				<card>
					<card-header class="flex-wrap">
						<span v-if="reg === ''">Nuevo producto</span>
						<template v-else>
							<span>{{ producto?.nombre }}</span>
							<span class="font-monospace small fw-normal text-body-secondary">{{ producto?.codigo }}</span>
							<span
								class="badge border rounded-1 fw-semibold"
								:class="Number(producto?.activo) === 1
									? 'bg-success-subtle text-success-emphasis border-success-subtle'
									: 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
							>{{ Number(producto?.activo) === 1 ? 'Activo' : 'Inactivo' }}</span>
						</template>
					</card-header>
					<card-body>
						<Form
							:key="`form-${apertura}`"
							:producto="producto"
							:pk="reg"
							:categorias="categorias"
							:marcas="marcas"
							:unidades="unidades"
							@actualizar="actualizar"
							@cancelar="regresar"
						/>
					</card-body>
				</card>
			</div>

			<!-- Los servicios no llevan presentaciones ni existencia -->
			<div v-if="producto?.tipo_producto !== 'S'" class="col-12 col-xl-4 d-flex flex-column gap-3">
				<card>
					<card-header>
						Presentaciones
					</card-header>
					<card-body class="p-0">
						<div v-if="cargandoFicha" class="text-center text-body-secondary p-4">
							<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
						</div>
						<Presentaciones
							v-else
							:key="`pre-${reg}-${apertura}`"
							:pk="reg"
							:unidad="unidadProducto"
							:presentaciones="ficha.presentaciones"
							:unidades="ficha.unidades"
							@cambio="ficha.presentaciones = $event"
						/>
					</card-body>
				</card>

				<card v-if="reg !== ''">
					<card-header>
						Existencias
						<span v-if="sucursal" class="ms-auto small fw-normal text-body-secondary text-truncate" :title="sucursal">
							<i class="fa-solid fa-store me-1" aria-hidden="true" />{{ sucursal }}
						</span>
					</card-header>
					<card-body class="p-0">
						<div v-if="cargandoFicha" class="text-center text-body-secondary p-4">
							<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
						</div>
						<Existencias
							v-else
							:existencias="ficha.existencias"
							:presentaciones="ficha.presentaciones"
							:unidad="unidadProducto"
							:existencia-minima="producto?.existencia_minima"
						/>
					</card-body>
				</card>
			</div>
		</div>
	</template>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Form from './Form.vue'
	import Presentaciones from './Presentaciones.vue'
	import Existencias from './Existencias.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto as monto } from '@/utils/numero'

	export default {
		name: "Producto",
		mixins: [Accion],
		data: () => ({
			producto: null,
			categoria: null,
			categorias: [],
			marcas: [],
			unidades: [],
			verFicha: false,
			apertura: 0,
			cargandoFicha: false,
			ficha: {
				presentaciones: [],
				existencias: [],
				unidades: []
			}
		}),
		created() {
			this.url = "mnt/producto"
			this.getDatos()
		},
		methods: {
			nuevo() {
				this.producto = null
				this.reg      = ""
				this.ficha    = { presentaciones: [], existencias: [], unidades: [] }
				this.apertura++
				this.verFicha = true
				window.scrollTo(0, 0)
			},
			abrir(obj) {
				this.producto = obj
				this.setDataForm(obj)
				this.reg = String(obj.id)
				this.apertura++
				this.verFicha = true
				this.getFicha()
				window.scrollTo(0, 0)
			},
			regresar() {
				this.verFicha = false
				this.producto = null
				this.reg      = ""
			},
			// Guardado: si es nuevo, la ficha queda abierta para agregar presentaciones.
			// La ficha se vuelve a leer: las unidades para presentaciones dependen de la unidad del producto
			actualizar(reg) {
				let nuevo = this.reg === ""
				this.setDataRegistro("producto", reg)

				if (nuevo) {
					this.producto = this.lista.find(e => String(e.id) === String(reg.id)) ?? reg
					this.reg      = String(reg.id)
				}

				this.getFicha()
			},
			// Presentaciones y existencia en la sucursal: solo lo que no viene en la lista
			getFicha() {
				this.cargandoFicha = true

				api
				.get(`/${this.url}/get_ficha/${this.reg}`)
				.then(result => {
					this.ficha = {
						presentaciones: result.data.presentaciones ?? [],
						existencias: result.data.existencias ?? [],
						unidades: result.data.unidades ?? []
					}
				})
				.catch(e => {
					this.ficha = { presentaciones: [], existencias: [], unidades: [] }
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.cargandoFicha = false
				})
			},
			// Catálogos del formulario y de las columnas de la tabla
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					this.categorias = result.data.cat?.categorias ?? []
					this.marcas     = result.data.cat?.marcas ?? []
					this.unidades   = result.data.cat?.unidades ?? []
				})
				.catch(() => {
					this.categorias = []
					this.marcas     = []
					this.unidades   = []
				})
			},
			nombreDe(lista, id) {
				let tmp = lista.find(e => String(e.id) === String(id))
				return tmp ? tmp.nombre : ""
			},
			etiquetaDe(categoriaId) {
				let tmp = this.categorias.find(e => String(e.id) === String(categoriaId))
				return tmp ? tmp.etiqueta : null
			},
			// Las fotos son archivos de Google Drive (se guarda su id)
			urlImagen(foto) {
				return `https://lh3.googleusercontent.com/d/${foto}`
			},
			formatoMonto(valor) {
				if (valor === null || valor === "" || valor === undefined) {
					return "—"
				}

				return monto(valor)
			},
			estiloEtiqueta
		},
		computed: {
			// Búsqueda del mixin más el filtro de categoría
			visibles() {
				if (!this.categoria) {
					return this.filtrada
				}

				return this.filtrada.filter(e => String(e.categoria_id) === this.categoria)
			},
			// Unidad de medida guardada del producto (las presentaciones se expresan en ella)
			unidadProducto() {
				return this.unidades.find(e => String(e.id) === String(this.producto?.unidad_medida_id)) ?? null
			},
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			}
		},
		components: {
			PageHeader,
			Form,
			Presentaciones,
			Existencias
		}
	}
</script>
