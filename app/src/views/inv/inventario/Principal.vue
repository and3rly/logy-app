<template>
	<PageHeader>
		<button type="button" class="btn btn-suave-info" :disabled="btnPlantilla" @click="descargarPlantilla">
			<span v-if="btnPlantilla" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
			<i v-else class="fa-solid fa-file-arrow-down me-1" aria-hidden="true" />Descargar plantilla
		</button>
		<button v-if="!inventario || editable" type="button" class="btn btn-primary" :disabled="cargando" @click="abrirImportar">
			<i class="fa-solid fa-file-import me-1" aria-hidden="true" />{{ inventario ? 'Importar otro archivo' : 'Importar Excel' }}
		</button>
	</PageHeader>

	<div v-if="cargando" class="text-center text-body-secondary py-5">
		<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
	</div>

	<!-- ===================== Sin inventario inicial: cómo empezar ===================== -->
	<template v-else-if="!inventario">
		<card>
			<card-body class="p-4">
				<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
					<span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis fs-3" style="width: 3.5rem; height: 3.5rem">
						<i class="fa-solid fa-boxes-packing" aria-hidden="true" />
					</span>
					<div class="flex-grow-1">
						<h2 class="h4 mb-1">La sucursal {{ sucursal }} aún no tiene inventario inicial</h2>
						<p class="text-body-secondary mb-0">
							Cargue las existencias con las que arranca desde un Excel; los productos, marcas, categorías y unidades que no existan se crean solos.
						</p>
					</div>
				</div>

				<div class="row g-3">
					<div v-for="(p, i) in pasos" :key="p.titulo" class="col-12 col-md-6 col-xl-3">
						<div class="rounded-3 border p-3 h-100">
							<div class="d-flex align-items-center gap-2 mb-2">
								<span class="badge rounded-pill text-bg-primary">{{ i + 1 }}</span>
								<i class="fa-solid text-primary" :class="p.icono" aria-hidden="true" />
								<span class="fw-semibold text-body">{{ p.titulo }}</span>
							</div>
							<div class="small text-body-secondary">{{ p.texto }}</div>
						</div>
					</div>
				</div>

				<div class="d-flex flex-wrap gap-2 mt-4">
					<button type="button" class="btn btn-primary" @click="abrirImportar">
						<i class="fa-solid fa-file-import me-1" aria-hidden="true" />Importar Excel
					</button>
					<button type="button" class="btn btn-suave-info" :disabled="btnPlantilla" @click="descargarPlantilla">
						<i class="fa-solid fa-file-arrow-down me-1" aria-hidden="true" />Descargar plantilla
					</button>
				</div>
			</card-body>
		</card>
	</template>

	<!-- ================================ Inventario ================================ -->
	<div v-else class="row g-3 align-items-start">
		<div class="col-12 col-xl-8">
			<card>
				<card-header class="flex-wrap">
					<span class="font-monospace">{{ inventario.numero }}</span>
					<span class="badge rounded-1 fw-semibold etiqueta-color" :style="estiloEtiqueta(etiquetaEstado(inventario.cestado))">{{ inventario.nestado }}</span>
					<span class="ms-auto small fw-normal text-body-secondary">
						Creado el {{ formatoFecha(inventario.fecha, true) }} por {{ inventario.nusuario }}
					</span>
				</card-header>
				<card-body class="p-0">
					<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
						<div class="input-group flex-grow-1 w-auto">
							<span class="input-group-text"><i class="fa-solid fa-magnifying-glass" aria-hidden="true" /></span>
							<input
								v-model="termino"
								type="search"
								class="form-control"
								placeholder="Buscar por producto, código, categoría o marca..."
								aria-label="Buscar productos del inventario"
							>
						</div>
						<span class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap" aria-live="polite">
							<i class="fa-solid fa-layer-group text-primary" aria-hidden="true" />
							<span>
								<span class="fw-semibold text-body">{{ termino ? `${detalleFiltrado.length} de ${detalle.length}` : detalle.length }}</span>
								{{ detalle.length === 1 ? 'línea' : 'líneas' }}
							</span>
						</span>
					</div>

					<div class="table-responsive">
						<table class="table table-sm table-hover mb-0 align-middle">
							<thead>
								<tr>
									<th class="ps-3">Producto</th>
									<th>Unidad</th>
									<th>Vence</th>
									<th class="text-end">Cantidad</th>
									<th class="text-end">Costo</th>
									<th class="text-end">Valor</th>
									<th v-if="editable" class="text-end pe-3"><span class="visually-hidden">Acciones</span></th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="d in detalleFiltrado" :key="d.id">
									<td class="ps-3" style="min-width: 16rem">
										<div class="fw-semibold text-body">{{ d.nproducto }}</div>
										<div class="small text-body-secondary">
											<span class="font-monospace">{{ d.cproducto }}</span>
											<template v-if="d.nmarca"> · {{ d.nmarca }}</template>
											<span
												v-if="d.ncategoria"
												class="badge rounded-1 fw-semibold etiqueta-color ms-1"
												:style="estiloEtiqueta(d.ecategoria)"
											>{{ d.ncategoria }}</span>
										</div>
									</td>
									<td class="text-nowrap">{{ d.nunidad }}</td>
									<td class="text-nowrap">{{ formatoFecha(d.fecha_vence) || '—' }}</td>
									<td class="text-end">
										<div>{{ formatoCantidad(d.diferencia) }}</div>
										<div v-if="procesado && Number(d.cantidad_sistema) > 0" class="small text-body-secondary text-nowrap" title="Existencia que ya tenía el lote al procesar">
											+ {{ formatoCantidad(d.cantidad_sistema) }} previas
										</div>
									</td>
									<td class="text-end text-nowrap">{{ formatoMonto(d.costo) }}</td>
									<td class="text-end fw-semibold text-nowrap">{{ formatoMonto(d.valor) }}</td>
									<td v-if="editable" class="text-end pe-3">
										<button
											type="button"
											class="btn btn-sm btn-link text-danger"
											title="Quitar del inventario"
											:disabled="btnQuitar === d.id"
											@click="quitar(d)"
										>
											<span v-if="btnQuitar === d.id" class="spinner-border spinner-border-sm" aria-hidden="true" />
											<i v-else class="fa-solid fa-trash-can" aria-hidden="true" />
										</button>
									</td>
								</tr>

								<tr v-if="btnDetalle">
									<td :colspan="editable ? 7 : 6" class="text-center text-body-secondary">
										<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
									</td>
								</tr>
								<tr v-else-if="detalleFiltrado.length === 0">
									<td :colspan="editable ? 7 : 6" class="text-center text-body-secondary py-4">
										{{ termino ? 'Sin resultados para la búsqueda' : 'El inventario no tiene productos; importe un archivo.' }}
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</card-body>
			</card>
		</div>

		<!-- Resumen: acompaña al desplazarse por los productos -->
		<div class="col-12 col-xl-4 position-sticky" style="top: 5rem">
			<card>
				<card-header>Resumen</card-header>
				<card-body>
					<div class="rounded-3 border p-3 mb-3 bg-success-subtle border-success-subtle">
						<div class="small mb-1 text-success-emphasis">
							<i class="fa-solid fa-arrow-up me-1" aria-hidden="true" />{{ procesado ? 'Entró al inventario' : 'Entrará al inventario' }}
						</div>
						<div class="fs-2 fw-bold lh-sm text-nowrap text-success-emphasis">{{ simbolo }} {{ formatoMonto(inventario.valor) }}</div>
						<div class="small text-body-secondary mt-1">Valor al costo del archivo</div>
					</div>

					<div class="row g-2 mb-3">
						<div class="col-6">
							<div class="rounded-3 border px-3 py-2 h-100">
								<div class="small text-body-secondary"><i class="fa-solid fa-boxes-stacked me-1" aria-hidden="true" />Productos</div>
								<div class="fs-5 fw-semibold text-body">{{ inventario.productos }}</div>
							</div>
						</div>
						<div class="col-6">
							<div class="rounded-3 border px-3 py-2 h-100">
								<div class="small text-body-secondary"><i class="fa-solid fa-cubes me-1" aria-hidden="true" />Unidades</div>
								<div class="fs-5 fw-semibold text-body">{{ formatoCantidad(inventario.unidades) }}</div>
							</div>
						</div>
					</div>

					<ul class="list-unstyled small mb-0">
						<li class="d-flex align-items-center gap-2 py-2 border-bottom">
							<i class="fa-solid fa-store fa-fw text-body-secondary" aria-hidden="true" />
							<span class="text-body-secondary">Sucursal</span>
							<span class="ms-auto fw-semibold text-body text-truncate" :title="inventario.nsucursal">{{ inventario.nsucursal }}</span>
						</li>
						<li v-if="inventario.archivo_nombre" class="d-flex align-items-center gap-2 py-2" :class="{ 'border-bottom': inventario.fecha_procesado }">
							<i class="fa-solid fa-file-excel fa-fw text-body-secondary" aria-hidden="true" />
							<span class="text-body-secondary text-nowrap">Último archivo</span>
							<span class="ms-auto text-body text-truncate" :title="inventario.archivo_nombre">{{ inventario.archivo_nombre }}</span>
						</li>
						<li v-if="inventario.fecha_procesado" class="d-flex align-items-center gap-2 py-2">
							<i class="fa-solid fa-circle-check fa-fw text-body-secondary" aria-hidden="true" />
							<span class="text-body-secondary">Procesado</span>
							<span class="ms-auto text-body text-end">{{ formatoFecha(inventario.fecha_procesado, true) }} · {{ inventario.nusuario_proceso }}</span>
						</li>
					</ul>

					<!-- Acciones: procesar es la principal; anular, ocasional y discreta -->
					<div v-if="editable" class="mt-3">
						<button
							type="button"
							class="btn btn-primary w-100"
							:disabled="btnEstado || Number(inventario.lineas) === 0"
							@click="$refs.confirmar?.abrir()"
						>
							<i class="fa-solid fa-check me-1" aria-hidden="true" />Procesar inventario
						</button>
						<div class="form-text text-center">
							{{ Number(inventario.lineas) === 0 ? 'Importe productos para poder procesarlo.' : 'Suma las cantidades a la existencia de la sucursal.' }}
						</div>
					</div>

					<div class="mt-3">
						<button type="button" class="btn btn-suave-danger w-100" :disabled="btnEstado" @click="pedirAnular">
							<i class="fa-solid fa-ban me-1" aria-hidden="true" />Anular inventario
						</button>
					</div>

					<div v-if="procesado" class="alert alert-success small py-2 mb-0 mt-3">
						<i class="fa-solid fa-circle-check me-1" aria-hidden="true" />Procesado: las existencias ya están en la sucursal. Para corregirlas use
						<RouterLink to="/ajuste">Ajustes</RouterLink>.
					</div>
				</card-body>
			</card>
		</div>
	</div>

	<!-- Inventarios iniciales anulados de la sucursal -->
	<card v-if="!cargando && anulados.length" class="mt-3">
		<card-header>Anulados</card-header>
		<card-body class="p-0">
			<div class="table-responsive">
				<table class="table table-sm mb-0 align-middle">
					<thead>
						<tr>
							<th class="ps-3">Número</th>
							<th>Creado</th>
							<th>Anulado</th>
							<th>Motivo</th>
							<th class="text-end pe-3">Valor</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="a in anulados" :key="a.id">
							<td class="ps-3 font-monospace">{{ a.numero }}</td>
							<td class="text-nowrap">{{ formatoFecha(a.fecha, true) }}</td>
							<td class="text-nowrap">{{ formatoFecha(a.fecha_anulado, true) }} · {{ a.nusuario_anulo }}</td>
							<td class="text-body-secondary">{{ a.anulado_motivo || '—' }}</td>
							<td class="text-end pe-3 text-nowrap">{{ simbolo }} {{ formatoMonto(a.valor) }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</card-body>
	</card>

	<Importar ref="importar" :simbolo="simbolo" :sucursal="sucursal" @importado="importado" />

	<ConfirmModal
		ref="confirmar"
		titulo="Procesar inventario inicial"
		:mensaje="mensajeProcesar"
		texto-confirmar="Procesar"
		variante="primary"
		@confirmar="procesar"
	/>

	<!-- Anular: pide el motivo -->
	<Teleport to="body">
		<div ref="modalAnular" class="modal fade" tabindex="-1" aria-labelledby="tituloAnularInventario" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<form class="modal-content" @submit.prevent="anular">
					<div class="modal-header py-2">
						<h2 id="tituloAnularInventario" class="modal-title h3">Anular inventario {{ inventario?.numero }}</h2>
						<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnEstado" @click="modalAnular?.hide()" />
					</div>
					<div class="modal-body">
						<p class="mb-2">{{ mensajeAnular }}</p>
						<label for="inventarioMotivo" class="form-label">Motivo <span class="text-danger">*</span></label>
						<textarea
							id="inventarioMotivo"
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

<script>
	import { Modal } from 'bootstrap'
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import ConfirmModal from '../../../components/ui/ConfirmModal.vue'
	import Importar from './Importar.vue'
	import api, { mensajeError } from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "Inventario",
		data: () => ({
			url: "inv/inventario",
			inventario: null,
			anulados: [],
			detalle: [],
			simbolo: "",
			termino: "",
			motivo: "",
			errorMotivo: "",
			cargando: true,
			btnDetalle: false,
			btnEstado: false,
			btnQuitar: null,
			btnPlantilla: false,
			pasos: [
				{
					icono: "fa-file-arrow-down",
					titulo: "Descargue la plantilla",
					texto: "Trae las columnas, las unidades de medida y una hoja de ejemplo."
				},
				{
					icono: "fa-pen-to-square",
					titulo: "Llene la hoja Productos",
					texto: "Una fila por producto y lote, con su costo, precio y cantidad. Sin código interno: lo genera el sistema."
				},
				{
					icono: "fa-magnifying-glass-chart",
					titulo: "Importe y revise",
					texto: "Cada fila se valida antes de guardar; puede importar varios archivos y las cantidades se suman."
				},
				{
					icono: "fa-check",
					titulo: "Procese",
					texto: "Las existencias entran a la sucursal. Cada sucursal tiene un solo inventario inicial."
				}
			]
		}),
		created() {
			this.getDatos()
		},
		beforeUnmount() {
			this.modalAnular?.dispose()
		},
		methods: {
			getDatos() {
				this.cargando = true

				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					let res = result.data

					this.simbolo    = res.simbolo ?? ""
					this.inventario = res.inventario ?? null
					this.anulados   = res.anulados ?? []
					this.getDetalle()
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.cargando = false
				})
			},
			getDetalle() {
				this.detalle = []

				if (!this.inventario) {
					return
				}

				this.btnDetalle = true

				api
				.get(`/${this.url}/get_detalle/${this.inventario.id}`)
				.then(result => {
					this.detalle = result.data.det ?? []
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnDetalle = false
				})
			},
			abrirImportar() {
				this.$refs.importar?.abrir()
			},
			importado(inventario) {
				this.inventario = inventario
				this.getDetalle()
			},
			// La plantilla la arma la API; se pide como blob porque la petición lleva el token
			descargarPlantilla() {
				this.btnPlantilla = true

				api
				.get(`/${this.url}/plantilla`, { responseType: "blob" })
				.then(result => {
					let enlace = document.createElement("a")

					enlace.href = URL.createObjectURL(result.data)
					enlace.download = "plantilla_inventario_inicial.xlsx"
					enlace.click()
					URL.revokeObjectURL(enlace.href)
				})
				.catch(() => {
					this.$toast.error("No se pudo generar la plantilla.")
				})
				.finally(() => {
					this.btnPlantilla = false
				})
			},
			quitar(det) {
				this.btnQuitar = det.id

				api
				.post(`/${this.url}/quitar`, { id: det.id })
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.detalle = this.detalle.filter(d => d.id !== det.id)
						Object.assign(this.inventario, res.inventario)
						this.$toast.success(res.mensaje)
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnQuitar = null
				})
			},
			procesar() {
				this.$refs.confirmar?.cerrar()
				this.cambiarEstado("procesar")
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
				.post(`/${this.url}/${accion}/${this.inventario.id}`, datos)
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.modalAnular?.hide()
						this.$toast.success(res.mensaje)

						// Anulado: la sucursal queda libre para otro inventario inicial
						accion === "anular" ? this.getDatos() : this.importado(res.inventario)
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
			etiquetaEstado(codigo) {
				return {
					BORRADOR: "primary",
					PROCESADO: "lime",
					ANULADO: "danger"
				}[codigo] ?? "secondary"
			},
			formatoMonto,
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 2
				})
			},
			// "2026-09-17 03:45:53" → "17/09/2026 03:45"; "2026-09-16" → "16/09/2026"
			formatoFecha(fecha, conHora) {
				if (!fecha) {
					return ""
				}

				let [dia, hora] = String(fecha).split(" ")
				let [a, m, d] = dia.split("-")

				return `${d}/${m}/${a}` + (conHora && hora && hora !== "00:00:00" ? ` ${hora.slice(0, 5)}` : "")
			},
			estiloEtiqueta
		},
		computed: {
			// Sucursal de la sesión: el inventario inicial es de esta sucursal
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			editable() {
				return this.inventario?.cestado === "BORRADOR"
			},
			procesado() {
				return this.inventario?.cestado === "PROCESADO"
			},
			detalleFiltrado() {
				let ter = this.termino.trim().toLowerCase()

				if (ter === "") {
					return this.detalle
				}

				return this.detalle.filter(d => [d.nproducto, d.cproducto, d.codigo_barra, d.ncategoria, d.nmarca]
					.some(v => String(v ?? "").toLowerCase().includes(ter)))
			},
			mensajeProcesar() {
				return `Se sumarán ${this.inventario?.productos ?? 0} productos a la existencia de la sucursal ${this.inventario?.nsucursal ?? ""}. Después ya no se podrá importar ni quitar productos.`
			},
			mensajeAnular() {
				return this.procesado
					? "Se restará de la existencia lo que entró con este inventario; no se puede si parte ya salió. Los productos creados se conservan."
					: "El inventario todavía no movió existencias; solo quedará anulado. Los productos creados se conservan."
			}
		},
		components: {
			PageHeader,
			ConfirmModal,
			Importar
		}
	}
</script>
