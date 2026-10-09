<template>
	<!-- Dos columnas: a la izquierda el formulario con pestañas (8); a la derecha la tarjeta del producto
	     con foto, nombre, clasificación e indicadores (4). En pantallas chicas la tarjeta va primero.
	     El <form> solo envuelve General porque Presentaciones tiene su propio formulario;
	     Activo y Guardar (en el pie) se unen con el atributo form="formProducto" -->
	<div class="row g-3 align-items-start">
		<div class="col-12 col-xl-4 order-xl-2">
			<card>
				<card-body class="text-center">
					<!-- Foto: viaja con Guardar y la API la sube a Google Drive -->
					<label
						for="inputFoto"
						class="foto-producto d-flex flex-column align-items-center justify-content-center mx-auto rounded-3 border border-2 overflow-hidden"
						:class="arrastrando ? 'border-primary bg-primary-subtle' : 'bg-body-tertiary'"
						:title="vistaFoto ? 'Cambiar foto' : 'Agregar foto'"
						@dragover.prevent="arrastrando = true"
						@dragleave.prevent="arrastrando = false"
						@drop.prevent="soltarFoto"
					>
						<img
							v-if="vistaFoto"
							:src="vistaFoto"
							:alt="form.nombre || 'Foto del producto'"
							class="w-100 h-100 object-fit-cover"
							referrerpolicy="no-referrer"
						>
						<template v-else>
							<i class="fa-solid fa-camera fs-3 text-body-secondary" aria-hidden="true" />
							<span class="fw-semibold text-body mt-2">Agregar foto</span>
							<span class="small text-body-secondary">Arrastre una imagen o haga clic</span>
						</template>
					</label>
					<input
						id="inputFoto"
						type="file"
						class="d-none"
						accept="image/*"
						:disabled="btnGuardar"
						@change="elegirFoto"
					>
					<div v-if="vistaFoto || form.imagen" class="d-flex justify-content-center align-items-center gap-2 mt-2 small">
						<span v-if="form.imagen" class="text-primary">Se sube al guardar</span>
						<button
							v-if="vistaFoto"
							type="button"
							class="btn btn-link btn-sm text-body-secondary p-0"
							:disabled="btnGuardar"
							@click="quitarFoto"
						>
							<i class="fa-solid fa-xmark me-1" aria-hidden="true" />Quitar
						</button>
					</div>

					<div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mt-3">
						<h2 class="h5 fw-semibold mb-0 text-break">{{ form.nombre || 'Nuevo producto' }}</h2>
						<span
							v-if="reg !== ''"
							class="badge border rounded-1 fw-semibold"
							:class="Number(form.activo) === 1
								? 'bg-success-subtle text-success-emphasis border-success-subtle'
								: 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
						>{{ Number(form.activo) === 1 ? 'Activo' : 'Inactivo' }}</span>
						<span v-if="form.tipo_producto === 'S'" class="badge border rounded-1 fw-semibold bg-info-subtle text-info-emphasis border-info-subtle">Servicio</span>
					</div>
					<div class="small text-body-secondary mt-1">
						<span class="font-monospace">{{ form.codigo || 'Código al guardar' }}</span>
						<span v-for="t in clasificacion" :key="t"> · {{ t }}</span>
					</div>

					<div class="row g-2 mt-2 text-start">
						<div v-for="i in indicadores" :key="i.titulo" class="col-6">
							<div class="rounded-3 bg-body-tertiary px-3 py-2 h-100">
								<div class="small text-body-secondary">{{ i.titulo }}</div>
								<div class="fw-semibold" :class="i.clase">{{ i.valor }}</div>
							</div>
						</div>
					</div>
				</card-body>
			</card>

			<!-- Existencia en la sucursal de la sesión (la manda la ficha al editar un bien) -->
			<card v-if="verExistencias" class="mt-3">
				<card-header>
					<i class="fa-solid fa-warehouse text-primary" aria-hidden="true" />Existencias
					<slot name="sucursal" />
				</card-header>
				<slot name="existencias" />
			</card>
		</div>

		<div class="col-12 col-xl-8 order-xl-1">
			<card>
				<!-- Pestañas: v-show para que los campos sigan en el formulario al cambiar de pestaña.
				     Con solo General (producto nuevo o servicio) no se muestra la barra -->
				<div v-if="pestanas.length > 1" class="pestanas-producto border-bottom px-2">
					<ul class="nav nav-tabs border-0 flex-nowrap" role="tablist">
						<li v-for="p in pestanas" :key="p.id" class="nav-item" role="presentation">
							<button
								type="button"
								class="nav-link text-nowrap"
								:class="{ active: pestana === p.id }"
								role="tab"
								:aria-selected="pestana === p.id"
								@click="pestana = p.id"
							>
								<i class="fa-solid me-1" :class="p.icono" aria-hidden="true" />{{ p.texto }}
								<span v-if="p.contador !== undefined" class="badge rounded-pill bg-body-tertiary text-body border ms-1">{{ p.contador }}</span>
							</button>
						</li>
					</ul>
				</div>

				<form id="formProducto" autocomplete="off" @submit.prevent="guardar" @invalid.capture="mostrarInvalido">
					<!-- General: datos, precios e inventario y descripción, por secciones -->
					<card-body v-show="pestana === 'general'" data-pestana="general">
						<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
							<i class="fa-solid fa-box text-primary" aria-hidden="true" />Datos generales
						</h6>
						<div class="row g-3 mb-4">
							<div class="col-12 col-md-8">
								<label for="inputNombre" class="form-label">Nombre <span class="text-danger">*</span></label>
								<input
									id="inputNombre"
									v-model="form.nombre"
									type="text"
									class="form-control"
									maxlength="300"
									placeholder="Ej. Mouse inalámbrico"
									required
								>
							</div>

							<div class="col-12 col-md-4">
								<label for="selectTipo" class="form-label">Tipo <span class="text-danger">*</span></label>
								<select id="selectTipo" v-model="form.tipo_producto" class="form-select" required>
									<option value="B">Bien</option>
									<option value="S">Servicio</option>
								</select>
							</div>

							<div class="col-12 col-md-6">
								<!-- El código lo genera la API con la abreviatura de los parámetros de la empresa -->
								<label for="inputCodigo" class="form-label">Código</label>
								<input
									id="inputCodigo"
									:value="form.codigo"
									type="text"
									class="form-control font-monospace bg-body-tertiary"
									placeholder="Se genera al guardar"
									readonly
									tabindex="-1"
								>
							</div>

							<div class="col-12 col-md-6">
								<label for="inputBarra" class="form-label">Código de barras</label>
								<div class="input-group">
									<span class="input-group-text">
										<i class="fa-solid fa-barcode" aria-hidden="true" />
									</span>
									<input
										id="inputBarra"
										v-model="form.codigo_barra"
										type="text"
										class="form-control"
										maxlength="100"
										placeholder="Escanear o escribir"
									>
								</div>
							</div>

							<div class="col-12 col-md-4">
								<label for="selectCategoria" class="form-label">Categoría <span class="text-danger">*</span></label>
								<select id="selectCategoria" v-model="form.categoria_id" class="form-select" required>
									<option :value="null" disabled>Seleccionar...</option>
									<option v-for="c in disponibles(categorias, form.categoria_id)" :key="c.id" :value="String(c.id)">{{ c.nombre }}</option>
								</select>
							</div>

							<div class="col-12 col-md-4">
								<label for="selectMarca" class="form-label">Marca <span class="text-danger">*</span></label>
								<select id="selectMarca" v-model="form.marca_id" class="form-select" required>
									<option :value="null" disabled>Seleccionar...</option>
									<option v-for="m in disponibles(marcas, form.marca_id)" :key="m.id" :value="String(m.id)">{{ m.nombre }}</option>
								</select>
							</div>

							<div class="col-12 col-md-4">
								<label for="selectUnidad" class="form-label">Unidad de medida <span class="text-danger">*</span></label>
								<select id="selectUnidad" v-model="form.unidad_medida_id" class="form-select" required>
									<option :value="null" disabled>Seleccionar...</option>
									<option v-for="u in disponibles(unidades, form.unidad_medida_id)" :key="u.id" :value="String(u.id)">{{ u.nombre }} ({{ u.codigo }})</option>
								</select>
							</div>
						</div>

						<!-- Precios e inventario: el margen se ve en la cabecera; el inventario solo para bienes -->
						<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
							<i class="fa-solid fa-sack-dollar text-primary" aria-hidden="true" />{{ form.tipo_producto !== 'S' ? 'Precios e inventario' : 'Precios' }}
						</h6>
						<div class="row g-3 mb-4">
							<div class="col-12 col-md-4">
								<label for="inputCosto" class="form-label">Costo</label>
								<input
									id="inputCosto"
									v-model="form.costo"
									type="number"
									class="form-control text-end"
									min="0"
									step="0.01"
									placeholder="0.00"
								>
							</div>

							<div class="col-12 col-md-4">
								<label for="inputPrecio" class="form-label">Precio de venta</label>
								<input
									id="inputPrecio"
									v-model="form.precio"
									type="number"
									class="form-control text-end"
									min="0"
									step="0.01"
									placeholder="0.00"
								>
							</div>

							<div v-if="form.tipo_producto !== 'S'" class="col-12 col-md-4">
								<label for="inputMinima" class="form-label">Existencia mínima</label>
								<input
									id="inputMinima"
									v-model="form.existencia_minima"
									type="number"
									class="form-control text-end"
									min="0"
									step="0.01"
									placeholder="0"
								>
							</div>

							<div v-if="form.tipo_producto !== 'S'" class="col-12">
								<div class="form-check form-switch">
									<input
										id="checkVence"
										v-model="form.control_vence"
										class="form-check-input"
										type="checkbox"
										role="switch"
										:true-value="1"
										:false-value="0"
									>
									<label class="form-check-label" for="checkVence">Controlar fecha de vencimiento</label>
								</div>
							</div>

							<!-- Solo al crear un bien: la API la ingresa con un ajuste INI aplicado en la sucursal de la sesión -->
							<template v-if="pk === '' && form.tipo_producto !== 'S'">
								<div class="col-12 col-md-4">
									<label for="inputInicial" class="form-label">Existencia inicial</label>
									<input
										id="inputInicial"
										v-model="form.existencia_inicial"
										type="number"
										class="form-control text-end"
										min="0"
										step="0.01"
										placeholder="0"
									>
								</div>

								<div v-if="Number(form.control_vence) === 1" class="col-12 col-md-4">
									<label for="inputVenceInicial" class="form-label">Fecha de vencimiento</label>
									<input
										id="inputVenceInicial"
										v-model="form.fecha_vence_inicial"
										type="date"
										class="form-control"
										:disabled="!Number(form.existencia_inicial)"
									>
								</div>

								<div class="col-12">
									<div class="form-text mt-0">
										<template v-if="Number(form.existencia_inicial) > 0 && !Number(form.costo)">
											<i class="fa-solid fa-triangle-exclamation text-warning me-1" aria-hidden="true" />Sin costo, la existencia entra valorizada en 0.
										</template>
										<template v-else>
											Opcional. Entra en la unidad de medida, en la sucursal actual, con el costo indicado.
										</template>
									</div>
								</div>
							</template>
						</div>

						<!-- Descripción: texto con formato (EditorTexto) -->
						<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
							<i class="fa-solid fa-align-left text-primary" aria-hidden="true" />Descripción
						</h6>
						<EditorTexto
							v-model="form.descripcion"
							placeholder="Características, usos, medidas…"
						/>
					</card-body>
				</form>

				<!-- Lo que agrega la ficha (fuera del <form>: tiene su propio formulario) -->
				<div v-if="verPresentaciones" v-show="pestana === 'presentaciones'">
					<slot name="presentaciones" />
				</div>

				<!-- Pie fijo: guarda todo el producto, no solo la pestaña visible -->
				<div class="acciones-producto card-footer d-flex flex-wrap align-items-center gap-2">
					<div v-if="reg !== ''" class="form-check form-switch mb-0">
						<input
							id="checkActivo"
							v-model="form.activo"
							form="formProducto"
							class="form-check-input"
							type="checkbox"
							role="switch"
							:true-value="1"
							:false-value="0"
						>
						<label class="form-check-label" for="checkActivo">Activo</label>
					</div>

					<div class="d-flex gap-2 ms-auto">
						<button
							type="button"
							class="btn btn-outline-secondary"
							:disabled="btnGuardar"
							@click="cancelar"
						>
							<i class="fa-solid fa-xmark me-1" aria-hidden="true" />Cancelar
						</button>

						<button
							type="submit"
							form="formProducto"
							class="btn btn-primary"
							:disabled="btnGuardar"
						>
							<template v-if="btnGuardar">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Guardando
							</template>
							<template v-else>
								<i class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
							</template>
						</button>
					</div>
				</div>
			</card>
		</div>
	</div>
</template>

<script>
	import Accion from '@/mixins/Accion.js'
	import EditorTexto from '@/components/ui/EditorTexto.vue'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "FormProducto",
		data: () => ({
			// Vista previa de la imagen elegida (data URL), antes de guardar
			previa: "",
			arrastrando: false,
			pestana: "general"
		}),
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			producto: {
				type: Object,
				required: false,
				default: null,
			},
			categorias: {
				type: Array,
				required: false,
				default: () => [],
			},
			marcas: {
				type: Array,
				required: false,
				default: () => [],
			},
			unidades: {
				type: Array,
				required: false,
				default: () => [],
			},
			// La ficha manda presentaciones (pestaña) y existencias (tarjeta bajo la foto) solo si aplican
			verPresentaciones: {
				type: Boolean,
				default: false,
			},
			verExistencias: {
				type: Boolean,
				default: false,
			},
			// Cuántas presentaciones tiene (contador de la pestaña)
			numPresentaciones: {
				type: Number,
				required: false,
				default: 0,
			},
		},
		mixins: [Accion],
		created() {
			this.url   = "mnt/producto"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.activo            = 1
				this.fbase.tipo_producto     = "B"
				this.fbase.control_vence     = 0
				this.fbase.existencia_minima = 0
				this.fbase.categoria_id      = null
				this.fbase.marca_id          = null
				this.fbase.unidad_medida_id  = null
				this.fbase.existencia_inicial  = null
				this.fbase.fecha_vence_inicial = null
			} else {
				// La ficha crea el formulario cada vez que se abre: al editar llega ya con pk
				this.setDataForm(this.producto)
			}
		},
		methods: {
			cancelar() {
				this.limpiar()
				this.$emit('cancelar')
			},
			// La imagen elegida va en form.imagen (base64) y la API la sube a Drive al guardar
			elegirFoto(e) {
				this.leerFoto(e.target.files?.[0])
				e.target.value = ""
			},
			soltarFoto(e) {
				this.arrastrando = false

				if (!this.btnGuardar) {
					this.leerFoto(e.dataTransfer?.files?.[0])
				}
			},
			leerFoto(archivo) {
				if (!archivo || !archivo.type.startsWith("image/")) {
					return
				}

				const lector = new FileReader()
				lector.onload = () => {
					this.previa = lector.result
					this.form.imagen = {
						name: archivo.name,
						type: archivo.type,
						base64: String(lector.result).split(",")[1]
					}
				}
				lector.readAsDataURL(archivo)
			},
			quitarFoto() {
				this.form.foto = null
				this.form.imagen = null
				this.previa = ""
			},
			// Un obligatorio vacío mientras se ve otra pestaña (Presentaciones, Existencias): se vuelve a General y se avisa en el campo
			mostrarInvalido(e) {
				const pestana = e.target.closest("[data-pestana]")?.dataset.pestana

				if (pestana && pestana !== this.pestana) {
					this.pestana = pestana
					this.$nextTick(() => e.target.reportValidity())
				}
			},
			// Nombre de la opción elegida en un catálogo
			nombreDe(lista, id) {
				return lista.find(e => String(e.id) === String(id))?.nombre ?? ""
			},
			// Opciones activas, más la que ya tiene el producto aunque esté inactiva
			disponibles(lista, actual) {
				return lista.filter(e => Number(e.activo) === 1 || String(e.id) === String(actual))
			}
		},
		computed: {
			// La imagen elegida o la que ya está en Drive (se guarda su id)
			vistaFoto() {
				if (this.form.imagen && this.previa) {
					return this.previa
				}

				return this.form.foto ? `https://lh3.googleusercontent.com/d/${this.form.foto}` : ""
			},
			ganancia() {
				if (!Number(this.form.precio) || !this.form.costo) {
					return "—"
				}

				return formatoMonto(Number(this.form.precio) - Number(this.form.costo))
			},
			margen() {
				let precio = Number(this.form.precio)
				let costo  = Number(this.form.costo)

				if (!precio || !this.form.costo) {
					return "—"
				}

				return (((precio - costo) / precio) * 100).toFixed(1) + " %"
			},
			margenNegativo() {
				return Number(this.form.precio) < Number(this.form.costo)
			},
			// Categoría · marca · unidad de la cabecera
			clasificacion() {
				return [
					this.nombreDe(this.categorias, this.form.categoria_id),
					this.nombreDe(this.marcas, this.form.marca_id),
					this.nombreDe(this.unidades, this.form.unidad_medida_id)
				].filter(t => t)
			},
			// Indicadores de la tarjeta del producto (la existencia tiene su propia tarjeta)
			indicadores() {
				const monto = valor => (valor === null || valor === undefined || valor === "") ? "—" : formatoMonto(valor)
				const color = this.margen === "—" ? "text-body-secondary" : (this.margenNegativo ? "text-danger" : "text-success")

				return [
					{ titulo: "Precio", valor: monto(this.form.precio) },
					{ titulo: "Costo", valor: monto(this.form.costo) },
					{ titulo: "Margen", valor: this.margen, clase: color },
					{ titulo: "Ganancia", valor: this.ganancia, clase: color }
				]
			},
			// Presentaciones solo si la ficha la manda (bienes)
			pestanas() {
				const lista = [
					{ id: "general", texto: "General", icono: "fa-box" }
				]

				if (this.verPresentaciones) {
					lista.push({ id: "presentaciones", texto: "Presentaciones", icono: "fa-boxes-stacked", contador: this.numPresentaciones })
				}

				return lista
			}
		},
		watch: {
			// Al guardar llega la foto que quedó en Drive: se descarta la elegida para no volver a subirla
			"producto.foto"(valor) {
				this.form.foto = valor
				this.form.imagen = null
				this.previa = ""
			},
			// Si la pestaña abierta deja de existir (ej. cambia a servicio), se vuelve a General
			pestanas(lista) {
				if (!lista.some(p => p.id === this.pestana)) {
					this.pestana = "general"
				}
			},
			pk(valor) {
				if (valor) {
					this.setDataForm(this.producto)
				} else {
					this.limpiar()
				}
			}
		},
		components: {
			EditorTexto
		}
	}
</script>
