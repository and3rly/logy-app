<template>
	<!-- ========================= Lista de cotizaciones ========================= -->
	<template v-if="!verDocumento">
		<PageHeader>
			<button type="button" class="btn btn-primary" @click="nueva">
				<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nueva cotización
			</button>
		</PageHeader>

		<!-- Indicadores del período -->
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
						<option v-for="e in estados" :key="e.id" :value="String(e.id)">{{ e.nombre }}</option>
					</select>

					<div class="input-group flex-grow-1 w-auto">
						<span class="input-group-text">
							<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
						</span>
						<input
							v-model="termino"
							type="search"
							class="form-control"
							placeholder="Buscar por número, cliente o referencia..."
							aria-label="Buscar cotizaciones"
						>
					</div>

					<span
						v-if="sucursal"
						class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
						title="Cotizaciones de esta sucursal"
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
								<th>Cliente</th>
								<th>Válida hasta</th>
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
								<td class="text-nowrap">{{ formatoFecha(i.fecha) }}</td>
								<td>
									<div class="lh-sm">
										<div class="text-body">{{ i.cliente_nombre }}</div>
										<div v-if="i.referencia" class="small text-body-secondary text-truncate" style="max-width: 18rem">{{ i.referencia }}</div>
									</div>
								</td>
								<td class="text-nowrap">
									<span :class="{ 'text-danger-emphasis': Number(i.vencida) === 1 }">{{ formatoFecha(i.valida_hasta) }}</span>
									<span v-if="Number(i.vencida) === 1" class="small text-danger-emphasis"> · vencida</span>
									<span v-else-if="porVencer(i)" class="small text-warning-emphasis"> · {{ textoDias(i.dias_restantes) }}</span>
								</td>
								<td class="text-end fw-semibold text-nowrap">{{ i.smoneda }} {{ formatoMonto(i.total) }}</td>
								<td class="text-nowrap">
									<span
										class="badge rounded-1 fw-semibold etiqueta-color"
										:style="estiloEtiqueta(etiquetaEstado(i.eestado))"
										:title="Number(i.anulado) === 1 && i.anulado_motivo ? i.anulado_motivo : null"
									>{{ i.nestado }}</span>
									<span v-if="i.venta_correlativo" class="small text-body-secondary ms-1">→ {{ i.venta_correlativo }}</span>
								</td>
								<td class="text-end pe-3 text-nowrap">
									<button type="button" class="btn btn-sm btn-link" title="Imprimir" :disabled="imprimiendo !== null" @click.stop="imprimir(i)">
										<span v-if="imprimiendo === i.id" class="spinner-border spinner-border-sm" aria-hidden="true" />
										<i v-else class="fa-solid fa-print" aria-hidden="true" />
									</button>
									<button type="button" class="btn btn-sm btn-link" title="Abrir cotización" @click.stop="abrir(i)">
										<i class="fa-solid fa-arrow-right" aria-hidden="true" />
									</button>
								</td>
							</tr>

							<tr v-if="btnBuscar">
								<td colspan="7" class="text-center text-body-secondary">
									<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
								</td>
							</tr>
							<tr v-else-if="filtrada.length === 0">
								<td colspan="7" class="text-center text-body-secondary py-4">
									{{ termino ? 'Sin resultados para la búsqueda' : 'No hay cotizaciones en el período seleccionado' }}
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</card-body>
		</card>
	</template>

	<!-- ====================== Documento de una cotización ====================== -->
	<template v-else>
		<PageHeader>
			<button type="button" class="btn btn-outline-secondary" @click="regresar">
				<i class="fa-solid fa-arrow-left me-1" aria-hidden="true" />Volver a cotizaciones
			</button>
			<template v-if="reg !== ''">
				<button type="button" class="btn btn-suave-info" :disabled="imprimiendo !== null" @click="imprimir(cotizacion)">
					<span v-if="imprimiendo !== null" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
					<i v-else class="fa-solid fa-print me-1" aria-hidden="true" />PDF
				</button>
				<button type="button" class="btn btn-suave-success" :disabled="btnEstado" title="Nueva cotización en borrador con los mismos datos y productos" @click="duplicar">
					<i class="fa-regular fa-copy me-1" aria-hidden="true" />Duplicar
				</button>
				<button v-if="anulable" type="button" class="btn btn-suave-danger" :disabled="btnEstado" @click="pedirAnular">
					<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anular
				</button>
				<button v-if="estado === 'ENVIADA'" type="button" class="btn btn-suave-warning" :disabled="btnEstado" @click="pedir('rechazar')">
					<i class="fa-solid fa-thumbs-down me-1" aria-hidden="true" />Marcar rechazada
				</button>
				<button v-if="estado === 'ENVIADA'" type="button" class="btn btn-primary" :disabled="btnEstado || vencida" :title="vencida ? 'Vencida: duplíquela para cotizar de nuevo' : null" @click="pedir('aceptar')">
					<i class="fa-solid fa-thumbs-up me-1" aria-hidden="true" />Marcar aceptada
				</button>
				<button v-if="estado === 'ACEPTADA' && Number(cotizacion?.anulado) === 0" type="button" class="btn btn-primary" :disabled="btnEstado || vencida" :title="vencida ? 'Vencida: duplíquela para cotizar de nuevo' : null" @click="pedirConvertir">
					<i class="fa-solid fa-cash-register me-1" aria-hidden="true" />Convertir en venta
				</button>
				<button v-if="estado === 'BORRADOR'" type="button" class="btn btn-primary" :disabled="btnEstado || lineas === 0" @click="pedir('enviar')">
					<i class="fa-solid fa-paper-plane me-1" aria-hidden="true" />Enviar al cliente
				</button>
			</template>
		</PageHeader>

		<!-- Avance de la cotización -->
		<card class="mb-3">
			<card-body class="py-2">
				<div class="d-flex flex-wrap align-items-center gap-3">
					<div class="d-flex align-items-center gap-2 me-auto">
						<span class="font-monospace fw-semibold fs-5 text-body">{{ cotizacion?.numero ?? 'Nueva cotización' }}</span>
						<span
							v-if="cotizacion"
							class="badge rounded-1 fw-semibold etiqueta-color"
							:style="estiloEtiqueta(etiquetaEstado(cotizacion.eestado))"
						>{{ cotizacion.nestado }}</span>
						<span v-if="vencida" class="badge rounded-1 bg-danger-subtle text-danger-emphasis">Vencida</span>
					</div>

					<ol class="list-unstyled d-flex flex-wrap gap-2 mb-0 small" aria-label="Avance de la cotización">
						<!-- Cada paso con el color de su estado: hechos con ✓, el actual resaltado, los pendientes en gris -->
						<li
							v-for="(p, n) in pasos"
							:key="p.texto"
							class="d-flex align-items-center gap-2 px-2 py-1 rounded-2 border"
							:class="p.alcanzado ? ['etiqueta-color', { 'fw-semibold border-2': p.actual }] : 'text-body-tertiary'"
							:style="p.alcanzado ? estiloEtiqueta(p.color) : null"
							:aria-current="p.actual ? 'step' : null"
						>
							<i v-if="p.alcanzado && !p.actual" class="fa-solid fa-check" aria-hidden="true" />
							<span v-else class="fw-semibold">{{ n + 1 }}</span>{{ p.texto }}
						</li>
					</ol>
				</div>

				<div v-if="cotizacion" class="small text-body-secondary mt-1">
					<i class="fa-solid fa-store me-1" aria-hidden="true" />{{ cotizacion.nsucursal }}
					· Creada el {{ formatoFecha(cotizacion.fecha, true) }} por {{ cotizacion.nusuario }}
					<template v-if="cotizacion.fecha_envio"> · Enviada el {{ formatoFecha(cotizacion.fecha_envio, true) }}</template>
					<template v-if="cotizacion.fecha_aceptacion"> · Aceptada el {{ formatoFecha(cotizacion.fecha_aceptacion, true) }}</template>
					<template v-if="cotizacion.fecha_rechazo"> · Rechazada el {{ formatoFecha(cotizacion.fecha_rechazo, true) }}</template>
					<template v-if="cotizacion.venta_correlativo"> · Venta {{ cotizacion.venta_correlativo }}</template>
				</div>
			</card-body>
		</card>

		<div v-if="Number(cotizacion?.anulado) === 1" class="alert alert-secondary small py-2">
			<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anulada el {{ formatoFecha(cotizacion.anulado_fecha, true) }}: {{ cotizacion.anulado_motivo }}
		</div>
		<div v-else-if="estado === 'ACEPTADA'" class="alert small py-2" :class="vencida ? 'alert-warning' : 'alert-success'">
			<template v-if="vencida">
				<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />La cotización venció antes de convertirse en venta: duplíquela para cotizar de nuevo.
			</template>
			<template v-else>
				<i class="fa-solid fa-circle-check me-1" aria-hidden="true" />El cliente aceptó la cotización: ya puede convertirla en venta con los precios cotizados.
			</template>
		</div>
		<div v-else-if="estado === 'CONVERTIDA'" class="alert alert-primary small py-2 d-flex flex-wrap align-items-center gap-2">
			<span class="me-auto">
				<i class="fa-solid fa-cash-register me-1" aria-hidden="true" />Convertida en la venta
				<span class="fw-semibold font-monospace">{{ cotizacion.venta_correlativo }}</span>.
			</span>
			<button
				v-if="cotizacion.venta_id"
				type="button"
				class="btn btn-sm btn-suave-info"
				:disabled="imprimiendo !== null"
				@click="imprimirTicket"
			>
				<span v-if="imprimiendo !== null" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
				<i v-else class="fa-solid fa-receipt me-1" aria-hidden="true" />Imprimir ticket
			</button>
		</div>
		<div v-else-if="estado === 'BORRADOR' && vencida" class="alert alert-warning small py-2">
			<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />La fecha de validez ya pasó: actualícela y guarde antes de enviarla.
		</div>

		<div class="row g-3 align-items-start">
			<!-- Datos de la cotización -->
			<div class="col-12 col-xl-4">
				<card>
					<card-header>Datos de la cotización</card-header>
					<card-body>
						<Form
							:key="`form-${apertura}`"
							:cotizacion="cotizacion"
							:pk="reg"
							:catalogo="catalogo"
							:moneda-defecto="monedaDefecto"
							:fecha-hoy="fechaHoy"
							:dias-defecto="diasValidez"
							:editable="editable"
							@actualizar="actualizar"
						/>
					</card-body>
				</card>
			</div>

			<!-- Productos y totales -->
			<div class="col-12 col-xl-8">
				<card>
					<card-header>Productos</card-header>
					<card-body class="p-0">
						<Detalle
							v-if="reg !== ''"
							:key="`det-${reg}-${apertura}`"
							:cotizacion-id="reg"
							:productos="catalogo.productos"
							:categorias="catalogo.categorias"
							:marcas="catalogo.marcas"
							:unidades="catalogo.unidades"
							:simbolo="cotizacion?.smoneda ?? ''"
							:editable="editable"
							:lista-precio-id="cotizacion?.lista_precio_id ?? null"
							@cotizacion="actualizarTotales"
							@lineas="lineas = $event"
							@producto-creado="productoCreado"
						/>
						<div v-else class="text-center text-body-secondary p-5">
							<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true" /></div>
							Complete los datos de la cotización y pulse <strong>Guardar</strong> para agregar productos.
						</div>
					</card-body>
				</card>
			</div>
		</div>

		<ConfirmModal
			ref="confirmar"
			:titulo="confirmacion.titulo"
			:mensaje="confirmacion.mensaje"
			:texto-confirmar="confirmacion.boton"
			variante="primary"
			@confirmar="cambiarEstado(accion)"
		/>

		<!-- Convertir en venta: serie y forma de pago -->
		<Teleport to="body">
			<div ref="modalConvertir" class="modal fade" tabindex="-1" aria-labelledby="tituloConvertirCotizacion" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content" @submit.prevent="convertir">
						<div class="modal-header py-2">
							<h2 id="tituloConvertirCotizacion" class="modal-title h3">Convertir {{ cotizacion?.numero }} en venta</h2>
							<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnEstado" @click="modalConvertir?.hide()" />
						</div>
						<div class="modal-body">
							<div class="rounded-3 border bg-body-tertiary p-3 mb-3">
								<div class="d-flex justify-content-between small text-body-secondary">
									<span>{{ cotizacion?.cliente_nombre }}</span>
									<span>NIT {{ cotizacion?.cliente_identificacion || 'CF' }}</span>
								</div>
								<div class="d-flex justify-content-between align-items-baseline mt-1">
									<span class="text-body-secondary">Total a vender</span>
									<span class="fs-4 fw-bold text-body text-nowrap">{{ cotizacion?.smoneda }} {{ formatoMonto(cotizacion?.total) }}</span>
								</div>
							</div>

							<div class="row g-2">
								<div class="col-6">
									<label for="convertirSerie" class="form-label">Serie <span class="text-danger">*</span></label>
									<select id="convertirSerie" v-model="conversion.venta_serie_id" class="form-select" required>
										<option :value="null" disabled>Seleccionar...</option>
										<option v-for="s in catalogo.series_venta" :key="s.id" :value="String(s.id)">{{ s.codigo }} · {{ s.nombre }}</option>
									</select>
								</div>
								<div class="col-6">
									<label for="convertirFormaPago" class="form-label">Forma de pago <span class="text-danger">*</span></label>
									<select id="convertirFormaPago" v-model="conversion.forma_pago_id" class="form-select" required>
										<option :value="null" disabled>Seleccionar...</option>
										<option
											v-for="f in catalogo.formas_pago.filter(e => Number(e.activo) === 1)"
											:key="f.id"
											:value="String(f.id)"
										>{{ f.nombre }}</option>
									</select>
								</div>
							</div>

							<ul class="small text-body-secondary mt-3 mb-0 ps-3">
								<li>Se venden los productos con los precios y descuentos cotizados.</li>
								<li>Se descuentan del inventario de la sucursal; si alguno no alcanza, no se registra la venta.</li>
								<li>Al crédito genera la cuenta por cobrar (el cliente debe tener crédito autorizado).</li>
							</ul>
						</div>
						<div class="modal-footer py-2">
							<button type="button" class="btn btn-outline-secondary" :disabled="btnEstado" @click="modalConvertir?.hide()">Cancelar</button>
							<button type="submit" class="btn btn-primary" :disabled="btnEstado">
								<span v-if="btnEstado" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
								<i v-else class="fa-solid fa-cash-register me-1" aria-hidden="true" />Registrar venta
							</button>
						</div>
					</form>
				</div>
			</div>
		</Teleport>

		<!-- Anular: pide el motivo -->
		<Teleport to="body">
			<div ref="modalAnular" class="modal fade" tabindex="-1" aria-labelledby="tituloAnularCotizacion" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content" @submit.prevent="anular">
						<div class="modal-header py-2">
							<h2 id="tituloAnularCotizacion" class="modal-title h3">Anular cotización {{ cotizacion?.numero }}</h2>
							<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnEstado" @click="modalAnular?.hide()" />
						</div>
						<div class="modal-body">
							<p class="mb-2">La cotización quedará anulada y ya no se podrá enviar ni aceptar. Esta acción no se puede deshacer.</p>
							<label for="anularMotivoCot" class="form-label">Motivo *</label>
							<textarea
								id="anularMotivoCot"
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
	import { imprimirPdf } from '@/utils/imprimir'
	import Form from './Form.vue'
	import Detalle from './Detalle.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto } from '@/utils/numero'

	// Estados que siguen abiertos (el cliente todavía puede comprar)
	const ABIERTOS = ["BORRADOR", "ENVIADA", "ACEPTADA"]

	export default {
		name: "Cotizacion",
		mixins: [Accion],
		data: () => ({
			cotizacion: null,
			// id de la cotización que se está preparando para imprimir
			imprimiendo: null,
			verDocumento: false,
			apertura: 0,
			catalogo: {
				series: [],
				series_venta: [],
				formas_pago: [],
				monedas: [],
				estados: [],
				categorias: [],
				marcas: [],
				unidades: [],
				productos: [],
				listas_precio: [],
				municipios: [],
				departamentos: []
			},
			monedaDefecto: null,
			fechaHoy: "",
			diasValidez: 15,
			accion: null,
			btnEstado: false,
			lineas: 0,
			motivo: "",
			errorMotivo: "",
			modalAnular: null,
			modalConvertir: null,
			conversion: {
				venta_serie_id: null,
				forma_pago_id: null
			}
		}),
		created() {
			this.url = "ven/cotizacion"
			this.autoBuscar = false
			this.inicioArray = true
			this.bform = {
				fdel: null,
				fal: null,
				estado: null
			}

			this.getDatos()
		},
		beforeUnmount() {
			this.modalAnular?.dispose()
			this.modalConvertir?.dispose()
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
					this.fechaHoy      = res.fecha
					this.diasValidez   = Number(res.dias_validez ?? 15)
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
				this.cotizacion = null
				this.reg        = ""
				this.lineas     = 0
				this.apertura++
				this.verDocumento = true
			},
			abrir(obj) {
				this.cotizacion = obj
				this.lineas     = 0
				this.setDataForm(obj)
				this.apertura++
				this.verDocumento = true
				this.$nextTick(() => this.prepararModal())
			},
			// Los modales de anular y convertir viven dentro del documento: se crean al abrirlo
			prepararModal() {
				if (this.$refs.modalAnular && !this.modalAnular) {
					this.modalAnular = new Modal(this.$refs.modalAnular)
					this.$refs.modalAnular.addEventListener("shown.bs.modal", () => this.$refs.motivo?.focus())
				}

				if (this.$refs.modalConvertir && !this.modalConvertir) {
					this.modalConvertir = new Modal(this.$refs.modalConvertir)
				}
			},
			regresar() {
				this.modalAnular?.dispose()
				this.modalConvertir?.dispose()
				this.modalAnular    = null
				this.modalConvertir = null
				this.verDocumento = false
				this.cotizacion   = null
				this.reg          = ""
			},
			// Muestra el PDF en el visor del navegador sobre la pantalla actual
			imprimir(obj) {
				this.imprimiendo = obj.id

				imprimirPdf(`/${this.url}/imprimir/${obj.id}`)
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudo generar la cotización."))
				})
				.finally(() => {
					this.imprimiendo = null
				})
			},
			// Encabezado guardado: si es nueva, queda abierta para agregar productos
			actualizar(reg) {
				let nueva = this.reg === ""

				this.setDataRegistro("cotizacion", reg)

				if (nueva) {
					this.cotizacion = this.lista.find(e => String(e.id) === String(reg.id)) ?? reg
					this.reg        = String(reg.id)
					this.$nextTick(() => this.prepararModal())
				}
			},
			// Totales que devuelve la API al cambiar una línea (también se reflejan en la lista)
			actualizarTotales(info) {
				if (this.cotizacion && info) {
					Object.assign(this.cotizacion, info)
				}
			},
			// Un producto creado desde la cotización queda disponible en el catálogo
			productoCreado(producto) {
				this.catalogo.productos.push(producto)
				this.catalogo.productos.sort((a, b) => a.nombre.localeCompare(b.nombre, "es"))
			},
			pedir(accion) {
				this.accion = accion
				this.$refs.confirmar?.abrir()
			},
			pedirAnular() {
				this.prepararModal()
				this.motivo = ""
				this.errorMotivo = ""
				this.modalAnular?.show()
			},
			anular() {
				if (!this.motivo.trim()) {
					this.errorMotivo = "Indique el motivo de la anulación."
					return
				}

				this.cambiarEstado("anular", this.motivo.trim())
			},
			cambiarEstado(accion, motivo = "") {
				this.$refs.confirmar?.cerrar()
				this.btnEstado = true

				api
				.post(`/${this.url}/cambio_estado/${this.reg}`, { accion, motivo })
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(this.cotizacion, res.linea)
						this.modalAnular?.hide()
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
			// Propone la primera serie y la forma de pago de la cotización (si sigue activa)
			pedirConvertir() {
				let activa = this.catalogo.formas_pago.find(f =>
					Number(f.activo) === 1 && String(f.id) === String(this.cotizacion?.forma_pago_id)
				)

				this.conversion = {
					venta_serie_id: this.catalogo.series_venta.length ? String(this.catalogo.series_venta[0].id) : null,
					forma_pago_id: activa ? String(activa.id) : null
				}

				this.prepararModal()
				this.modalConvertir?.show()
			},
			convertir() {
				this.btnEstado = true

				api
				.post(`/${this.url}/convertir/${this.reg}`, this.conversion)
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(this.cotizacion, res.linea)
						this.catalogo.productos = res.productos ?? this.catalogo.productos
						this.modalConvertir?.hide()
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
			// Ticket de 80 mm de la venta generada
			imprimirTicket() {
				this.imprimiendo = this.cotizacion.venta_id

				imprimirPdf(`/ven/venta/imprimir/${this.cotizacion.venta_id}`)
				.catch(e => {
					this.$toast.error(mensajeError(e, "No se pudo generar el ticket."))
				})
				.finally(() => {
					this.imprimiendo = null
				})
			},
			// La copia se abre en borrador para ajustarla
			duplicar() {
				this.btnEstado = true

				api
				.post(`/${this.url}/duplicar/${this.reg}`)
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.lista.unshift(res.linea)
						this.$toast.success(res.mensaje)
						this.abrir(this.lista[0])
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
			// Abierta y a 3 días o menos de vencer
			porVencer(i) {
				return ABIERTOS.includes(i.cestado) && Number(i.anulado) === 0 && Number(i.vencida) === 0 && Number(i.dias_restantes) <= 3
			},
			textoDias(dias) {
				dias = Number(dias)

				if (dias === 0) {
					return "vence hoy"
				}

				return dias === 1 ? "1 día" : `${dias} días`
			},
			// cotizacion_estado.etiqueta viene como "badge bg-warning"; el color es lo que va después de "bg-"
			etiquetaEstado(etiqueta) {
				return String(etiqueta ?? "").split("bg-").pop().trim()
			},
			formatoMonto,
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
			// Sucursal de la sesión: la lista y las cotizaciones nuevas son de esta sucursal
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			estados() {
				return [...this.catalogo.estados].sort((a, b) => Number(a.orden) - Number(b.orden))
			},
			estado() {
				return this.cotizacion?.cestado ?? "BORRADOR"
			},
			editable() {
				return !this.cotizacion || (this.estado === "BORRADOR" && Number(this.cotizacion.anulado) === 0)
			},
			anulable() {
				return this.cotizacion && ABIERTOS.includes(this.estado) && Number(this.cotizacion.anulado) === 0
			},
			vencida() {
				return Number(this.cotizacion?.vencida) === 1
			},
			// Borrador → Enviada → Aceptada/Rechazada → Convertida
			pasos() {
				let nivel = {
					BORRADOR: 0,
					ENVIADA: 1,
					ACEPTADA: 2,
					RECHAZADA: 2,
					CONVERTIDA: 3
				}[this.estado] ?? -1

				let rechazada = this.estado === "RECHAZADA"
				let pasos = [
					{ texto: "Borrador", codigo: "BORRADOR" },
					{ texto: "Enviada", codigo: "ENVIADA" },
					rechazada ? { texto: "Rechazada", codigo: "RECHAZADA" } : { texto: "Aceptada", codigo: "ACEPTADA" },
					{ texto: "Convertida en venta", codigo: "CONVERTIDA" }
				]

				// El color de cada paso es la etiqueta de su estado (ej. "badge bg-warning")
				return pasos.map((p, n) => {
					let estado = this.catalogo.estados.find(e => e.codigo === p.codigo)

					return {
						texto: p.texto,
						color: this.etiquetaEstado(estado?.etiqueta),
						alcanzado: n <= nivel,
						actual: n === nivel
					}
				})
			},
			confirmacion() {
				let numero = this.cotizacion?.numero ?? ""

				switch (this.accion) {
					case "enviar":
						return {
							titulo: "Enviar al cliente",
							mensaje: `La cotización ${numero} queda enviada y ya no se podrá modificar. Si necesita cambios, duplíquela.`,
							boton: "Enviar"
						}
					case "aceptar":
						return {
							titulo: "Marcar aceptada",
							mensaje: `Se registrará que el cliente aceptó la cotización ${numero}.`,
							boton: "Marcar aceptada"
						}
					default:
						return {
							titulo: "Marcar rechazada",
							mensaje: `Se registrará que el cliente rechazó la cotización ${numero}.`,
							boton: "Marcar rechazada"
						}
				}
			},
			indicadores() {
				let abiertas = this.lista.filter(i => ABIERTOS.includes(i.cestado) && Number(i.anulado) === 0 && Number(i.vencida) === 0)
				let porVencer = abiertas.filter(i => this.porVencer(i))
				let ganadas = this.lista.filter(i => ["ACEPTADA", "CONVERTIDA"].includes(i.cestado)).length
				let decididas = ganadas + this.lista.filter(i => i.cestado === "RECHAZADA").length
				let simbolo = this.lista[0]?.smoneda ?? ""

				return [
					{
						titulo: "Abiertas",
						icono: "fa-folder-open",
						color: "text-primary",
						valor: abiertas.length
					},
					{
						titulo: "Por vencer (3 días)",
						icono: "fa-hourglass-half",
						color: "text-warning",
						valor: porVencer.length,
						valorColor: porVencer.length ? "text-warning-emphasis" : null
					},
					{
						titulo: "Monto en cotización",
						icono: "fa-sack-dollar",
						color: "text-primary",
						valor: `${simbolo} ${this.formatoMonto(abiertas.reduce((s, i) => s + Number(i.total), 0))}`
					},
					{
						titulo: "Tasa de cierre",
						icono: "fa-handshake",
						color: "text-success",
						valor: decididas ? `${Math.round(ganadas * 100 / decididas)} %` : "—"
					}
				]
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
