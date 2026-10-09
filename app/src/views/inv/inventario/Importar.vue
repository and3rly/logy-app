<template>
	<!-- Importar Excel: al elegir el archivo la API lo revisa (sin guardar); se importa solo si no hay errores -->
	<Teleport to="body">
		<div ref="modal" class="modal fade" tabindex="-1" aria-labelledby="tituloImportarInventario" aria-hidden="true" data-bs-backdrop="static">
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloImportarInventario" class="modal-title fw-semibold mb-0">Importar productos desde Excel</h5>
							<div class="small text-body-secondary">
								Se lee la hoja <strong>Productos</strong> de la plantilla; las cantidades entran a la sucursal <strong>{{ sucursal }}</strong>
								<template v-if="borrador"> en el borrador <strong class="font-monospace">{{ borrador }}</strong></template>
								<template v-else> en un inventario inicial nuevo</template>
							</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" :disabled="btnImportar" @click="cerrar" />
					</div>

					<div class="modal-body">
						<!-- Archivo: arrastrar o elegir -->
						<label
							for="archivoInventario"
							class="d-flex align-items-center gap-3 rounded-3 border border-2 p-3 mb-3"
							:class="arrastrando ? 'border-primary bg-primary-subtle' : 'bg-body-tertiary'"
							style="border-style: dashed !important; cursor: pointer"
							@dragover.prevent="arrastrando = true"
							@dragleave.prevent="arrastrando = false"
							@drop.prevent="soltar"
						>
							<span class="fs-2 text-success">
								<i class="fa-solid fa-file-excel" aria-hidden="true" />
							</span>
							<span class="flex-grow-1 min-w-0">
								<span v-if="archivo" class="d-block fw-semibold text-body text-truncate">{{ archivo.name }}</span>
								<span v-else class="d-block fw-semibold text-body">Arrastre aquí el archivo o haga clic para elegirlo</span>
								<span class="d-block small text-body-secondary">Excel (.xlsx) de hasta 5 MB, hecho con la plantilla</span>
							</span>
							<span class="btn btn-outline-primary btn-sm">
								<i class="fa-solid fa-folder-open me-1" aria-hidden="true" />{{ archivo ? 'Cambiar' : 'Elegir' }}
							</span>
							<input
								id="archivoInventario"
								ref="archivo"
								type="file"
								class="d-none"
								accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
								@change="elegir"
							>
						</label>

						<div v-if="btnValidar" class="text-center text-body-secondary py-5">
							<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Revisando el archivo...
						</div>

						<div v-else-if="mensaje" class="alert alert-danger mb-0">
							<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />{{ mensaje }}
						</div>

						<template v-else-if="validacion">
							<!-- Resumen de la revisión -->
							<div class="row g-2 mb-3">
								<div class="col-6 col-lg-3">
									<div class="rounded-3 border px-3 py-2 h-100">
										<div class="small text-body-secondary"><i class="fa-solid fa-table-list me-1" aria-hidden="true" />Filas</div>
										<div class="fs-5 fw-semibold text-body">{{ resumen.filas }}</div>
									</div>
								</div>
								<div class="col-6 col-lg-3">
									<div class="rounded-3 border px-3 py-2 h-100" :class="resumen.errores > 0 ? 'bg-danger-subtle border-danger-subtle' : 'bg-success-subtle border-success-subtle'">
										<div class="small" :class="resumen.errores > 0 ? 'text-danger-emphasis' : 'text-success-emphasis'">
											<i class="fa-solid me-1" :class="resumen.errores > 0 ? 'fa-circle-xmark' : 'fa-circle-check'" aria-hidden="true" />Con errores
										</div>
										<div class="fs-5 fw-semibold" :class="resumen.errores > 0 ? 'text-danger-emphasis' : 'text-success-emphasis'">{{ resumen.errores }}</div>
									</div>
								</div>
								<div class="col-6 col-lg-3">
									<div class="rounded-3 border px-3 py-2 h-100">
										<div class="small text-body-secondary"><i class="fa-solid fa-boxes-stacked me-1" aria-hidden="true" />Productos</div>
										<div class="fs-5 fw-semibold text-body">
											{{ resumen.productos_nuevos }} <span class="small fw-normal text-body-secondary">nuevos</span>
											· {{ resumen.productos_existentes }} <span class="small fw-normal text-body-secondary">existentes</span>
										</div>
									</div>
								</div>
								<div class="col-6 col-lg-3">
									<div class="rounded-3 border px-3 py-2 h-100">
										<div class="small text-body-secondary"><i class="fa-solid fa-coins me-1" aria-hidden="true" />Valor al costo</div>
										<div class="fs-5 fw-semibold text-body text-nowrap">{{ simbolo }} {{ formatoMonto(resumen.valor) }}</div>
									</div>
								</div>
							</div>

							<!-- Catálogos que se crearán -->
							<div v-if="catalogosNuevos.length" class="alert alert-info small py-2">
								<i class="fa-solid fa-circle-info me-1" aria-hidden="true" />Se crearán automáticamente:
								<span v-for="(c, i) in catalogosNuevos" :key="c.titulo">
									<strong>{{ c.titulo }}</strong> ({{ c.lista.join(', ') }}){{ i < catalogosNuevos.length - 1 ? '; ' : '.' }}
								</span>
							</div>

							<div v-if="resumen.advertencias > 0" class="alert alert-warning small py-2">
								<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />{{ resumen.advertencias === 1 ? 'Una fila trae un producto' : `${resumen.advertencias} filas traen productos` }}
								que ya entró con otro inventario inicial; si importa, la cantidad se suma otra vez. Revise que no esté repetido.
							</div>

							<div v-if="resumen.errores > 0" class="alert alert-warning small py-2">
								<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />Corrija las filas marcadas en el Excel y vuelva a elegir el archivo; no se importa nada mientras haya errores.
							</div>

							<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
								<div class="input-group flex-grow-1 w-auto">
									<span class="input-group-text"><i class="fa-solid fa-magnifying-glass" aria-hidden="true" /></span>
									<input v-model="termino" type="search" class="form-control" placeholder="Buscar en las filas..." aria-label="Buscar en las filas">
								</div>
								<div class="btn-group" role="group" aria-label="Filtrar filas">
									<input id="filtroTodas" v-model="filtro" type="radio" class="btn-check" value="todas">
									<label class="btn btn-outline-primary" for="filtroTodas">Todas</label>
									<input id="filtroErrores" v-model="filtro" type="radio" class="btn-check" value="errores" :disabled="resumen.errores === 0">
									<label class="btn btn-outline-primary" for="filtroErrores">Con errores</label>
									<input id="filtroAvisos" v-model="filtro" type="radio" class="btn-check" value="avisos" :disabled="!resumen.advertencias">
									<label class="btn btn-outline-primary" for="filtroAvisos">Con aviso</label>
								</div>
							</div>

							<div class="table-responsive border rounded-3">
								<table class="table table-sm mb-0 align-middle">
									<thead>
										<tr>
											<th class="ps-3 text-end">Fila</th>
											<th>Producto</th>
											<th>Unidad</th>
											<th>Vence</th>
											<th class="text-end">Cantidad</th>
											<th class="text-end">Costo</th>
											<th class="text-end">Precio</th>
											<th class="pe-3">Estado</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="f in filas" :key="f.fila">
											<td class="ps-3 text-end" :class="f.errores.length ? 'text-danger fw-semibold' : 'text-body-secondary'">
												<i v-if="f.errores.length" class="fa-solid fa-circle-exclamation me-1" aria-hidden="true" />{{ f.fila }}
											</td>
											<td style="min-width: 16rem">
												<div class="fw-semibold text-body">{{ f.nombre || '—' }}</div>
												<div class="small text-body-secondary">
													{{ [f.categoria, f.marca, f.codigo_barra].filter(Boolean).join(' · ') }}
												</div>
											</td>
											<td class="text-nowrap">{{ f.unidad }}</td>
											<td class="text-nowrap">{{ formatoFecha(f.vence) || '—' }}</td>
											<td class="text-end">{{ formatoCantidad(f.cantidad) }}</td>
											<td class="text-end text-nowrap">{{ formatoMonto(f.costo) }}</td>
											<td class="text-end text-nowrap">{{ formatoMonto(f.precio) }}</td>
											<td class="pe-3" style="min-width: 12rem">
												<template v-if="f.errores.length">
													<div v-for="e in f.errores" :key="e" class="small text-danger-emphasis">
														<i class="fa-solid fa-circle-xmark me-1" aria-hidden="true" />{{ e }}
													</div>
												</template>
												<span v-else-if="f.accion === 'existente'" class="badge text-bg-light border" :title="`Se usa el producto ${f.cproducto}`">
													Existente · {{ f.cproducto }}
												</span>
												<span v-else class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Producto nuevo</span>
												<div v-for="a in f.advertencias ?? []" :key="a" class="small text-warning-emphasis mt-1">
													<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />{{ a }}
												</div>
											</td>
										</tr>
										<tr v-if="filas.length === 0">
											<td colspan="8" class="text-center text-body-secondary py-4">Sin filas para mostrar</td>
										</tr>
									</tbody>
								</table>
							</div>
						</template>

						<div v-else class="text-center text-body-secondary py-4">
							<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-magnifying-glass-chart" aria-hidden="true" /></div>
							Al elegir el archivo se revisa cada fila antes de importar.
						</div>
					</div>

					<div class="modal-footer">
						<span v-if="validacion && resumen.errores === 0" class="small text-body-secondary me-auto">
							{{ textoImportar }}
						</span>
						<button type="button" class="btn btn-outline-secondary" :disabled="btnImportar" @click="cerrar">Cancelar</button>
						<button
							type="button"
							class="btn btn-primary"
							:disabled="!puedeImportar || btnImportar"
							@click="importar"
						>
							<span v-if="btnImportar" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
							<i v-else class="fa-solid fa-file-import me-1" aria-hidden="true" />{{ textoBoton }}
						</button>
					</div>
				</div>
			</div>
		</div>
	</Teleport>
</template>

<script>
	import { Modal } from 'bootstrap'
	import api, { mensajeError } from '@/services/api'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "InventarioImportar",
		props: {
			simbolo: {
				type: String,
				default: ""
			},
			sucursal: {
				type: String,
				default: ""
			},
			// Número del borrador abierto al que se agrega el archivo ("" = se crea uno nuevo)
			borrador: {
				type: String,
				default: ""
			}
		},
		emits: ["importado"],
		data: () => ({
			url: "inv/inventario",
			archivo: null,
			validacion: null,
			mensaje: "",
			termino: "",
			filtro: "todas",
			arrastrando: false,
			btnValidar: false,
			btnImportar: false
		}),
		mounted() {
			this.modal = new Modal(this.$refs.modal)
		},
		beforeUnmount() {
			this.modal?.dispose()
		},
		methods: {
			abrir() {
				this.archivo = null
				this.validacion = null
				this.mensaje = ""
				this.termino = ""
				this.filtro = "todas"
				this.$refs.archivo.value = ""
				this.modal.show()
			},
			cerrar() {
				this.modal.hide()
			},
			elegir(e) {
				this.revisar(e.target.files?.[0] ?? null)
			},
			soltar(e) {
				this.arrastrando = false
				this.revisar(e.dataTransfer?.files?.[0] ?? null)
			},
			datos() {
				let datos = new FormData()
				datos.append("archivo", this.archivo)

				return datos
			},
			// La API revisa el archivo sin guardar nada
			revisar(archivo) {
				if (!archivo) {
					return
				}

				this.archivo = archivo
				this.validacion = null
				this.mensaje = ""
				this.filtro = "todas"
				this.btnValidar = true

				api
				.post(`/${this.url}/validar`, this.datos(), {
					headers: { "Content-Type": "multipart/form-data" }
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.validacion = res.validacion
						this.filtro = res.validacion.resumen.errores > 0 ? "errores" : "todas"
					} else {
						this.mensaje = res.mensaje
					}
				})
				.catch(e => {
					this.mensaje = mensajeError(e)
				})
				.finally(() => {
					this.btnValidar = false
					this.$refs.archivo.value = ""
				})
			},
			importar() {
				this.btnImportar = true

				api
				.post(`/${this.url}/importar`, this.datos(), {
					headers: { "Content-Type": "multipart/form-data" }
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.$toast.success(res.mensaje)
						this.$emit("importado", res.inventario)
						this.modal.hide()
					} else {
						// Si cambió algo desde la revisión, se muestran los errores nuevos
						if (res.validacion) {
							this.validacion = res.validacion
							this.filtro = "errores"
						}

						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnImportar = false
				})
			},
			formatoMonto,
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 2
				})
			},
			// "2027-06-30" → "30/06/2027"
			formatoFecha(fecha) {
				if (!fecha) {
					return ""
				}

				let [a, m, d] = String(fecha).slice(0, 10).split("-")

				return `${d}/${m}/${a}`
			}
		},
		computed: {
			resumen() {
				return this.validacion?.resumen ?? null
			},
			filas() {
				let ter = this.termino.trim().toLowerCase()

				return (this.validacion?.filas ?? []).filter(f => {
					if (this.filtro === "errores" && f.errores.length === 0) {
						return false
					}

					if (this.filtro === "avisos" && !(f.advertencias ?? []).length) {
						return false
					}

					return ter === "" || [f.nombre, f.categoria, f.marca, f.codigo_barra, f.unidad]
						.some(v => String(v ?? "").toLowerCase().includes(ter))
				})
			},
			catalogosNuevos() {
				let nuevos = this.resumen?.nuevos ?? {}

				return [
					{ titulo: "categorías", lista: nuevos.categorias ?? [] },
					{ titulo: "marcas", lista: nuevos.marcas ?? [] },
					{ titulo: "unidades", lista: nuevos.unidades ?? [] }
				].filter(c => c.lista.length > 0)
			},
			textoBoton() {
				if (!this.resumen) {
					return "Importar"
				}

				return this.resumen.filas === 1 ? "Importar 1 fila" : `Importar ${this.resumen.filas} filas`
			},
			puedeImportar() {
				return this.archivo && this.resumen && this.resumen.errores === 0 && this.resumen.filas > 0 && !this.btnValidar
			},
			textoImportar() {
				let nuevos = this.resumen.productos_nuevos

				return nuevos > 0
					? `Se crearán ${nuevos} ${nuevos === 1 ? 'producto' : 'productos'} y se sumarán las cantidades al inventario.`
					: "Se sumarán las cantidades al inventario."
			}
		}
	}
</script>
