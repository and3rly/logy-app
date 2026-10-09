<template>
	<!-- ============================ Lista de compras ============================ -->
	<template v-if="!verDocumento">
		<PageHeader>
			<button type="button" class="btn btn-primary" @click="nueva">
				<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nueva compra
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

					<select v-model="bform.estado" class="form-select w-auto" aria-label="Estado" @change="buscar">
						<option :value="null">Todos los estados</option>
						<option v-for="e in catalogo.estados" :key="e.id" :value="String(e.id)">{{ e.nombre }}</option>
					</select>

					<div class="input-group flex-grow-1 w-auto">
						<span class="input-group-text">
							<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
						</span>
						<input
							v-model="termino"
							type="search"
							class="form-control"
							placeholder="Buscar por número, proveedor o factura..."
							aria-label="Buscar compras"
						>
					</div>

					<!-- Las OC se manejan por sucursal: la lista es solo de la sucursal de la sesión -->
					<span
						v-if="sucursal"
						class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
						title="Órdenes de compra de esta sucursal"
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
								<th>Proveedor</th>
								<th>Factura</th>
								<th>Forma de pago</th>
								<th class="text-end">Total</th>
								<th>Estado</th>
								<th class="text-end pe-3">Acciones</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="i in filtrada"
								:key="i.id"
								class="align-middle"
								role="button"
								@click="abrir(i)"
							>
								<td class="ps-3 fw-semibold font-monospace text-body">{{ i.numero }}</td>
								<td class="text-nowrap">{{ formatoFecha(i.fecha, true) }}</td>
								<td>
									<div class="lh-sm">
										<div class="text-body">{{ i.nproveedor }}</div>
										<div v-if="i.nit_proveedor" class="small text-body-secondary">NIT {{ i.nit_proveedor }}</div>
									</div>
								</td>
								<td>
									<div v-if="i.factura_numero" class="lh-sm">
										<div>{{ i.factura_numero }}</div>
										<div v-if="i.factura_fecha" class="small text-body-secondary">{{ formatoFecha(i.factura_fecha) }}</div>
									</div>
									<span v-else class="text-body-secondary">—</span>
								</td>
								<td>{{ i.nforma_pago }}</td>
								<td class="text-end fw-semibold text-nowrap">{{ i.smoneda }} {{ formatoMonto(i.total_costo) }}</td>
								<td>
									<span
										class="badge rounded-1 fw-semibold etiqueta-color"
										:style="estiloEtiqueta(i.eestado)"
									>{{ i.nestado }}</span>
								</td>
								<td class="text-end pe-3 text-nowrap">
									<button type="button" class="btn btn-sm btn-link" title="Imprimir" :disabled="imprimiendo !== null" @click.stop="imprimir(i)">
										<span v-if="imprimiendo === i.id" class="spinner-border spinner-border-sm" aria-hidden="true" />
										<i v-else class="fa-solid fa-print" aria-hidden="true" />
									</button>
									<button type="button" class="btn btn-sm btn-link" title="Abrir compra" @click.stop="abrir(i)">
										<i class="fa-solid fa-arrow-right" aria-hidden="true" />
									</button>
								</td>
							</tr>

							<tr v-if="btnBuscar">
								<td colspan="8" class="text-center text-body-secondary">
									<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
								</td>
							</tr>
							<tr v-else-if="filtrada.length === 0">
								<td colspan="8" class="text-center text-body-secondary py-4">
									{{ termino ? 'Sin resultados para la búsqueda' : 'No hay compras en el período seleccionado' }}
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</card-body>
		</card>
	</template>

	<!-- ========================= Documento de una compra ======================== -->
	<template v-else>
		<PageHeader>
			<button type="button" class="btn btn-outline-secondary" @click="regresar">
				<i class="fa-solid fa-arrow-left me-1" aria-hidden="true" />Volver a compras
			</button>
			<button v-if="reg !== ''" type="button" class="btn btn-suave-info" :disabled="imprimiendo !== null" @click="imprimir(compra)">
				<span v-if="imprimiendo !== null" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
				<i v-else class="fa-solid fa-print me-1" aria-hidden="true" />Imprimir
			</button>
		</PageHeader>

		<!-- Dos columnas: a la izquierda los datos (arriba) y los productos (abajo); a la derecha el resumen -->
		<div class="row g-3 align-items-start">
			<div class="col-12 col-xl-8 d-flex flex-column gap-3">
				<!-- Datos de la compra -->
				<card>
					<card-header class="flex-wrap">
						<span v-if="reg === ''">Nueva compra</span>
						<template v-else>
							<span class="font-monospace">{{ compra.numero }}</span>
							<span
								class="badge rounded-1 fw-semibold etiqueta-color"
								:style="estiloEtiqueta(compra.eestado)"
							>{{ compra.nestado }}</span>
							<span class="ms-auto small fw-normal text-body-secondary">
								Creada el {{ formatoFecha(compra.fecha, true) }}
								<template v-if="Number(compra.anulado) === 1 && compra.fecha_anulado"> · Anulada el {{ formatoFecha(compra.fecha_anulado, true) }}</template>
							</span>
						</template>
					</card-header>
					<card-body>
						<Form
							:key="`form-${apertura}`"
							:compra="compra"
							:pk="reg"
							:catalogo="catalogo"
							:moneda-defecto="monedaDefecto"
							:editable="editable"
							@actualizar="actualizar"
							@cancelar="regresar"
						/>
					</card-body>
				</card>

				<!-- Productos de la orden de compra -->
				<card>
					<card-header>Productos</card-header>
					<card-body class="p-0">
						<Detalle
							v-if="reg !== ''"
							:key="`det-${reg}-${apertura}`"
							:compra-id="reg"
							:productos="catalogo.productos"
							:categorias="catalogo.categorias"
							:marcas="catalogo.marcas"
							:unidades="catalogo.unidades"
							:presentaciones="catalogo.presentaciones"
							:simbolo="compra?.smoneda ?? ''"
							:editable="editable"
							@resumen="actualizarResumen"
							@producto-creado="productoCreado"
						/>
						<div v-else class="text-center text-body-secondary p-5">
							<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-file-invoice" aria-hidden="true" /></div>
							Complete los datos de la compra y pulse <strong>Guardar</strong> para agregar productos.
						</div>
					</card-body>
				</card>
			</div>

			<!-- Resumen: acompaña al desplazarse por los productos -->
			<div class="col-12 col-xl-4 position-sticky" style="top: 5rem">
				<card>
					<card-header>Resumen</card-header>
					<card-body>
						<!-- Total destacado -->
						<div class="rounded-3 border bg-body-tertiary p-3 mb-3">
							<div class="d-flex justify-content-between align-items-center small text-body-secondary mb-1">
								<span>Total de la compra</span>
								<span v-if="compra" class="font-monospace">{{ compra.cmoneda }}</span>
							</div>
							<div class="fs-2 fw-bold text-body lh-sm text-nowrap">
								{{ compra?.smoneda ?? '' }} {{ formatoMonto(resumen.total) }}
							</div>
						</div>

						<!-- Conteos en dos mosaicos -->
						<div class="row g-2 mb-3">
							<div class="col-6">
								<div class="rounded-3 border px-3 py-2 h-100">
									<div class="small text-body-secondary">
										<i class="fa-solid fa-boxes-stacked me-1" aria-hidden="true" />Productos
									</div>
									<div class="fs-5 fw-semibold text-body">{{ resumen.lineas }}</div>
								</div>
							</div>
							<div class="col-6">
								<div class="rounded-3 border px-3 py-2 h-100">
									<div class="small text-body-secondary">
										<i class="fa-solid fa-cubes me-1" aria-hidden="true" />Unidades
									</div>
									<div class="fs-5 fw-semibold text-body">{{ formatoCantidad(resumen.unidades) }}</div>
								</div>
							</div>
						</div>

						<!-- Datos clave con iconos -->
						<ul v-if="compra" class="list-unstyled small mb-0">
							<li class="d-flex align-items-center gap-2 py-2 border-bottom">
								<i class="fa-solid fa-store fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Sucursal</span>
								<span class="ms-auto fw-semibold text-body text-truncate" :title="compra.nsucursal">{{ compra.nsucursal }}</span>
							</li>
							<li class="d-flex align-items-center gap-2 py-2 border-bottom">
								<i class="fa-solid fa-truck fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Proveedor</span>
								<span class="ms-auto fw-semibold text-body text-truncate" :title="compra.nproveedor">{{ compra.nproveedor }}</span>
							</li>
							<li class="d-flex align-items-center gap-2 py-2">
								<i class="fa-solid fa-credit-card fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Forma de pago</span>
								<span class="ms-auto fw-semibold text-body">{{ compra.nforma_pago }}</span>
							</li>
						</ul>

						<!-- Acciones: recibir es la principal; anular, ocasional y discreta -->
						<div v-if="reg !== '' && editable" class="mt-3">
							<button
								type="button"
								class="btn btn-primary w-100"
								:disabled="btnEstado || resumen.lineas === 0"
								@click="pedirRecibir"
							>
								<i class="fa-solid fa-box-open me-1" aria-hidden="true" />Recibir compra
							</button>
							<div class="form-text text-center">
								{{ resumen.lineas === 0 ? 'Agregue productos para poder recibirla.' : 'Suma los productos al inventario de la sucursal.' }}
							</div>
							<div class="mt-3">
								<button
									type="button"
									class="btn btn-suave-danger w-100"
									:disabled="btnEstado"
									@click="pedirAnular"
								>
									<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anular compra
								</button>
							</div>
						</div>

						<div
							v-else-if="compra && Number(compra.compra_estado_id) === 2"
							class="alert alert-success small py-2 mb-0 mt-3"
						>
							<i class="fa-solid fa-circle-check me-1" aria-hidden="true" />Recibida: los productos ya están en inventario.
						</div>
						<div
							v-else-if="compra && Number(compra.anulado) === 1"
							class="alert alert-secondary small py-2 mb-0 mt-3"
						>
							<i class="fa-solid fa-ban me-1" aria-hidden="true" />Compra anulada.
						</div>
					</card-body>
				</card>
			</div>
		</div>

		<ConfirmModal
			ref="confirmar"
			:titulo="accion === 'recibir' ? 'Recibir compra' : 'Anular compra'"
			:mensaje="mensajeConfirmar"
			:texto-confirmar="accion === 'recibir' ? 'Recibir' : 'Anular'"
			:variante="accion === 'recibir' ? 'primary' : 'danger'"
			@confirmar="cambiarEstado"
		/>
	</template>

</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import ConfirmModal from '../../../components/ui/ConfirmModal.vue'
	import { imprimirPdf } from '@/utils/imprimir'
	import Form from './Form.vue'
	import Detalle from './Detalle.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto, formatoCantidad } from '@/utils/numero'

	const RECIBIDA = 2
	const ANULADA = 3

	export default {
		name: "Compra",
		mixins: [Accion],
		data: () => ({
			compra: null,
			// id de la compra que se está preparando para imprimir
			imprimiendo: null,
			verDocumento: false,
			apertura: 0,
			catalogo: {
				proveedores: [],
				formas_pago: [],
				monedas: [],
				estados: [],
				productos: [],
				categorias: [],
				marcas: [],
				unidades: [],
				presentaciones: []
			},
			monedaDefecto: null,
			accion: null,
			btnEstado: false,
			resumen: {
				total: 0,
				lineas: 0,
				unidades: 0
			}
		}),
		created() {
			this.url = "com/compra"
			this.autoBuscar = false
			this.inicioArray = true
			this.bform = {
				fdel: null,
				fal: null,
				estado: null
			}

			this.getDatos()
		},
		methods: {
			// Catálogos y rango de fechas por defecto (mes actual); luego la lista
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					let res = result.data

					this.catalogo      = { ...this.catalogo, ...(res.cat ?? {}) }
					this.monedaDefecto = res.moneda_id ?? null
					this.bform.fdel    = res.fecha_inicial
					this.bform.fal     = res.fecha
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.buscar()
				})
			},
			nueva() {
				this.resumen = { total: 0, lineas: 0, unidades: 0 }
				this.compra = null
				this.reg    = ""
				this.apertura++
				this.verDocumento = true
			},
			abrir(obj) {
				// El detalle confirma líneas y unidades al cargar
				this.resumen = { total: Number(obj.total_costo), lineas: 0, unidades: 0 }
				this.compra = obj
				this.setDataForm(obj)
				this.apertura++
				this.verDocumento = true
			},
			// Muestra el PDF en el visor del navegador sobre la pantalla actual (sin cambiar de vista)
			imprimir(obj) {
				this.imprimiendo = obj.id

				imprimirPdf(`/${this.url}/imprimir/${obj.id}`)
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudo generar el documento."))
				})
				.finally(() => {
					this.imprimiendo = null
				})
			},
			regresar() {
				this.verDocumento = false
				this.compra = null
				this.reg    = ""
			},
			// Los datos se guardaron: si es nueva, queda abierta para agregar productos
			actualizar(reg) {
				let nueva = this.reg === ""

				this.setDataRegistro("compra", reg)

				if (nueva) {
					this.compra = this.lista.find(e => String(e.id) === String(reg.id)) ?? reg
					this.reg    = String(reg.id)
				}
			},
			// Total, líneas y unidades que informa el detalle; el total también se refleja en la lista
			// Un producto creado desde la compra queda disponible en el catálogo
			productoCreado(producto) {
				this.catalogo.productos.push(producto)
				this.catalogo.productos.sort((a, b) => a.nombre.localeCompare(b.nombre, "es"))
			},
			actualizarResumen(resumen) {
				this.resumen = resumen

				if (this.compra) {
					this.compra.total_costo = resumen.total
				}
			},
			pedirRecibir() {
				this.accion = "recibir"
				this.$refs.confirmar?.abrir()
			},
			pedirAnular() {
				this.accion = "anular"
				this.$refs.confirmar?.abrir()
			},
			cambiarEstado() {
				this.$refs.confirmar?.cerrar()
				this.btnEstado = true

				api
				.post(`/${this.url}/cambio_estado/${this.reg}`, {
					compra_estado_id: this.accion === "recibir" ? RECIBIDA : ANULADA
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(this.compra, res.compra)
						this.apertura++
						this.$toast.success(res.mensaje)
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnEstado = false
				})
			},
			formatoMonto,
			formatoCantidad,
			// "2026-09-17 03:45:53" → "17/09/2026 03:45"; "2026-09-16" → "16/09/2026"
			formatoFecha(fecha, conHora) {
				if (!fecha) {
					return ""
				}

				let [dia, hora] = String(fecha).split(" ")
				let [a, m, d] = dia.split("-")

				return `${d}/${m}/${a}` + (conHora && hora ? ` ${hora.slice(0, 5)}` : "")
			},
			estiloEtiqueta
		},
		computed: {
			// Sucursal de la sesión: la lista y las OC nuevas son de esta sucursal
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			editable() {
				return !this.compra || (Number(this.compra.compra_estado_id) === 1 && Number(this.compra.anulado) === 0)
			},
			mensajeConfirmar() {
				if (this.accion === "recibir") {
					return `Los productos se sumarán al inventario de la sucursal ${this.compra?.nsucursal ?? ""} y, si la compra es al crédito, se generará la cuenta por pagar. Después ya no se podrá modificar.`
				}

				return "La compra quedará anulada y ya no se podrá modificar ni recibir."
			}
		},
		components: {
			PageHeader,
			ConfirmModal,
			Form,
			Detalle
		}
	}
</script>
