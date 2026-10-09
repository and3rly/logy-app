<template>
	<!-- Un solo buscador: el lector (código exacto + Enter) agrega al momento; al escribir un nombre se
	     sugieren productos (flechas + Enter o clic). Siempre cantidad 1, se ajusta en la tabla -->
	<form
		v-if="editable"
		class="d-flex flex-wrap gap-2 px-3 py-2 border-bottom bg-body-tertiary"
		autocomplete="off"
		@submit.prevent="agregarBusqueda"
	>
		<div class="position-relative flex-grow-1" style="min-width: 16rem">
			<div class="input-group">
				<span class="input-group-text">
					<span v-if="btnGuardar" class="spinner-border spinner-border-sm" aria-hidden="true" />
					<i v-else class="fa-solid fa-magnifying-glass" aria-hidden="true" />
				</span>
				<input
					id="cotizacionProducto"
					ref="producto"
					v-model="busqueda"
					type="search"
					class="form-control"
					placeholder="Nombre, código o escanee el código de barras"
					aria-label="Buscar producto para agregar"
					role="combobox"
					aria-autocomplete="list"
					aria-controls="cotizacionSugerencias"
					:aria-expanded="mostrarSugerencias"
					@input="sugerido = 0; sugerenciasAbiertas = true"
					@focus="sugerenciasAbiertas = true"
					@blur="sugerenciasAbiertas = false"
					@keydown.down.prevent="moverSugerencia(1)"
					@keydown.up.prevent="moverSugerencia(-1)"
					@keydown.esc="busqueda = ''"
					@keydown.enter.prevent="agregarBusqueda"
				>
			</div>

			<!-- Sugerencias: mousedown.prevent para que el clic llegue antes de perder el foco -->
			<ul
				v-if="mostrarSugerencias"
				id="cotizacionSugerencias"
				class="list-group position-absolute start-0 end-0 mt-1 shadow-sm overflow-auto"
				style="z-index: 1050; max-height: 20rem"
				role="listbox"
			>
				<li
					v-for="(p, n) in sugerencias"
					:key="p.producto_id"
					class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 py-2"
					:class="{ active: n === sugerido }"
					role="option"
					:aria-selected="n === sugerido"
					style="cursor: pointer"
					@mousedown.prevent="agregar(p)"
					@mouseenter="sugerido = n"
				>
					<div class="lh-sm text-truncate">
						<div class="fw-semibold text-truncate">{{ p.nombre }}</div>
						<div class="small" :class="n === sugerido ? '' : 'text-body-secondary'">
							<span class="font-monospace">{{ p.codigo }}</span> · {{ p.nunidad }}
							<template v-if="enCotizacion(p)"> · En la cotización: {{ enCotizacion(p) }}</template>
						</div>
					</div>
					<div class="text-end small text-nowrap lh-sm">
						<div class="fw-semibold">{{ simbolo }} {{ formatoMonto(precioVenta(p, null)) }}</div>
						<div :class="n === sugerido ? '' : (Number(p.existencia) > 0 ? 'text-body-secondary' : 'text-danger-emphasis')">
							Existencia {{ formatoCantidad(p.existencia) }}
						</div>
					</div>
				</li>
				<li v-if="sugerencias.length === 0" class="list-group-item small text-body-secondary">
					Sin coincidencias. Revise el catálogo o cree el producto.
				</li>
			</ul>
		</div>

		<!-- Secundarios neutros: el buscador es la forma principal de agregar -->
		<button type="button" class="btn btn-outline-secondary" title="Ver todos los productos y agregar varios" @click="abrirCatalogo">
			<i class="fa-solid fa-list" aria-hidden="true" /><span class="ms-1 d-none d-md-inline">Catálogo</span>
		</button>
		<button type="button" class="btn btn-outline-secondary" title="Crear un producto nuevo y agregarlo a la cotización" @click="abrirNuevoProducto">
			<i class="fa-solid fa-plus" aria-hidden="true" /><span class="ms-1 d-none d-md-inline">Nuevo producto</span>
		</button>
	</form>

	<!-- Cliente con lista de precios: los productos que se agregan toman el precio de la lista -->
	<div v-if="editable && nlistaPrecio" class="px-3 py-2 border-bottom small text-body-secondary">
		<i class="fa-solid fa-tags text-primary me-1" aria-hidden="true" />Precios de la lista <span class="fw-semibold text-body">{{ nlistaPrecio }}</span>;
		lo que no está en ella va al precio general.
	</div>

	<!-- Nuevo producto: el mismo formulario del mantenimiento de productos -->
	<Teleport to="body">
		<div
			v-if="editable"
			ref="nuevoProducto"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloNuevoProductoCotizacion"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloNuevoProductoCotizacion" class="modal-title fw-semibold mb-0">Nuevo producto</h5>
							<div class="small text-body-secondary">Al guardarlo se agrega a esta cotización con cantidad 1</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarNuevoProducto" />
					</div>
					<div class="modal-body">
						<FormProducto
							v-if="nuevoProductoAbierto"
							:key="aperturaProducto"
							pk=""
							:categorias="categorias"
							:marcas="marcas"
							:unidades="unidades"
							@actualizar="productoCreado"
							@cancelar="cerrarNuevoProducto"
						/>
					</div>
				</div>
			</div>
		</div>
	</Teleport>

	<!-- Catálogo: todos los productos para agregar varios sin cerrar (mismo diseño de tabla que el resto) -->
	<Teleport to="body">
		<div
			v-if="editable"
			ref="catalogo"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloCatalogoCotizacion"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloCatalogoCotizacion" class="modal-title fw-semibold mb-0">Productos disponibles</h5>
							<div class="small text-body-secondary">Indique la cantidad y agregue; puede agregar varios sin cerrar</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarCatalogo" />
					</div>

					<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
						<div class="input-group flex-grow-1 w-auto">
							<span class="input-group-text">
								<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
							</span>
							<input
								ref="filtroCatalogo"
								v-model="filtroCatalogo"
								type="search"
								class="form-control"
								placeholder="Buscar por nombre, código o código de barras..."
								aria-label="Buscar productos"
							>
						</div>

						<select v-model="categoriaCatalogo" class="form-select w-auto" aria-label="Filtrar por categoría">
							<option :value="null">Todas las categorías</option>
							<option v-for="c in categorias" :key="c.id" :value="String(c.id)">{{ c.nombre }}</option>
						</select>

						<span
							class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
							aria-live="polite"
						>
							<i class="fa-solid fa-layer-group text-primary" aria-hidden="true" />
							<span>
								<span class="fw-semibold text-body">{{ filtroCatalogo || categoriaCatalogo ? `${productosCatalogo.length} de ${productos.length}` : productos.length }}</span>
								{{ productos.length === 1 ? 'registro' : 'registros' }}
							</span>
						</span>
					</div>

					<div class="modal-body p-0">
						<div class="table-responsive">
							<table class="table table-sm mb-0">
								<thead>
									<tr>
										<th class="ps-3">Producto</th>
										<th>Categoría</th>
										<th>Marca</th>
										<th>Presentación</th>
										<th class="text-end">Existencia</th>
										<th class="text-end">Precio</th>
										<th class="text-end" style="width: 7rem">Cantidad</th>
										<th class="text-end pe-3">Acciones</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="p in productosCatalogo" :key="p.producto_id" class="align-middle">
										<td class="ps-3">
											<div class="lh-sm">
												<div class="fw-semibold text-body">{{ p.nombre }}</div>
												<div class="small text-body-secondary">
													<span class="font-monospace">{{ p.codigo }}</span>
													<span
														v-if="enCotizacion(p)"
														class="badge border rounded-1 fw-semibold bg-success-subtle text-success-emphasis border-success-subtle ms-1"
													>En la cotización: {{ enCotizacion(p) }}</span>
												</div>
											</div>
										</td>
										<td>
											<span
												v-if="nombreDe(categorias, p.categoria_id)"
												class="badge rounded-1 fw-semibold etiqueta-color"
												:style="estiloEtiqueta(etiquetaDe(p.categoria_id))"
											>{{ nombreDe(categorias, p.categoria_id) }}</span>
										</td>
										<td>{{ nombreDe(marcas, p.marca_id) }}</td>
										<td>
											<select
												v-if="presentacionesDe(p.producto_id).length"
												:value="presentacionesCatalogo[p.producto_id] ?? ''"
												class="form-select form-select-sm w-auto"
												:aria-label="`Presentación de ${p.nombre}`"
												@change="presentacionesCatalogo[p.producto_id] = $event.target.value"
											>
												<option value="">{{ p.nunidad || 'Unidad' }}</option>
												<option v-for="pre in presentacionesDe(p.producto_id)" :key="pre.producto_presentacion_id" :value="String(pre.producto_presentacion_id)">{{ pre.nombre }}</option>
											</select>
											<span v-else class="text-body-secondary">{{ p.nunidad }}</span>
										</td>
										<td class="text-end" :class="Number(articuloCatalogo(p).existencia) > 0 ? 'text-body-secondary' : 'text-danger-emphasis'">
											{{ formatoCantidad(articuloCatalogo(p).existencia) }}
										</td>
										<td class="text-end">{{ formatoMonto(precioCatalogo(p)) }}</td>
										<td class="text-end">
											<input
												v-model="cantidades[p.producto_id]"
												type="number"
												class="form-control form-control-sm text-end"
												min="0.01"
												step="0.01"
												placeholder="1"
												:aria-label="`Cantidad de ${p.nombre}`"
												@keydown.enter.prevent="agregarDesdeCatalogo(p)"
											>
										</td>
										<td class="text-end pe-3">
											<button
												type="button"
												class="btn btn-sm btn-primary"
												title="Agregar a la cotización"
												:aria-label="`Agregar ${p.nombre} a la cotización`"
												:disabled="btnGuardar"
												@click="agregarDesdeCatalogo(p)"
											>
												<i class="fa-solid fa-plus" aria-hidden="true" />
											</button>
										</td>
									</tr>

									<tr v-if="productosCatalogo.length === 0">
										<td colspan="8" class="text-center text-body-secondary">Sin resultados para la búsqueda</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" @click="cerrarCatalogo">Listo</button>
					</div>
				</div>
			</div>
		</div>
	</Teleport>

	<!-- Cantidad, precio, descuento y nota se editan en la tabla y se guardan al salir del campo;
	     la unidad o la presentación van debajo del nombre. Los totales se muestran en el panel lateral -->
	<div class="table-responsive">
		<table class="table table-sm mb-0">
			<thead>
				<tr>
					<th class="ps-3">Producto</th>
					<th class="text-center" style="width: 9rem">Cantidad</th>
					<th class="text-end" style="width: 8.5rem">Precio</th>
					<th class="text-end" style="width: 5.5rem">Desc. %</th>
					<th class="text-end">Total</th>
					<th v-if="editable" class="text-end pe-3" style="width: 1%"></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="i in lista" :key="i.id" class="align-middle">
					<td class="ps-3">
						<div class="lh-sm">
							<div class="fw-semibold text-body">{{ i.producto_nombre }}</div>
							<div class="d-flex flex-wrap align-items-center gap-1 small text-body-secondary mt-1">
								<span class="font-monospace">{{ i.producto_codigo }}</span>
								<span aria-hidden="true">·</span>
								<!-- Sin presentación la línea es en la unidad de medida -->
								<select
									v-if="editable && presentacionesDe(i.producto_id).length"
									:value="i.producto_presentacion_id ? String(i.producto_presentacion_id) : ''"
									class="form-select form-select-sm w-auto py-0"
									:aria-label="`Presentación de ${i.producto_nombre}`"
									@change="cambiarPresentacion(i, $event.target.value)"
								>
									<option value="">{{ i.unidad_codigo }}</option>
									<option v-for="pre in presentacionesDe(i.producto_id)" :key="pre.producto_presentacion_id" :value="String(pre.producto_presentacion_id)">{{ pre.nombre }}</option>
								</select>
								<span v-else class="badge rounded-1 border bg-body-tertiary text-body-secondary fw-semibold">{{ i.presentacion_nombre || i.unidad_codigo }}</span>
								<span aria-hidden="true">·</span>
								<span :class="existencia(i).clase">{{ existencia(i).texto }}</span>
							</div>
						</div>
						<input
							v-if="editable && (notas[i.id] || i.observacion)"
							v-model="i.observacion"
							type="text"
							class="form-control form-control-sm mt-1"
							maxlength="300"
							placeholder="Nota para el cliente (sale en la cotización)"
							:aria-label="`Nota de ${i.producto_nombre}`"
							@change="guardarFila(i)"
							@keydown.enter.prevent="$event.target.blur()"
						>
						<div v-else-if="i.observacion" class="small text-body-secondary fst-italic mt-1">
							<i class="fa-regular fa-comment me-1" aria-hidden="true" />{{ i.observacion }}
						</div>
					</td>
					<td class="text-center">
						<div v-if="editable" class="input-group input-group-sm flex-nowrap cantidad-pasos">
							<button
								type="button"
								class="btn btn-outline-secondary"
								:disabled="Number(i.cantidad) <= 1"
								:aria-label="`Restar uno a ${i.producto_nombre}`"
								@click="sumarCantidad(i, -1)"
							>
								<i class="fa-solid fa-minus" aria-hidden="true" />
							</button>
							<input
								v-model="i.cantidad"
								type="number"
								class="form-control text-center px-1"
								min="0.01"
								step="0.01"
								:aria-label="`Cantidad de ${i.producto_nombre}`"
								@change="guardarFila(i)"
								@keydown.enter.prevent="$event.target.blur()"
							>
							<button
								type="button"
								class="btn btn-outline-secondary"
								:aria-label="`Sumar uno a ${i.producto_nombre}`"
								@click="sumarCantidad(i, 1)"
							>
								<i class="fa-solid fa-plus" aria-hidden="true" />
							</button>
						</div>
						<template v-else>{{ formatoCantidad(i.cantidad) }}</template>
					</td>
					<td class="text-end">
						<input
							v-if="editable"
							v-model="i.precio"
							type="number"
							class="form-control form-control-sm text-end"
							min="0"
							step="any"
							:aria-label="`Precio de ${i.producto_nombre}`"
							@change="guardarFila(i)"
							@keydown.enter.prevent="$event.target.blur()"
						>
						<template v-else>{{ formatoMonto(i.precio) }}</template>
					</td>
					<td class="text-end">
						<input
							v-if="editable"
							v-model="i.descuento_porcentaje"
							type="number"
							class="form-control form-control-sm text-end"
							min="0"
							max="100"
							step="any"
							:aria-label="`Descuento de ${i.producto_nombre}`"
							@change="guardarFila(i)"
							@keydown.enter.prevent="$event.target.blur()"
						>
						<template v-else>{{ Number(i.descuento_porcentaje) > 0 ? formatoPorcentaje(i.descuento_porcentaje) : '' }}</template>
					</td>
					<td class="text-end text-nowrap">
						<div class="fw-semibold">{{ formatoMonto(calculo(i).total) }}</div>
						<div v-if="calculo(i).descuento > 0" class="small text-body-secondary">
							<del>{{ formatoMonto(calculo(i).subtotal) }}</del>
						</div>
					</td>
					<td v-if="editable" class="text-end pe-3 text-nowrap">
						<button
							type="button"
							class="btn btn-sm btn-link"
							title="Agregar una nota para el cliente"
							:aria-label="`Nota de ${i.producto_nombre}`"
							@click="notas[i.id] = true"
						>
							<i class="fa-regular fa-comment" aria-hidden="true" />
						</button>
						<button type="button" class="btn btn-sm btn-link text-danger" title="Quitar" :aria-label="`Quitar ${i.producto_nombre}`" @click="quitarLinea(i)">
							<i class="fa-regular fa-trash-can" aria-hidden="true" />
						</button>
					</td>
				</tr>

				<tr v-if="btnBuscar">
					<td :colspan="columnas" class="text-center text-body-secondary">
						<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
					</td>
				</tr>
				<tr v-else-if="lista.length === 0">
					<td :colspan="columnas" class="text-center text-body-secondary py-5">
						<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-cart-plus" aria-hidden="true" /></div>
						<template v-if="editable">Busque un producto arriba o escanee su código para agregarlo</template>
						<template v-else>La cotización no tiene productos</template>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
	import { Modal } from 'bootstrap'
	import Accion from '@/mixins/Accion.js'
	import FormProducto from '../../mnt/producto/Form.vue'
	import api, { mensajeError } from '@/services/api'
	import { normalizar } from '@/utils/texto'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { formatoMonto, formatoCantidad, decimalesMonto } from '@/utils/numero'

	export default {
		name: "DetalleCotizacion",
		props: {
			cotizacionId: {
				type: String,
				required: true,
			},
			productos: {
				type: Array,
				required: false,
				default: () => [],
			},
			categorias: {
				type: Array,
				required: false,
				default: () => [],
			},
			marcas: {
				type: Array,
				required: false,
				default: () => [],
			},
			unidades: {
				type: Array,
				required: false,
				default: () => [],
			},
			simbolo: {
				type: String,
				required: false,
				default: "",
			},
			editable: {
				type: Boolean,
				required: false,
				default: true,
			},
			// Lista de precios de la cotización (la del cliente); null = precio general
			listaPrecioId: {
				type: [String, Number],
				required: false,
				default: null,
			},
		},
		emits: ["cotizacion", "lineas", "resumen", "producto-creado"],
		components: {
			FormProducto
		},
		mixins: [Accion],
		data: () => ({
			busqueda: "",
			// Sugerencia resaltada del buscador y si la lista está a la vista
			sugerido: 0,
			sugerenciasAbiertas: false,
			filtroCatalogo: "",
			categoriaCatalogo: null,
			cantidades: {},
			// Presentación elegida por producto en el catálogo ('' = unidad de medida)
			presentacionesCatalogo: {},
			nuevoProductoAbierto: false,
			aperturaProducto: 0,
			// Líneas con la nota abierta aunque todavía esté vacía
			notas: {},
			// Precios de la lista: { "producto-presentación": precio }; vacío = precio general
			precios: {},
			nlistaPrecio: null,
			consultaPrecios: 0
		}),
		created() {
			this.url   = "ven/cotizacion_detalle"
			this.autoBuscar = false
			// Espera de los botones − / + antes de guardar, por línea
			this.esperas = {}

			this.cargarDetalle()
			this.cargarPrecios()
		},
		mounted() {
			if (this.$refs.catalogo) {
				this.modalCatalogo = new Modal(this.$refs.catalogo)
				this.$refs.catalogo.addEventListener("shown.bs.modal", () => this.$refs.filtroCatalogo?.focus())
			}

			if (this.$refs.nuevoProducto) {
				// Fondo estático: un clic fuera no pierde lo escrito
				this.modalProducto = new Modal(this.$refs.nuevoProducto, { backdrop: "static" })
				this.$refs.nuevoProducto.addEventListener("hidden.bs.modal", () => {
					this.nuevoProductoAbierto = false
				})
			}
		},
		beforeUnmount() {
			this.modalCatalogo?.dispose()
			this.modalProducto?.dispose()
			Object.values(this.esperas).forEach(clearTimeout)
		},
		methods: {
			cargarDetalle() {
				this.btnBuscar = true

				api
				.get(`/ven/cotizacion/get_detalle/${this.cotizacionId}`)
				.then(result => {
					this.lista = (result.data.det ?? []).map(e => this.normalizarLinea(e))
					this.$emit("lineas", this.lista.length)
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnBuscar = false
				})
			},
			// Enter: el código o código de barras exacto (lector) se agrega al momento con cantidad 1;
			// si no, la sugerencia resaltada
			agregarBusqueda() {
				let texto = this.busqueda.trim().toLowerCase()

				if (!texto) {
					return
				}

				let p = this.productoPorCodigo(texto) ?? this.sugerencias[this.sugerido]

				if (!p) {
					this.$toast.error(`No hay un producto que coincida con "${this.busqueda.trim()}".`)
					this.$refs.producto?.select()
					return
				}

				this.agregar(p)
			},
			productoPorCodigo(texto) {
				return this.productos.find(e =>
					String(e.codigo).toLowerCase() === texto ||
					String(e.codigo_barra ?? "").toLowerCase() === texto
				)
			},
			moverSugerencia(paso) {
				let total = this.sugerencias.length

				if (total) {
					this.sugerenciasAbiertas = true
					this.sugerido = (this.sugerido + paso + total) % total
				}
			},
			// Si el producto (en esa presentación) ya está en la cotización se suma a su línea; si no, se agrega
			// con su precio de venta (el de la lista del cliente o el general; ver precioVenta)
			agregar(p, cantidad = 1, enfocar = true, presentacionId = null) {
				if (this.btnGuardar) {
					return Promise.resolve(false)
				}

				let pre = this.presentacionDe(p.producto_id, presentacionId)

				let existente = this.lista.find(e =>
					String(e.producto_id) === String(p.producto_id) &&
					String(e.producto_presentacion_id ?? "") === String(pre?.producto_presentacion_id ?? "")
				)

				if (existente) {
					existente.cantidad = Number(existente.cantidad) + cantidad

					// Aviso propio con la nueva cantidad
					return this.guardarFila(existente, false).then(exito => {
						if (exito) {
							this.$toast.success(`${existente.producto_nombre}: cantidad ${this.formatoCantidad(existente.cantidad)}`)
							this.nuevaLinea(enfocar)
						}

						return exito
					})
				}

				this.btnGuardar = true

				return api
				.post(`/${this.url}/guardar`, {
					cotizacion_id: this.cotizacionId,
					producto_id: p.producto_id,
					producto_presentacion_id: pre?.producto_presentacion_id ?? null,
					cantidad,
					precio: this.precioVenta(p, pre),
					descuento_porcentaje: 0
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.lista.push(this.normalizarLinea(res.linea))
						this.$emit("cotizacion", res.cotizacion)
						this.$emit("lineas", this.lista.length)
						this.nuevaLinea(enfocar)
						return true
					}

					this.$toast.error(res.mensaje)
					return false
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
					return false
				})
				.finally(() => {
					this.btnGuardar = false
				})
			},
			nuevaLinea(enfocar = true) {
				this.busqueda = ""
				this.sugerido = 0

				if (enfocar) {
					this.$nextTick(() => this.$refs.producto?.focus())
				}
			},
			// --- Catálogo (modal) ---
			abrirCatalogo() {
				this.modalCatalogo?.show()
			},
			cerrarCatalogo() {
				this.modalCatalogo?.hide()
				this.$nextTick(() => this.$refs.producto?.focus())
			},
			agregarDesdeCatalogo(p) {
				let cantidad = Number(this.cantidades[p.producto_id] || 1)

				if (!(cantidad > 0)) {
					this.$toast.error("La cantidad debe ser mayor a cero.")
					return
				}

				this.agregar(p, cantidad, false, this.presentacionesCatalogo[p.producto_id]).then(exito => {
					if (exito) {
						this.cantidades[p.producto_id] = ""
					}
				})
			},
			// --- Nuevo producto (modal) ---
			abrirNuevoProducto() {
				this.aperturaProducto++
				this.nuevoProductoAbierto = true
				this.modalProducto?.show()
			},
			cerrarNuevoProducto() {
				this.modalProducto?.hide()
				this.$nextTick(() => this.$refs.producto?.focus())
			},
			// El producto guardado pasa al catálogo (con la forma de las existencias, sin inventario) y se agrega con cantidad 1
			productoCreado(producto) {
				let unidad = this.unidades.find(e => String(e.id) === String(producto.unidad_medida_id))
				let fila = {
					...producto,
					producto_id: producto.id,
					existencia: 0,
					nunidad: unidad ? unidad.nombre : ""
				}

				this.$emit("producto-creado", fila)
				this.cerrarNuevoProducto()
				this.agregar(fila)
			},
			// Lo que ya está en la cotización de un producto, por presentación: "2 UND · 1 Caja 12"
			enCotizacion(p) {
				return this.lista
				.filter(e => String(e.producto_id) === String(p.producto_id))
				.map(e => `${this.formatoCantidad(e.cantidad)} ${e.presentacion_nombre || e.unidad_codigo}`)
				.join(" · ")
			},
			// Presentaciones activas de un producto, con su existencia y precio
			presentacionesDe(productoId) {
				let p = this.productos.find(e => String(e.producto_id) === String(productoId))
				return (p?.presentaciones ?? []).filter(e => Number(e.activo) === 1)
			},
			presentacionDe(productoId, presentacionId) {
				if (!presentacionId) {
					return null
				}

				return this.presentacionesDe(productoId).find(e => String(e.producto_presentacion_id) === String(presentacionId)) ?? null
			},
			// Existencia y precio del producto en la presentación elegida en el catálogo
			articuloCatalogo(p) {
				return this.presentacionDe(p.producto_id, this.presentacionesCatalogo[p.producto_id]) ?? p
			},
			precioCatalogo(p) {
				return this.precioVenta(p, this.presentacionDe(p.producto_id, this.presentacionesCatalogo[p.producto_id]))
			},
			// Precio de la lista de la cotización para el producto en esa presentación (null = unidad) o,
			// si no está en la lista, el general (el de la presentación: precio del producto por su factor)
			precioVenta(p, pre) {
				let precio = this.precios[`${p.producto_id}-${pre?.producto_presentacion_id ?? 0}`]
				return precio ?? Number((pre ?? p).precio ?? 0)
			},
			cargarPrecios() {
				let consulta = ++this.consultaPrecios

				if (!this.listaPrecioId) {
					this.precios      = {}
					this.nlistaPrecio = null
					return
				}

				api
				.get(`/mnt/lista_precio/get_precios/${this.listaPrecioId}`)
				.then(result => {
					// Si mientras tanto cambió el cliente, esta respuesta ya no sirve
					if (consulta !== this.consultaPrecios) {
						return
					}

					let precios = {}

					for (let f of result.data.lista ?? []) {
						precios[`${f.producto_id}-${f.producto_presentacion_id ?? 0}`] = Number(f.precio)
					}

					this.precios      = precios
					this.nlistaPrecio = result.data.nombre ?? null
				})
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudieron cargar los precios de la lista del cliente."))
				})
			},
			// Otra presentación: toma el precio de venta de la nueva y se guarda la fila
			cambiarPresentacion(linea, presentacionId) {
				let p = this.productos.find(e => String(e.producto_id) === String(linea.producto_id))
				let pre = this.presentacionDe(linea.producto_id, presentacionId)

				linea.producto_presentacion_id = pre ? pre.producto_presentacion_id : null
				linea.precio = p ? this.montoEntrada(this.precioVenta(p, pre)) : linea.precio
				this.guardarFila(linea)
			},
			nombreDe(lista, id) {
				let tmp = lista.find(e => String(e.id) === String(id))
				return tmp ? tmp.nombre : ""
			},
			etiquetaDe(categoriaId) {
				let tmp = this.categorias.find(e => String(e.id) === String(categoriaId))
				return tmp ? tmp.etiqueta : null
			},
			estiloEtiqueta,
			// Guarda los cambios de una fila; aviso = mostrar la notificación
			guardarFila(linea, aviso = true) {
				let porcentaje = Number(linea.descuento_porcentaje || 0)

				if (!(Number(linea.cantidad) > 0) || linea.precio === "" || !(Number(linea.precio) >= 0) || porcentaje < 0 || porcentaje > 100) {
					this.$toast.error("La cantidad debe ser mayor a cero, el precio no puede quedar vacío y el descuento va de 0 a 100 %.")
					this.cargarDetalle()
					return Promise.resolve(false)
				}

				return api
				.post(`/${this.url}/guardar/${linea.id}`, {
					cotizacion_id: this.cotizacionId,
					producto_id: linea.producto_id,
					producto_presentacion_id: linea.producto_presentacion_id,
					cantidad: linea.cantidad,
					precio: linea.precio,
					descuento_porcentaje: porcentaje,
					observacion: linea.observacion
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(linea, this.normalizarLinea(res.linea))
						this.$emit("cotizacion", res.cotizacion)

						if (aviso) {
							this.$toast.success(res.mensaje)
						}

						return true
					}

					this.$toast.error(res.mensaje)
					this.cargarDetalle()
					return false
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
					this.cargarDetalle()
					return false
				})
			},
			quitarLinea(obj) {
				api
				.post(`/${this.url}/anular`, { id: obj.id })
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.lista = this.lista.filter(e => e.id !== obj.id)
						this.$toast.success(res.mensaje)
						this.$emit("cotizacion", res.cotizacion)
						this.$emit("lineas", this.lista.length)
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			// Botones − / +: se ve al momento y se guarda al dejar de pulsar
			sumarCantidad(linea, paso) {
				let cantidad = Math.round((Number(linea.cantidad) + paso) * 100) / 100

				if (!(cantidad > 0)) {
					return
				}

				linea.cantidad = cantidad

				clearTimeout(this.esperas[linea.id])
				this.esperas[linea.id] = setTimeout(() => {
					delete this.esperas[linea.id]
					this.guardarFila(linea, false)
				}, 500)
			},
			// La API manda los decimales de la columna ("1.00000"): en los campos, la cantidad sin ceros
			// de sobra y el precio con los decimales de montos
			normalizarLinea(linea) {
				return {
					...linea,
					cantidad: Number(linea.cantidad),
					precio: this.montoEntrada(linea.precio),
					descuento_porcentaje: Number(linea.descuento_porcentaje)
				}
			},
			// Monto para un campo editable: con los decimales configurados, salvo que traiga más
			// (no se redondea un precio guardado solo por mostrarlo)
			montoEntrada(valor) {
				let numero = Number(valor ?? 0)
				let texto = numero.toFixed(decimalesMonto())

				return Number(texto) === numero ? texto : String(numero)
			},
			// Mientras se edita, los importes se calculan en pantalla (igual que la API)
			calculo(linea) {
				let redondear = v => Math.round(v * 100) / 100
				let subtotal = redondear((Number(linea.cantidad) || 0) * (Number(linea.precio) || 0))
				let descuento = redondear(subtotal * (Number(linea.descuento_porcentaje) || 0) / 100)
				let costo = redondear((Number(linea.cantidad) || 0) * (Number(linea.costo) || 0))

				return {
					subtotal,
					descuento,
					total: subtotal - descuento,
					ganancia: subtotal - descuento - costo
				}
			},
			// Existencia de la sucursal: solo informa, cotizar no aparta inventario
			existencia(linea) {
				let p = this.productos.find(e => String(e.producto_id) === String(linea.producto_id))
				let pre = linea.producto_presentacion_id
					? (p?.presentaciones ?? []).find(e => String(e.producto_presentacion_id) === String(linea.producto_presentacion_id))
					: null
				let hay = Number((linea.producto_presentacion_id ? pre : p)?.existencia ?? 0)

				if (hay <= 0) {
					return { texto: "Sin existencia", clase: "text-danger-emphasis" }
				}

				return {
					texto: `Existencia ${this.formatoCantidad(hay)}`,
					clase: hay >= Number(linea.cantidad) ? "text-success-emphasis" : "text-warning-emphasis"
				}
			},
			formatoMonto,
			formatoCantidad,
			formatoPorcentaje(valor) {
				return `${Number(valor).toLocaleString("en-US", { maximumFractionDigits: 2 })} %`
			}
		},
		computed: {
			// Búsqueda por texto y filtro de categoría del catálogo
			productosCatalogo() {
				let texto = normalizar(this.filtroCatalogo.trim())

				return this.productos.filter(p => {
					let coincide = !texto || [p.nombre, p.codigo, p.codigo_barra].some(v => normalizar(String(v ?? "")).includes(texto))
					let categoria = !this.categoriaCatalogo || String(p.categoria_id) === this.categoriaCatalogo

					return coincide && categoria
				})
			},
			resumen() {
				return this.lista.reduce((suma, e) => {
					let c = this.calculo(e)

					suma.subtotal  += c.subtotal
					suma.descuento += c.descuento
					suma.total     += c.total
					suma.ganancia  += c.ganancia

					return suma
				}, { subtotal: 0, descuento: 0, total: 0, ganancia: 0 })
			},
			// Coincidencias del buscador por nombre, código o código de barras (las de código primero)
			sugerencias() {
				let texto = normalizar(this.busqueda.trim())

				if (!texto) {
					return []
				}

				let porCodigo = []
				let porNombre = []

				for (let p of this.productos) {
					if ([p.codigo, p.codigo_barra].some(v => normalizar(String(v ?? "")).startsWith(texto))) {
						porCodigo.push(p)
					} else if (normalizar(String(p.nombre ?? "")).includes(texto)) {
						porNombre.push(p)
					}
				}

				return [...porCodigo, ...porNombre].slice(0, 8)
			},
			mostrarSugerencias() {
				return this.sugerenciasAbiertas && this.busqueda.trim() !== ""
			},
			// Líneas cuya cantidad supera la existencia de la sucursal
			faltantes() {
				return this.lista.filter(e => this.existencia(e).clase !== "text-success-emphasis").length
			},
			columnas() {
				return this.editable ? 6 : 5
			}
		},
		watch: {
			// Los totales se muestran en el panel lateral de la cotización
			resumen: {
				handler(valor) {
					this.$emit("resumen", { ...valor, faltantes: this.faltantes })
				},
				immediate: true
			},
			faltantes() {
				this.$emit("resumen", { ...this.resumen, faltantes: this.faltantes })
			},
			// Al guardar otra lista en el encabezado cambian los precios (las líneas ya agregadas conservan el suyo)
			listaPrecioId() {
				this.cargarPrecios()
			}
		}
	}
</script>
