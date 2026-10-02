<template>
	<div class="p-3">
		<!-- Total en la unidad de medida (las presentaciones se convierten con su factor) -->
		<div class="rounded-3 border bg-body-tertiary p-3">
			<div class="d-flex justify-content-between align-items-center small text-body-secondary mb-1">
				<span>Existencia total</span>
				<span
					class="badge border rounded-1 fw-semibold"
					:class="estado.clase"
				>{{ estado.texto }}</span>
			</div>
			<div class="fs-2 fw-bold text-body lh-sm text-nowrap">
				{{ formatoCantidad(total) }}
				<span class="fs-6 fw-semibold text-body-secondary">{{ unidad?.codigo }}</span>
			</div>
			<div v-if="Number(existenciaMinima) > 0" class="small text-body-secondary mt-1">
				Mínimo: {{ formatoCantidad(existenciaMinima) }} {{ unidad?.codigo }}
			</div>
		</div>
	</div>

	<table v-if="existencias.length" class="table table-sm mb-0 border-top">
		<thead>
			<tr>
				<th class="ps-3">Presentación</th>
				<th v-if="hayLotes">Vence</th>
				<th class="text-end pe-3">Existencia</th>
			</tr>
		</thead>
		<tbody>
			<tr v-for="(i, idx) in existencias" :key="idx" class="align-middle">
				<td class="ps-3">{{ i.npresentacion ?? unidad?.nombre ?? i.cunidad }}</td>
				<td v-if="hayLotes" class="text-nowrap" :class="{ 'text-danger fw-semibold': vencido(i.fecha_vence) }">
					{{ i.fecha_vence ? formatoFecha(i.fecha_vence) : 'Sin fecha' }}
				</td>
				<td class="text-end pe-3 text-nowrap" :class="{ 'text-danger': Number(i.cantidad) < 0 }">
					{{ formatoCantidad(i.cantidad) }}
					<span class="text-body-secondary">{{ i.npresentacion ? '' : i.cunidad }}</span>
				</td>
			</tr>
		</tbody>
	</table>
	<div v-else class="text-center text-body-secondary small pb-3">
		Sin existencia en esta sucursal.
	</div>
</template>

<script>
	export default {
		name: "ExistenciasProducto",
		props: {
			// Filas de la API: una por unidad, presentación y lote
			existencias: {
				type: Array,
				required: false,
				default: () => [],
			},
			presentaciones: {
				type: Array,
				required: false,
				default: () => [],
			},
			unidad: {
				type: Object,
				required: false,
				default: null,
			},
			existenciaMinima: {
				type: [String, Number],
				required: false,
				default: 0,
			},
		},
		methods: {
			factor(presentacionId) {
				if (!presentacionId) {
					return 1
				}

				let tmp = this.presentaciones.find(e => String(e.id) === String(presentacionId))
				return tmp ? Number(tmp.factor) : 1
			},
			vencido(fecha) {
				return fecha && new Date(fecha.replace(" ", "T")) < new Date()
			},
			formatoFecha(fecha) {
				let [a, m, d] = String(fecha).split(" ")[0].split("-")
				return `${d}/${m}/${a}`
			},
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 2
				})
			}
		},
		computed: {
			total() {
				return this.existencias.reduce((suma, e) =>
					suma + Number(e.cantidad) * this.factor(e.producto_presentacion_id), 0)
			},
			hayLotes() {
				return this.existencias.some(e => e.fecha_vence)
			},
			estado() {
				let minimo = Number(this.existenciaMinima)

				if (this.total <= 0) {
					return {
						texto: "Agotado",
						clase: "bg-danger-subtle text-danger-emphasis border-danger-subtle"
					}
				}

				if (minimo > 0 && this.total < minimo) {
					return {
						texto: "Bajo mínimo",
						clase: "bg-warning-subtle text-warning-emphasis border-warning-subtle"
					}
				}

				return {
					texto: "Disponible",
					clase: "bg-success-subtle text-success-emphasis border-success-subtle"
				}
			}
		}
	}
</script>
