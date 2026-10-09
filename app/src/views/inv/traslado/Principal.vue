<template>
	<!-- =========================== Lista de traslados =========================== -->
	<template v-if="!verDocumento">
		<PageHeader>
			<button type="button" class="btn btn-primary" @click="nuevo">
				<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nuevo traslado
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

					<select v-model="bform.vista" class="form-select w-auto" aria-label="Dirección" @change="buscar">
						<option :value="null">Enviados y recibidos</option>
						<option value="enviados">Solo enviados</option>
						<option value="recibidos">Solo los que llegan</option>
					</select>

					<div class="input-group flex-grow-1 w-auto">
						<span class="input-group-text">
							<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
						</span>
						<input
							v-model="termino"
							type="search"
							class="form-control"
							placeholder="Buscar por número, sucursal u observación..."
							aria-label="Buscar traslados"
						>
					</div>

					<!-- La lista es de la sucursal de la sesión: lo que envía y lo que le llega -->
					<span
						v-if="sucursal"
						class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
						title="Traslados de esta sucursal"
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
								<th>Origen → destino</th>
								<th>Observación</th>
								<th class="text-end">Productos</th>
								<th class="text-end">Valor</th>
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
								<td class="text-nowrap">
									<i
										class="fa-solid fa-fw me-1"
										:class="i.direccion === 'ENVIO' ? 'fa-arrow-up text-danger' : 'fa-arrow-down text-success'"
										:title="i.direccion === 'ENVIO' ? 'Sale de esta sucursal' : 'Llega a esta sucursal'"
										aria-hidden="true"
									/>{{ i.nsucursal }} → {{ i.nsucursal_destino }}
								</td>
								<td class="text-truncate text-body-secondary" style="max-width: 16rem" :title="i.observacion">{{ i.observacion || '—' }}</td>
								<td class="text-end">{{ i.lineas }}</td>
								<td class="text-end fw-semibold text-nowrap">{{ simbolo }} {{ formatoMonto(i.valor) }}</td>
								<td>
									<span
										class="badge rounded-1 fw-semibold etiqueta-color"
										:style="estiloEtiqueta(i.eestado)"
									>{{ porRecibir(i) ? 'Por recibir' : i.nestado }}</span>
								</td>
								<td class="text-end pe-3 text-nowrap">
									<button type="button" class="btn btn-sm btn-link" title="Abrir traslado" @click.stop="abrir(i)">
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
									{{ termino ? 'Sin resultados para la búsqueda' : 'No hay traslados en el período seleccionado' }}
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</card-body>
		</card>
	</template>

	<!-- ======================== Documento de un traslado ======================== -->
	<template v-else>
		<PageHeader>
			<button type="button" class="btn btn-outline-secondary" @click="regresar">
				<i class="fa-solid fa-arrow-left me-1" aria-hidden="true" />Volver a traslados
			</button>
		</PageHeader>

		<!-- Dos columnas: a la izquierda los datos (arriba) y los productos (abajo); a la derecha el resumen -->
		<div class="row g-3 align-items-start">
			<div class="col-12 col-xl-8 d-flex flex-column gap-3">
				<card>
					<card-header class="flex-wrap">
						<span v-if="reg === ''">Nuevo traslado</span>
						<template v-else>
							<span class="font-monospace">{{ traslado.numero }}</span>
							<span
								class="badge rounded-1 fw-semibold etiqueta-color"
								:style="estiloEtiqueta(traslado.eestado)"
							>{{ porRecibir(traslado) ? 'Por recibir' : traslado.nestado }}</span>
							<span class="ms-auto small fw-normal text-body-secondary">
								Creado el {{ formatoFecha(traslado.fecha, true) }} por {{ traslado.nusuario }}
							</span>
						</template>
					</card-header>
					<card-body>
						<Form
							:key="`form-${apertura}`"
							:traslado="traslado"
							:pk="reg"
							:sucursales="catalogo.sucursales"
							:sucursal-id="sucursalId"
							:editable="editable"
							@actualizar="actualizar"
							@cancelar="regresar"
						/>
					</card-body>
				</card>

				<card>
					<card-header>Productos</card-header>
					<card-body class="p-0">
						<Detalle
							v-if="reg !== ''"
							:key="`det-${reg}-${apertura}`"
							:documento-id="reg"
							documento="traslado"
							url-documento="inv/traslado"
							url-detalle="inv/traslado_detalle"
							campo-documento="inventario_traslado_id"
							sentido="SALIDA"
							:productos="productos"
							:categorias="catalogo.categorias"
							:editable="editable"
							@resumen="actualizarResumen"
						/>
						<div v-else class="text-center text-body-secondary p-5">
							<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-truck" aria-hidden="true" /></div>
							Elija la sucursal destino y pulse <strong>Guardar</strong> para agregar productos.
						</div>
					</card-body>
				</card>
			</div>

			<!-- Resumen: acompaña al desplazarse por los productos -->
			<div class="col-12 col-xl-4 position-sticky" style="top: 5rem">
				<card>
					<card-header>Resumen</card-header>
					<card-body>
						<!-- Valor destacado: sale (rojo) si es de esta sucursal, entra (verde) si llega a ella -->
						<div
							class="rounded-3 border p-3 mb-3"
							:class="llega ? 'bg-success-subtle border-success-subtle' : 'bg-danger-subtle border-danger-subtle'"
						>
							<div class="small mb-1" :class="llega ? 'text-success-emphasis' : 'text-danger-emphasis'">
								<i class="fa-solid me-1" :class="llega ? 'fa-arrow-down' : 'fa-arrow-up'" aria-hidden="true" />
								{{ llega ? 'Llega a esta sucursal' : 'Sale de esta sucursal' }}
							</div>
							<div class="fs-2 fw-bold lh-sm text-nowrap" :class="llega ? 'text-success-emphasis' : 'text-danger-emphasis'">
								{{ simbolo }} {{ formatoMonto(resumen.valor) }}
							</div>
							<div v-if="traslado && !traslado.fecha_enviado" class="small text-body-secondary mt-1">Al costo actual; se congela al enviar</div>
						</div>

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

						<ul v-if="traslado" class="list-unstyled small mb-0">
							<li class="d-flex align-items-center gap-2 py-2 border-bottom">
								<i class="fa-solid fa-store fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Origen</span>
								<span class="ms-auto fw-semibold text-body text-truncate" :title="traslado.nsucursal">{{ traslado.nsucursal }}</span>
							</li>
							<li class="d-flex align-items-center gap-2 py-2" :class="{ 'border-bottom': traslado.fecha_enviado || traslado.fecha_anulado }">
								<i class="fa-solid fa-location-dot fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Destino</span>
								<span class="ms-auto fw-semibold text-body text-truncate" :title="traslado.nsucursal_destino">{{ traslado.nsucursal_destino }}</span>
							</li>
							<li v-if="traslado.fecha_enviado" class="d-flex align-items-center gap-2 py-2" :class="{ 'border-bottom': traslado.fecha_recibido || traslado.fecha_anulado }">
								<i class="fa-solid fa-truck fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Enviado</span>
								<span class="ms-auto text-body text-end">{{ formatoFecha(traslado.fecha_enviado, true) }} · {{ traslado.nusuario_envio }}</span>
							</li>
							<li v-if="traslado.fecha_recibido" class="d-flex align-items-center gap-2 py-2">
								<i class="fa-solid fa-circle-check fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Recibido</span>
								<span class="ms-auto text-body text-end">{{ formatoFecha(traslado.fecha_recibido, true) }} · {{ traslado.nusuario_recibio }}</span>
							</li>
							<li v-if="traslado.fecha_anulado" class="d-flex align-items-center gap-2 py-2">
								<i class="fa-solid fa-ban fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Anulado</span>
								<span class="ms-auto text-body text-end">{{ formatoFecha(traslado.fecha_anulado, true) }} · {{ traslado.nusuario_anulo }}</span>
							</li>
						</ul>

						<!-- Acciones: enviar (origen) o recibir (destino) es la principal; anular, ocasional y discreta -->
						<div v-if="reg !== '' && editable" class="mt-3">
							<button
								type="button"
								class="btn btn-primary w-100"
								:disabled="btnEstado || resumen.lineas === 0 || resumen.sinExistencia > 0"
								@click="pedir('enviar')"
							>
								<i class="fa-solid fa-truck me-1" aria-hidden="true" />Enviar traslado
							</button>
							<div class="form-text text-center" :class="{ 'text-danger': resumen.sinExistencia > 0 }">
								{{ textoEnviar }}
							</div>
						</div>

						<div v-if="porRecibir(traslado)" class="mt-3">
							<button
								type="button"
								class="btn btn-primary w-100"
								:disabled="btnEstado"
								@click="pedir('recibir')"
							>
								<i class="fa-solid fa-check me-1" aria-hidden="true" />Recibir traslado
							</button>
							<div class="form-text text-center">Suma los productos al inventario de esta sucursal.</div>
						</div>

						<div v-if="reg !== '' && esOrigen && (estado === BORRADOR || estado === ENVIADO)" class="mt-3">
							<button
								type="button"
								class="btn btn-suave-danger w-100"
								:disabled="btnEstado"
								@click="pedirAnular"
							>
								<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anular traslado
							</button>
						</div>

						<div v-if="estado === ENVIADO && esOrigen" class="alert alert-warning small py-2 mb-0 mt-3">
							<i class="fa-solid fa-truck me-1" aria-hidden="true" />En camino: falta que {{ traslado.nsucursal_destino }} lo reciba.
						</div>
						<div v-else-if="estado === RECIBIDO" class="alert alert-success small py-2 mb-0 mt-3">
							<i class="fa-solid fa-circle-check me-1" aria-hidden="true" />Recibido: el inventario de las dos sucursales ya refleja este traslado.
						</div>
						<div v-else-if="estado === ANULADO" class="alert alert-secondary small py-2 mb-0 mt-3">
							<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anulado<template v-if="traslado.anulado_motivo">: {{ traslado.anulado_motivo }}</template>
						</div>
					</card-body>
				</card>
			</div>
		</div>

		<ConfirmModal
			ref="confirmar"
			:titulo="accion === 'recibir' ? 'Recibir traslado' : 'Enviar traslado'"
			:mensaje="mensajeConfirmar"
			:texto-confirmar="accion === 'recibir' ? 'Recibir' : 'Enviar'"
			variante="primary"
			@confirmar="confirmar"
		/>

		<!-- Anular: pide el motivo -->
		<Teleport to="body">
			<div ref="modalAnular" class="modal fade" tabindex="-1" aria-labelledby="tituloAnularTraslado" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content" @submit.prevent="anular">
						<div class="modal-header py-2">
							<h2 id="tituloAnularTraslado" class="modal-title h3">Anular traslado {{ traslado?.numero }}</h2>
							<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnEstado" @click="modalAnular?.hide()" />
						</div>
						<div class="modal-body">
							<p class="mb-2">{{ mensajeAnular }}</p>
							<label for="trasladoMotivo" class="form-label">Motivo <span class="text-danger">*</span></label>
							<textarea
								id="trasladoMotivo"
								ref="motivo"
								v-model="motivo"
								class="form-control"
								:class="{ 'is-invalid': errorMotivo }"
								rows="2"
								maxlength="500"
								@input="errorMotivo = ''"
							/>
							<div v-if="errorMotivo" class="invalid-feedback">{{ errorMotivo }}</div>
						</div>
						<div class="modal-footer py-2">
							<button type="button" class="btn btn-outline-secondary" :disabled="btnEstado" @click="modalAnular?.hide()">Cancelar</button>
							<button type="submit" class="btn btn-danger" :disabled="btnEstado">
								<span v-if="btnEstado" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Anular
							</button>
						</div>
					</form>
				</div>
			</div>
		</Teleport>
	</template>
</template>

<script>
	import { Modal } from 'bootstrap'
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import ConfirmModal from '../../../components/ui/ConfirmModal.vue'
	import Form from './Form.vue'
	import Detalle from '../ajuste/Detalle.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto, formatoCantidad } from '@/utils/numero'

	const BORRADOR = 1
	const ENVIADO = 2
	const RECIBIDO = 3
	const ANULADO = 4

	export default {
		name: "Traslado",
		mixins: [Accion],
		data: () => ({
			traslado: null,
			verDocumento: false,
			apertura: 0,
			simbolo: "",
			catalogo: {
				estados: [],
				sucursales: [],
				categorias: []
			},
			// Productos con su existencia en la sucursal (se recargan al enviar, recibir o anular)
			productos: [],
			btnEstado: false,
			// Acción que espera confirmación: enviar o recibir
			accion: "enviar",
			motivo: "",
			errorMotivo: "",
			resumen: {
				valor: 0,
				lineas: 0,
				unidades: 0,
				sinExistencia: 0
			},
			BORRADOR,
			ENVIADO,
			RECIBIDO,
			ANULADO
		}),
		created() {
			this.url = "inv/traslado"
			this.autoBuscar = false
			this.inicioArray = true
			this.bform = {
				fdel: null,
				fal: null,
				estado: null,
				vista: null
			}

			this.getDatos()
			this.getProductos()
			this.abrirDesdeRuta()
		},
		beforeUnmount() {
			this.modalAnular?.dispose()
		},
		methods: {
			// Catálogos y rango de fechas por defecto (mes actual); luego la lista
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					let res = result.data

					this.catalogo   = { ...this.catalogo, ...(res.cat ?? {}) }
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
					this.productos = (result.data.lista ?? []).map(p => ({ ...p, id: p.producto_id }))
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			// /traslado?id=3 (desde una notificación): abre ese traslado aunque no esté en el período de la lista
			abrirDesdeRuta() {
				let id = this.$route.query.id

				if (!id) {
					return
				}

				this.$router.replace({ query: {} })

				api
				.get(`/${this.url}/get_info/${id}`)
				.then(result => {
					if (result.data.traslado) {
						this.abrir(result.data.traslado)
					} else {
						this.$toast.error("El traslado no existe o no es de esta sucursal.")
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
			},
			nuevo() {
				this.resumen  = { valor: 0, lineas: 0, unidades: 0, sinExistencia: 0 }
				this.traslado = null
				this.reg      = ""
				this.apertura++
				this.verDocumento = true
			},
			abrir(obj) {
				// El detalle confirma los totales al cargar
				this.resumen = {
					valor: Number(obj.valor),
					lineas: Number(obj.lineas),
					unidades: Number(obj.unidades),
					sinExistencia: 0
				}
				// El de la lista, si está, para que los cambios se vean también allí
				this.traslado = this.lista.find(e => String(e.id) === String(obj.id)) ?? obj
				this.setDataForm(obj)
				this.reg = String(obj.id)
				this.apertura++
				this.verDocumento = true
			},
			regresar() {
				// El modal vive dentro del documento: se crea de nuevo al volver a abrir uno
				this.modalAnular?.dispose()
				this.modalAnular = null

				this.verDocumento = false
				this.traslado = null
				this.reg      = ""
			},
			// Encabezado guardado: si es nuevo, queda abierto para agregar productos
			actualizar(reg) {
				let nuevo = this.reg === ""

				this.setDataRegistro("traslado", reg)

				if (nuevo) {
					this.traslado = this.lista.find(e => String(e.id) === String(reg.id)) ?? reg
					this.reg      = String(reg.id)
				}
			},
			// Totales que informa el detalle; también se reflejan en la lista
			actualizarResumen(resumen) {
				this.resumen = resumen

				if (this.traslado) {
					this.traslado.valor  = resumen.valor
					this.traslado.lineas = resumen.lineas
				}
			},
			// Lo envía otra sucursal y todavía no se recibe
			porRecibir(t) {
				return !!t && t.direccion === "RECEPCION" && Number(t.inventario_traslado_estado_id) === ENVIADO
			},
			pedir(accion) {
				this.accion = accion
				this.$refs.confirmar?.abrir()
			},
			confirmar() {
				this.$refs.confirmar?.cerrar()
				this.cambiarEstado(this.accion)
			},
			pedirAnular() {
				if (!this.modalAnular) {
					this.modalAnular = new Modal(this.$refs.modalAnular)
					this.$refs.modalAnular.addEventListener("shown.bs.modal", () => this.$refs.motivo?.focus())
				}

				this.motivo = ""
				this.errorMotivo = ""
				this.modalAnular.show()
			},
			anular() {
				if (!this.motivo.trim()) {
					this.errorMotivo = "Indique el motivo de la anulación."
					return
				}

				this.cambiarEstado("anular", { motivo: this.motivo.trim() })
			},
			cambiarEstado(accion, datos = {}) {
				this.btnEstado = true

				api
				.post(`/${this.url}/${accion}/${this.reg}`, datos)
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(this.traslado, res.traslado)
						this.modalAnular?.hide()
						this.apertura++
						this.getProductos()
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
			// Sucursal de la sesión: el origen de los traslados nuevos
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			sucursalId() {
				return useSesionStore().usuario?.sucursal?.id ?? null
			},
			estado() {
				return Number(this.traslado?.inventario_traslado_estado_id ?? BORRADOR)
			},
			// Un traslado nuevo siempre sale de esta sucursal
			esOrigen() {
				return !this.traslado || this.traslado.direccion === "ENVIO"
			},
			llega() {
				return !this.esOrigen
			},
			editable() {
				return this.esOrigen && this.estado === BORRADOR
			},
			textoEnviar() {
				if (this.resumen.lineas === 0) {
					return "Agregue productos para poder enviarlo."
				}

				if (this.resumen.sinExistencia > 0) {
					return "Hay productos sin existencia suficiente; corrija la cantidad o el lote."
				}

				return "Descuenta los productos del inventario de esta sucursal."
			},
			mensajeConfirmar() {
				if (this.accion === "recibir") {
					return `Los productos entrarán al inventario de ${this.traslado?.nsucursal_destino ?? ""} con el mismo vencimiento con el que salieron.`
				}

				return `Los productos se descontarán del inventario de ${this.traslado?.nsucursal ?? ""} y quedarán en camino hasta que ${this.traslado?.nsucursal_destino ?? ""} los reciba. Después ya no se podrá modificar.`
			},
			mensajeAnular() {
				if (this.estado === ENVIADO) {
					return "Se devolverá al inventario de esta sucursal lo que salió con este traslado, a los mismos lotes."
				}

				return "El traslado todavía no movió inventario; solo quedará anulado."
			}
		},
		watch: {
			// Otra notificación con la pantalla ya abierta
			"$route.query.id"(valor) {
				if (valor) {
					this.abrirDesdeRuta()
				}
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
