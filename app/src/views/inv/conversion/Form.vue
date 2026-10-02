<template>
	<form @submit.prevent="enviar" autocomplete="off">
		<fieldset :disabled="btnGuardar">
			<div class="row g-3">
				<div v-if="!fijo" class="col-12">
					<span class="form-label d-block">Sentido</span>
					<div class="btn-group w-100" role="group" aria-label="Sentido de la conversión">
						<template v-for="s in sentidos" :key="s.valor">
							<input
								:id="`conversion${s.valor}`"
								v-model="form.sentido"
								type="radio"
								class="btn-check"
								name="sentidoConversion"
								:value="s.valor"
							>
							<label class="btn" :class="form.sentido === s.valor ? 'btn-primary' : 'btn-outline-secondary'" :for="`conversion${s.valor}`">
								<i class="fa-solid me-1" :class="s.icono" aria-hidden="true" />{{ s.texto }}
							</label>
						</template>
					</div>
					<div class="form-text">{{ explosion ? 'Abre presentaciones y las pasa a unidades sueltas.' : 'Arma presentaciones con unidades sueltas.' }}</div>
				</div>

				<template v-if="!fijo">
					<div class="col-12 col-md-7">
						<label for="selectProductoConversion" class="form-label">Producto <span class="text-danger">*</span></label>
						<select id="selectProductoConversion" v-model="form.producto_id" class="form-select" required>
							<option :value="null" disabled>Seleccionar...</option>
							<option v-for="p in productos" :key="p.producto_id" :value="String(p.producto_id)">{{ p.codigo }} · {{ p.nombre }}</option>
						</select>
					</div>

					<div class="col-12 col-md-5">
						<label for="selectPresentacionConversion" class="form-label">Presentación <span class="text-danger">*</span></label>
						<select id="selectPresentacionConversion" v-model="form.producto_presentacion_id" class="form-select" required :disabled="!producto">
							<option :value="null" disabled>Seleccionar...</option>
							<option v-for="pre in presentaciones" :key="pre.producto_presentacion_id" :value="String(pre.producto_presentacion_id)">
								{{ pre.nombre }} ({{ formatoCantidad(pre.factor) }} {{ producto?.nunidad }})
							</option>
						</select>
					</div>
				</template>

				<div class="col-12" :class="{ 'col-md-4': !fijo }">
					<label for="inputCantidadConversion" class="form-label">
						{{ presentacion ? `Cantidad de ${presentacion.nombre}` : 'Cantidad' }} <span class="text-danger">*</span>
					</label>
					<input
						id="inputCantidadConversion"
						ref="cantidad"
						v-model.number="form.cantidad"
						type="number"
						class="form-control"
						:class="{ 'is-invalid': errorCantidad }"
						min="1"
						step="1"
						:max="maximo || undefined"
						required
						@input="errorCantidad = ''"
					>
					<div v-if="errorCantidad" class="invalid-feedback">{{ errorCantidad }}</div>
					<div v-else-if="presentacion" class="form-text">Máximo {{ formatoCantidad(maximo) }}</div>
				</div>

				<div class="col-12" :class="{ 'col-md-8': !fijo }">
					<label for="inputObservacionConversion" class="form-label">Observación</label>
					<input
						id="inputObservacionConversion"
						v-model="form.observacion"
						type="text"
						class="form-control"
						maxlength="300"
						:placeholder="explosion ? 'Ej. Caja abierta para venta al detalle' : 'Ej. Cajas armadas para despacho'"
					>
				</div>

				<!-- Resultado esperado: lo que sale y lo que entra, con la existencia antes y después -->
				<div v-if="presentacion" class="col-12">
					<div class="row g-2">
						<div v-for="lado in lados" :key="lado.titulo" class="col-12 col-sm-6">
							<div class="rounded-3 border p-3 h-100" :class="lado.fondo">
								<div class="small mb-1" :class="lado.texto">
									<i class="fa-solid me-1" :class="lado.icono" aria-hidden="true" />{{ lado.titulo }}
								</div>
								<div class="fs-5 fw-bold text-nowrap" :class="lado.texto">{{ formatoCantidad(lado.cantidad) }} {{ lado.nombre }}</div>
								<div class="small text-body-secondary">
									Existencia: {{ formatoCantidad(lado.antes) }} → {{ formatoCantidad(lado.despues) }}
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-12 d-flex justify-content-end gap-2">
					<button type="button" class="btn btn-outline-secondary" @click="$emit('cancelar')">Cancelar</button>
					<button type="submit" class="btn btn-primary">
						<template v-if="btnGuardar">
							<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Guardando
						</template>
						<template v-else>
							<i class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
						</template>
					</button>
				</div>
			</div>
		</fieldset>
	</form>
</template>

<script>
	import Accion from '@/mixins/Accion.js'
	import { formatoCantidad } from '@/utils/numero'

	const EXPLOSION = "EXPLOSION"

	export default {
		name: "FormConversion",
		props: {
			// Productos con presentaciones y su existencia (Stock_model::existencias)
			productos: {
				type: Array,
				required: true,
			},
			// { sentido, producto_id, producto_presentacion_id }: fija la conversión (ej. abrir una caja desde el punto de venta)
			fijo: {
				type: Object,
				required: false,
				default: null,
			},
			// Tope de presentaciones a abrir cuando ya hay algunas en el ticket del punto de venta
			tope: {
				type: Number,
				required: false,
				default: null,
			},
		},
		emits: ["actualizar", "cancelar"],
		mixins: [Accion],
		data: () => ({
			errorCantidad: "",
			sentidos: [
				{ valor: "EXPLOSION", texto: "Explosión (abrir)", icono: "fa-box-open" },
				{ valor: "IMPLOSION", texto: "Implosión (armar)", icono: "fa-box" }
			]
		}),
		created() {
			this.url   = "inv/conversion"
			this._emit = true
			this.autoBuscar = false

			this.fbase = {
				sentido: this.fijo?.sentido ?? EXPLOSION,
				producto_id: this.fijo ? String(this.fijo.producto_id) : null,
				producto_presentacion_id: this.fijo ? String(this.fijo.producto_presentacion_id) : null,
				cantidad: 1,
				observacion: ""
			}
		},
		methods: {
			enviar() {
				let cantidad = Number(this.form.cantidad)

				if (!Number.isInteger(cantidad) || cantidad < 1) {
					this.errorCantidad = "Ingrese un número entero mayor que cero."
					return
				}

				if (cantidad > this.maximo) {
					this.errorCantidad = this.maximo > 0
						? `Solo alcanza para ${formatoCantidad(this.maximo)}.`
						: (this.explosion ? "No hay existencia de esta presentación." : "No hay unidades sueltas suficientes.")
					return
				}

				this.guardar()
			},
			enfocar() {
				this.$refs.cantidad?.focus()
				this.$refs.cantidad?.select()
			},
			formatoCantidad
		},
		computed: {
			explosion() {
				return this.form.sentido === EXPLOSION
			},
			producto() {
				return this.productos.find(p => String(p.producto_id) === String(this.form.producto_id)) ?? null
			},
			// Para armar solo las activas; para abrir, también las inactivas que tienen existencia
			presentaciones() {
				return (this.producto?.presentaciones ?? []).filter(pre =>
					Number(pre.activo) === 1 || (this.explosion && Number(pre.existencia) > 0)
				)
			},
			presentacion() {
				return this.presentaciones.find(pre => String(pre.producto_presentacion_id) === String(this.form.producto_presentacion_id)) ?? null
			},
			factor() {
				return Number(this.presentacion?.factor ?? 0)
			},
			existenciaPresentacion() {
				return Number(this.presentacion?.existencia ?? 0)
			},
			existenciaUnidad() {
				return Number(this.producto?.existencia ?? 0)
			},
			// Presentaciones enteras que se pueden abrir o armar con la existencia actual
			maximo() {
				if (!this.presentacion || this.factor <= 0) {
					return 0
				}

				let max = this.explosion
					? Math.floor(this.existenciaPresentacion + 1e-9)
					: Math.floor(this.existenciaUnidad / this.factor + 1e-9)

				return this.tope === null ? max : Math.min(max, Math.floor(this.tope))
			},
			unidades() {
				return Math.round(Number(this.form.cantidad || 0) * this.factor * 100) / 100
			},
			lados() {
				let cantidad = Number(this.form.cantidad || 0)
				let caja = {
					nombre: this.presentacion.nombre,
					cantidad,
					antes: this.existenciaPresentacion
				}
				let unidad = {
					nombre: this.producto.nunidad,
					cantidad: this.unidades,
					antes: this.existenciaUnidad
				}
				let [sale, entra] = this.explosion ? [caja, unidad] : [unidad, caja]

				return [
					{
						...sale,
						titulo: "Sale",
						icono: "fa-arrow-down",
						fondo: "bg-danger-subtle border-danger-subtle",
						texto: "text-danger-emphasis",
						despues: sale.antes - sale.cantidad
					},
					{
						...entra,
						titulo: "Entra",
						icono: "fa-arrow-up",
						fondo: "bg-success-subtle border-success-subtle",
						texto: "text-success-emphasis",
						despues: entra.antes + entra.cantidad
					}
				]
			}
		},
		watch: {
			// Otro producto o sentido: la presentación elegida puede dejar de valer
			"form.producto_id"() {
				if (!this.fijo) {
					this.form.producto_presentacion_id = this.presentaciones.length === 1
						? String(this.presentaciones[0].producto_presentacion_id)
						: null
				}
			},
			"form.sentido"() {
				if (!this.fijo && !this.presentacion) {
					this.form.producto_presentacion_id = null
				}
			}
		}
	}
</script>
