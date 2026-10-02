<template>
	<!-- ============================ Punto de venta ============================ -->
	<template v-if="!verLista">
		<PageHeader>
			<span
				v-if="sucursal"
				class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
				title="Las ventas y la existencia son de esta sucursal"
			>
				<i class="fa-solid fa-store text-primary" aria-hidden="true" />
				<span class="fw-semibold text-body">{{ sucursal }}</span>
			</span>
			<button type="button" class="btn btn-suave-info" @click="verLista = true">
				<i class="fa-solid fa-list me-1" aria-hidden="true" />Ventas del día
			</button>
		</PageHeader>

		<!-- Última venta registrada: imprimir el ticket sin salir del punto de venta -->
		<div
			v-if="ultima"
			class="alert alert-success d-flex flex-wrap align-items-center gap-2 py-2"
			role="status"
		>
			<i class="fa-solid fa-circle-check" aria-hidden="true" />
			<span>
				Venta <span class="fw-semibold font-monospace">{{ ultima.correlativo }}</span>
				registrada por {{ ultima.smoneda }} {{ formatoMonto(ultima.total_precio) }}
			</span>
			<span v-if="vuelto > 0" class="fw-semibold">· Vuelto {{ ultima.smoneda }} {{ formatoMonto(vuelto) }}</span>
			<button type="button" class="btn btn-sm btn-primary ms-auto" :disabled="imprimiendo" @click="imprimir(ultima)">
				<span v-if="imprimiendo" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
				<i v-else class="fa-solid fa-print me-1" aria-hidden="true" />Imprimir ticket
			</button>
			<button type="button" class="btn-close" aria-label="Cerrar" @click="ultima = null" />
		</div>

		<div class="row g-3 align-items-start">
			<!-- Productos de la sucursal -->
			<div class="col-12 col-lg-7 col-xxl-8">
				<card>
					<card-body class="p-0">
						<form class="p-3 border-bottom" autocomplete="off" @submit.prevent="agregarPorCodigo">
							<div class="input-group">
								<span class="input-group-text bg-body" title="Escanee o escriba el código y pulse Enter para agregarlo">
									<i class="fa-solid fa-barcode text-primary" aria-hidden="true" />
								</span>
								<input
									ref="buscador"
									v-model="termino"
									type="search"
									class="form-control"
									placeholder="Escanee o busque por código, código de barras o nombre"
									aria-label="Buscar producto"
								>
								<span class="input-group-text bg-body small text-body-secondary" aria-live="polite">
									{{ productosFiltrados.length }} {{ productosFiltrados.length === 1 ? 'producto' : 'productos' }}
								</span>
							</div>

							<!-- Categorías con productos: un toque filtra; el punto lleva el color de la categoría -->
							<div v-if="categoriasConProductos.length > 1" class="d-flex flex-wrap gap-2 mt-3" role="group" aria-label="Filtrar por categoría">
								<button
									type="button"
									class="filtro-pv"
									:class="{ activo: categoria === null }"
									:aria-pressed="categoria === null"
									@click="categoria = null"
								>
									<i class="fa-solid fa-table-cells-large" aria-hidden="true" />Todas
									<span class="filtro-pv-cuenta">{{ articulos.length }}</span>
								</button>
								<button
									v-for="c in categoriasConProductos"
									:key="c.id"
									type="button"
									class="filtro-pv"
									:class="{ activo: categoria === String(c.id) }"
									:style="estiloEtiqueta(c.etiqueta)"
									:aria-pressed="categoria === String(c.id)"
									@click="categoria = categoria === String(c.id) ? null : String(c.id)"
								>
									<span class="filtro-pv-punto" aria-hidden="true" />{{ c.nombre }}
									<span class="filtro-pv-cuenta">{{ c.total }}</span>
								</button>
							</div>
						</form>

						<div class="p-3 overflow-auto bg-body-tertiary rounded-bottom" style="max-height: calc(100vh - 19rem)">
							<div v-if="cargando" class="text-center text-body-secondary py-5">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando productos...
							</div>
							<div v-else-if="productosFiltrados.length === 0" class="text-center text-body-secondary py-5">
								<div class="fs-2 mb-2 opacity-50"><i class="fa-solid fa-magnifying-glass" aria-hidden="true" /></div>
								{{ termino || categoria ? 'Sin productos para la búsqueda' : 'No hay productos activos' }}
							</div>

							<div v-else class="row row-cols-2 row-cols-md-3 row-cols-xxl-4 g-3">
								<div v-for="p in productosFiltrados" :key="llave(p)" class="col position-relative">
									<!-- Abrir la presentación en unidades sueltas (fuera de la tarjeta: no se anidan botones) -->
									<button
										v-if="p.producto_presentacion_id && disponible(p) >= 1"
										type="button"
										class="btn btn-sm btn-light border shadow-sm position-absolute top-0 start-0 mt-2 ms-3 z-1"
										:title="`Abrir ${p.npresentacion} en ${p.nunidad}`"
										@click="abrirPresentacion(p)"
									>
										<i class="fa-solid fa-box-open me-1" aria-hidden="true" />Abrir
									</button>
									<button
										type="button"
										class="producto-pv card h-100 w-100 p-0 text-start overflow-hidden"
										:class="{ 'producto-pv--activo': enTicket(p) > 0 }"
										:disabled="disponible(p) <= 0"
										:title="disponible(p) <= 0 ? 'Sin existencia en esta sucursal' : `Agregar ${p.nombre}`"
										@click="agregar(p)"
									>
										<!-- Foto; sin foto o si no carga, un icono -->
										<div class="position-relative w-100 border-bottom">
											<div class="ratio ratio-4x3 bg-body">
												<img
													v-if="p.foto && !fotoFallida[p.producto_id]"
													:src="urlFoto(p.foto)"
													:alt="p.nombre"
													class="object-fit-contain p-2"
													loading="lazy"
													@error="fotoFallida[p.producto_id] = true"
												>
												<div v-else class="d-flex align-items-center justify-content-center bg-body-tertiary text-body-secondary">
													<i class="fa-solid fa-box-open fs-1 opacity-50" aria-hidden="true" />
												</div>
											</div>

											<span
												v-if="enTicket(p) > 0"
												class="position-absolute top-0 end-0 m-2 badge rounded-pill bg-primary shadow-sm"
											>
												<i class="fa-solid fa-cart-shopping me-1" aria-hidden="true" />{{ formatoCantidad(enTicket(p)) }}
											</span>
											<span
												v-else-if="Number(p.existencia) <= 0"
												class="position-absolute top-0 end-0 m-2 badge rounded-pill text-bg-danger"
											>Agotado</span>
										</div>

										<div class="card-body p-2 d-flex flex-column w-100">
											<div class="d-flex align-items-center gap-1 mb-1 min-w-0">
												<span
													v-if="p.ncategoria"
													class="badge rounded-1 fw-semibold etiqueta-color text-truncate"
													:style="estiloEtiqueta(p.ecategoria)"
												>{{ p.ncategoria }}</span>
												<span class="small text-body-secondary font-monospace text-truncate ms-auto">{{ p.codigo }}</span>
											</div>

											<div class="producto-pv-nombre fw-semibold text-body lh-sm mb-2">{{ p.nombre }}</div>
											<div v-if="p.npresentacion" class="mb-2">
												<span class="badge rounded-1 border bg-primary-subtle text-primary-emphasis border-primary-subtle">
													<i class="fa-solid fa-boxes-stacked me-1" aria-hidden="true" />{{ p.npresentacion }}
												</span>
											</div>

											<div class="d-flex justify-content-between align-items-end gap-1 mt-auto">
												<span class="fs-5 fw-bold text-body text-nowrap lh-1">{{ simbolo }} {{ formatoMonto(p.precio) }}</span>
												<span class="small text-nowrap" :class="claseExistencia(p)">
													<i class="fa-solid fa-circle me-1" style="font-size: .5rem; vertical-align: middle" aria-hidden="true" />{{ formatoCantidad(Math.max(disponible(p), 0)) }} {{ p.npresentacion || p.nunidad }}
												</span>
											</div>
										</div>
									</button>
								</div>
							</div>
						</div>
					</card-body>
				</card>
			</div>

			<!-- Ticket: acompaña al desplazarse -->
			<div class="col-12 col-lg-5 col-xxl-4 position-sticky" style="top: 5rem">
				<card>
					<card-header>
						<span>Venta actual</span>
						<span class="ms-auto small fw-normal text-body-secondary">{{ unidades }} {{ unidades === 1 ? 'producto' : 'productos' }}</span>
					</card-header>
					<card-body>
						<div class="row g-2 mb-3">
							<div class="col-12">
								<label for="ventaCliente" class="form-label small mb-1">Cliente</label>
								<BuscarCliente
									id="ventaCliente"
									v-model="cliente"
									:departamentos="catalogo.departamentos"
									:municipios="catalogo.municipios"
								/>
							</div>
							<div class="col-6">
								<label for="ventaSerie" class="form-label small mb-1">Serie *</label>
								<select id="ventaSerie" v-model="form.venta_serie_id" class="form-select">
									<option v-for="s in catalogo.series" :key="s.id" :value="String(s.id)">{{ s.codigo }} · {{ s.nombre }}</option>
								</select>
							</div>
							<div class="col-6">
								<label for="ventaFormaPago" class="form-label small mb-1">Forma de pago *</label>
								<select id="ventaFormaPago" v-model="form.forma_pago_id" class="form-select">
									<option v-for="f in catalogo.formas_pago" :key="f.id" :value="String(f.id)">{{ f.nombre }}</option>
								</select>
							</div>
						</div>

						<!-- Líneas del ticket -->
						<div class="border rounded-3 mb-3">
							<div v-if="ticket.length === 0" class="text-center text-body-secondary py-5 px-3 small">
								<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-cart-shopping" aria-hidden="true" /></div>
								Toque un producto o escanee su código para agregarlo.
							</div>

							<ul v-else class="list-group list-group-flush rounded-3 overflow-auto" style="max-height: 20rem">
								<li v-for="l in ticket" :key="l.llave" class="list-group-item px-2 py-2">
									<div class="d-flex align-items-start gap-2">
										<div class="flex-grow-1 min-w-0">
											<div class="fw-semibold text-body lh-sm">{{ l.nombre }}</div>
											<div v-if="l.npresentacion" class="small fw-semibold text-primary-emphasis">{{ l.npresentacion }}</div>
											<!-- El vendedor puede cambiar el precio, nunca bajo el costo -->
											<div class="d-flex align-items-center gap-1 small text-body-secondary mt-1">
												<div class="input-group input-group-sm w-auto">
													<span class="input-group-text">{{ simbolo }}</span>
													<input
														:value="l.precio.toFixed(2)"
														type="number"
														min="0"
														step="0.01"
														class="form-control text-end"
														:class="{ 'border-warning': l.precio !== l.precio_lista }"
														style="width: 6.5rem"
														:aria-label="`Precio de ${l.nombre}`"
														@change="cambiarPrecio(l, $event)"
													>
												</div>
												c/u
												<button
													v-if="l.precio !== l.precio_lista"
													type="button"
													class="btn btn-sm btn-link p-0 ms-1"
													:title="`Volver al precio de venta (${simbolo} ${formatoMonto(l.precio_lista)})`"
													:aria-label="`Volver al precio de venta de ${l.nombre}`"
													@click="l.precio = l.precio_lista"
												>
													<i class="fa-solid fa-rotate-left" aria-hidden="true" />
												</button>
											</div>
										</div>
										<div class="fw-semibold text-body text-nowrap">{{ simbolo }} {{ formatoMonto(l.precio * l.cantidad) }}</div>
									</div>
									<div class="d-flex align-items-center gap-2 mt-1">
										<div class="input-group input-group-sm w-auto">
											<button type="button" class="btn btn-outline-secondary" aria-label="Quitar uno" @click="cambiarCantidad(l, l.cantidad - 1)">
												<i class="fa-solid fa-minus" aria-hidden="true" />
											</button>
											<input
												:value="l.cantidad"
												type="number"
												min="0"
												step="1"
												class="form-control text-center"
												style="width: 4.5rem"
												:aria-label="`Cantidad de ${l.nombre}`"
												@change="cambiarCantidad(l, $event.target.value)"
											>
											<button type="button" class="btn btn-outline-secondary" aria-label="Agregar uno" @click="cambiarCantidad(l, l.cantidad + 1)">
												<i class="fa-solid fa-plus" aria-hidden="true" />
											</button>
										</div>
										<span class="small text-body-secondary">de {{ formatoCantidad(l.existencia) }}</span>
										<button type="button" class="btn btn-sm btn-link text-danger ms-auto" :aria-label="`Quitar ${l.nombre}`" @click="quitar(l)">
											<i class="fa-solid fa-trash-can" aria-hidden="true" />
										</button>
									</div>
								</li>
							</ul>
						</div>

						<!-- Total -->
						<div class="rounded-3 border bg-body-tertiary p-3 mb-3">
							<div class="d-flex justify-content-between align-items-center small text-body-secondary mb-1">
								<span>Total a cobrar</span>
								<span class="font-monospace">{{ moneda?.codigo ?? '' }}</span>
							</div>
							<div class="fs-2 fw-bold text-body lh-sm text-nowrap">{{ simbolo }} {{ formatoMonto(total) }}</div>
						</div>

						<div class="d-flex gap-2">
							<button
								type="button"
								class="btn btn-outline-secondary"
								title="Vaciar la venta actual"
								:disabled="ticket.length === 0 || btnGuardar"
								@click="vaciar"
							>
								<i class="fa-solid fa-broom" aria-hidden="true" />
							</button>
							<button
								type="button"
								class="btn btn-primary flex-grow-1"
								:disabled="ticket.length === 0 || btnGuardar"
								@click="cobrar"
							>
								<span v-if="btnGuardar" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
								<i v-else class="fa-solid fa-cash-register me-1" aria-hidden="true" />Cobrar (F9)
							</button>
						</div>
					</card-body>
				</card>
			</div>
		</div>

		<Cobro
			ref="cobro"
			:total="total"
			:simbolo="simbolo"
			:credito="esCredito"
			:efectivo="esEfectivo"
			:cliente="cliente"
			:forma-pago="formaPago?.nombre ?? ''"
			:guardando="btnGuardar"
			@confirmar="registrar"
		/>

		<AbrirPresentacion
			ref="abrirPresentacion"
			:productos="productos"
			@abierta="getProductos"
		/>
	</template>

	<!-- ============================ Ventas del día ============================ -->
	<Lista
		v-else
		:estados="catalogo.estados"
		:fecha="fecha"
		@volver="volverPuntoVenta"
	/>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Cobro from './Cobro.vue'
	import Lista from './Lista.vue'
	import BuscarCliente from './BuscarCliente.vue'
	import AbrirPresentacion from './AbrirPresentacion.vue'
	import api, { mensajeError } from '@/services/api'
	import { imprimirPdf } from '@/utils/imprimir'
	import { normalizar } from '@/utils/texto'
	import { useSesionStore } from '@/stores/sesion'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "Venta",
		data: () => ({
			verLista: false,
			cargando: true,
			btnGuardar: false,
			imprimiendo: false,
			fecha: null,
			catalogo: {
				series: [],
				formas_pago: [],
				monedas: [],
				estados: [],
				categorias: [],
				departamentos: [],
				municipios: []
			},
			productos: [],
			// null = consumidor final
			cliente: null,
			termino: "",
			categoria: null,
			ticket: [],
			form: {
				venta_serie_id: null,
				forma_pago_id: null,
				moneda_id: null
			},
			ultima: null,
			vuelto: 0,
			// producto_id de las fotos que no cargaron: se muestra el icono
			fotoFallida: {}
		}),
		created() {
			this.getDatos()
		},
		mounted() {
			window.addEventListener("keydown", this.atajos)
			this.$refs.buscador?.focus()
		},
		beforeUnmount() {
			window.removeEventListener("keydown", this.atajos)
		},
		methods: {
			getDatos() {
				this.cargando = true

				api
				.get("/ven/venta/get_datos")
				.then(result => {
					let res = result.data

					this.catalogo  = { ...this.catalogo, ...(res.cat ?? {}) }
					this.productos = res.cat?.productos ?? []
					this.fecha     = res.fecha ?? null

					// Primera serie y contado por defecto
					this.form.moneda_id      = res.moneda_id ? String(res.moneda_id) : null
					this.form.venta_serie_id = this.catalogo.series[0] ? String(this.catalogo.series[0].id) : null
					this.form.forma_pago_id  = this.formaContado()
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.cargando = false
				})
			},
			// Existencia actual (después de una venta o de un rechazo por existencia)
			getProductos() {
				api
				.get("/ven/venta/get_productos")
				.then(result => {
					this.actualizarProductos(result.data.lista ?? [])
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			actualizarProductos(lista) {
				this.productos = lista

				// La existencia del ticket se ajusta a la nueva
				for (let l of this.ticket) {
					let p = this.articulos.find(e => this.llave(e) === l.llave)
					l.existencia = p ? Number(p.existencia) : 0
				}
			},
			// Efectivo por defecto; si no existe, la primera que no sea crédito
			formaContado() {
				let f = this.catalogo.formas_pago.find(e => normalizar(e.nombre).includes("efectivo")) ??
					this.catalogo.formas_pago.find(e => !normalizar(e.nombre).includes("credito")) ??
					this.catalogo.formas_pago[0]

				return f ? String(f.id) : null
			},
			// Sin presentación el artículo es la unidad de medida (0)
			llave(p) {
				return `${p.producto_id}-${p.unidad_medida_id}-${p.producto_presentacion_id ?? 0}`
			},
			enTicket(p) {
				let l = this.ticket.find(e => e.llave === this.llave(p))
				return l ? l.cantidad : 0
			},
			disponible(p) {
				return Number(p.existencia) - this.enTicket(p)
			},
			agregar(p) {
				if (Number(p.precio) <= 0) {
					this.$toast.error(`${p.nombre} no tiene precio de venta.`)
					return
				}

				let l = this.ticket.find(e => e.llave === this.llave(p))

				if (this.disponible(p) < 1) {
					this.$toast.error(`Solo hay ${this.formatoCantidad(p.existencia)} de ${this.nombreArticulo(p)} en la sucursal.`)
					return
				}

				if (l) {
					l.cantidad++
				} else {
					this.ticket.push({
						llave: this.llave(p),
						producto_id: p.producto_id,
						unidad_medida_id: p.unidad_medida_id,
						producto_presentacion_id: p.producto_presentacion_id ?? null,
						npresentacion: p.npresentacion ?? "",
						nombre: p.nombre,
						precio: Number(p.precio),
						precio_lista: Number(p.precio),
						costo: Math.round(Number(p.costo ?? 0) * 100) / 100,
						existencia: Number(p.existencia),
						cantidad: 1
					})
				}

				this.ultima = null
			},
			// Enter: código o código de barras exacto; si la búsqueda deja un solo producto, también se agrega
			agregarPorCodigo() {
				let texto = this.termino.trim().toLowerCase()

				if (!texto) {
					return
				}

				// El código es del producto: agrega la unidad de medida (primer artículo del producto)
				let p = this.articulos.find(e =>
					String(e.codigo).toLowerCase() === texto ||
					String(e.codigo_barra ?? "").toLowerCase() === texto
				)

				if (!p && this.productosFiltrados.length === 1) {
					p = this.productosFiltrados[0]
				}

				if (p) {
					this.agregar(p)
					this.termino = ""
				} else {
					this.$toast.error("No hay un producto con ese código.")
				}
			},
			cambiarCantidad(l, valor) {
				let cantidad = Math.round(Number(valor) * 100) / 100

				if (!(cantidad > 0)) {
					this.quitar(l)
					return
				}

				if (cantidad > l.existencia) {
					this.$toast.error(`Solo hay ${this.formatoCantidad(l.existencia)} de ${this.nombreArticulo(l)} en la sucursal.`)
					cantidad = l.existencia
				}

				l.cantidad = cantidad
			},
			// Precio cambiado por el vendedor: no puede quedar bajo el costo
			cambiarPrecio(l, evento) {
				let precio = Math.round(Number(evento.target.value) * 100) / 100

				if (evento.target.value === "" || !(precio >= 0)) {
					this.$toast.error("Indique un precio válido.")
				} else if (precio < l.costo) {
					this.$toast.error(`El precio de ${this.nombreArticulo(l)} no puede ser menor al costo (${this.simbolo} ${this.formatoMonto(l.costo)}).`)
				} else {
					l.precio = precio
				}

				evento.target.value = l.precio.toFixed(2)
			},
			quitar(l) {
				this.ticket = this.ticket.filter(e => e.llave !== l.llave)
			},
			vaciar() {
				this.ticket = []
				this.cliente = null
				this.$refs.buscador?.focus()
			},
			cobrar() {
				if (this.ticket.length === 0 || this.btnGuardar) {
					return
				}

				if (!this.form.venta_serie_id || !this.form.forma_pago_id || !this.form.moneda_id) {
					this.$toast.error("Seleccione la serie y la forma de pago.")
					return
				}

				if (this.esCredito && (!this.cliente || Number(this.cliente.credito) !== 1)) {
					this.$toast.error("Una venta al crédito necesita un cliente con crédito autorizado.")
					return
				}

				this.$refs.cobro?.abrir()
			},
			// El modal de cobro confirmó: se registra la venta completa
			registrar(recibido) {
				this.btnGuardar = true

				api
				.post("/ven/venta/guardar", {
					...this.form,
					cliente_id: this.cliente?.id ?? null,
					lineas: this.ticket.map(l => ({
						producto_id: l.producto_id,
						unidad_medida_id: l.unidad_medida_id,
						producto_presentacion_id: l.producto_presentacion_id,
						cantidad: l.cantidad,
						// Solo si el vendedor lo cambió; si no, la API toma el precio de venta
						precio: l.precio !== l.precio_lista ? l.precio : null
					}))
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.$refs.cobro?.cerrar()
						this.$toast.success(res.mensaje)

						this.vuelto = recibido ? Math.max(recibido - Number(res.linea.total_precio), 0) : 0
						this.ultima = res.linea
						this.ticket = []
						this.cliente            = null
						this.form.forma_pago_id = this.formaContado()
						this.actualizarProductos(res.productos ?? this.productos)
						this.$refs.buscador?.focus()
					} else {
						this.$toast.error(res.mensaje)
						this.getProductos()
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnGuardar = false
				})
			},
			imprimir(venta) {
				this.imprimiendo = true

				imprimirPdf(`/ven/venta/imprimir/${venta.id}`)
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudo generar el ticket."))
				})
				.finally(() => {
					this.imprimiendo = false
				})
			},
			// Al volver de la lista la existencia pudo cambiar (ej. una venta anulada)
			volverPuntoVenta() {
				this.verLista = false
				this.getProductos()
				this.$nextTick(() => this.$refs.buscador?.focus())
			},
			// F9 cobra; no aplica con un modal abierto (el de cobro tiene su propio Enter)
			atajos(e) {
				if (e.key === "F9" && !this.verLista && !document.querySelector(".modal.show")) {
					e.preventDefault()
					this.cobrar()
				}
			},
			// Agotado en rojo, bajo el mínimo en ámbar, disponible en verde
			claseExistencia(p) {
				if (this.disponible(p) <= 0) {
					return "text-danger-emphasis"
				}

				return Number(p.existencia_minima) > 0 && Number(p.existencia) < Number(p.existencia_minima)
					? "text-warning-emphasis"
					: "text-success-emphasis"
			},
			// Las presentaciones que ya están en el ticket no se pueden abrir
			abrirPresentacion(p) {
				this.$refs.abrirPresentacion?.abrir(p, this.disponible(p))
			},
			nombreArticulo(p) {
				return p.npresentacion ? `${p.nombre} (${p.npresentacion})` : p.nombre
			},
			estiloEtiqueta,
			urlFoto(foto) {
				return `https://lh3.googleusercontent.com/d/${foto}`
			},
			formatoMonto,
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 2
				})
			}
		},
		computed: {
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			// Lo que se puede vender: cada producto en su unidad de medida y, después, en cada presentación
			// activa (precio y existencia propios; el mínimo es solo de la unidad)
			articulos() {
				return this.productos.flatMap(p => [
					p,
					...(p.presentaciones ?? [])
					.filter(pre => Number(pre.activo) === 1)
					.map(pre => ({
						...p,
						producto_presentacion_id: pre.producto_presentacion_id,
						npresentacion: pre.nombre,
						precio: pre.precio,
						costo: pre.costo,
						existencia: pre.existencia,
						existencia_minima: 0
					}))
				])
			},
			productosFiltrados() {
				let ter = normalizar(this.termino.trim())

				return this.articulos.filter(p => {
					if (this.categoria && String(p.categoria_id) !== this.categoria) {
						return false
					}

					return ter === "" ||
						normalizar(String(p.nombre)).includes(ter) ||
						normalizar(String(p.codigo)).includes(ter) ||
						normalizar(String(p.codigo_barra ?? "")).includes(ter)
				})
			},
			// Solo categorías que tienen productos, con cuántos
			categoriasConProductos() {
				return this.catalogo.categorias
				.map(c => ({
					id: c.id,
					nombre: c.nombre,
					etiqueta: c.etiqueta,
					total: this.articulos.filter(p => String(p.categoria_id) === String(c.id)).length
				}))
				.filter(c => c.total > 0)
			},
			moneda() {
				return this.catalogo.monedas.find(e => String(e.id) === String(this.form.moneda_id)) ?? null
			},
			simbolo() {
				return this.moneda?.simbolo ?? ""
			},
			formaPago() {
				return this.catalogo.formas_pago.find(e => String(e.id) === String(this.form.forma_pago_id)) ?? null
			},
			// Solo en efectivo se pide lo recibido y se calcula el vuelto
			esEfectivo() {
				return this.formaPago ? normalizar(this.formaPago.nombre).includes("efectivo") : false
			},
			esCredito() {
				return this.formaPago ? normalizar(this.formaPago.nombre).includes("credito") : false
			},
			total() {
				return Math.round(this.ticket.reduce((s, l) => s + l.precio * l.cantidad, 0) * 100) / 100
			},
			unidades() {
				return this.ticket.reduce((s, l) => s + l.cantidad, 0)
			}
		},
		components: {
			PageHeader,
			Cobro,
			Lista,
			BuscarCliente,
			AbrirPresentacion
		}
	}
</script>
