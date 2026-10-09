<template>
	<div v-if="pk === ''" class="text-center text-body-secondary p-4">
		<div class="fs-3 mb-2 opacity-50"><i class="fa-solid fa-boxes-stacked" aria-hidden="true" /></div>
		Guarde el producto para agregar presentaciones.
	</div>

	<template v-else>
		<table class="table table-sm mb-0">
			<thead>
				<tr>
					<th class="ps-3">Nombre</th>
					<th class="text-end">Equivalencia</th>
					<th class="text-end pe-3">Acciones</th>
				</tr>
			</thead>
			<tbody>
				<!-- Sin presentación se trabaja con la unidad de medida -->
				<tr class="align-middle">
					<td class="ps-3">
						<span class="fw-semibold text-body">{{ unidad?.nombre ?? 'Unidad de medida' }}</span>
						<span class="badge rounded-1 bg-body-tertiary text-body-secondary border ms-1">Base</span>
					</td>
					<td class="text-end text-nowrap text-body-secondary">Unidad de medida</td>
					<td class="pe-3" />
				</tr>

				<tr
					v-for="i in ordenadas"
					:key="i.id"
					class="align-middle"
					:class="{ 'table-active': String(i.id) === reg, 'text-body-secondary': Number(i.activo) !== 1 }"
				>
					<td class="ps-3">
						<span :class="Number(i.activo) === 1 ? 'text-body' : 'text-decoration-line-through'">{{ i.nombre }}</span>
						<span v-if="Number(i.activo) !== 1" class="badge rounded-1 bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle ms-1">Inactiva</span>
					</td>
					<td class="text-end text-nowrap">{{ equivalencia(i.nombre, i.factor, unidad?.codigo ?? '') }}</td>
					<td class="text-end pe-3 text-nowrap">
						<!-- Una unidad toma la equivalencia del catálogo: no se edita, solo se activa o desactiva -->
						<button
							v-if="!i.unidad_medida_id"
							type="button"
							class="btn btn-sm btn-link"
							title="Editar"
							:disabled="btnGuardar"
							@click="editarPresentacion(i)"
						>
							<i class="fa-solid fa-pen" aria-hidden="true" />
						</button>
						<button
							type="button"
							class="btn btn-sm btn-link"
							:title="Number(i.activo) === 1 ? 'Desactivar' : 'Activar'"
							:disabled="btnGuardar"
							@click="cambiarActivo(i)"
						>
							<i class="fa-solid" :class="Number(i.activo) === 1 ? 'fa-toggle-on' : 'fa-toggle-off'" aria-hidden="true" />
						</button>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Captura en línea: Enter agrega (o guarda la que se está editando) -->
		<form class="p-3 border-top bg-body-tertiary" autocomplete="off" @submit.prevent="agregar">
			<div class="small fw-semibold text-body-secondary mb-2">
				{{ reg === '' ? 'Nueva presentación' : 'Editar presentación' }}
			</div>

			<!-- Otra unidad (Libra, con la equivalencia del catálogo) o un empaque propio del producto (Caja 12) -->
			<div v-if="reg === ''" class="btn-group w-100 mb-2" role="group" aria-label="Tipo de presentación">
				<template v-for="t in tipos" :key="t.valor">
					<input
						:id="`tipoPresentacion${t.valor}`"
						v-model="form.tipo"
						type="radio"
						class="btn-check"
						name="tipoPresentacion"
						:value="t.valor"
					>
					<label
						class="btn btn-sm"
						:class="form.tipo === t.valor ? 'btn-primary' : 'btn-outline-secondary'"
						:for="`tipoPresentacion${t.valor}`"
					>
						<i class="fa-solid me-1" :class="t.icono" aria-hidden="true" />{{ t.texto }}
					</label>
				</template>
			</div>

			<template v-if="form.tipo === 'unidad'">
				<select
					v-if="disponibles.length > 0"
					ref="unidad"
					v-model="form.unidad_medida_id"
					class="form-select"
					aria-label="Unidad de la presentación"
					required
				>
					<option :value="null" disabled>Seleccionar unidad...</option>
					<option
						v-for="u in disponibles"
						:key="u.unidad_medida_id"
						:value="String(u.unidad_medida_id)"
						:disabled="u.factor === null"
					>
						{{ u.nombre }} · {{ textoEquivalencia(u) }}{{ u.factor === null ? ' (no cabe entera)' : '' }}
					</option>
				</select>
				<div v-else class="small text-body-secondary">
					No hay unidades con equivalencia a {{ unidad?.nombre ?? 'esta unidad' }}.
					<router-link to="/unidad_medida">Regístrelas en Unidades de medida</router-link>.
				</div>
			</template>

			<div v-else class="row g-2">
				<div class="col-12 col-sm-6 col-xl-12 col-xxl-6">
					<input
						ref="nombre"
						v-model="form.nombre"
						type="text"
						class="form-control"
						maxlength="50"
						placeholder="Ej. Caja 12"
						aria-label="Nombre del empaque"
						required
					>
				</div>
				<div class="col-12 col-sm-6 col-xl-12 col-xxl-6">
					<div class="input-group">
						<span class="input-group-text">Contiene</span>
						<input
							v-model.number="form.factor"
							type="number"
							class="form-control text-end"
							min="1.00001"
							step="any"
							aria-label="Unidades que contiene el empaque"
							required
						>
						<span class="input-group-text">{{ unidad?.codigo ?? '' }}</span>
					</div>
				</div>
			</div>

			<div class="d-flex justify-content-end gap-2 mt-2">
				<button
					v-if="reg !== ''"
					type="button"
					class="btn btn-sm btn-outline-secondary"
					:disabled="btnGuardar"
					@click="cancelarEdicion"
				>
					<i class="fa-solid fa-xmark me-1" aria-hidden="true" />Cancelar
				</button>
				<button
					type="submit"
					class="btn btn-sm btn-primary"
					:disabled="btnGuardar || (form.tipo === 'unidad' && disponibles.length === 0)"
				>
					<span v-if="btnGuardar" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
					<template v-if="reg === ''">
						<i v-if="!btnGuardar" class="fa-solid fa-plus me-1" aria-hidden="true" />Agregar
					</template>
					<template v-else>
						<i v-if="!btnGuardar" class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
					</template>
				</button>
			</div>
			<div class="form-text mb-0">{{ ayuda }}</div>
		</form>
	</template>
</template>

<script>
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'
	import { equivalencia } from '@/utils/numero'

	function numero(valor) {
		return Number(valor ?? 0).toLocaleString("en-US", {
			maximumFractionDigits: 5
		})
	}

	export default {
		name: "PresentacionesProducto",
		props: {
			pk: {
				type: String,
				required: false,
				default: '',
			},
			// Unidad de medida del producto: { codigo, nombre }
			unidad: {
				type: Object,
				required: false,
				default: null,
			},
			presentaciones: {
				type: Array,
				required: false,
				default: () => [],
			},
			// Unidades con equivalencia a la del producto (get_ficha): { unidad_medida_id, nombre, codigo, cantidad, mayor, factor }
			unidades: {
				type: Array,
				required: false,
				default: () => [],
			},
		},
		emits: ["cambio"],
		mixins: [Accion],
		data: () => ({
			tipos: [
				{ valor: "unidad", texto: "Otra unidad", icono: "fa-scale-balanced" },
				{ valor: "empaque", texto: "Empaque", icono: "fa-box" }
			]
		}),
		created() {
			this.url  = "mnt/producto"
			this.urlg = "guardar_presentacion"
			this.autoBuscar  = false
			this._blqconfirm = true

			this.fbase.producto_id = this.pk
			this.fbase.tipo = this.unidades.length > 0 ? "unidad" : "empaque"
			this.fbase.unidad_medida_id = null
			this.lista = [...this.presentaciones]
		},
		methods: {
			agregar() {
				if (this.form.tipo === "unidad" && !this.form.unidad_medida_id) {
					this.$toast.error("Elija la unidad.")
					return
				}

				if (this.form.tipo === "empaque" && !(Number(this.form.factor) > 1)) {
					this.$toast.error(`Indique cuántas ${this.unidad?.codigo ?? 'unidades'} contiene: más de 1.`)
					return
				}

				// Una unidad toma nombre y factor de la equivalencia (los pone la API); un empaque no lleva unidad
				if (this.form.tipo === "empaque") {
					this.form.unidad_medida_id = null
				}

				this.guardar()
				.then(exito => {
					if (exito) {
						// Al editar el mixin actualiza la fila pero deja el registro: vuelve a modo nuevo
						let tipo = this.form.tipo

						this.limpiar()
						this.form.tipo = tipo
						this.$emit("cambio", this.lista)
					}
				})
				.catch(() => {})
			},
			editarPresentacion(obj) {
				this.limpiar()
				this.setDataForm({
					id: String(obj.id),
					tipo: "empaque",
					nombre: obj.nombre,
					factor: Number(obj.factor)
				})
				this.$nextTick(() => this.$refs.nombre?.focus())
			},
			cancelarEdicion() {
				this.limpiar()
			},
			cambiarActivo(obj) {
				this.btnGuardar = true

				api
				.post(`/${this.url}/${this.urlg}/${obj.id}`, {
					nombre: obj.nombre,
					factor: obj.factor,
					activo: Number(obj.activo) === 1 ? 0 : 1
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						Object.assign(obj, res.linea)
						this.$emit("cambio", this.lista)
						this.$toast.success(res.mensaje)
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnGuardar = false
				})
			},
			// "1 Quintal = 100 Libra" desde la unidad grande
			textoEquivalencia(u) {
				let base = this.unidad?.nombre ?? ""

				return u.mayor
					? `1 ${u.nombre} = ${numero(u.cantidad)} ${base}`
					: `1 ${base} = ${numero(u.cantidad)} ${u.nombre}`
			},
			equivalencia
		},
		computed: {
			// Las unidades que todavía no son presentación activa del producto
			disponibles() {
				let usadas = this.lista
				.filter(p => Number(p.activo) === 1 && p.unidad_medida_id)
				.map(p => String(p.unidad_medida_id))

				return this.unidades.filter(u => !usadas.includes(String(u.unidad_medida_id)))
			},
			elegida() {
				return this.unidades.find(u => String(u.unidad_medida_id) === String(this.form.unidad_medida_id)) ?? null
			},
			// Lo que pasará al abrir la medida grande, con lo que se lleva elegido o escrito
			ayuda() {
				let base = this.unidad?.nombre ?? "unidad de medida"

				if (this.form.tipo === "unidad") {
					if (!this.elegida) {
						return "Las equivalencias (1 Quintal = 100 Libras) se registran en Unidades de medida."
					}

					return this.elegida.mayor
						? `Al abrir 1 ${this.elegida.nombre} entran ${numero(this.elegida.cantidad)} ${base}.`
						: `Al abrir 1 ${base} entran ${numero(this.elegida.cantidad)} ${this.elegida.nombre}.`
				}

				let factor = Number(this.form.factor)
				let nombre = (this.form.nombre ?? "").trim()

				return factor > 1 && nombre !== ""
					? `Al abrir 1 ${nombre} entran ${numero(factor)} ${base}.`
					: `Un empaque es más grande que la unidad: cuántas ${base} trae, ej. Caja 12 = 12.`
			},
			// Activas primero y de menor a mayor contenido
			ordenadas() {
				return [...this.lista].sort((a, b) =>
					Number(b.activo) - Number(a.activo) || Number(a.factor) - Number(b.factor)
				)
			}
		}
	}
</script>
