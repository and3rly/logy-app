<template>
	<PageHeader>
		<button type="button" class="btn btn-primary" @click="nueva">
			<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nueva conversión
		</button>
	</PageHeader>

	<card>
		<card-body class="p-0">
			<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
				<div class="input-group w-auto">
					<span class="input-group-text">Del</span>
					<input v-model="bform.fdel" type="date" class="form-control" aria-label="Desde" @change="buscar">
					<span class="input-group-text">al</span>
					<input v-model="bform.fal" type="date" class="form-control" aria-label="Hasta" @change="buscar">
				</div>

				<select v-model="bform.sentido" class="form-select w-auto" aria-label="Sentido" @change="buscar">
					<option :value="null">Todas las conversiones</option>
					<option value="EXPLOSION">De presentación a unidad</option>
					<option value="IMPLOSION">De unidad a presentación</option>
				</select>

				<div class="input-group flex-grow-1 w-auto">
					<span class="input-group-text">
						<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
					</span>
					<input
						v-model="termino"
						type="search"
						class="form-control"
						placeholder="Buscar por número, producto u observación..."
						aria-label="Buscar conversiones"
					>
				</div>

				<!-- Las conversiones se manejan por sucursal: la lista es solo de la sucursal de la sesión -->
				<span
					v-if="sucursal"
					class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
					title="Conversiones de esta sucursal"
				>
					<i class="fa-solid fa-store text-primary" aria-hidden="true" />
					<span class="fw-semibold text-body">{{ sucursal }}</span>
				</span>

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
				<table class="table table-sm table-hover mb-0">
					<thead>
						<tr>
							<th class="ps-3">Número</th>
							<th>Fecha</th>
							<th>Producto</th>
							<th>Conversión</th>
							<th>Observación</th>
							<th class="text-end">Valor</th>
							<th class="pe-3">Usuario</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="i in filtrada" :key="i.id" class="align-middle">
							<td class="ps-3 fw-semibold font-monospace text-body">{{ i.numero }}</td>
							<td class="text-nowrap">{{ formatoFecha(i.fecha) }}</td>
							<td>
								<div class="fw-semibold text-body">{{ i.nproducto }}</div>
								<div class="small text-body-secondary font-monospace">{{ i.cproducto }}</div>
							</td>
							<td class="text-nowrap">
								<i
									class="fa-solid fa-fw me-1 text-primary"
									:class="abierta(i) ? 'fa-box-open' : 'fa-box'"
									:title="abierta(i) ? 'Abrir' : 'Armar'"
									aria-hidden="true"
								/>
								<template v-if="i.sentido === 'EXPLOSION'">
									{{ i.cantidad }} {{ i.npresentacion }}
									<i class="fa-solid fa-arrow-right mx-1 text-body-secondary" aria-hidden="true" />
									{{ formatoCantidad(i.unidades) }} {{ i.nunidad }}
								</template>
								<template v-else>
									{{ formatoCantidad(i.unidades) }} {{ i.nunidad }}
									<i class="fa-solid fa-arrow-right mx-1 text-body-secondary" aria-hidden="true" />
									{{ i.cantidad }} {{ i.npresentacion }}
								</template>
							</td>
							<td class="text-truncate text-body-secondary" style="max-width: 16rem" :title="i.observacion">{{ i.observacion || '—' }}</td>
							<td class="text-end fw-semibold text-nowrap">{{ simbolo }} {{ formatoMonto(i.valor) }}</td>
							<td class="pe-3 text-nowrap">{{ i.nusuario }}</td>
						</tr>

						<tr v-if="btnBuscar">
							<td colspan="7" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="filtrada.length === 0">
							<td colspan="7" class="text-center text-body-secondary py-4">
								{{ termino ? 'Sin resultados para la búsqueda' : 'No hay conversiones en el período seleccionado' }}
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</card-body>
	</card>

	<!-- Nueva conversión -->
	<Teleport to="body">
		<div ref="modal" class="modal fade" tabindex="-1" aria-labelledby="tituloConversion" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-lg">
				<div class="modal-content">
					<div class="modal-header py-2">
						<h2 id="tituloConversion" class="modal-title h3">Nueva conversión</h2>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrar" />
					</div>
					<div class="modal-body">
						<div v-if="productos.length === 0" class="text-center text-body-secondary py-4">
							<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-boxes-stacked" aria-hidden="true" /></div>
							Ningún producto tiene presentaciones. Agréguelas en la ficha del producto.
						</div>
						<Form
							v-else
							:key="`form-${apertura}`"
							:productos="productos"
							@actualizar="actualizar"
							@cancelar="cerrar"
						/>
					</div>
				</div>
			</div>
		</div>
	</Teleport>
</template>

<script>
	import { Modal } from 'bootstrap'
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Form from './Form.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto, formatoCantidad } from '@/utils/numero'

	export default {
		name: "Conversion",
		mixins: [Accion],
		data: () => ({
			apertura: 0,
			simbolo: "",
			// Productos con presentaciones y su existencia en la sucursal (se recargan después de cada conversión)
			productos: []
		}),
		created() {
			this.url = "inv/conversion"
			this.autoBuscar = false
			this.inicioArray = true
			this.bform = {
				fdel: null,
				fal: null,
				sentido: null
			}

			this.getDatos()
			this.getProductos()
		},
		mounted() {
			this.modal = new Modal(this.$refs.modal)
		},
		beforeUnmount() {
			this.modal?.dispose()
		},
		methods: {
			// Rango de fechas por defecto (mes actual); luego la lista
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					let res = result.data

					this.simbolo    = res.simbolo ?? ""
					this.bform.fdel = res.fecha_inicial
					this.bform.fal  = res.fecha
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.buscar()
				})
			},
			getProductos() {
				api
				.get(`/${this.url}/get_productos`)
				.then(result => {
					this.productos = result.data.lista ?? []
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			nueva() {
				this.apertura++
				this.modal?.show()
			},
			cerrar() {
				this.modal?.hide()
			},
			actualizar(linea) {
				this.lista.unshift(linea)
				this.getProductos()
				this.cerrar()
			},
			// Se abrió (de lo grande a lo pequeño) o se armó; con la presentación más pequeña que la unidad se invierte
			abierta(i) {
				return (i.sentido === "EXPLOSION") !== (Number(i.factor) < 1)
			},
			formatoMonto,
			formatoCantidad,
			// "2026-09-17 03:45:53" → "17/09/2026 03:45"
			formatoFecha(fecha) {
				if (!fecha) {
					return ""
				}

				let [dia, hora] = String(fecha).split(" ")
				let [a, m, d] = dia.split("-")

				return `${d}/${m}/${a}` + (hora ? ` ${hora.slice(0, 5)}` : "")
			}
		},
		computed: {
			// Sucursal de la sesión: la lista y las conversiones nuevas son de esta sucursal
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			}
		},
		components: {
			PageHeader,
			Form
		}
	}
</script>
