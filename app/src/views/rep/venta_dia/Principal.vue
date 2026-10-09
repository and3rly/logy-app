<template>
	<PageHeader>
		<button
			type="button"
			class="btn btn-suave-success"
			:disabled="btnExcel || lista.length === 0"
			title="Descargar en Excel el resumen por día y las ventas del período"
			@click="descargarExcel"
		>
			<span v-if="btnExcel" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
			<i v-else class="fa-solid fa-file-excel me-1" aria-hidden="true" />Excel
		</button>
		<button type="button" class="btn btn-outline-primary" :disabled="btnBuscar" @click="buscar">
			<i class="fa-solid fa-rotate me-1" :class="{ 'fa-spin': btnBuscar }" aria-hidden="true" />Actualizar
		</button>
	</PageHeader>

	<!-- Qué se consulta: período, forma de pago y (administrador) vendedor -->
	<card class="mb-3">
		<card-body>
			<div class="row g-3 align-items-end">
				<div class="col-6 col-sm-4 col-lg-2">
					<label for="vdia-fdel" class="form-label fw-semibold">Desde</label>
					<input id="vdia-fdel" v-model="bform.fdel" type="date" class="form-control" :max="bform.fal || undefined" @change="buscar">
				</div>
				<div class="col-6 col-sm-4 col-lg-2">
					<label for="vdia-fal" class="form-label fw-semibold">Hasta</label>
					<input id="vdia-fal" v-model="bform.fal" type="date" class="form-control" :min="bform.fdel || undefined" @change="buscar">
				</div>
				<div class="col-12 col-sm-4 col-lg-3">
					<label for="vdia-fpago" class="form-label fw-semibold">Forma de pago</label>
					<select id="vdia-fpago" v-model="bform.forma_pago" class="form-select" @change="buscar">
						<option value="">Todas</option>
						<option v-for="f in formasPago" :key="f.id" :value="String(f.id)">{{ f.nombre }}</option>
					</select>
				</div>
				<div v-if="administrador" class="col-12 col-sm-6 col-lg-3">
					<label for="vdia-usuario" class="form-label fw-semibold">Vendedor</label>
					<select id="vdia-usuario" v-model="bform.usuario" class="form-select" @change="buscar">
						<option value="">Todos</option>
						<option v-for="u in vendedores" :key="u.id" :value="String(u.id)">{{ u.nombre }}</option>
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
					title="Ventas de esta sucursal"
				>
					<i class="fa-solid fa-store text-primary" aria-hidden="true" />
					<span class="fw-semibold text-body">{{ sucursal }}</span>
				</span>
			</div>
		</card-body>
	</card>

	<!-- Resumen del período: vendido − costo = ganancia -->
	<div class="row g-2 align-items-stretch mb-3">
		<div class="col-6 col-xl">
			<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
				<span class="icono-suave icono-suave-primario" aria-hidden="true">
					<i class="fa-solid fa-cash-register" />
				</span>
				<div class="lh-sm">
					<div class="small text-body-secondary">Vendido</div>
					<div class="fs-5 fw-bold text-body">{{ simbolo }} {{ formatoMonto(totales.total) }}</div>
					<div class="small text-body-secondary">{{ plural(totales.ventas, 'venta', 'ventas') }}</div>
				</div>
			</div>
		</div>
		<div class="col-auto d-none d-xl-flex align-items-center fs-4 text-body-secondary" aria-hidden="true">−</div>
		<div class="col-6 col-xl">
			<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
				<span class="icono-suave icono-suave-danger" aria-hidden="true">
					<i class="fa-solid fa-tags" />
				</span>
				<div class="lh-sm">
					<div class="small text-body-secondary">Costo</div>
					<div class="fs-5 fw-bold text-body">{{ simbolo }} {{ formatoMonto(totales.costo) }}</div>
					<div class="small text-body-secondary">de lo vendido</div>
				</div>
			</div>
		</div>
		<div class="col-auto d-none d-xl-flex align-items-center fs-4 text-body-secondary" aria-hidden="true">=</div>
		<div class="col-6 col-xl">
			<div class="d-flex align-items-center gap-3 rounded-3 border border-primary-subtle bg-body px-3 py-3 h-100 shadow-sm">
				<span class="icono-suave icono-suave-success" aria-hidden="true">
					<i class="fa-solid fa-sack-dollar" />
				</span>
				<div class="lh-sm">
					<div class="small text-body-secondary">Ganancia</div>
					<div class="fs-5 fw-bold" :class="totales.ganancia < 0 ? 'text-danger' : 'text-success-emphasis'">
						{{ simbolo }} {{ formatoMonto(totales.ganancia) }}
					</div>
					<div class="small text-body-secondary">Margen {{ formatoMargen(totales.ganancia, totales.total) }}</div>
				</div>
			</div>
		</div>
		<div class="col-6 col-xl">
			<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
				<span class="icono-suave icono-suave-info" aria-hidden="true">
					<i class="fa-solid fa-receipt" />
				</span>
				<div class="lh-sm">
					<div class="small text-body-secondary">Ticket promedio</div>
					<div class="fs-5 fw-bold text-body">{{ simbolo }} {{ formatoMonto(promedio(totales.total, totales.ventas)) }}</div>
					<div class="small text-body-secondary">
						{{ totales.anuladas ? `${plural(totales.anuladas, 'anulada', 'anuladas')} (no suman)` : 'sin anuladas' }}
					</div>
				</div>
			</div>
		</div>
	</div>

	<card>
		<card-body class="p-0">
			<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
				<span class="fw-semibold text-body">Ventas por día</span>
				<span class="small text-body-secondary">· Haga clic en un día para ver sus ventas y productos</span>
				<span class="ms-auto small text-body-secondary text-nowrap" aria-live="polite">
					<span class="fw-semibold text-body">{{ lista.length }}</span>
					{{ lista.length === 1 ? 'día con ventas' : 'días con ventas' }}
				</span>
			</div>

			<div class="table-responsive tabla-pantalla">
				<table class="table table-sm table-hover mb-0">
					<thead>
						<tr>
							<th class="ps-3">Día</th>
							<th class="text-end">Ventas</th>
							<th class="text-end">Vendido</th>
							<th class="text-end">Costo</th>
							<th class="text-end">Ganancia</th>
							<th class="text-end">Margen</th>
							<th class="text-end">Ticket prom.</th>
							<th class="pe-3" style="width: 40px"><span class="visually-hidden">Ver detalle</span></th>
						</tr>
					</thead>
					<tbody>
						<template v-for="d in lista" :key="d.dia">
							<tr
								class="align-middle"
								role="button"
								:class="{ 'table-active': abierto === d.dia }"
								:aria-expanded="abierto === d.dia"
								@click="alternar(d.dia)"
							>
								<td class="ps-3">
									<div class="lh-sm">
										<div class="fw-semibold text-body text-capitalize">{{ formatoDia(d.dia) }}</div>
										<div class="small text-body-secondary">
											{{ formatoFecha(d.dia) }}
											<template v-if="Number(d.anuladas)"> · {{ plural(Number(d.anuladas), 'anulada', 'anuladas') }}</template>
										</div>
									</div>
								</td>
								<td class="text-end">{{ d.ventas }}</td>
								<td class="text-end text-nowrap fw-semibold">{{ formatoMonto(d.total) }}</td>
								<td class="text-end text-nowrap text-body-secondary">{{ formatoMonto(d.costo) }}</td>
								<td class="text-end text-nowrap fw-bold" :class="Number(d.ganancia) < 0 ? 'text-danger' : 'text-success-emphasis'">
									{{ formatoMonto(d.ganancia) }}
								</td>
								<td class="text-end text-nowrap">
									<span class="badge rounded-1 border" :class="claseMargen(d.ganancia, d.total)">{{ formatoMargen(d.ganancia, d.total) }}</span>
								</td>
								<td class="text-end text-nowrap">{{ formatoMonto(promedio(d.total, d.ventas)) }}</td>
								<td class="pe-3 text-body-secondary">
									<i class="fa-solid" :class="abierto === d.dia ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true" />
								</td>
							</tr>
							<tr v-if="abierto === d.dia">
								<td colspan="8" class="p-0 bg-body-tertiary">
									<Dia :dia="d.dia" :filtros="bform" />
								</td>
							</tr>
						</template>

						<tr v-if="btnBuscar">
							<td colspan="8" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="lista.length === 0">
							<td colspan="8" class="text-center text-body-secondary py-4">No hubo ventas en este período</td>
						</tr>
					</tbody>
					<tfoot v-if="lista.length">
						<tr>
							<td class="ps-3">Total</td>
							<td class="text-end">{{ totales.ventas }}</td>
							<td class="text-end text-nowrap">{{ formatoMonto(totales.total) }}</td>
							<td class="text-end text-nowrap">{{ formatoMonto(totales.costo) }}</td>
							<td class="text-end text-nowrap" :class="totales.ganancia < 0 ? 'text-danger' : 'text-success-emphasis'">{{ formatoMonto(totales.ganancia) }}</td>
							<td class="text-end text-nowrap">{{ formatoMargen(totales.ganancia, totales.total) }}</td>
							<td class="text-end text-nowrap">{{ formatoMonto(promedio(totales.total, totales.ventas)) }}</td>
							<td class="pe-3" />
						</tr>
					</tfoot>
				</table>
			</div>
		</card-body>
	</card>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Dia from './Dia.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { formatoMonto } from '@/utils/numero'
	import { useSesionStore } from '@/stores/sesion'

	// Fecha local en AAAA-MM-DD (toISOString la pasaría a UTC y podría cambiar el día)
	function iso(fecha) {
		let m = String(fecha.getMonth() + 1).padStart(2, "0")
		let d = String(fecha.getDate()).padStart(2, "0")

		return `${fecha.getFullYear()}-${m}-${d}`
	}

	export default {
		name: "VentaDia",
		mixins: [Accion],
		data: () => ({
			simbolo: "",
			administrador: false,
			formasPago: [],
			vendedores: [],
			abierto: null,
			btnExcel: false,
			periodos: [
				{ id: "hoy", nombre: "Hoy" },
				{ id: "7", nombre: "Últimos 7 días" },
				{ id: "mes", nombre: "Este mes" },
				{ id: "anterior", nombre: "Mes anterior" },
				{ id: "anio", nombre: "Este año" }
			]
		}),
		created() {
			this.url = "rep/venta_dia"
			this.autoBuscar = false
			this.bform = {
				fdel: "",
				fal: "",
				forma_pago: "",
				usuario: ""
			}
			this.usarPeriodo("mes", false)
			this.getDatos()
			this.buscar()
		},
		methods: {
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					let res = result.data

					this.simbolo = res.simbolo ?? ""
					this.administrador = Boolean(res.administrador)
					this.formasPago = res.formas_pago ?? []
					this.vendedores = res.vendedores ?? []
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			// Reemplaza el buscar del mixin: al cambiar los filtros se cierra el día abierto
			buscar() {
				this.btnBuscar = true
				this.abierto = null

				api
				.get(`/${this.url}/buscar`, { params: this.bform })
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.lista = res.lista ?? []
					} else {
						this.lista = []
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
			alternar(dia) {
				this.abierto = this.abierto === dia ? null : dia
			},
			usarPeriodo(id, consultar = true) {
				let hoy = new Date()
				let del = hoy
				let al = hoy

				switch (id) {
					case "7":
						del = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 6)
						break
					case "mes":
						del = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
						break
					case "anterior":
						del = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1)
						al = new Date(hoy.getFullYear(), hoy.getMonth(), 0)
						break
					case "anio":
						del = new Date(hoy.getFullYear(), 0, 1)
						break
				}

				this.bform.fdel = iso(del)
				this.bform.fal = iso(al)

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
					let nombre = /filename="([^"]+)"/.exec(result.headers["content-disposition"] ?? "")?.[1] ?? "ventas_por_dia.xlsx"
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
			promedio(total, ventas) {
				return Number(ventas) ? Number(total) / Number(ventas) : 0
			},
			formatoMargen(ganancia, total) {
				return Number(total) ? `${(Number(ganancia) / Number(total) * 100).toFixed(1)} %` : "—"
			},
			// Margen bajo (< 10 %) en rojo, medio en amarillo, bueno en verde
			claseMargen(ganancia, total) {
				let margen = Number(total) ? Number(ganancia) / Number(total) * 100 : 0

				if (margen < 10) {
					return "bg-danger-subtle text-danger-emphasis border-danger-subtle"
				}

				return margen < 25 ? "bg-warning-subtle text-warning-emphasis border-warning-subtle" : "bg-success-subtle text-success-emphasis border-success-subtle"
			},
			plural(n, uno, varios) {
				return `${n} ${n === 1 ? uno : varios}`
			},
			// "2026-09-16" → "16/09/2026"
			formatoFecha(fecha) {
				let [a, m, d] = String(fecha).slice(0, 10).split("-")

				return `${d}/${m}/${a}`
			},
			// "2026-09-28" → "lunes 28 de septiembre"
			formatoDia(dia) {
				let [a, m, d] = dia.split("-").map(Number)

				return new Date(a, m - 1, d).toLocaleDateString("es", {
					weekday: "long",
					day: "numeric",
					month: "long"
				})
			},
			formatoMonto
		},
		computed: {
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			periodoActivo() {
				let hoy = new Date()
				let al = iso(hoy)
				let { fdel, fal } = this.bform

				let rangos = {
					hoy: [al, al],
					7: [iso(new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 6)), al],
					mes: [iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), al],
					anterior: [iso(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1)), iso(new Date(hoy.getFullYear(), hoy.getMonth(), 0))],
					anio: [iso(new Date(hoy.getFullYear(), 0, 1)), al]
				}

				return Object.keys(rangos).find(k => rangos[k][0] === fdel && rangos[k][1] === fal) ?? null
			},
			totales() {
				return this.lista.reduce((t, d) => {
					t.ventas += Number(d.ventas)
					t.anuladas += Number(d.anuladas)
					t.total += Number(d.total)
					t.costo += Number(d.costo)
					t.ganancia += Number(d.ganancia)

					return t
				}, { ventas: 0, anuladas: 0, total: 0, costo: 0, ganancia: 0 })
			}
		},
		components: {
			PageHeader,
			Dia
		}
	}
</script>
