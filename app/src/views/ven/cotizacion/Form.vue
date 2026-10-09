<template>
	<form @submit.prevent="guardar" autocomplete="off">
		<fieldset :disabled="!editable || btnGuardar">
			<div class="mb-3">
				<label for="cotizacionCliente" class="form-label">Cliente</label>
				<BuscarCliente
					v-if="editable"
					id="cotizacionCliente"
					v-model="cliente"
					:departamentos="catalogo.departamentos"
					:municipios="catalogo.municipios"
				/>
				<!-- Ya enviada: los datos que se copiaron del cliente -->
				<div v-else class="border rounded-3 px-3 py-2 bg-body-tertiary lh-sm">
					<div class="fw-semibold text-body">{{ cotizacion?.cliente_nombre }}</div>
					<div class="small text-body-secondary">
						NIT {{ cotizacion?.cliente_identificacion || 'CF' }}
						<template v-if="cotizacion?.cliente_telefono"> · {{ cotizacion.cliente_telefono }}</template>
					</div>
				</div>
			</div>

			<!-- Al elegir el cliente toma su lista; se puede cambiar. Aplica a los productos que se agreguen después de Guardar -->
			<div v-if="listasDisponibles.length > 0" class="mb-3">
				<label for="selectListaPrecioCot" class="form-label">Lista de precios</label>
				<select id="selectListaPrecioCot" v-model="form.lista_precio_id" class="form-select">
					<option :value="null">Precio general</option>
					<option v-for="l in listasDisponibles" :key="l.id" :value="String(l.id)">
						{{ l.nombre }}{{ Number(l.activo) !== 1 ? ' (inactiva)' : '' }}
					</option>
				</select>
			</div>

			<div class="row g-2 mb-3">
				<div class="col-6">
					<label for="inputValidaHasta" class="form-label">Válida hasta <span class="text-danger">*</span></label>
					<input
						id="inputValidaHasta"
						v-model="form.valida_hasta"
						type="date"
						class="form-control"
						:min="editable ? fechaHoy : null"
						required
					>
					<div v-if="diasValidez !== null" class="form-text" :class="{ 'text-danger': diasValidez < 0 }">
						{{ textoValidez }}
					</div>
				</div>

				<div class="col-6">
					<label for="selectFormaPagoCot" class="form-label">Forma de pago</label>
					<select id="selectFormaPagoCot" v-model="form.forma_pago_id" class="form-select">
						<option :value="null">Sin indicar</option>
						<option
							v-for="f in disponibles(catalogo.formas_pago, form.forma_pago_id)"
							:key="f.id"
							:value="String(f.id)"
						>{{ f.nombre }}</option>
					</select>
				</div>

				<div class="col-6">
					<label for="selectMonedaCot" class="form-label">Moneda <span class="text-danger">*</span></label>
					<select id="selectMonedaCot" v-model="form.moneda_id" class="form-select" required>
						<option :value="null" disabled>Seleccionar...</option>
						<option
							v-for="m in disponibles(catalogo.monedas, form.moneda_id)"
							:key="m.id"
							:value="String(m.id)"
						>{{ m.nombre }} ({{ m.simbolo }})</option>
					</select>
				</div>

				<!-- La serie solo se elige al crear y si hay más de una -->
				<div v-if="pk === '' && catalogo.series.length > 1" class="col-6">
					<label for="selectSerieCot" class="form-label">Serie</label>
					<select id="selectSerieCot" v-model="form.cotizacion_serie_id" class="form-select">
						<option v-for="s in catalogo.series" :key="s.id" :value="String(s.id)">{{ s.codigo }} · {{ s.nombre }}</option>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<label for="inputReferencia" class="form-label">Referencia</label>
				<input
					id="inputReferencia"
					v-model="form.referencia"
					type="text"
					class="form-control"
					maxlength="300"
					placeholder="Proyecto bodega norte"
				>
			</div>

			<div class="mb-3">
				<label for="inputCondiciones" class="form-label">Condiciones</label>
				<EditorTexto
					id="inputCondiciones"
					v-model="form.condiciones"
					:disabled="!editable"
					placeholder="Entrega en 3 días hábiles. Precios sujetos a existencia."
				/>
				<div class="form-text">Se imprimen en la cotización.</div>
			</div>

			<div class="mb-3">
				<label for="inputObservaciones" class="form-label">Observaciones</label>
				<EditorTexto
					id="inputObservaciones"
					v-model="form.observaciones"
					:disabled="!editable"
				/>
			</div>

			<div v-if="editable" class="d-grid">
				<!-- En una cotización nueva es la acción principal; después, secundaria (la principal es Enviar) -->
				<button type="submit" class="btn" :class="pk === '' ? 'btn-primary' : 'btn-outline-primary'">
					<template v-if="btnGuardar">
						<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Guardando
					</template>
					<template v-else>
						<i class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
					</template>
				</button>
			</div>
		</fieldset>
	</form>
</template>

<script>
	import Accion from '@/mixins/Accion.js'
	import BuscarCliente from '../venta/BuscarCliente.vue'
	import EditorTexto from '@/components/ui/EditorTexto.vue'

	export default {
		name: "FormCotizacion",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			cotizacion: {
				type: Object,
				required: false,
				default: null,
			},
			catalogo: {
				type: Object,
				required: true,
			},
			monedaDefecto: {
				type: [String, Number],
				required: false,
				default: null,
			},
			fechaHoy: {
				type: String,
				required: false,
				default: '',
			},
			diasDefecto: {
				type: Number,
				required: false,
				default: 15,
			},
			editable: {
				type: Boolean,
				required: false,
				default: true,
			},
		},
		emits: ["actualizar"],
		mixins: [Accion],
		components: {
			BuscarCliente,
			EditorTexto
		},
		data: () => ({
			cliente: null
		}),
		created() {
			this.url   = "ven/cotizacion"
			this._key  = "id"
			this._emit = true
			this.autoBuscar = false

			if (this.pk === "") {
				this.fbase.cliente_id          = null
				this.fbase.lista_precio_id     = null
				this.fbase.forma_pago_id       = null
				this.fbase.moneda_id           = this.monedaDefecto ? String(this.monedaDefecto) : null
				this.fbase.cotizacion_serie_id = this.catalogo.series.length ? String(this.catalogo.series[0].id) : null
				this.fbase.valida_hasta        = this.sumarDias(this.fechaHoy, this.diasDefecto)
				this.fbase.referencia          = null
				this.fbase.condiciones         = null
				this.fbase.observaciones       = null
			} else {
				this.cargar()
			}
		},
		methods: {
			cargar() {
				this.setDataForm(this.cotizacion)

				this.form.forma_pago_id   = this.cotizacion.forma_pago_id ? String(this.cotizacion.forma_pago_id) : null
				this.form.moneda_id       = String(this.cotizacion.moneda_id)
				this.form.lista_precio_id = this.cotizacion.lista_precio_id ? String(this.cotizacion.lista_precio_id) : null
				// Con la lista de la cotización (no la del cliente): el watch de cliente la vuelve a poner tal cual
				this.cliente = this.cotizacion.cliente_id ? {
					id: this.cotizacion.cliente_id,
					nombre: this.cotizacion.cliente_nombre,
					identificacion: this.cotizacion.cliente_identificacion,
					lista_precio_id: this.cotizacion.lista_precio_id
				} : null
			},
			// Opciones activas, más la que ya tiene la cotización aunque esté inactiva
			disponibles(lista, actual) {
				return (lista ?? []).filter(e => Number(e.activo) === 1 || String(e.id) === String(actual))
			},
			// "2026-09-28" + 15 → "2026-10-13" (sin zona horaria)
			sumarDias(fecha, dias) {
				if (!fecha) {
					return null
				}

				let [a, m, d] = fecha.split("-").map(Number)
				let tmp = new Date(Date.UTC(a, m - 1, d + dias))

				return tmp.toISOString().slice(0, 10)
			},
			diasEntre(desde, hasta) {
				let [a1, m1, d1] = desde.split("-").map(Number)
				let [a2, m2, d2] = hasta.split("-").map(Number)

				return Math.round((Date.UTC(a2, m2 - 1, d2) - Date.UTC(a1, m1 - 1, d1)) / 86400000)
			}
		},
		computed: {
			diasValidez() {
				if (!this.form.valida_hasta || !this.fechaHoy) {
					return null
				}

				return this.diasEntre(this.fechaHoy, this.form.valida_hasta)
			},
			textoValidez() {
				let dias = this.diasValidez

				if (dias < 0) {
					return "Vencida"
				}

				if (dias === 0) {
					return "Vence hoy"
				}

				return dias === 1 ? "1 día" : `${dias} días`
			},
			// Listas activas, más la que ya tiene la cotización aunque esté inactiva
			listasDisponibles() {
				return this.disponibles(this.catalogo.listas_precio, this.cotizacion?.lista_precio_id)
			}
		},
		watch: {
			// Al cambiar el cliente se propone su lista (si se puede elegir) o el precio general
			cliente(valor) {
				let lista = this.listasDisponibles.find(l => String(l.id) === String(valor?.lista_precio_id ?? ""))

				this.form.cliente_id      = valor ? valor.id : null
				this.form.lista_precio_id = lista ? String(lista.id) : null
			},
			pk(valor) {
				if (valor) {
					this.cargar()
				} else {
					this.limpiar()
					this.cliente = null
				}
			}
		}
	}
</script>
