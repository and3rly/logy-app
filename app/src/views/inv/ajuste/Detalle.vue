<template>
	<!-- Barra para agregar: escanear (Enter agrega al momento con cantidad 1) o abrir el catálogo -->
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
				title="Agregar el producto con ese código (cantidad 1)"
			>
				<span v-if="btnGuardar" class="spinner-border spinner-border-sm" aria-hidden="true" />
				<template v-else>
					<i class="fa-solid fa-plus me-1" aria-hidden="true" />Agregar
				</template>
			</button>
		</div>

		<button type="button" class="btn btn-suave-info" @click="abrirCatalogo">
			<i class="fa-solid fa-list me-1" aria-hidden="true" />Ver productos
		</button>
	</form>

	<!-- Catálogo: productos con su existencia en la sucursal; se pueden agregar varios sin cerrar -->
	<Teleport to="body">
		<div
			v-if="editable"
			ref="catalogo"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloCatalogoAjuste"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloCatalogoAjuste" class="modal-title fw-semibold mb-0">Productos</h5>
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

						<div v-if="salida" class="form-check form-switch mb-0">
							<input id="soloExistencia" v-model="soloConExistencia" class="form-check-input" type="checkbox" role="switch">
							<label class="form-check-label" for="soloExistencia">Solo con existencia</label>
						</div>
					</div>

					<div class="modal-body p-0">
						<div class="table-responsive">
							<table class="table table-sm mb-0">
								<thead>
									<tr>
										<th class="ps-3">Producto</th>
										<th>Categoría</th>
										<th>Presentación</th>
										<th class="text-end">Existencia</th>
										<th class="text-end">Costo</th>
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
														v-if="enAjuste(p)"
														class="badge border rounded-1 fw-semibold bg-success-subtle text-success-emphasis border-success-subtle ms-1"
													>En el {{ documento }}: {{ enAjuste(p) }}</span>
												</div>
											</div>
										</td>
										<td>
											<span
												v-if="p.ncategoria"
												class="badge rounded-1 fw-semibold etiqueta-color"
												:style="estiloEtiqueta(p.ecategoria)"
											>{{ p.ncategoria }}</span>
										</td>
										<td>
											<select
												v-if="presentacionesDe(p.id).length"
												:value="presentacionesCatalogo[p.id] ?? ''"
												class="form-select form-select-sm w-auto"
												:aria-label="`Presentación de ${p.nombre}`"
												@change="presentacionesCatalogo[p.id] = $event.target.value"
											>
												<option value="">{{ p.nunidad || 'Unidad' }}</option>
												<option v-for="pre in presentacionesDe(p.id)" :key="pre.producto_presentacion_id" :value="String(pre.producto_presentacion_id)">{{ pre.nombre }}</option>
											</select>
											<span v-else class="text-body-secondary">{{ p.nunidad }}</span>
										</td>
										<td class="text-end" :class="Number(articuloCatalogo(p).existencia) > 0 ? 'text-body' : 'text-body-secondary'">
											{{ formatoCantidad(articuloCatalogo(p).existencia) }}
										</td>
										<td class="text-end text-body-secondary">{{ formatoMonto(articuloCatalogo(p).costo) }}</td>
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
												:title="`Agregar al ${documento}`"
												:aria-label="`Agregar ${p.nombre} al ${documento}`"
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

	<!-- Tabla: lote y cantidad se editan aquí mismo y se guardan al cambiar -->
	<div class="table-responsive">
		<table class="table table-sm mb-0">
			<thead>
				<tr>
					<th class="ps-3">Producto</th>
					<th style="min-width: 12rem">{{ salida ? 'Lote' : 'Vencimiento' }}</th>
					<th class="text-end">Existencia</th>
					<th class="text-end" style="width: 8rem">Cantidad</th>
					<th class="text-end">Costo</th>
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
								<span class="font-monospace">{{ i.cproducto }}</span> ·
								<!-- Sin presentación la línea es en la unidad de medida -->
								<select
									v-if="editable && presentacionesDe(i.producto_id).length"
									:value="i.producto_presentacion_id ? String(i.producto_presentacion_id) : ''"
									class="form-select form-select-sm d-inline-block w-auto py-0"
									:aria-label="`Presentación de ${i.nproducto}`"
									@change="cambiarPresentacion(i, $event.target.value)"
								>
									<option value="">{{ i.cunidad }}</option>
									<option v-for="pre in presentacionesDe(i.producto_id)" :key="pre.producto_presentacion_id" :value="String(pre.producto_presentacion_id)">{{ pre.nombre }}</option>
								</select>
								<template v-else>{{ i.npresentacion || i.cunidad }}</template>
							</div>
						</div>
					</td>
					<td>
						<!-- Salida: de qué lote sale (automático = el que vence primero) -->
						<template v-if="salida">
							<select
								v-if="editable && lotesConFecha(i).length > 0"
								v-model="i.fecha_vence"
								class="form-select form-select-sm"
								:aria-label="`Lote de ${i.nproducto}`"
								@change="guardarFila(i)"
							>
								<option :value="null">Automático (vence primero)</option>
								<option v-for="l in lotesConFecha(i)" :key="l.fecha" :value="l.fecha">
									Vence {{ formatoFecha(l.fecha) }} · {{ formatoCantidad(l.cantidad) }}
								</option>
							</select>
							<span v-else-if="i.fecha_vence">Vence {{ formatoFecha(i.fecha_vence) }}</span>
							<span v-else class="text-body-secondary">Automático (vence primero)</span>
						</template>
						<!-- Entrada: vencimiento del lote que entra (solo productos con control de vencimiento) -->
						<template v-else>
							<input
								v-if="editable && Number(i.control_vence) === 1"
								v-model="i.fecha_vence"
								type="date"
								class="form-control form-control-sm"
								:aria-label="`Vencimiento de ${i.nproducto}`"
								@change="guardarFila(i)"
							>
							<span v-else-if="i.fecha_vence">{{ formatoFecha(i.fecha_vence) }}</span>
							<span v-else class="text-body-secondary">Sin vencimiento</span>
						</template>
					</td>
					<td class="text-end text-nowrap">{{ formatoCantidad(i.existencia) }}</td>
					<td class="text-end">
						<input
							v-if="editable"
							v-model="i.cantidad"
							type="number"
							class="form-control form-control-sm text-end"
							:class="{ 'is-invalid': sinExistencia(i) }"
							min="0.01"
							step="0.01"
							:aria-label="`Cantidad de ${i.nproducto}`"
							@change="guardarFila(i)"
							@keydown.enter.prevent="$event.target.blur()"
						>
						<template v-else>{{ formatoCantidad(i.cantidad) }}</template>
						<div v-if="editable && sinExistencia(i)" class="small text-danger text-nowrap">Solo hay {{ formatoCantidad(i.existencia) }}</div>
					</td>
					<td class="text-end text-body-secondary">{{ formatoMonto(i.costo_unitario) }}</td>
					<td class="text-end fw-semibold text-nowrap">{{ formatoMonto(subtotalDe(i)) }}</td>
					<td v-if="editable" class="text-end pe-3">
						<button type="button" class="btn btn-sm btn-link text-danger" title="Quitar" @click="quitarLinea(i)">
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
					<td :colspan="columnas" class="text-center text-body-secondary py-4">
						<div class="fs-4 mb-2 opacity-50"><i class="fa-solid fa-boxes-stacked" aria-hidden="true" /></div>
						Todavía no hay productos en este {{ documento }}
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
	import { Modal } from 'bootstrap'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { normalizar } from '@/utils/texto'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { formatoMonto, formatoCantidad } from '@/utils/numero'

	export default {
		// Lo usan los ajustes y los traslados (documento, urlDocumento, urlDetalle y campoDocumento)
		name: "DetalleAjuste",
		props: {
			documentoId: {
				type: String,
				required: true,
			},
			// Nombre en los textos ("En el ajuste", "Agregar al traslado")
			documento: {
				type: String,
				required: false,
				default: "ajuste",
			},
			// API del documento (get_detalle, get_lotes) y de sus líneas (guardar, quitar)
			urlDocumento: {
				type: String,
				required: false,
				default: "inv/ajuste",
			},
			urlDetalle: {
				type: String,
				required: false,
				default: "inv/ajuste_detalle",
			},
			campoDocumento: {
				type: String,
				required: false,
				default: "inventario_ajuste_id",
			},
			// ENTRADA o SALIDA (del tipo del ajuste; un traslado siempre es SALIDA)
			sentido: {
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
			editable: {
				type: Boolean,
				required: false,
				default: true,
			},
		},
		emits: ["resumen"],
		mixins: [Accion],
		data: () => ({
			busqueda: "",
			filtroCatalogo: "",
			categoriaCatalogo: null,
			soloConExistencia: true,
			cantidades: {},
			// Presentación elegida por producto en el catálogo ('' = unidad de medida)
			presentacionesCatalogo: {},
			// Lotes con existencia por producto y presentación: { llaveLote(): [{ fecha, cantidad }] }
			lotes: {}
		}),
		created() {
			this.url   = this.urlDetalle
			this._key  = "id"
			this.autoBuscar = false
			this._blqconfirm = true

			this.fbase[this.campoDocumento] = this.documentoId
			this.fbase.producto_id = null
			this.fbase.producto_presentacion_id = null
			this.fbase.cantidad    = 1
			this.fbase.fecha_vence = null

			this.cargarDetalle()
		},
		mounted() {
			if (this.$refs.catalogo) {
				this.modalCatalogo = new Modal(this.$refs.catalogo)
				this.$refs.catalogo.addEventListener("shown.bs.modal", () => this.$refs.filtroCatalogo?.focus())
			}
		},
		beforeUnmount() {
			this.modalCatalogo?.dispose()
		},
		methods: {
			cargarDetalle() {
				this.btnBuscar = true

				api
				.get(`/${this.urlDocumento}/get_detalle/${this.documentoId}`)
				.then(result => {
					this.lista = (result.data.det ?? []).map(this.normalizarLinea)
					this.$emit("resumen", this.resumen)

					if (this.salida && this.editable) {
						this.lista.forEach(e => this.cargarLotes(e.producto_id, e.producto_presentacion_id))
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnBuscar = false
				})
			},
			// Sin presentación, los lotes de la unidad de medida
			llaveLote(productoId, presentacionId) {
				return `${productoId}-${presentacionId || 0}`
			},
			cargarLotes(productoId, presentacionId = null, recargar = false) {
				let llave = this.llaveLote(productoId, presentacionId)

				if (this.lotes[llave] && !recargar) {
					return
				}

				// Marca la llave para no pedir dos veces los mismos lotes
				this.lotes[llave] = []

				api
				.get(`/${this.urlDocumento}/get_lotes/${productoId}`, {
					params: { presentacion: presentacionId || "" }
				})
				.then(result => {
					this.lotes[llave] = (result.data.lotes ?? []).map(l => ({
						fecha: l.fecha_vence ? String(l.fecha_vence).slice(0, 10) : null,
						cantidad: Number(l.cantidad)
					}))
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			// La fecha de vencimiento llega con hora; los campos necesitan AAAA-MM-DD
			normalizarLinea(linea) {
				linea.fecha_vence = linea.fecha_vence ? String(linea.fecha_vence).slice(0, 10) : null
				return linea
			},
			// Lotes con vencimiento de la línea; los que no vencen se toman solo en automático
			lotesConFecha(linea) {
				return (this.lotes[this.llaveLote(linea.producto_id, linea.producto_presentacion_id)] ?? []).filter(l => l.fecha)
			},
			sinExistencia(linea) {
				return this.salida && Number(linea.cantidad) > Number(linea.existencia)
			},
			// Enter o Agregar: solo código o código de barras exacto (lector)
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

				this.form.producto_id = String(p.id)
				this.form.producto_presentacion_id = null
				this.form.cantidad = 1
				this.agregar()
			},
			// Si el producto (en esa presentación y sin lote elegido) ya está en el ajuste se suma a su línea;
			// si no, se crea una nueva
			agregar(enfocar = true) {
				if (!this.form.producto_id || this.btnGuardar) {
					return Promise.resolve(false)
				}

				let existente = this.lista.find(e =>
					String(e.producto_id) === String(this.form.producto_id) &&
					String(e.producto_presentacion_id ?? "") === String(this.form.producto_presentacion_id ?? "") &&
					!e.fecha_vence
				)

				if (existente) {
					existente.cantidad = Number(existente.cantidad) + Number(this.form.cantidad || 1)

					return this.guardarFila(existente, false).then(exito => {
						if (exito) {
							this.$toast.success(`${existente.nproducto}: cantidad ${this.formatoCantidad(existente.cantidad)}`)
							this.nuevaLinea(enfocar)
						}

						return exito
					})
				}

				let productoId = String(this.form.producto_id)
				let presentacionId = this.form.producto_presentacion_id

				return this.guardar().then(exito => {
					if (exito) {
						let ultima = this.lista[this.lista.length - 1]
						if (ultima) {
							this.normalizarLinea(ultima)
						}

						if (this.salida) {
							this.cargarLotes(productoId, presentacionId)
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

				this.form.producto_id = String(p.id)
				this.form.producto_presentacion_id = this.presentacionesCatalogo[p.id] || null
				this.form.cantidad = cantidad

				this.agregar(false).then(exito => {
					if (exito) {
						this.cantidades[p.id] = ""
					}
				})
			},
			// Lo que ya está en el ajuste de un producto, por presentación: "2 UND · 1 Caja 12"
			enAjuste(p) {
				return this.lista
				.filter(e => String(e.producto_id) === String(p.id))
				.map(e => `${this.formatoCantidad(e.cantidad)} ${e.npresentacion || e.cunidad}`)
				.join(" · ")
			},
			// Presentaciones activas de un producto, con su existencia y costo
			presentacionesDe(productoId) {
				let p = this.productos.find(e => String(e.id) === String(productoId))
				return (p?.presentaciones ?? []).filter(e => Number(e.activo) === 1)
			},
			// Existencia y costo del producto en la presentación elegida en el catálogo
			articuloCatalogo(p) {
				let id = this.presentacionesCatalogo[p.id]
				return (id && this.presentacionesDe(p.id).find(e => String(e.producto_presentacion_id) === String(id))) || p
			},
			// Otra presentación: el lote elegido ya no aplica (vuelve a automático) y se guarda la fila
			cambiarPresentacion(linea, presentacionId) {
				linea.producto_presentacion_id = presentacionId || null

				if (this.salida) {
					linea.fecha_vence = null
				}

				this.guardarFila(linea).then(exito => {
					if (exito && this.salida) {
						this.cargarLotes(linea.producto_id, linea.producto_presentacion_id)
					}
				})
			},
			estiloEtiqueta,
			// Guarda los cambios de una fila (lote, vencimiento o cantidad); aviso = mostrar la notificación
			guardarFila(linea, aviso = true) {
				if (!(Number(linea.cantidad) > 0)) {
					this.$toast.error("La cantidad debe ser mayor a cero.")
					this.cargarDetalle()
					return Promise.resolve(false)
				}

				return api
				.post(`/${this.url}/guardar/${linea.id}`, {
					[this.campoDocumento]: this.documentoId,
					producto_id: linea.producto_id,
					producto_presentacion_id: linea.producto_presentacion_id,
					cantidad: linea.cantidad,
					fecha_vence: linea.fecha_vence || null
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
				.post(`/${this.url}/quitar`, { id: obj.id })
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
			subtotalDe(linea) {
				return Math.round((Number(linea.cantidad) || 0) * (Number(linea.costo_unitario) || 0) * 100) / 100
			},
			formatoMonto,
			formatoCantidad,
			formatoFecha(fecha) {
				let [a, m, d] = String(fecha).slice(0, 10).split("-")
				return `${d}/${m}/${a}`
			}
		},
		computed: {
			salida() {
				return this.sentido === "SALIDA"
			},
			columnas() {
				return this.editable ? 7 : 6
			},
			// Búsqueda por texto, categoría y (en salidas) solo productos con existencia
			productosCatalogo() {
				let texto = normalizar(this.filtroCatalogo.trim())

				return this.productos.filter(p => {
					let coincide = !texto || [p.nombre, p.codigo, p.codigo_barra].some(v => normalizar(String(v ?? "")).includes(texto))
					let categoria = !this.categoriaCatalogo || String(p.categoria_id) === this.categoriaCatalogo
					let existencia = !this.salida || !this.soloConExistencia ||
						Number(p.existencia) > 0 ||
						(p.presentaciones ?? []).some(e => Number(e.activo) === 1 && Number(e.existencia) > 0)

					return coincide && categoria && existencia
				})
			},
			// Para el panel de resumen del ajuste
			resumen() {
				return {
					valor: this.lista.reduce((suma, e) => suma + this.subtotalDe(e), 0),
					lineas: this.lista.length,
					unidades: this.lista.reduce((suma, e) => suma + (Number(e.cantidad) || 0), 0),
					sinExistencia: this.lista.filter(e => this.sinExistencia(e)).length
				}
			}
		}
	}
</script>
