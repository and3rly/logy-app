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
					<th class="text-end">Contiene</th>
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
					<td class="text-end text-nowrap">1 {{ unidad?.codigo }}</td>
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
					<td class="text-end text-nowrap">{{ formatoCantidad(i.factor) }} {{ unidad?.codigo }}</td>
					<td class="text-end pe-3 text-nowrap">
						<button
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
			<div class="row g-2">
				<div class="col-12 col-sm-6 col-xl-12 col-xxl-6">
					<input
						ref="nombre"
						v-model="form.nombre"
						type="text"
						class="form-control"
						maxlength="50"
						placeholder="Ej. Caja 12"
						aria-label="Nombre de la presentación"
						required
					>
				</div>
				<div class="col-12 col-sm-6 col-xl-12 col-xxl-6">
					<div class="input-group">
						<input
							v-model="form.factor"
							type="number"
							class="form-control text-end"
							min="0.00001"
							step="any"
							placeholder="Contiene"
							aria-label="Cantidad de unidades que contiene"
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
				<button type="submit" class="btn btn-sm btn-primary" :disabled="btnGuardar">
					<span v-if="btnGuardar" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
					<template v-if="reg === ''">
						<i v-if="!btnGuardar" class="fa-solid fa-plus me-1" aria-hidden="true" />Agregar
					</template>
					<template v-else>
						<i v-if="!btnGuardar" class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />Guardar
					</template>
				</button>
			</div>
			<div class="form-text mb-0">
				Contiene: cantidad en {{ unidad?.codigo ?? 'la unidad de medida' }} que trae cada presentación.
			</div>
		</form>
	</template>
</template>

<script>
	import Accion from '@/mixins/Accion.js'
	import api, { mensajeError } from '@/services/api'

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
		},
		emits: ["cambio"],
		mixins: [Accion],
		created() {
			this.url  = "mnt/producto"
			this.urlg = "guardar_presentacion"
			this.autoBuscar  = false
			this._blqconfirm = true

			this.fbase.producto_id = this.pk
			this.lista = [...this.presentaciones]
		},
		methods: {
			agregar() {
				this.guardar()
				.then(exito => {
					if (exito) {
						// Al editar el mixin actualiza la fila pero deja el registro: vuelve a modo nuevo
						this.limpiar()
						this.$emit("cambio", this.lista)
						this.$refs.nombre?.focus()
					}
				})
				.catch(() => {})
			},
			editarPresentacion(obj) {
				this.limpiar()
				this.setDataForm({
					id: String(obj.id),
					nombre: obj.nombre,
					factor: Number(obj.factor)
				})
				this.$refs.nombre?.focus()
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
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 5
				})
			}
		},
		computed: {
			// Activas primero y de menor a mayor contenido
			ordenadas() {
				return [...this.lista].sort((a, b) =>
					Number(b.activo) - Number(a.activo) || Number(a.factor) - Number(b.factor)
				)
			}
		}
	}
</script>
