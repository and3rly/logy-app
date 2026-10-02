<template>
	<PageHeader>
		<span
			v-if="sucursal"
			class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
			title="Cuentas de las compras de esta sucursal"
		>
			<i class="fa-solid fa-store text-primary" aria-hidden="true" />
			<span class="fw-semibold text-body">{{ sucursal }}</span>
		</span>
	</PageHeader>

	<!-- Indicadores de las cuentas pendientes -->
	<div class="row g-3 mb-3">
		<div v-for="k in indicadores" :key="k.titulo" class="col-6 col-xl-3">
			<div class="rounded-3 border bg-body p-3 h-100">
				<div class="d-flex align-items-center gap-2 small text-body-secondary mb-1">
					<i class="fa-solid fa-fw" :class="[k.icono, k.color]" aria-hidden="true" />{{ k.titulo }}
				</div>
				<div class="fs-4 fw-bold lh-sm text-nowrap" :class="k.valorColor ?? 'text-body'">{{ k.valor }}</div>
			</div>
		</div>
	</div>

	<div class="row g-3 align-items-start">
		<div class="col-12" :class="{ 'col-xl-8': cuenta }">
			<card>
				<card-body class="p-0">
					<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
						<div class="filtro-segmentado" role="group" aria-label="Filtrar por estado">
							<button
								v-for="f in filtros"
								:key="f.id"
								type="button"
								class="filtro-opcion"
								:class="{ activo: filtro === f.id, vacio: conteo(f.id) === 0 }"
								:style="f.color ? estiloEtiqueta(f.color) : null"
								:aria-pressed="filtro === f.id"
								@click="filtro = f.id"
							>
								<span v-if="f.color" class="filtro-punto" aria-hidden="true" />{{ f.nombre }}
								<span class="filtro-conteo" :class="{ alerta: f.alerta && conteo(f.id) > 0 }">{{ conteo(f.id) }}</span>
							</button>
						</div>

						<div class="input-group flex-grow-1 w-auto" style="min-width: 14rem">
							<span class="input-group-text">
								<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
							</span>
							<input
								v-model="termino"
								type="search"
								class="form-control"
								placeholder="Buscar por proveedor, NIT, factura o compra..."
								aria-label="Buscar cuentas"
							>
						</div>
					</div>

					<div class="table-responsive">
						<table class="table table-sm table-hover mb-0">
							<thead>
								<tr>
									<th class="ps-3">Factura</th>
									<th>Proveedor</th>
									<th>Vence</th>
									<th class="text-end">Total</th>
									<th class="text-end" style="min-width: 8rem">Saldo</th>
									<th class="pe-3">Estado</th>
								</tr>
							</thead>
							<tbody>
								<tr
									v-for="i in visibles"
									:key="i.id"
									class="align-middle"
									:class="{ 'table-active': cuenta && cuenta.id === i.id }"
									role="button"
									@click="seleccionar(i)"
								>
									<td class="ps-3">
										<div class="lh-sm">
											<div class="fw-semibold font-monospace text-body">{{ i.factura_numero }}</div>
											<div class="small text-body-secondary">{{ formatoFecha(i.factura_fecha) }} · {{ i.compra_numero }}</div>
										</div>
									</td>
									<td>
										<div class="lh-sm">
											<div class="text-body">{{ i.nproveedor }}</div>
											<div class="small text-body-secondary">NIT {{ i.nit_proveedor || '—' }}</div>
										</div>
									</td>
									<td class="text-nowrap">
										<div class="lh-sm">
											<div>{{ formatoFecha(i.fecha_vence) }}</div>
											<div class="small" :class="estado(i).id === 'vencida' ? 'text-danger-emphasis' : 'text-body-secondary'">{{ textoDias(i) }}</div>
										</div>
									</td>
									<td class="text-end text-nowrap">{{ i.smoneda }} {{ formatoMonto(i.total) }}</td>
									<td class="text-end text-nowrap">
										<div class="fw-semibold text-body">{{ i.smoneda }} {{ formatoMonto(i.saldo) }}</div>
										<div
											class="progress mt-1"
											style="height: 4px"
											role="progressbar"
											:aria-label="`Pagado ${pagado(i)}%`"
											:aria-valuenow="pagado(i)"
											aria-valuemin="0"
											aria-valuemax="100"
										>
											<div class="progress-bar bg-success" :style="{ width: `${pagado(i)}%` }" />
										</div>
									</td>
									<td class="pe-3">
										<span class="badge rounded-1 fw-semibold etiqueta-color" :style="estiloEtiqueta(estado(i).color)">{{ estado(i).nombre }}</span>
									</td>
								</tr>

								<tr v-if="btnBuscar">
									<td colspan="6" class="text-center text-body-secondary">
										<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
									</td>
								</tr>
								<tr v-else-if="visibles.length === 0">
									<td colspan="6" class="text-center text-body-secondary py-4">
										{{ termino ? 'Sin resultados para la búsqueda' : 'No hay cuentas en este filtro' }}
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</card-body>
			</card>
		</div>

		<!-- Cuenta seleccionada: pago e historial; acompaña al desplazarse -->
		<div v-if="cuenta" class="col-12 col-xl-4 position-sticky" style="top: 5rem">
			<Pago
				:key="cuenta.id"
				:cuenta="cuenta"
				:formas-pago="formasPago"
				:fecha="fecha"
				@actualizar="actualizar"
				@cerrar="cuenta = null"
			/>
		</div>
	</div>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Pago from './Pago.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "CuentaPagar",
		mixins: [Accion],
		components: {
			PageHeader,
			Pago
		},
		data: () => ({
			cuenta: null,
			formasPago: [],
			fecha: null,
			filtro: "pendientes",
			filtros: [
				{ id: "pendientes", nombre: "Pendientes", color: "primary" },
				{ id: "vencidas", nombre: "Vencidas", color: "danger", alerta: true },
				{ id: "por_vencer", nombre: "Por vencer", color: "warning" },
				{ id: "pagadas", nombre: "Pagadas", color: "success" },
				{ id: "anuladas", nombre: "Anuladas", color: "secondary" },
				{ id: "todas", nombre: "Todas" }
			]
		}),
		created() {
			this.url = "fin/cuenta_pagar"
			// Se traen todas: los filtros y los indicadores se calculan aquí
			this.bform = {
				estado: "todas"
			}

			this.getDatos()
		},
		methods: {
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					this.formasPago = result.data.cat?.formas_pago ?? []
					this.fecha      = result.data.fecha ?? null
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			seleccionar(i) {
				this.cuenta = this.cuenta && this.cuenta.id === i.id ? null : i
			},
			// Un pago o su anulación cambió saldo y abono de la cuenta
			actualizar(linea) {
				Object.assign(this.cuenta, linea)
			},
			// pagada, anulada, vencida, por vencer (7 días) o al día
			estado(i) {
				if (Number(i.anulado) === 1) {
					return { id: "anulada", nombre: "Anulada", color: "secondary" }
				}

				if (Number(i.saldo) <= 0) {
					return { id: "pagada", nombre: "Pagada", color: "success" }
				}

				if (Number(i.dias) < 0) {
					return { id: "vencida", nombre: "Vencida", color: "danger" }
				}

				if (Number(i.dias) <= 7) {
					return { id: "por_vencer", nombre: "Por vencer", color: "warning" }
				}

				return { id: "al_dia", nombre: "Al día", color: "primary" }
			},
			cumple(i, filtro) {
				let e = this.estado(i).id

				switch (filtro) {
					case "pendientes":
						return ["vencida", "por_vencer", "al_dia"].includes(e)
					case "vencidas":
						return e === "vencida"
					case "por_vencer":
						return e === "por_vencer"
					case "pagadas":
						return e === "pagada"
					case "anuladas":
						return e === "anulada"
					default:
						return true
				}
			},
			conteo(filtro) {
				return this.lista.filter(i => this.cumple(i, filtro)).length
			},
			textoDias(i) {
				if (Number(i.anulado) === 1 || Number(i.saldo) <= 0) {
					return "—"
				}

				let d = Number(i.dias)

				if (d === 0) {
					return "Vence hoy"
				}

				return d < 0 ? `Hace ${-d} ${d === -1 ? 'día' : 'días'}` : `En ${d} ${d === 1 ? 'día' : 'días'}`
			},
			pagado(i) {
				let total = Number(i.total)
				return total > 0 ? Math.min(100, Math.round(Number(i.abono) / total * 100)) : 0
			},
			formatoMonto,
			// "2026-09-16" → "16/09/2026"
			formatoFecha(fecha) {
				if (!fecha) {
					return ""
				}

				let [a, m, d] = String(fecha).slice(0, 10).split("-")
				return `${d}/${m}/${a}`
			},
			estiloEtiqueta
		},
		computed: {
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			visibles() {
				return this.filtrada.filter(i => this.cumple(i, this.filtro))
			},
			indicadores() {
				let pendientes = this.lista.filter(i => this.cumple(i, "pendientes"))
				let suma = lista => lista.reduce((s, i) => s + Number(i.saldo), 0)
				let simbolo = this.lista[0]?.smoneda ?? ""

				return [
					{
						titulo: "Por pagar",
						icono: "fa-money-bill-transfer",
						color: "text-primary",
						valor: `${simbolo} ${this.formatoMonto(suma(pendientes))}`
					},
					{
						titulo: "Vencido",
						icono: "fa-triangle-exclamation",
						color: "text-danger",
						valorColor: "text-danger-emphasis",
						valor: `${simbolo} ${this.formatoMonto(suma(pendientes.filter(i => Number(i.dias) < 0)))}`
					},
					{
						titulo: "Vence en 7 días",
						icono: "fa-clock",
						color: "text-warning",
						valorColor: "text-warning-emphasis",
						valor: `${simbolo} ${this.formatoMonto(suma(pendientes.filter(i => Number(i.dias) >= 0 && Number(i.dias) <= 7)))}`
					},
					{
						titulo: "Cuentas pendientes",
						icono: "fa-file-invoice-dollar",
						color: "text-primary",
						valor: pendientes.length
					}
				]
			}
		}
	}
</script>
