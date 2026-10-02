<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<!-- Datos generales -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-box text-primary" aria-hidden="true" />Datos generales
		</h6>
		<div class="row g-2 mb-4">
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
		</div>

		<!-- Clasificación -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-tags text-primary" aria-hidden="true" />Clasificación
		</h6>
		<div class="row g-2 mb-4">
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

		<!-- Precios: el margen se calcula, no se guarda -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-sack-dollar text-primary" aria-hidden="true" />Precios
		</h6>
		<div class="row g-2 mb-4">
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

			<div class="col-12 col-md-4">
				<label for="inputMargen" class="form-label">Margen</label>
				<input
					id="inputMargen"
					:value="margen"
					type="text"
					class="form-control text-end fw-semibold"
					:class="margenNegativo ? 'text-danger' : 'text-success'"
					readonly
					tabindex="-1"
				>
			</div>
		</div>

		<!-- Inventario: solo para bienes -->
		<template v-if="form.tipo_producto !== 'S'">
			<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
				<i class="fa-solid fa-warehouse text-primary" aria-hidden="true" />Inventario
			</h6>
			<div class="row g-2 align-items-end mb-4">
				<div class="col-12 col-md-4">
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

				<div class="col-12 col-md-8">
					<div class="form-check form-switch mb-2">
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
			</div>
		</template>

		<!-- Descripción: texto con formato (EditorTexto) -->
		<h6 class="d-flex align-items-center gap-2 fw-semibold text-body-secondary mb-2">
			<i class="fa-solid fa-align-left text-primary" aria-hidden="true" />Descripción
		</h6>
		<div class="mb-4">
			<EditorTexto
				v-model="form.descripcion"
				placeholder="Características, usos, medidas…"
			/>
		</div>

		<div class="mb-4" v-if="reg !== ''">
			<div class="form-check form-switch">
				<input
					id="checkActivo"
					v-model="form.activo"
					class="form-check-input"
					type="checkbox"
					role="switch"
					:true-value="1"
					:false-value="0"
				>
				<label class="form-check-label" for="checkActivo">Activo</label>
			</div>
		</div>

		<div class="d-flex justify-content-end gap-2 pt-3 border-top">
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
	</form>
</template>

<script>
	import Accion from '@/mixins/Accion.js'
	import EditorTexto from '@/components/ui/EditorTexto.vue'

	export default {
		name: "FormProducto",
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
			// Opciones activas, más la que ya tiene el producto aunque esté inactiva
			disponibles(lista, actual) {
				return lista.filter(e => Number(e.activo) === 1 || String(e.id) === String(actual))
			}
		},
		computed: {
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
			}
		},
		watch: {
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
