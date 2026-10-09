<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<div class="row g-4 align-items-start">
			<!-- Navegación de secciones + resumen de la configuración -->
			<div class="col-lg-3">
				<div class="sticky-lg-top d-flex flex-column gap-3" style="top: calc(var(--navbar-alto) + 1rem); z-index: 1">
					<card>
						<div class="list-group list-group-flush rounded-3">
							<button
								v-for="s in secciones"
								:key="s.id"
								type="button"
								class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3"
								:class="{ active: s.id === seccion }"
								@click="irA(s.id)"
							>
								<i :class="s.icono" class="fa-fw" aria-hidden="true" />
								<span class="lh-sm">
									<span class="d-block fw-semibold">{{ s.titulo }}</span>
									<small class="opacity-75">{{ s.resumen }}</small>
								</span>
							</button>
						</div>
					</card>

					<card>
						<card-body>
							<div class="small text-body-secondary text-uppercase fw-semibold mb-3">Vista previa</div>

							<div class="d-flex align-items-center gap-3 mb-3">
								<span class="icono-suave icono-suave-primario fw-bold" aria-hidden="true">{{ monedaActual ? monedaActual.simbolo : '?' }}</span>
								<div class="lh-sm">
									<div class="fw-semibold">{{ monedaActual ? monedaActual.nombre : 'Sin moneda' }}</div>
									<small class="text-body-secondary">Moneda predeterminada</small>
								</div>
							</div>

							<dl class="row small mb-0">
								<dt class="col-6 fw-normal text-body-secondary">Monto</dt>
								<dd class="col-6 text-end font-monospace">{{ ejemploMonto }}</dd>
								<dt class="col-6 fw-normal text-body-secondary">Venta</dt>
								<dd class="col-6 text-end font-monospace mb-0">{{ correlativo('abr_venta') }}</dd>
							</dl>
						</card-body>
					</card>
				</div>
			</div>

			<div class="col-lg-9 d-flex flex-column gap-4">
				<!-- Moneda predeterminada -->
				<section id="seccion-moneda" ref="moneda" style="scroll-margin-top: calc(var(--navbar-alto) + 1rem)">
					<card>
						<card-header>
							<div class="d-flex align-items-center gap-3">
								<span class="icono-suave icono-suave-primario" aria-hidden="true">
									<i class="fa-solid fa-coins" />
								</span>
								<div class="lh-sm">
									<div class="fw-semibold">Moneda predeterminada <span class="text-danger">*</span></div>
									<small class="text-body-secondary fw-normal">Se propone al crear compras, ventas y cotizaciones.</small>
								</div>
							</div>
						</card-header>
						<card-body>
							<div v-if="monedas.length" class="row g-2" role="radiogroup" aria-label="Moneda predeterminada">
								<div v-for="m in monedas" :key="m.id" class="col-sm-6 col-xl-4">
									<input
										:id="`moneda_${m.id}`"
										v-model="form.moneda_id"
										type="radio"
										class="btn-check"
										name="moneda"
										:value="String(m.id)"
										required
									>
									<label
										:for="`moneda_${m.id}`"
										class="btn btn-outline-primary w-100 h-100 d-flex align-items-center gap-2 py-2 px-3 text-start"
									>
										<span class="fw-bold" style="min-width: 2rem">{{ m.simbolo }}</span>
										<span class="flex-grow-1 text-truncate">
											<span class="fw-semibold">{{ m.nombre }}</span>
											<small v-if="m.codigo" class="opacity-75 ms-1">{{ m.codigo }}</small>
										</span>
										<i
											v-if="String(m.id) === String(form.moneda_id)"
											class="fa-solid fa-circle-check"
											aria-hidden="true"
										/>
									</label>
								</div>
							</div>
							<div v-else class="alert alert-warning mb-0">
								<i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true" />No hay monedas activas. Regístrelas en Catálogos → Monedas.
							</div>
						</card-body>
					</card>
				</section>

				<!-- Formato numérico -->
				<section id="seccion-formato" ref="formato" style="scroll-margin-top: calc(var(--navbar-alto) + 1rem)">
					<card>
						<card-header>
							<div class="d-flex align-items-center gap-3">
								<span class="icono-suave icono-suave-info" aria-hidden="true">
									<i class="fa-solid fa-calculator" />
								</span>
								<div class="lh-sm">
									<div class="fw-semibold">Formato numérico</div>
									<small class="text-body-secondary fw-normal">Cantidad de decimales que se muestran en pantalla y documentos.</small>
								</div>
							</div>
						</card-header>
						<div class="list-group list-group-flush">
							<div
								v-for="d in formatos"
								:key="d.campo"
								class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3 py-3"
							>
								<div class="lh-sm">
									<div class="fw-semibold">{{ d.titulo }}</div>
									<small class="text-body-secondary">{{ d.descripcion }}</small>
									<div class="mt-2">
										<span class="badge bg-body-tertiary text-body border font-monospace fw-normal">
											{{ ejemploMonto }}
										</span>
										<small v-if="form[d.campo] === null || form[d.campo] === ''" class="text-body-secondary ms-2">Sin definir, se usan 2</small>
									</div>
								</div>

								<div class="btn-group" role="group" :aria-label="d.titulo">
									<template v-for="n in decimales" :key="n">
										<input
											:id="`${d.campo}_${n}`"
											v-model="form[d.campo]"
											type="radio"
											class="btn-check"
											:name="d.campo"
											:value="n"
										>
										<label :for="`${d.campo}_${n}`" class="btn btn-outline-primary px-3">{{ n }}</label>
									</template>
								</div>
							</div>
						</div>
					</card>
				</section>

				<!-- Formato de impresión -->
				<section id="seccion-impresion" ref="impresion" style="scroll-margin-top: calc(var(--navbar-alto) + 1rem)">
					<card>
						<card-header>
							<div class="d-flex align-items-center gap-3">
								<span class="icono-suave icono-suave-primario" aria-hidden="true">
									<i class="fa-solid fa-print" />
								</span>
								<div class="lh-sm">
									<div class="fw-semibold">Formato de impresión</div>
									<small class="text-body-secondary fw-normal">Tamaño de papel con el que se imprimen las ventas.</small>
								</div>
							</div>
						</card-header>
						<card-body>
							<div class="row g-2" role="radiogroup" aria-label="Formato de impresión">
								<div v-for="f in impresiones" :key="f.valor" class="col-sm-6">
									<input
										:id="`impresion_${f.valor}`"
										v-model="form.formato_impresion"
										type="radio"
										class="btn-check"
										name="formato_impresion"
										:value="f.valor"
									>
									<label
										:for="`impresion_${f.valor}`"
										class="btn btn-outline-primary w-100 h-100 d-flex align-items-center gap-3 py-2 px-3 text-start"
									>
										<i :class="f.icono" class="fa-fw fs-5" aria-hidden="true" />
										<span class="flex-grow-1 lh-sm">
											<span class="d-block fw-semibold">{{ f.titulo }}</span>
											<small class="opacity-75">{{ f.descripcion }}</small>
										</span>
										<i
											v-if="f.valor === String(form.formato_impresion)"
											class="fa-solid fa-circle-check"
											aria-hidden="true"
										/>
									</label>
								</div>
							</div>
						</card-body>
					</card>
				</section>

				<!-- Correlativos -->
				<section id="seccion-correlativos" ref="correlativos" style="scroll-margin-top: calc(var(--navbar-alto) + 1rem)">
					<card>
						<card-header>
							<div class="d-flex align-items-center gap-3">
								<span class="icono-suave icono-suave-success" aria-hidden="true">
									<i class="fa-solid fa-hashtag" />
								</span>
								<div class="lh-sm">
									<div class="fw-semibold">Correlativos de documentos</div>
									<small class="text-body-secondary fw-normal">Prefijo de hasta 5 caracteres que identifica cada tipo de documento; no se pueden repetir.</small>
								</div>
							</div>
						</card-header>
						<div class="list-group list-group-flush">
							<div
								v-for="a in abreviaturas"
								:key="a.campo"
								class="list-group-item d-flex flex-wrap align-items-center gap-3 py-3"
							>
								<span class="icono-suave icono-suave-primario" aria-hidden="true">
									<i :class="a.icono" />
								</span>

								<div class="flex-grow-1 lh-sm">
									<label :for="`input_${a.campo}`" class="fw-semibold">{{ a.texto }}</label>
									<small class="d-block text-body-secondary">{{ a.descripcion }}</small>
								</div>

								<div style="width: 9rem">
									<input
										:id="`input_${a.campo}`"
										v-model="form[a.campo]"
										type="text"
										class="form-control text-uppercase font-monospace text-center"
										:class="{ 'is-invalid': repetidas.includes(a.campo) }"
										maxlength="5"
										:placeholder="a.ejemplo"
									>
									<div class="invalid-feedback">Repetida</div>
								</div>

								<span class="text-body-secondary font-monospace small text-end" style="min-width: 7rem">
									{{ correlativo(a.campo) }}
								</span>
							</div>
						</div>
					</card>
				</section>

				<!-- Barra de guardado: siempre visible al pie -->
				<div class="sticky-bottom pb-3">
					<card>
						<card-body class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
							<span v-if="hayCambios" class="text-warning-emphasis">
								<i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true" />Tiene cambios sin guardar
							</span>
							<span v-else class="text-body-secondary">
								<i class="fa-solid fa-circle-check text-success me-1" aria-hidden="true" />La configuración está al día
							</span>

							<div class="d-flex gap-2">
								<button
									type="button"
									class="btn btn-outline-secondary"
									:disabled="btnGuardar || !hayCambios"
									@click="cancelar"
								>
									<i class="fa-solid fa-rotate-left me-1" aria-hidden="true" />Descartar
								</button>

								<button
									type="submit"
									class="btn btn-primary"
									:disabled="btnGuardar || !hayCambios || repetidas.length > 0"
								>
									<template v-if="btnGuardar">
										<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Guardando
									</template>
									<template v-else>
										<i class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
									</template>
								</button>
							</div>
						</card-body>
					</card>
				</div>
			</div>
		</div>
	</form>
</template>

<script>
	import Accion from '@/mixins/Accion.js'

	// Campos del formulario, para saber si hay cambios
	const CAMPOS = [
		"moneda_id",
		"decimal_monto",
		"formato_impresion",
		"abr_producto",
		"abr_cotizacion",
		"abr_compra",
		"abr_venta",
		"abr_recepcion"
	]

	export default {
		name: "FormParametro",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			parametro: {
				type: Object,
				required: false,
				default: null,
			},
			monedas: {
				type: Array,
				required: false,
				default: () => [],
			},
		},
		mixins: [Accion],
		data: () => ({
			seccion: "moneda",
			decimales: ["0", "1", "2"],
			formatos: [
				{ campo: "decimal_monto", titulo: "Montos", descripcion: "Precios, costos y totales." }
			],
			// Valores de empresa_parametro.formato_impresion (constantes IMPRESION_* de Empresa_parametro_model)
			impresiones: [
				{ valor: "1", titulo: "Ticket", descripcion: "Rollo de 80 mm para impresora térmica.", icono: "fa-solid fa-receipt" },
				{ valor: "2", titulo: "Carta", descripcion: "Hoja tamaño carta con el detalle completo.", icono: "fa-solid fa-file-lines" }
			],
			abreviaturas: [
				{ campo: "abr_producto", texto: "Producto", descripcion: "Código de los productos nuevos.", ejemplo: "PRD", icono: "fa-solid fa-box" },
				{ campo: "abr_cotizacion", texto: "Cotización", descripcion: "Cotizaciones a clientes.", ejemplo: "COT", icono: "fa-solid fa-file-invoice" },
				{ campo: "abr_compra", texto: "Compra", descripcion: "Órdenes de compra a proveedores.", ejemplo: "COM", icono: "fa-solid fa-cart-shopping" },
				{ campo: "abr_venta", texto: "Venta", descripcion: "Ventas del punto de venta.", ejemplo: "VEN", icono: "fa-solid fa-cash-register" },
				{ campo: "abr_recepcion", texto: "Recepción", descripcion: "Ingresos de mercadería.", ejemplo: "REC", icono: "fa-solid fa-truck-ramp-box" }
			]
		}),
		created() {
			this.url   = "mnt/parametro"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				for (let c of CAMPOS) {
					this.fbase[c] = null
				}

				this.fbase.formato_impresion = "1"
			} else {
				this.setDataForm(this.parametro)
			}
		},
		methods: {
			cancelar() {
				if (this.parametro) {
					this.setDataForm(this.parametro)
				} else {
					this.limpiar()
				}
			},
			irA(id) {
				this.seccion = id
				this.$refs[id]?.scrollIntoView({ behavior: "smooth", block: "start" })
			},
			formatear(valor, decimales) {
				let d = decimales === null || decimales === undefined || decimales === "" ? 2 : Number(decimales)

				return valor.toLocaleString("es-GT", {
					minimumFractionDigits: d,
					maximumFractionDigits: d
				})
			},
			abreviatura(campo) {
				return String(this.form[campo] ?? "").trim().toUpperCase()
			},
			correlativo(campo) {
				let abr = this.abreviatura(campo)
				return abr ? `${abr}-000001` : "000001"
			}
		},
		computed: {
			monedaActual() {
				return this.monedas.find(m => String(m.id) === String(this.form.moneda_id)) ?? null
			},
			impresionActual() {
				return this.impresiones.find(f => f.valor === String(this.form.formato_impresion)) ?? null
			},
			ejemploMonto() {
				let simbolo = this.monedaActual ? `${this.monedaActual.simbolo} ` : ""
				return `${simbolo}${this.formatear(1234.5678, this.form.decimal_monto)}`
			},
			// Campos cuya abreviatura se repite en otro documento
			repetidas() {
				return this.abreviaturas
				.map(a => a.campo)
				.filter(c => {
					let abr = this.abreviatura(c)
					return abr !== "" && this.abreviaturas.some(o => o.campo !== c && this.abreviatura(o.campo) === abr)
				})
			},
			hayCambios() {
				return CAMPOS.some(c => {
					let actual   = String(this.form[c] ?? "").trim().toUpperCase()
					let guardado = String(this.parametro?.[c] ?? "").trim().toUpperCase()
					return actual !== guardado
				})
			},
			secciones() {
				let definidas = this.abreviaturas.filter(a => this.abreviatura(a.campo) !== "").length

				return [
					{ id: "moneda", titulo: "Moneda", icono: "fa-solid fa-coins", resumen: this.monedaActual ? this.monedaActual.nombre : "Sin definir" },
					{ id: "formato", titulo: "Formato numérico", icono: "fa-solid fa-calculator", resumen: this.ejemploMonto },
					{ id: "impresion", titulo: "Impresión", icono: "fa-solid fa-print", resumen: this.impresionActual ? this.impresionActual.titulo : "Ticket" },
					{ id: "correlativos", titulo: "Correlativos", icono: "fa-solid fa-hashtag", resumen: `${definidas} de ${this.abreviaturas.length} definidos` }
				]
			}
		},
		watch: {
			pk(valor) {
				if (valor) {
					this.setDataForm(this.parametro)
				} else {
					this.limpiar()
				}
			}
		}
	}
</script>
