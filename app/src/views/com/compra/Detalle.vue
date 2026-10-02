<template>
	<!-- Barra para agregar en una fila: escanear (Enter agrega al momento) o buscar y agregar; siempre cantidad 1 (se ajusta en la tabla) -->
	<form
		v-if="editable"
		class="d-flex flex-wrap gap-2 px-3 py-2 border-bottom bg-body-tertiary"
		autocomplete="off"
		@submit.prevent="agregarPorCodigo"
	>
		<div class="input-group flex-grow-1 w-auto">
			<span
				class="input-group-text"
				title="Solo por código o código de barras exacto: se agrega con cantidad 1; si ya está en la lista, suma 1. Para buscar por nombre use Ver productos."
			>
				<i class="fa-solid fa-barcode" aria-hidden="true" />
			</span>
			<input
				id="inputProducto"
				ref="producto"
				v-model="busqueda"
				type="search"
				class="form-control"
				placeholder="Escanee el código de barras o escriba el código del producto"
				aria-label="Código o código de barras del producto"
				@keydown.enter.prevent="agregarPorCodigo"
			>
			<button
				type="submit"
				class="btn btn-primary"
				:disabled="btnGuardar || !busqueda.trim()"
				title="Agregar el producto con ese código (cantidad 1, último costo)"
			>
				<span v-if="btnGuardar" class="spinner-border spinner-border-sm" aria-hidden="true" />
				<template v-else>
					<i class="fa-solid fa-plus me-1" aria-hidden="true" />Agregar
				</template>
			</button>
		</div>

		<!-- Un solo botón sólido (Agregar, color del sistema); los secundarios suaves: celeste = consultar, verde = crear -->
		<button type="button" class="btn btn-suave-info" @click="abrirCatalogo">
			<i class="fa-solid fa-list me-1" aria-hidden="true" />Ver productos
		</button>
		<button type="button" class="btn btn-suave-success" title="Crear un producto nuevo y agregarlo a la compra" @click="abrirNuevoProducto">
			<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nuevo producto
		</button>
	</form>

	<!-- Nuevo producto: el mismo formulario del mantenimiento de productos -->
	<Teleport to="body">
		<div
			v-if="editable"
			ref="nuevoProducto"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloNuevoProductoCompra"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloNuevoProductoCompra" class="modal-title fw-semibold mb-0">Nuevo producto</h5>
							<div class="small text-body-secondary">Al guardarlo se agrega a esta compra con cantidad 1</div>
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

	<!-- Catálogo: todos los productos disponibles para agregar varios sin cerrar (mismo diseño de tabla que el resto) -->
	<Teleport to="body">
		<div
			v-if="editable"
			ref="catalogo"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloCatalogoCompra"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloCatalogoCompra" class="modal-title fw-semibold mb-0">Productos disponibles</h5>
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
										<th class="text-end">Último costo</th>
										<th class="text-end" style="width: 7rem">Cantidad</th>
										<th class="text-end pe-3">Acciones</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="p in productosCatalogo" :key="p.id" class="align-middle">
										<td class="ps-3">
											<div class="lh-sm">
												<div class="fw-semibold text-body">{{ p.nombre }}</div>
												<div class="small text-body-secondary">
													<span class="font-monospace">{{ p.codigo }}</span>
													<span
														v-if="enCompra(p)"
														class="badge border rounded-1 fw-semibold bg-success-subtle text-success-emphasis border-success-subtle ms-1"
													>En la compra: {{ enCompra(p) }}</span>
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
												v-if="presentacionesDe(p.id).length"
												:value="presentacionesCatalogo[p.id] ?? ''"
												class="form-select form-select-sm w-auto"
												:aria-label="`Presentación de ${p.nombre}`"
												@change="presentacionesCatalogo[p.id] = $event.target.value"
											>
												<option value="">{{ nombreDe(unidades, p.unidad_medida_id) || 'Unidad' }}</option>
												<option v-for="pre in presentacionesDe(p.id)" :key="pre.id" :value="String(pre.id)">{{ pre.nombre }}</option>
											</select>
											<span v-else class="text-body-secondary">{{ nombreDe(unidades, p.unidad_medida_id) }}</span>
										</td>
										<td class="text-end text-body-secondary">{{ formatoMonto(costoCatalogo(p)) }}</td>
										<td class="text-end">
											<input
												v-model="cantidades[p.id]"
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
												title="Agregar a la compra"
												:aria-label="`Agregar ${p.nombre} a la compra`"
												:disabled="btnGuardar"
												@click="agregarDesdeCatalogo(p)"
											>
												<i class="fa-solid fa-plus" aria-hidden="true" />
											</button>
										</td>
									</tr>

									<tr v-if="productosCatalogo.length === 0">
										<td colspan="7" class="text-center text-body-secondary">Sin resultados para la búsqueda</td>
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

	<!-- Tabla: cantidad y costo se editan aquí mismo y se guardan al salir del campo -->
	<div class="table-responsive">
		<table class="table table-sm mb-0">
			<thead>
				<tr>
					<th class="ps-3">Producto</th>
					<th>Unidad</th>
					<th>Presentación</th>
					<th class="text-end" style="width: 8rem">Cantidad</th>
					<th class="text-end" style="width: 9rem">Costo</th>
					<th class="text-end">Subtotal</th>
					<th v-if="editable" class="text-end pe-3" style="width: 1%"></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="i in lista" :key="i.id" class="align-middle">
					<td class="ps-3">
						<div class="lh-sm">
							<div class="fw-semibold text-body">{{ i.nproducto }}</div>
							<div class="small text-body-secondary">
								<span class="font-monospace">{{ i.cproducto }}</span>
								<template v-if="i.fecha_vence"> · Vence {{ formatoFecha(i.fecha_vence) }}</template>
							</div>
						</div>
					</td>
					<td>{{ i.cunidad }}</td>
					<td>
						<!-- Sin presentación la línea es en la unidad de medida -->
						<select
							v-if="editable && presentacionesDe(i.producto_id).length"
							:value="i.producto_presentacion_id ? String(i.producto_presentacion_id) : ''"
							class="form-select form-select-sm w-auto"
							:aria-label="`Presentación de ${i.nproducto}`"
							@change="cambiarPresentacion(i, $event.target.value)"
						>
							<option value="">Sin presentación</option>
							<option v-for="pre in presentacionesDe(i.producto_id)" :key="pre.id" :value="String(pre.id)">{{ pre.nombre }}</option>
						</select>
						<template v-else-if="i.npresentacion">{{ i.npresentacion }}</template>
						<span v-else class="text-body-secondary">—</span>
					</td>
					<td class="text-end">
						<input
							v-if="editable"
							v-model="i.cantidad"
							type="number"
							class="form-control form-control-sm text-end"
							min="0.01"
							step="0.01"
							:aria-label="`Cantidad de ${i.nproducto}`"
							@change="guardarFila(i)"
							@keydown.enter.prevent="$event.target.blur()"
						>
						<template v-else>{{ formatoCantidad(i.cantidad) }}</template>
					</td>
					<td class="text-end">
						<input
							v-if="editable"
							v-model="i.precio_costo"
							type="number"
							class="form-control form-control-sm text-end"
							min="0"
							step="0.01"
							:aria-label="`Costo de ${i.nproducto}`"
							@change="guardarFila(i)"
							@keydown.enter.prevent="$event.target.blur()"
						>
						<template v-else>{{ formatoMonto(i.precio_costo) }}</template>
					</td>
					<td class="text-end fw-semibold text-nowrap">{{ formatoMonto(subtotalDe(i)) }}</td>
					<td v-if="editable" class="text-end pe-3">
						<button type="button" class="btn btn-sm btn-link text-danger" title="Quitar" @click="quitarLinea(i)">
							<i class="fa-regular fa-trash-can" aria-hidden="true" />
						</button>
					</td>
				</tr>

				<tr v-if="btnBuscar">
					<td :colspan="editable ? 7 : 6" class="text-center text-body-secondary">
						<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
					</td>
				</tr>
				<tr v-else-if="lista.length === 0">
					<td :colspan="editable ? 7 : 6" class="text-center text-body-secondary py-4">
						<div class="fs-4 mb-2 opacity-50"><i class="fa-solid fa-cart-plus" aria-hidden="true" /></div>
						Todavía no hay productos en esta compra
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
	import { formatoMonto, formatoCantidad } from '@/utils/numero'

	export default {
		name: "DetalleCompra",
		props: {
			compraId: {
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
			// Presentaciones activas de los productos: { id, nombre, factor, producto_id }
			presentaciones: {
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
		},
		emits: ["resumen", "producto-creado"],
		components: {
			FormProducto
		},
		mixins: [Accion],
		data: () => ({
			busqueda: "",
			filtroCatalogo: "",
			categoriaCatalogo: null,
			cantidades: {},
			presentacionesCatalogo: {},
			nuevoProductoAbierto: false,
			aperturaProducto: 0
		}),
		created() {
			this.url   = "com/detalle"
			this._key  = "id"
			this.autoBuscar = false
			this._blqconfirm = true

			this.fbase.compra_id    = this.compraId
			this.fbase.producto_id  = null
			this.fbase.producto_presentacion_id = null
			this.fbase.cantidad     = 1
			this.fbase.precio_costo = null
			this.fbase.fecha_vence  = null

			this.cargarDetalle()
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
		},
		methods: {
			cargarDetalle() {
				this.btnBuscar = true

				api
				.get(`/com/compra/get_detalle/${this.compraId}`)
				.then(result => {
					this.lista = (result.data.det ?? []).map(this.normalizarLinea)
					this.$emit("resumen", this.resumen)
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnBuscar = false
				})
			},
			// La fecha de vencimiento llega con hora; el campo de fecha necesita AAAA-MM-DD
			normalizarLinea(linea) {
				linea.fecha_vence = linea.fecha_vence ? String(linea.fecha_vence).slice(0, 10) : null
				return linea
			},
			seleccionar(p, presentacionId = null) {
				this.form.producto_id  = String(p.id)
				this.form.producto_presentacion_id = presentacionId || null
				// Último costo del producto (por el factor de la presentación); se ajusta después en la tabla
				this.form.precio_costo = (Number(p.costo ?? 0) * this.factorDe(presentacionId)).toFixed(2)
				this.form.fecha_vence  = null
			},
			// Enter o Agregar: solo código o código de barras exacto (lector); se agrega al momento con cantidad 1
			agregarPorCodigo() {
				let texto = this.busqueda.trim().toLowerCase()

				if (!texto) {
					return
				}

				let p = this.productos.find(e =>
					String(e.codigo).toLowerCase() === texto ||
					String(e.codigo_barra ?? "").toLowerCase() === texto
				)

				if (!p) {
					this.$toast.error(`No hay un producto con el código "${this.busqueda.trim()}".`)
					this.$refs.producto?.select()
					return
				}

				this.seleccionar(p)
				this.agregar()
			},
			// Si el producto (en esa presentación) ya está en la compra se suma a su línea; si no, se crea una nueva
			agregar(enfocar = true) {
				if (!this.form.producto_id || this.btnGuardar) {
					return Promise.resolve(false)
				}

				let existente = this.lista.find(e =>
					String(e.producto_id) === String(this.form.producto_id) &&
					String(e.producto_presentacion_id ?? "") === String(this.form.producto_presentacion_id ?? "")
				)

				if (existente) {
					existente.cantidad = Number(existente.cantidad) + Number(this.form.cantidad || 1)

					// Aviso propio con la nueva cantidad
					return this.guardarFila(existente, false).then(exito => {
						if (exito) {
							this.$toast.success(`${existente.nproducto}: cantidad ${this.formatoCantidad(existente.cantidad)}`)
							this.nuevaLinea(enfocar)
						}

						return exito
					})
				}

				return this.guardar().then(exito => {
					if (exito) {
						let ultima = this.lista[this.lista.length - 1]
						if (ultima) {
							this.normalizarLinea(ultima)
						}

						this.nuevaLinea(enfocar)
						this.$emit("resumen", this.resumen)
					}

					return exito
				})
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
				let cantidad = Number(this.cantidades[p.id] || 1)

				if (!(cantidad > 0)) {
					this.$toast.error("La cantidad debe ser mayor a cero.")
					return
				}

				this.seleccionar(p, this.presentacionesCatalogo[p.id])
				this.form.cantidad = cantidad

				this.agregar(false).then(exito => {
					if (exito) {
						this.cantidades[p.id] = ""
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
			// El producto guardado pasa al catálogo y se agrega a la compra con cantidad 1
			productoCreado(producto) {
				this.$emit("producto-creado", producto)
				this.cerrarNuevoProducto()

				this.seleccionar(producto)
				this.form.cantidad = 1
				this.agregar()
			},
			// Lo que ya está en la compra de un producto, por presentación: "2 UND · 1 Caja 12"
			enCompra(p) {
				return this.lista
				.filter(e => String(e.producto_id) === String(p.id))
				.map(e => `${this.formatoCantidad(e.cantidad)} ${e.npresentacion || e.cunidad}`)
				.join(" · ")
			},
			presentacionesDe(productoId) {
				return this.presentaciones.filter(e => String(e.producto_id) === String(productoId))
			},
			factorDe(presentacionId) {
				let tmp = this.presentaciones.find(e => String(e.id) === String(presentacionId))
				return tmp ? Number(tmp.factor) : 1
			},
			// Último costo del producto en la presentación elegida en el catálogo
			costoCatalogo(p) {
				return Number(p.costo ?? 0) * this.factorDe(this.presentacionesCatalogo[p.id])
			},
			// Otra presentación: el costo se lleva a la nueva (costo por unidad por su factor) y se guarda la fila
			cambiarPresentacion(linea, presentacionId) {
				let unitario = Number(linea.precio_costo || 0) / (Number(linea.factor) || 1)
				let factor = this.factorDe(presentacionId || null)

				linea.producto_presentacion_id = presentacionId || null
				linea.precio_costo = (Math.round(unitario * factor * 100) / 100).toFixed(2)
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
			// Guarda los cambios de una fila de la tabla (cantidad o costo); aviso = mostrar la notificación
			guardarFila(linea, aviso = true) {
				if (!(Number(linea.cantidad) > 0) || !(Number(linea.precio_costo) >= 0) || linea.precio_costo === "") {
					this.$toast.error("La cantidad debe ser mayor a cero y el costo no puede quedar vacío.")
					this.cargarDetalle()
					return Promise.resolve(false)
				}

				return api
				.post(`/${this.url}/guardar/${linea.id}`, {
					compra_id: this.compraId,
					producto_id: linea.producto_id,
					producto_presentacion_id: linea.producto_presentacion_id,
					cantidad: linea.cantidad,
					precio_costo: linea.precio_costo,
					fecha_vence: linea.fecha_vence
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(linea, this.normalizarLinea(res.linea))
						this.$emit("resumen", this.resumen)

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
			nuevaLinea(enfocar = true) {
				this.limpiar()
				this.busqueda = ""

				if (enfocar) {
					this.$nextTick(() => this.$refs.producto?.focus())
				}
			},
			quitarLinea(obj) {
				api
				.post("/com/detalle/anular", { id: obj.id })
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.lista = this.lista.filter(e => e.id !== obj.id)
						this.$toast.success(res.mensaje)
						this.$emit("resumen", this.resumen)
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			// Mientras se edita, el subtotal se calcula en pantalla
			subtotalDe(linea) {
				return Math.round((Number(linea.cantidad) || 0) * (Number(linea.precio_costo) || 0) * 100) / 100
			},
			formatoMonto,
			formatoCantidad,
			formatoFecha(fecha) {
				let [a, m, d] = String(fecha).slice(0, 10).split("-")
				return `${d}/${m}/${a}`
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
			// Para el panel de resumen de la compra
			resumen() {
				return {
					total: this.lista.reduce((suma, e) => suma + this.subtotalDe(e), 0),
					lineas: this.lista.length,
					unidades: this.lista.reduce((suma, e) => suma + (Number(e.cantidad) || 0), 0)
				}
			}
		}
	}
</script>
