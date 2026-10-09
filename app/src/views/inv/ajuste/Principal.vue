<template>
	<!-- ============================ Lista de ajustes ============================ -->
	<template v-if="!verDocumento">
		<PageHeader>
			<button type="button" class="btn btn-primary" @click="nuevo">
				<i class="fa-solid fa-plus me-1" aria-hidden="true" />Nuevo ajuste
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

					<select v-model="bform.sentido" class="form-select w-auto" aria-label="Sentido" @change="buscar">
						<option :value="null">Entradas y salidas</option>
						<option value="ENTRADA">Solo entradas</option>
						<option value="SALIDA">Solo salidas</option>
					</select>

					<div class="input-group flex-grow-1 w-auto">
						<span class="input-group-text">
							<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
						</span>
						<input
							v-model="termino"
							type="search"
							class="form-control"
							placeholder="Buscar por número, tipo u observación..."
							aria-label="Buscar ajustes"
						>
					</div>

					<!-- Los ajustes se manejan por sucursal: la lista es solo de la sucursal de la sesión -->
					<span
						v-if="sucursal"
						class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
						title="Ajustes de esta sucursal"
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
								<th>Tipo</th>
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
										:class="i.sentido === 'ENTRADA' ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger'"
										:title="i.sentido === 'ENTRADA' ? 'Entrada' : 'Salida'"
										aria-hidden="true"
									/>{{ i.ntipo }}
								</td>
								<td class="text-truncate text-body-secondary" style="max-width: 16rem" :title="i.observacion">{{ i.observacion || '—' }}</td>
								<td class="text-end">{{ i.lineas }}</td>
								<td class="text-end fw-semibold text-nowrap">{{ simbolo }} {{ formatoMonto(i.valor) }}</td>
								<td>
									<span
										class="badge rounded-1 fw-semibold etiqueta-color"
										:style="estiloEtiqueta(i.eestado)"
									>{{ i.nestado }}</span>
								</td>
								<td class="text-end pe-3 text-nowrap">
									<button type="button" class="btn btn-sm btn-link" title="Abrir ajuste" @click.stop="abrir(i)">
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
									{{ termino ? 'Sin resultados para la búsqueda' : 'No hay ajustes en el período seleccionado' }}
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</card-body>
		</card>
	</template>

	<!-- ========================= Documento de un ajuste ========================= -->
	<template v-else>
		<PageHeader>
			<button type="button" class="btn btn-outline-secondary" @click="regresar">
				<i class="fa-solid fa-arrow-left me-1" aria-hidden="true" />Volver a ajustes
			</button>
		</PageHeader>

		<!-- Dos columnas: a la izquierda los datos (arriba) y los productos (abajo); a la derecha el resumen -->
		<div class="row g-3 align-items-start">
			<div class="col-12 col-xl-8 d-flex flex-column gap-3">
				<card>
					<card-header class="flex-wrap">
						<span v-if="reg === ''">Nuevo ajuste</span>
						<template v-else>
							<span class="font-monospace">{{ ajuste.numero }}</span>
							<span
								class="badge rounded-1 fw-semibold etiqueta-color"
								:style="estiloEtiqueta(ajuste.eestado)"
							>{{ ajuste.nestado }}</span>
							<span class="ms-auto small fw-normal text-body-secondary">
								Creado el {{ formatoFecha(ajuste.fecha, true) }} por {{ ajuste.nusuario }}
							</span>
						</template>
					</card-header>
					<card-body>
						<Form
							:key="`form-${apertura}`"
							:ajuste="ajuste"
							:pk="reg"
							:tipos="catalogo.tipos"
							:tiene-lineas="resumen.lineas > 0"
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
							:key="`det-${reg}-${apertura}-${ajuste?.sentido}`"
							:documento-id="reg"
							:sentido="ajuste?.sentido ?? 'SALIDA'"
							:productos="productos"
							:categorias="catalogo.categorias"
							:editable="editable"
							@resumen="actualizarResumen"
						/>
						<div v-else class="text-center text-body-secondary p-5">
							<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-boxes-stacked" aria-hidden="true" /></div>
							Elija el tipo de ajuste y pulse <strong>Guardar</strong> para agregar productos.
						</div>
					</card-body>
				</card>
			</div>

			<!-- Resumen: acompaña al desplazarse por los productos -->
			<div class="col-12 col-xl-4 position-sticky" style="top: 5rem">
				<card>
					<card-header>Resumen</card-header>
					<card-body>
						<!-- Valor destacado, en el color del sentido -->
						<div
							class="rounded-3 border p-3 mb-3"
							:class="entrada ? 'bg-success-subtle border-success-subtle' : 'bg-danger-subtle border-danger-subtle'"
						>
							<div class="small mb-1" :class="entrada ? 'text-success-emphasis' : 'text-danger-emphasis'">
								<i class="fa-solid me-1" :class="entrada ? 'fa-arrow-up' : 'fa-arrow-down'" aria-hidden="true" />
								{{ entrada ? 'Entra al inventario' : 'Sale del inventario' }}
							</div>
							<div class="fs-2 fw-bold lh-sm text-nowrap" :class="entrada ? 'text-success-emphasis' : 'text-danger-emphasis'">
								{{ simbolo }} {{ formatoMonto(resumen.valor) }}
							</div>
							<div v-if="ajuste && !ajuste.fecha_aplicado" class="small text-body-secondary mt-1">Al costo actual; se congela al aplicar</div>
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

						<ul v-if="ajuste" class="list-unstyled small mb-0">
							<li class="d-flex align-items-center gap-2 py-2 border-bottom">
								<i class="fa-solid fa-store fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Sucursal</span>
								<span class="ms-auto fw-semibold text-body text-truncate" :title="ajuste.nsucursal">{{ ajuste.nsucursal }}</span>
							</li>
							<li class="d-flex align-items-center gap-2 py-2" :class="{ 'border-bottom': ajuste.fecha_aplicado || ajuste.fecha_anulado }">
								<i class="fa-solid fa-tag fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Tipo</span>
								<span class="ms-auto fw-semibold text-body text-truncate" :title="ajuste.ntipo">{{ ajuste.ntipo }}</span>
							</li>
							<li v-if="ajuste.fecha_aplicado" class="d-flex align-items-center gap-2 py-2" :class="{ 'border-bottom': ajuste.fecha_anulado }">
								<i class="fa-solid fa-circle-check fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Aplicado</span>
								<span class="ms-auto text-body text-end">{{ formatoFecha(ajuste.fecha_aplicado, true) }} · {{ ajuste.nusuario_aplico }}</span>
							</li>
							<li v-if="ajuste.fecha_anulado" class="d-flex align-items-center gap-2 py-2">
								<i class="fa-solid fa-ban fa-fw text-body-secondary" aria-hidden="true" />
								<span class="text-body-secondary">Anulado</span>
								<span class="ms-auto text-body text-end">{{ formatoFecha(ajuste.fecha_anulado, true) }} · {{ ajuste.nusuario_anulo }}</span>
							</li>
						</ul>

						<!-- Acciones: aplicar es la principal; anular, ocasional y discreta -->
						<div v-if="reg !== '' && editable" class="mt-3">
							<button
								type="button"
								class="btn btn-primary w-100"
								:disabled="btnEstado || resumen.lineas === 0 || resumen.sinExistencia > 0"
								@click="pedirAplicar"
							>
								<i class="fa-solid fa-check me-1" aria-hidden="true" />Aplicar ajuste
							</button>
							<div class="form-text text-center" :class="{ 'text-danger': resumen.sinExistencia > 0 }">
								{{ textoAplicar }}
							</div>
						</div>

						<div v-if="reg !== '' && estado !== ANULADO" class="mt-3">
							<button
								type="button"
								class="btn btn-suave-danger w-100"
								:disabled="btnEstado"
								@click="pedirAnular"
							>
								<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anular ajuste
							</button>
						</div>

						<div v-if="estado === APLICADO" class="alert alert-success small py-2 mb-0 mt-3">
							<i class="fa-solid fa-circle-check me-1" aria-hidden="true" />Aplicado: el inventario ya refleja este ajuste.
						</div>
						<div v-else-if="estado === ANULADO" class="alert alert-secondary small py-2 mb-0 mt-3">
							<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anulado<template v-if="ajuste.anulado_motivo">: {{ ajuste.anulado_motivo }}</template>
						</div>
					</card-body>
				</card>
			</div>
		</div>

		<ConfirmModal
			ref="confirmar"
			titulo="Aplicar ajuste"
			:mensaje="mensajeAplicar"
			texto-confirmar="Aplicar"
			variante="primary"
			@confirmar="aplicar"
		/>

		<!-- Anular: pide el motivo -->
		<Teleport to="body">
			<div ref="modalAnular" class="modal fade" tabindex="-1" aria-labelledby="tituloAnularAjuste" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content" @submit.prevent="anular">
						<div class="modal-header py-2">
							<h2 id="tituloAnularAjuste" class="modal-title h3">Anular ajuste {{ ajuste?.numero }}</h2>
							<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnEstado" @click="modalAnular?.hide()" />
						</div>
						<div class="modal-body">
							<p class="mb-2">{{ mensajeAnular }}</p>
							<label for="ajusteMotivo" class="form-label">Motivo <span class="text-danger">*</span></label>
							<textarea
								id="ajusteMotivo"
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
	import Detalle from './Detalle.vue'
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto, formatoCantidad } from '@/utils/numero'

	const BORRADOR = 1
	const APLICADO = 2
	const ANULADO = 3

	export default {
		name: "Ajuste",
		mixins: [Accion],
		data: () => ({
			ajuste: null,
			verDocumento: false,
			apertura: 0,
			simbolo: "",
			catalogo: {
				tipos: [],
				estados: [],
				categorias: []
			},
			// Productos con su existencia en la sucursal (se recargan al aplicar o anular)
			productos: [],
			btnEstado: false,
			motivo: "",
			errorMotivo: "",
			resumen: {
				valor: 0,
				lineas: 0,
				unidades: 0,
				sinExistencia: 0
			},
			APLICADO,
			ANULADO
		}),
		created() {
			this.url = "inv/ajuste"
			this.autoBuscar = false
			this.inicioArray = true
			this.bform = {
				fdel: null,
				fal: null,
				estado: null,
				sentido: null
			}

			this.getDatos()
			this.getProductos()
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
			nuevo() {
				this.resumen = { valor: 0, lineas: 0, unidades: 0, sinExistencia: 0 }
				this.ajuste = null
				this.reg    = ""
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
				this.ajuste = obj
				this.setDataForm(obj)
				this.apertura++
				this.verDocumento = true
			},
			regresar() {
				// El modal vive dentro del documento: se crea de nuevo al volver a abrir uno
				this.modalAnular?.dispose()
				this.modalAnular = null

				this.verDocumento = false
				this.ajuste = null
				this.reg    = ""
			},
			// Encabezado guardado: si es nuevo, queda abierto para agregar productos
			actualizar(reg) {
				let nuevo = this.reg === ""

				this.setDataRegistro("ajuste", reg)

				if (nuevo) {
					this.ajuste = this.lista.find(e => String(e.id) === String(reg.id)) ?? reg
					this.reg    = String(reg.id)
				}
			},
			// Totales que informa el detalle; también se reflejan en la lista
			actualizarResumen(resumen) {
				this.resumen = resumen

				if (this.ajuste) {
					this.ajuste.valor  = resumen.valor
					this.ajuste.lineas = resumen.lineas
				}
			},
			pedirAplicar() {
				this.$refs.confirmar?.abrir()
			},
			aplicar() {
				this.$refs.confirmar?.cerrar()
				this.cambiarEstado("aplicar")
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
						Object.assign(this.ajuste, res.ajuste)
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
			// Sucursal de la sesión: la lista y los ajustes nuevos son de esta sucursal
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			estado() {
				return Number(this.ajuste?.inventario_ajuste_estado_id ?? BORRADOR)
			},
			editable() {
				return this.estado === BORRADOR
			},
			entrada() {
				return this.ajuste?.sentido === "ENTRADA"
			},
			textoAplicar() {
				if (this.resumen.lineas === 0) {
					return "Agregue productos para poder aplicarlo."
				}

				if (this.resumen.sinExistencia > 0) {
					return "Hay productos sin existencia suficiente; corrija la cantidad o el lote."
				}

				return this.entrada ? "Suma los productos al inventario de la sucursal." : "Descuenta los productos del inventario de la sucursal."
			},
			mensajeAplicar() {
				let accion = this.entrada ? "se sumarán al" : "se descontarán del"
				return `Los productos ${accion} inventario de la sucursal ${this.ajuste?.nsucursal ?? ""} al costo actual. Después ya no se podrá modificar.`
			},
			mensajeAnular() {
				if (this.estado === APLICADO) {
					return this.entrada
						? "Se restará del inventario lo que entró con este ajuste. No se puede si esa existencia ya salió."
						: "Se devolverá al inventario lo que salió con este ajuste, al mismo lote."
				}

				return "El ajuste todavía no movió inventario; solo quedará anulado."
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
