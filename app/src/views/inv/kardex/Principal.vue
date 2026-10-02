<template>
	<PageHeader>
		<button
			type="button"
			class="btn btn-suave-success"
			:disabled="btnExcel || lista.length === 0"
			title="Descargar en Excel el kardex del período (y producto) elegidos"
			@click="descargarExcel"
		>
			<span v-if="btnExcel" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
			<i v-else class="fa-solid fa-file-excel me-1" aria-hidden="true" />Excel
		</button>
		<button type="button" class="btn btn-outline-primary" :disabled="btnBuscar" @click="buscar">
			<i class="fa-solid fa-rotate me-1" :class="{ 'fa-spin': btnBuscar }" aria-hidden="true" />Actualizar
		</button>
	</PageHeader>

	<!-- Qué se consulta: producto, período y (si controla vencimiento) lote -->
	<card class="mb-3">
		<card-body>
			<div class="row g-3 align-items-end">
				<div class="col-12 col-lg-5">
					<label for="kardex-producto" class="form-label fw-semibold">Producto</label>
					<div class="position-relative">
						<div class="input-group">
							<span class="input-group-text">
								<i class="fa-solid fa-box" aria-hidden="true" />
							</span>
							<input
								id="kardex-producto"
								ref="producto"
								v-model="busqueda"
								type="search"
								class="form-control"
								placeholder="Todos los productos · escriba para elegir uno"
								autocomplete="off"
								role="combobox"
								aria-autocomplete="list"
								aria-controls="kardex-opciones"
								:aria-expanded="abierto"
								@focus="abrir"
								@input="abrir"
								@blur="abierto = false"
								@keydown.down.prevent="mover(1)"
								@keydown.up.prevent="mover(-1)"
								@keydown.enter.prevent="elegir(sugerencias[resaltado])"
								@keydown.esc="abierto = false"
							>
							<button
								v-if="producto"
								type="button"
								class="btn btn-outline-secondary"
								title="Quitar el producto y ver todos"
								@click="quitarProducto"
							>
								<i class="fa-solid fa-xmark" aria-hidden="true" />
							</button>
						</div>

						<!-- Sugerencias: mousedown.prevent para que el blur no cierre la lista antes del clic -->
						<ul
							v-if="abierto"
							id="kardex-opciones"
							class="dropdown-menu show w-100 shadow overflow-auto py-1"
							style="max-height: 320px"
							role="listbox"
						>
							<li v-for="(p, idx) in sugerencias" :key="`${p.producto_id}-${p.unidad_medida_id}`" role="option" :aria-selected="idx === resaltado">
								<button
									type="button"
									class="dropdown-item d-flex align-items-center justify-content-between gap-3 py-2"
									:class="{ active: idx === resaltado }"
									@mousedown.prevent="elegir(p)"
									@mouseenter="resaltado = idx"
								>
									<span class="lh-sm text-truncate">
										<span class="d-block fw-semibold text-truncate">{{ p.nombre }}</span>
										<span class="small font-monospace opacity-75">{{ p.codigo }}</span>
									</span>
									<span class="small text-nowrap opacity-75">{{ formatoCantidad(p.existencia) }} {{ p.nunidad }}</span>
								</button>
							</li>
							<li v-if="sugerencias.length === 0" class="px-3 py-2 small text-body-secondary">
								No hay productos que coincidan con "{{ busqueda }}"
							</li>
						</ul>
					</div>
				</div>

				<div class="col-6 col-sm-4 col-lg-2">
					<label for="kardex-fdel" class="form-label fw-semibold">Desde</label>
					<input id="kardex-fdel" v-model="bform.fdel" type="date" class="form-control" :max="bform.fal || undefined" @change="buscar">
				</div>
				<div class="col-6 col-sm-4 col-lg-2">
					<label for="kardex-fal" class="form-label fw-semibold">Hasta</label>
					<input id="kardex-fal" v-model="bform.fal" type="date" class="form-control" :min="bform.fdel || undefined" @change="buscar">
				</div>

				<!-- Cada presentación lleva su propio kardex; sin presentación, el de la unidad de medida -->
				<div v-if="producto && presentacionesProducto.length" class="col-12 col-sm-4 col-lg-2">
					<label for="kardex-presentacion" class="form-label fw-semibold">Presentación</label>
					<select id="kardex-presentacion" v-model="bform.presentacion" class="form-select" @change="cambiarPresentacion">
						<option value="">{{ producto.nunidad }}</option>
						<option v-for="pre in presentacionesProducto" :key="pre.producto_presentacion_id" :value="String(pre.producto_presentacion_id)">{{ pre.nombre }}</option>
					</select>
				</div>

				<!-- Solo productos con control de vencimiento que han tenido lotes -->
				<div v-if="producto && lotes.length" class="col-12 col-sm-4 col-lg-3">
					<label for="kardex-lote" class="form-label fw-semibold">Lote</label>
					<select id="kardex-lote" v-model="bform.lote" class="form-select" @change="buscar">
						<option value="">Todos los lotes</option>
						<option v-for="l in lotes" :key="l.fecha" :value="l.fecha">
							Vence {{ formatoFecha(l.fecha) }} · {{ formatoCantidad(l.existencia) }} en existencia
						</option>
					</select>
				</div>
			</div>

			<!-- Períodos rápidos y sucursal consultada -->
			<div class="d-flex flex-wrap align-items-center gap-2 mt-3">
				<span class="small text-body-secondary me-1">Período:</span>
				<button
					v-for="p in periodos"
					:key="p.id"
					type="button"
					class="btn btn-sm rounded-pill"
					:class="periodoActivo === p.id ? 'btn-primary' : 'btn-outline-secondary'"
					:aria-pressed="periodoActivo === p.id"
					@click="usarPeriodo(p.id)"
				>{{ p.nombre }}</button>

				<span
					v-if="sucursal"
					class="ms-auto d-inline-flex align-items-center gap-2 px-3 py-1 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap small"
					title="Movimientos de esta sucursal"
				>
					<i class="fa-solid fa-store text-primary" aria-hidden="true" />
					<span class="fw-semibold text-body">{{ sucursal }}</span>
				</span>
			</div>
		</card-body>
	</card>

	<!-- Sin producto: todos los movimientos del período; cada saldo es el del producto de la fila -->
	<template v-if="!producto">
		<div class="d-flex flex-wrap align-items-center gap-2 mb-3 small text-body-secondary">
			<i class="fa-solid fa-circle-info text-primary" aria-hidden="true" />
			<span>
				Viendo <span class="fw-semibold text-body">todos los productos</span>.
				Elija uno arriba, o haga clic en su nombre en la tabla, para ver su saldo inicial y final.
			</span>
		</div>

		<div class="row g-3 mb-3">
			<div class="col-6 col-xl-3">
				<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-primario" aria-hidden="true">
						<i class="fa-solid fa-right-left" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Movimientos</div>
						<div class="fs-5 fw-bold text-body">{{ lista.length }}</div>
					</div>
				</div>
			</div>
			<div class="col-6 col-xl-3">
				<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-success" aria-hidden="true">
						<i class="fa-solid fa-arrow-down" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Entradas</div>
						<div class="fs-5 fw-bold text-success-emphasis">{{ resumen.nentradas }}</div>
					</div>
				</div>
			</div>
			<div class="col-6 col-xl-3">
				<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-danger" aria-hidden="true">
						<i class="fa-solid fa-arrow-up" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Salidas</div>
						<div class="fs-5 fw-bold text-danger-emphasis">{{ resumen.nsalidas }}</div>
					</div>
				</div>
			</div>
			<div class="col-6 col-xl-3">
				<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-info" aria-hidden="true">
						<i class="fa-solid fa-boxes-stacked" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Productos con movimiento</div>
						<div class="fs-5 fw-bold text-body">{{ resumen.productos }}</div>
					</div>
				</div>
			</div>
		</div>
	</template>

	<template v-else>
		<!-- Producto consultado -->
		<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
			<div class="lh-sm">
				<div class="fs-5 fw-bold text-body">{{ producto.nombre }}</div>
				<div class="small text-body-secondary">
					<span class="font-monospace">{{ producto.codigo }}</span>
					· {{ presentacion ? `Presentación: ${presentacion.nombre}` : `Unidad: ${producto.nunidad}` }}
					<template v-if="producto.nmarca"> · {{ producto.nmarca }}</template>
				</div>
			</div>
			<span
				v-if="producto.ncategoria"
				class="badge rounded-1 fw-semibold etiqueta-color"
				:style="estiloEtiqueta(producto.ecategoria)"
			>{{ producto.ncategoria }}</span>
			<span class="ms-sm-auto small text-body-secondary">
				Existencia actual:
				<span class="fw-semibold text-body">{{ formatoCantidad((presentacion ?? producto).existencia) }} {{ presentacion ? presentacion.nombre : producto.nunidad }}</span>
			</span>
		</div>

		<!-- Resumen como una operación: saldo inicial + entradas − salidas = saldo final -->
		<div class="row g-2 align-items-stretch mb-3">
			<div class="col-6 col-xl">
				<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-info" aria-hidden="true">
						<i class="fa-solid fa-flag" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Saldo inicial</div>
						<div class="fs-5 fw-bold text-body">{{ formatoCantidad(saldoInicial) }}</div>
						<div class="small text-body-secondary">{{ bform.fdel ? `al iniciar el ${formatoFecha(bform.fdel)}` : 'desde el inicio' }}</div>
					</div>
				</div>
			</div>
			<div class="col-auto d-none d-xl-flex align-items-center fs-4 text-body-secondary" aria-hidden="true">+</div>
			<div class="col-6 col-xl">
				<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-success" aria-hidden="true">
						<i class="fa-solid fa-arrow-down" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Entradas</div>
						<div class="fs-5 fw-bold text-success-emphasis">+{{ formatoCantidad(resumen.entradas) }}</div>
						<div class="small text-body-secondary">{{ plural(resumen.nentradas, 'movimiento', 'movimientos') }}</div>
					</div>
				</div>
			</div>
			<div class="col-auto d-none d-xl-flex align-items-center fs-4 text-body-secondary" aria-hidden="true">−</div>
			<div class="col-6 col-xl">
				<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-danger" aria-hidden="true">
						<i class="fa-solid fa-arrow-up" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Salidas</div>
						<div class="fs-5 fw-bold text-danger-emphasis">−{{ formatoCantidad(resumen.salidas) }}</div>
						<div class="small text-body-secondary">{{ plural(resumen.nsalidas, 'movimiento', 'movimientos') }}</div>
					</div>
				</div>
			</div>
			<div class="col-auto d-none d-xl-flex align-items-center fs-4 text-body-secondary" aria-hidden="true">=</div>
			<div class="col-6 col-xl">
				<div class="d-flex align-items-center gap-3 rounded-3 border border-primary-subtle bg-body px-3 py-3 h-100 shadow-sm">
					<span class="icono-suave icono-suave-primario" aria-hidden="true">
						<i class="fa-solid fa-boxes-stacked" />
					</span>
					<div class="lh-sm">
						<div class="small text-body-secondary">Saldo final</div>
						<div class="fs-5 fw-bold" :class="saldoFinal < 0 ? 'text-danger' : 'text-body'">{{ formatoCantidad(saldoFinal) }}</div>
						<div class="small text-body-secondary">{{ bform.fal ? `al cerrar el ${formatoFecha(bform.fal)}` : 'a la fecha' }}</div>
					</div>
				</div>
			</div>
		</div>
	</template>

	<card>
	<card-body class="p-0">
			<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
				<!-- Tipo de movimiento: el saldo de cada fila no cambia al filtrar -->
				<div class="btn-group" role="group" aria-label="Filtrar por sentido">
					<button
						v-for="s in sentidos"
						:key="s.id"
						type="button"
						class="btn btn-sm"
						:class="sentido === s.id ? 'btn-primary' : 'btn-outline-secondary'"
						:aria-pressed="sentido === s.id"
						@click="sentido = s.id"
					>
						<i v-if="s.icono" class="fa-solid me-1" :class="s.icono" aria-hidden="true" />{{ s.nombre }}
					</button>
				</div>

				<div class="input-group input-group-sm flex-grow-1 w-auto">
					<span class="input-group-text">
						<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
					</span>
					<input
						v-model="termino"
						type="search"
						class="form-control"
						placeholder="Buscar por documento, detalle o usuario..."
						aria-label="Buscar movimientos"
					>
				</div>

				<span class="small text-body-secondary text-nowrap" aria-live="polite">
					<span class="fw-semibold text-body">{{ filtrando ? `${visibles.length} de ${lista.length}` : lista.length }}</span>
					{{ lista.length === 1 ? 'movimiento' : 'movimientos' }}
				</span>
			</div>

			<div class="table-responsive">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th class="ps-3" style="width: 80px">Hora</th>
							<th v-if="!producto">Producto</th>
							<th>Movimiento</th>
							<th>Detalle</th>
							<th v-if="conLotes">Lote (vence)</th>
							<th class="text-end">Entrada</th>
							<th class="text-end">Salida</th>
							<th class="text-end pe-3" :title="producto ? '' : 'Existencia del producto después del movimiento'">
								{{ producto ? 'Saldo' : 'Saldo del producto' }}
							</th>
						</tr>
					</thead>
					<tbody>
						<!-- Punto de partida del período (solo con un producto) -->
						<tr v-if="producto" class="align-middle">
							<td class="ps-3 text-body-secondary">—</td>
							<td :colspan="conLotes ? 5 : 4" class="text-body-secondary fst-italic">
								<i class="fa-solid fa-flag me-2" aria-hidden="true" />Saldo inicial
								{{ bform.fdel ? `al iniciar el ${formatoFecha(bform.fdel)}` : '' }}
							</td>
							<td class="text-end pe-3 fw-bold text-body">{{ formatoCantidad(saldoInicial) }}</td>
						</tr>

						<template v-for="g in grupos" :key="g.dia">
							<!-- Separador por día -->
							<tr>
								<td :colspan="columnas" class="ps-3 py-1 small fw-semibold text-body-secondary bg-body-tertiary text-capitalize">
									<i class="fa-regular fa-calendar me-2" aria-hidden="true" />{{ formatoDia(g.dia) }}
								</td>
							</tr>

							<tr v-for="m in g.movimientos" :key="m.id" class="align-middle">
								<td class="ps-3 text-body-secondary text-nowrap">{{ String(m.fecha).slice(11, 16) }}</td>
								<td v-if="!producto">
									<button
										type="button"
										class="btn btn-link p-0 text-start text-decoration-none lh-sm"
										:title="`Ver el kardex de ${m.nproducto}`"
										@click="elegirPorId(m.producto_id, m.producto_presentacion_id)"
									>
										<span class="d-block fw-semibold">{{ m.nproducto }}</span>
										<span class="small text-body-secondary font-monospace">{{ m.cproducto }}</span>
										<span
											v-if="m.npresentacion"
											class="badge rounded-1 border bg-primary-subtle text-primary-emphasis border-primary-subtle ms-1"
										>{{ m.npresentacion }}</span>
									</button>
								</td>
								<td>
									<div class="d-flex align-items-center gap-2">
										<i
											class="fa-solid fs-5"
											:class="esEntrada(m) ? 'fa-circle-arrow-down text-success' : 'fa-circle-arrow-up text-danger'"
											:title="esEntrada(m) ? 'Entrada' : 'Salida'"
											aria-hidden="true"
										/>
										<div class="lh-sm">
											<div class="fw-semibold text-body">{{ m.ntipo }}</div>
											<div v-if="m.documento" class="small text-body-secondary font-monospace">{{ m.documento }}</div>
										</div>
									</div>
								</td>
								<td>
									<div class="lh-sm">
										<div class="text-body">{{ m.observacion || '—' }}</div>
										<div class="small text-body-secondary">
											<i class="fa-regular fa-user me-1" aria-hidden="true" />{{ m.nusuario }}
										</div>
									</div>
								</td>
								<td v-if="conLotes" class="text-nowrap">
									<span v-if="m.fecha_vence">{{ formatoFecha(m.fecha_vence) }}</span>
									<span v-else class="text-body-secondary">Sin vencimiento</span>
								</td>
								<td class="text-end text-nowrap">
									<span v-if="esEntrada(m)" class="fw-semibold text-success-emphasis">+{{ formatoCantidad(m.cantidad) }}</span>
								</td>
								<td class="text-end text-nowrap">
									<span v-if="!esEntrada(m)" class="fw-semibold text-danger-emphasis">−{{ formatoCantidad(Math.abs(m.cantidad)) }}</span>
								</td>
								<td class="text-end pe-3 fw-bold text-nowrap" :class="Number(m.saldo) < 0 ? 'text-danger' : 'text-body'">
									{{ formatoCantidad(m.saldo) }}
									<span v-if="!producto" class="fw-normal small text-body-secondary">{{ m.npresentacion || m.nunidad }}</span>
								</td>
							</tr>
						</template>

						<tr v-if="btnBuscar">
							<td :colspan="columnas" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="visibles.length === 0">
							<td :colspan="columnas" class="text-center text-body-secondary py-4">
								{{ filtrando ? 'Sin movimientos para la búsqueda' : producto ? 'El producto no tuvo movimientos en este período' : 'No hubo movimientos de inventario en este período' }}
							</td>
						</tr>
					</tbody>
					<!-- Con un producto: totales de lo que se ve y saldo con que cierra el período
						 (con todos no se suman: cada producto tiene su unidad) -->
					<tfoot v-if="producto">
						<tr>
							<td :colspan="conLotes ? 4 : 3" class="ps-3">
								Total
								<span class="fw-normal text-body-secondary">· {{ plural(visibles.length, 'movimiento', 'movimientos') }}</span>
							</td>
							<td class="text-end text-nowrap text-success-emphasis">+{{ formatoCantidad(totales.entradas) }}</td>
							<td class="text-end text-nowrap text-danger-emphasis">−{{ formatoCantidad(totales.salidas) }}</td>
							<td class="text-end pe-3 text-nowrap" :class="{ 'text-danger': saldoFinal < 0 }">
								{{ formatoCantidad(saldoFinal) }}
								<span class="fw-normal small text-body-secondary d-block">saldo final</span>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</card-body>
	</card>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'

	// Fecha local en AAAA-MM-DD (toISOString la pasaría a UTC y podría cambiar el día)
	function iso(fecha) {
		let m = String(fecha.getMonth() + 1).padStart(2, "0")
		let d = String(fecha.getDate()).padStart(2, "0")

		return `${fecha.getFullYear()}-${m}-${d}`
	}

	export default {
		name: "Kardex",
		mixins: [Accion],
		data: () => ({
			productos: [],
			lotes: [],
			saldoInicial: 0,
			busqueda: "",
			abierto: false,
			resaltado: 0,
			sentido: null,
			btnExcel: false,
			periodos: [
				{ id: "mes", nombre: "Este mes" },
				{ id: "anterior", nombre: "Mes anterior" },
				{ id: "90", nombre: "Últimos 90 días" },
				{ id: "anio", nombre: "Este año" },
				{ id: "todo", nombre: "Todo" }
			],
			sentidos: [
				{ id: null, nombre: "Todos" },
				{ id: "ENTRADA", nombre: "Entradas", icono: "fa-arrow-down" },
				{ id: "SALIDA", nombre: "Salidas", icono: "fa-arrow-up" }
			]
		}),
		created() {
			this.url = "inv/kardex"
			this.autoBuscar = false
			this.bform = {
				producto: null,
				presentacion: "",
				fdel: "",
				fal: "",
				lote: ""
			}
			this.usarPeriodo("mes", false)
			this.getDatos()
		},
		methods: {
			// Productos con su existencia; si la ruta trae ?producto=ID se abre su kardex, si no, el de todos
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					this.productos = result.data.productos ?? []

					let id = this.$route.query.producto
					let p = id ? this.productos.find(e => String(e.producto_id) === String(id)) : null

					p ? this.elegir(p) : this.buscar()
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
					this.buscar()
				})
			},
			// Reemplaza el buscar del mixin: además de la lista trae el saldo inicial y los lotes del producto
			buscar() {
				this.btnBuscar = true

				api
				.get(`/${this.url}/buscar`, { params: this.bform })
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.lista = res.lista ?? []
						this.lotes = res.lotes ?? []
						this.saldoInicial = Number(res.saldo_inicial ?? 0)
					} else {
						this.lista = []
						this.lotes = []
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnBuscar = false
				})
			},
			abrir() {
				this.abierto = true
				this.resaltado = 0
			},
			mover(paso) {
				if (!this.abierto) {
					this.abrir()
					return
				}

				let total = this.sugerencias.length
				this.resaltado = total ? (this.resaltado + paso + total) % total : 0
			},
			elegir(p, presentacionId = "") {
				if (!p) {
					return
				}

				this.bform.producto = p.producto_id
				this.bform.presentacion = presentacionId ? String(presentacionId) : ""
				this.bform.lote = ""
				this.busqueda = `${p.nombre} (${p.codigo})`
				this.abierto = false
				this.sentido = null
				this.termino = ""
				this.buscar()
			},
			// Desde la tabla general: clic en el nombre del producto
			elegirPorId(id, presentacionId = "") {
				this.elegir(this.productos.find(e => String(e.producto_id) === String(id)), presentacionId)
				window.scrollTo(0, 0)
			},
			// Otra presentación: los lotes son otros
			cambiarPresentacion() {
				this.bform.lote = ""
				this.buscar()
			},
			// Vuelve a la vista de todos los productos
			quitarProducto() {
				this.bform.producto = null
				this.bform.presentacion = ""
				this.bform.lote = ""
				this.busqueda = ""
				this.lotes = []
				this.saldoInicial = 0
				this.sentido = null
				this.termino = ""
				this.buscar()
			},
			// Rangos rápidos; "todo" deja las fechas vacías (todo el historial)
			usarPeriodo(id, consultar = true) {
				let hoy = new Date()
				let del = null
				let al = hoy

				switch (id) {
					case "mes":
						del = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
						break
					case "anterior":
						del = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1)
						al = new Date(hoy.getFullYear(), hoy.getMonth(), 0)
						break
					case "90":
						del = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 89)
						break
					case "anio":
						del = new Date(hoy.getFullYear(), 0, 1)
						break
				}

				this.bform.fdel = del ? iso(del) : ""
				this.bform.fal = del ? iso(al) : ""

				if (consultar) {
					this.buscar()
				}
			},
			descargarExcel() {
				this.btnExcel = true

				api
				.get(`/${this.url}/excel`, {
					params: this.bform,
					responseType: "blob"
				})
				.then(result => {
					let nombre = /filename="([^"]+)"/.exec(result.headers["content-disposition"] ?? "")?.[1] ?? "kardex.xlsx"
					let enlace = document.createElement("a")

					enlace.href = URL.createObjectURL(result.data)
					enlace.download = nombre
					enlace.click()
					URL.revokeObjectURL(enlace.href)
				})
				.catch(() => {
					this.$toast.error("No se pudo generar el archivo de Excel.")
				})
				.finally(() => {
					this.btnExcel = false
				})
			},
			esEntrada(m) {
				return m.sentido === "ENTRADA"
			},
			plural(n, uno, varios) {
				return `${n} ${n === 1 ? uno : varios}`
			},
			// Cantidades sin decimales de sobra: 12 → "12", 0.5 → "0.5"
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 2
				})
			},
			// "2026-09-16" o "2026-09-16 00:00:00" → "16/09/2026"
			formatoFecha(fecha) {
				if (!fecha) {
					return ""
				}

				let [a, m, d] = String(fecha).slice(0, 10).split("-")

				return `${d}/${m}/${a}`
			},
			// "2026-09-28" → "lunes, 28 de septiembre de 2026"
			formatoDia(dia) {
				let [a, m, d] = dia.split("-").map(Number)

				return new Date(a, m - 1, d).toLocaleDateString("es", {
					weekday: "long",
					day: "numeric",
					month: "long",
					year: "numeric"
				})
			},
			estiloEtiqueta
		},
		computed: {
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			producto() {
				return this.productos.find(e => String(e.producto_id) === String(this.bform.producto)) ?? null
			},
			// Presentaciones del producto (activas o con existencia)
			presentacionesProducto() {
				return this.producto?.presentaciones ?? []
			},
			presentacion() {
				return this.presentacionesProducto.find(e => String(e.producto_presentacion_id) === String(this.bform.presentacion)) ?? null
			},
			// Coincidencias por nombre o código (sin importar tildes ni mayúsculas)
			sugerencias() {
				let normal = t => String(t ?? "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase()
				let ter = normal(this.busqueda).trim()

				// Con el producto ya elegido en el cuadro, se muestran todos para poder cambiarlo
				if (!ter || (this.producto && this.busqueda === `${this.producto.nombre} (${this.producto.codigo})`)) {
					return this.productos.slice(0, 50)
				}

				return this.productos
				.filter(p => normal(p.nombre).includes(ter) || normal(p.codigo).includes(ter) || normal(p.codigo_barra) === ter)
				.slice(0, 50)
			},
			periodoActivo() {
				let hoy = new Date()
				let al = iso(hoy)
				let { fdel, fal } = this.bform

				if (!fdel && !fal) {
					return "todo"
				}

				let rangos = {
					mes: [iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), al],
					anterior: [iso(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1)), iso(new Date(hoy.getFullYear(), hoy.getMonth(), 0))],
					90: [iso(new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 89)), al],
					anio: [iso(new Date(hoy.getFullYear(), 0, 1)), al]
				}

				return Object.keys(rangos).find(k => rangos[k][0] === fdel && rangos[k][1] === fal) ?? null
			},
			// Columna de lote: producto con vencimiento o, con todos, si algún movimiento tiene lote
			conLotes() {
				if (!this.producto) {
					return this.lista.some(m => m.fecha_vence)
				}

				return Boolean(Number(this.producto.control_vence)) || this.lotes.length > 0
			},
			columnas() {
				return 6 + (this.conLotes ? 1 : 0) + (this.producto ? 0 : 1)
			},
			filtrando() {
				return Boolean(this.termino || this.sentido)
			},
			visibles() {
				return this.sentido ? this.filtrada.filter(m => m.sentido === this.sentido) : this.filtrada
			},
			// Movimientos visibles agrupados por día, en el mismo orden
			grupos() {
				return this.visibles.reduce((g, m) => {
					let dia = String(m.fecha).slice(0, 10)
					let ultimo = g[g.length - 1]

					if (ultimo && ultimo.dia === dia) {
						ultimo.movimientos.push(m)
					} else {
						g.push({ dia, movimientos: [m] })
					}

					return g
				}, [])
			},
			// Del período completo (sin filtros de pantalla), para los mosaicos
			resumen() {
				let productos = new Set(this.lista.map(m => m.producto_id))

				return this.lista.reduce((t, m) => {
					let cantidad = Math.abs(Number(m.cantidad))

					if (this.esEntrada(m)) {
						t.entradas += cantidad
						t.nentradas++
					} else {
						t.salidas += cantidad
						t.nsalidas++
					}

					return t
				}, { entradas: 0, salidas: 0, nentradas: 0, nsalidas: 0, productos: productos.size })
			},
			totales() {
				return this.visibles.reduce((t, m) => {
					let cantidad = Math.abs(Number(m.cantidad))

					this.esEntrada(m) ? t.entradas += cantidad : t.salidas += cantidad

					return t
				}, { entradas: 0, salidas: 0 })
			},
			saldoFinal() {
				return Math.round((this.saldoInicial + this.resumen.entradas - this.resumen.salidas) * 100) / 100
			}
		},
		components: {
			PageHeader
		}
	}
</script>
