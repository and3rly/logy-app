<template>
	<form @submit.prevent="enviar" autocomplete="off">
		<fieldset :disabled="btnGuardar">
			<div class="row g-3">
				<div v-if="!fijo" class="col-12 col-md-7">
					<label for="selectProductoConversion" class="form-label">Producto <span class="text-danger">*</span></label>
					<select id="selectProductoConversion" v-model="form.producto_id" class="form-select" required>
						<option :value="null" disabled>Seleccionar...</option>
						<option v-for="p in productos" :key="p.producto_id" :value="String(p.producto_id)">{{ p.codigo }} · {{ p.nombre }}</option>
					</select>
				</div>

				<div v-if="!fijo?.producto_presentacion_id" class="col-12" :class="{ 'col-md-5': !fijo }">
					<label for="selectPresentacionConversion" class="form-label">Presentación <span class="text-danger">*</span></label>
					<select id="selectPresentacionConversion" v-model="form.producto_presentacion_id" class="form-select" required :disabled="!producto">
						<option :value="null" disabled>Seleccionar...</option>
						<option v-for="pre in presentaciones" :key="pre.producto_presentacion_id" :value="String(pre.producto_presentacion_id)">
							{{ pre.nombre }} ({{ equivalencia(pre.nombre, pre.factor, producto?.nunidad ?? '') }})
						</option>
					</select>
				</div>

				<!-- Abrir: de la medida grande a la pequeña; armar: al revés. Sirve con la presentación más grande o más pequeña que la unidad -->
				<div v-if="!fijo" class="col-12">
					<span class="form-label d-block">Sentido</span>
					<div class="btn-group w-100" role="group" aria-label="Sentido de la conversión">
						<template v-for="a in acciones" :key="a.valor">
							<input
								:id="`conversion${a.valor}`"
								v-model="form.accion"
								type="radio"
								class="btn-check"
								name="accionConversion"
								:value="a.valor"
							>
							<label class="btn" :class="form.accion === a.valor ? 'btn-primary' : 'btn-outline-secondary'" :for="`conversion${a.valor}`">
								<i class="fa-solid me-1" :class="a.icono" aria-hidden="true" />{{ a.texto }}
							</label>
						</template>
					</div>
					<div class="form-text">
						<template v-if="presentacion">{{ abrir ? `Pasa ${grande.nombre} a ${pequena.nombre}.` : `Junta ${pequena.nombre} para formar ${grande.nombre}.` }}</template>
						<template v-else>Abrir pasa la medida grande a la pequeña; armar, al revés.</template>
					</div>
				</div>

				<div class="col-12" :class="{ 'col-md-4': !fijo }">
					<label for="inputCantidadConversion" class="form-label">
						{{ presentacion ? `${grande.nombre} a ${abrir ? 'abrir' : 'armar'}` : 'Cantidad' }} <span class="text-danger">*</span>
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
						:placeholder="abrir ? 'Ej. Abierto para venta al detalle' : 'Ej. Armado para despacho'"
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
	import { formatoCantidad, equivalencia } from '@/utils/numero'

	const ABRIR = "abrir"

	export default {
		name: "FormConversion",
		props: {
			// Productos con presentaciones y su existencia (Stock_model::existencias)
			productos: {
				type: Array,
				required: true,
			},
			// { producto_id, producto_presentacion_id }: abre esa medida desde el punto de venta.
			// Sin presentación se abre la unidad de medida en una de sus presentaciones más pequeñas
			fijo: {
				type: Object,
				required: false,
				default: null,
			},
			// Tope de lo que se puede abrir cuando ya hay algo en el ticket del punto de venta
			tope: {
				type: Number,
				required: false,
				default: null,
			},
		},
		emits: ["actualizar", "cancelar"],
		mixins: [Accion],
		data: () => ({
			errorCantidad: ""
		}),
		created() {
			this.url   = "inv/conversion"
			this._emit = true
			this.autoBuscar = false

			this.fbase = {
				accion: ABRIR,
				producto_id: this.fijo ? String(this.fijo.producto_id) : null,
				producto_presentacion_id: this.fijo?.producto_presentacion_id ? String(this.fijo.producto_presentacion_id) : null,
				cantidad: 1,
				observacion: ""
			}
		},
		mounted() {
			// Al abrir la unidad desde el punto de venta con una sola presentación más pequeña, ya va elegida
			if (this.fijo && !this.fijo.producto_presentacion_id && this.presentaciones.length === 1) {
				this.form.producto_presentacion_id = String(this.presentaciones[0].producto_presentacion_id)
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
						: (this.abrir ? `No hay existencia de ${this.grande.nombre}.` : `No hay ${this.pequena.nombre} suficientes.`)
					return
				}

				// La API sigue el sentido de la presentación: explosión sale de ella, implosión entra a ella
				this.form.sentido = this.abrir !== this.menor ? "EXPLOSION" : "IMPLOSION"

				this.guardar()
			},
			enfocar() {
				this.$refs.cantidad?.focus()
				this.$refs.cantidad?.select()
			},
			formatoCantidad,
			equivalencia
		},
		computed: {
			abrir() {
				return this.form.accion === ABRIR
			},
			acciones() {
				let grande = this.presentacion ? ` ${this.grande.nombre}` : ""

				return [
					{ valor: "abrir", texto: `Abrir${grande}`, icono: "fa-box-open" },
					{ valor: "armar", texto: `Armar${grande}`, icono: "fa-box" }
				]
			},
			producto() {
				return this.productos.find(p => String(p.producto_id) === String(this.form.producto_id)) ?? null
			},
			// Activas o con existencia (una inactiva solo sirve para sacar lo que le queda).
			// Abriendo la unidad desde el punto de venta: solo las más pequeñas que ella
			presentaciones() {
				return (this.producto?.presentaciones ?? []).filter(pre =>
					(Number(pre.activo) === 1 || Number(pre.existencia) > 0) &&
					(!this.fijo || this.fijo.producto_presentacion_id || Number(pre.factor) < 1)
				)
			},
			presentacion() {
				return this.presentaciones.find(pre => String(pre.producto_presentacion_id) === String(this.form.producto_presentacion_id)) ?? null
			},
			factor() {
				return Number(this.presentacion?.factor ?? 0)
			},
			// La presentación es más pequeña que la unidad (1 Quintal = 100 Libras)
			menor() {
				return this.factor > 0 && this.factor < 1
			},
			unidad() {
				return {
					nombre: this.producto?.nunidad ?? "",
					existencia: Number(this.producto?.existencia ?? 0)
				}
			},
			pre() {
				return {
					nombre: this.presentacion?.nombre ?? "",
					existencia: Number(this.presentacion?.existencia ?? 0)
				}
			},
			grande() {
				return this.menor ? this.unidad : this.pre
			},
			pequena() {
				return this.menor ? this.pre : this.unidad
			},
			// Cuántas pequeñas trae una grande
			porGrande() {
				return this.menor ? Math.round(1 / this.factor) : this.factor
			},
			// Medidas grandes enteras que se pueden abrir o armar con la existencia actual
			maximo() {
				if (!this.presentacion || this.factor <= 0) {
					return 0
				}

				let max = this.abrir
					? Math.floor(this.grande.existencia + 1e-9)
					: Math.floor(this.pequena.existencia / this.porGrande + 1e-9)

				return this.tope === null ? max : Math.min(max, Math.floor(this.tope))
			},
			lados() {
				let cantidad = Number(this.form.cantidad || 0)
				let grande = {
					nombre: this.grande.nombre,
					cantidad,
					antes: this.grande.existencia
				}
				let pequena = {
					nombre: this.pequena.nombre,
					cantidad: Math.round(cantidad * this.porGrande * 100) / 100,
					antes: this.pequena.existencia
				}
				let [sale, entra] = this.abrir ? [grande, pequena] : [pequena, grande]

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
			// Otro producto: la presentación elegida deja de valer
			"form.producto_id"() {
				if (!this.fijo) {
					this.form.producto_presentacion_id = this.presentaciones.length === 1
						? String(this.presentaciones[0].producto_presentacion_id)
						: null
				}
			}
		}
	}
</script>
