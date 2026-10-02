<template>
	<PageHeader>
		<button
			type="button"
			class="btn btn-suave-success"
			:disabled="btnExcel || lista.length === 0"
			title="Descargar en Excel lo que se ve en la tabla"
			@click="descargarExcel"
		>
			<span v-if="btnExcel" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
			<i v-else class="fa-solid fa-file-excel me-1" aria-hidden="true" />Excel
		</button>
		<button type="button" class="btn btn-outline-primary" :disabled="btnBuscar" @click="buscar">
			<i class="fa-solid fa-rotate me-1" :class="{ 'fa-spin': btnBuscar }" aria-hidden="true" />Actualizar
		</button>
	</PageHeader>

	<!-- Resumen de lo que se está viendo (respeta los filtros) -->
	<div class="row g-3 mb-3">
		<div class="col-12 col-sm-6 col-xl-3">
			<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
				<span class="icono-suave icono-suave-primario" aria-hidden="true">
					<i class="fa-solid fa-boxes-stacked" />
				</span>
				<div class="lh-sm">
					<div class="small text-body-secondary">Productos</div>
					<div class="fs-5 fw-bold text-body">
						{{ visibles.length }}
						<span v-if="filtrando" class="small fw-normal text-body-secondary">de {{ lista.length }}</span>
					</div>
				</div>
			</div>
		</div>
		<div class="col-12 col-sm-6 col-xl-3">
			<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
				<span class="icono-suave icono-suave-info" aria-hidden="true">
					<i class="fa-solid fa-cubes" />
				</span>
				<div class="lh-sm">
					<div class="small text-body-secondary">Unidades en existencia</div>
					<div class="fs-5 fw-bold text-body">{{ formatoCantidad(totales.unidades) }}</div>
				</div>
			</div>
		</div>
		<div class="col-12 col-sm-6 col-xl-3">
			<div class="d-flex align-items-center gap-3 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
				<span class="icono-suave icono-suave-success" aria-hidden="true">
					<i class="fa-solid fa-sack-dollar" />
				</span>
				<div class="lh-sm text-truncate">
					<div class="small text-body-secondary text-truncate">
						Valor al costo
						<span v-if="fecha" :title="`Calculado el ${formatoFecha(fecha, true)}`">· {{ formatoFecha(fecha, true) }}</span>
					</div>
					<div class="fs-5 fw-bold text-body text-nowrap">{{ simbolo }} {{ formatoMonto(totales.valor) }}</div>
				</div>
			</div>
		</div>

		<!-- Estado del inventario: proporción por color (la misma de las filas); cada chip filtra la tabla -->
		<div class="col-12 col-sm-6 col-xl-3">
			<div class="d-flex flex-column justify-content-center gap-2 rounded-3 border bg-body px-3 py-3 h-100 shadow-sm">
				<div class="progress-stacked" style="height: 6px">
					<div
						v-for="e in estados"
						:key="e.id"
						class="progress"
						role="progressbar"
						:aria-label="e.nombre"
						:aria-valuenow="porcentaje(e.id)"
						aria-valuemin="0"
						aria-valuemax="100"
						:style="{ width: `${porcentaje(e.id)}%` }"
					>
						<div class="progress-bar" :class="e.barra" />
					</div>
				</div>

				<div class="d-flex flex-wrap gap-1">
					<button
						v-for="e in estados"
						:key="e.id"
						type="button"
						class="btn btn-sm d-inline-flex align-items-center gap-1 px-2 py-0 rounded-pill border"
						:class="estado === e.id ? 'bg-body-tertiary fw-semibold border-secondary-subtle' : 'border-0'"
						:title="`${e.nombre}: filtrar la tabla`"
						:aria-pressed="estado === e.id"
						@click="estado = estado === e.id ? null : e.id"
					>
						<i class="fa-solid fa-circle" :class="e.texto" style="font-size: 0.5rem" aria-hidden="true" />
						<span class="small text-body-secondary">{{ e.nombre }}</span>
						<span class="small fw-semibold text-body">{{ conteo[e.id] }}</span>
					</button>
				</div>
			</div>
		</div>
	</div>

	<card>
		<card-body class="p-0">
			<div class="d-flex flex-wrap align-items-center gap-2 p-3 border-bottom">
				<div class="input-group flex-grow-1 w-auto">
					<span class="input-group-text">
						<i class="fa-solid fa-magnifying-glass" aria-hidden="true" />
					</span>
					<input
						v-model="termino"
						type="search"
						class="form-control"
						placeholder="Buscar por nombre o código..."
						aria-label="Buscar productos"
					>
				</div>

				<select v-model="categoria" class="form-select w-auto" aria-label="Filtrar por categoría">
					<option :value="null">Todas las categorías</option>
					<option v-for="c in catalogo.categorias" :key="c.id" :value="String(c.id)">{{ c.nombre }}</option>
				</select>

				<select v-model="marca" class="form-select w-auto" aria-label="Filtrar por marca">
					<option :value="null">Todas las marcas</option>
					<option v-for="m in catalogo.marcas" :key="m.id" :value="String(m.id)">{{ m.nombre }}</option>
				</select>

				<select v-model="estado" class="form-select w-auto" aria-label="Filtrar por estado">
					<option :value="null">Todos los estados</option>
					<option v-for="e in estados" :key="e.id" :value="e.id">{{ e.nombre }}</option>
				</select>

				<!-- Las existencias son de la sucursal de la sesión -->
				<span
					v-if="sucursal"
					class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
					title="Existencias de esta sucursal"
				>
					<i class="fa-solid fa-store text-primary" aria-hidden="true" />
					<span class="fw-semibold text-body">{{ sucursal }}</span>
				</span>

				<span
					class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 bg-body-tertiary border text-body-secondary text-nowrap"
					aria-live="polite"
				>
					<i class="fa-solid fa-layer-group text-primary" aria-hidden="true" />
					<span>
						<span class="fw-semibold text-body">{{ filtrando ? `${visibles.length} de ${lista.length}` : lista.length }}</span>
						{{ lista.length === 1 ? 'registro' : 'registros' }}
					</span>
				</span>
			</div>

			<div class="table-responsive">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th class="ps-3">Producto</th>
							<th>Categoría</th>
							<th>Marca</th>
							<th>Unidad</th>
							<th class="text-end">Existencia</th>
							<th class="text-end">Mínimo</th>
							<th class="text-end">Costo</th>
							<th class="text-end">Valor</th>
							<th class="pe-3">Próx. vence</th>
						</tr>
					</thead>
					<tbody>
						<!-- El color de la fila indica el estado: verde con existencia, amarillo bajo mínimo, rojo sin existencia -->
						<template v-for="i in visibles" :key="`${i.producto_id}-${i.unidad_medida_id}`">
						<tr
							class="align-middle"
							:class="claseFila(estadoDe(i))"
						>
							<td class="ps-3">
								<div class="lh-sm">
									<div class="fw-semibold text-body">{{ i.nombre }}</div>
									<div class="small text-body-secondary font-monospace">{{ i.codigo }}</div>
								</div>
							</td>
							<td>
								<span
									v-if="i.ncategoria"
									class="badge rounded-1 fw-semibold etiqueta-color"
									:style="estiloEtiqueta(i.ecategoria)"
								>{{ i.ncategoria }}</span>
							</td>
							<td>{{ i.nmarca }}</td>
							<td>{{ i.nunidad }}</td>
							<td class="text-end fw-semibold text-body">{{ formatoCantidad(i.existencia) }}</td>
							<td class="text-end text-body-secondary">{{ formatoCantidad(i.existencia_minima) }}</td>
							<td class="text-end text-body-secondary">{{ formatoMonto(i.costo) }}</td>
							<td class="text-end fw-semibold text-nowrap">{{ formatoMonto(valorDe(i)) }}</td>
							<td class="pe-3 text-nowrap">
								<span v-if="i.proximo_vence" :class="{ 'text-danger fw-semibold': porVencer(i.proximo_vence) }">
									{{ formatoFecha(i.proximo_vence) }}
								</span>
								<span v-else class="text-body-secondary">—</span>
							</td>
						</tr>

						<!-- Presentaciones con existencia: se cuentan aparte de la unidad de medida -->
						<tr
							v-for="pre in conExistencia(i)"
							:key="`${i.producto_id}-p${pre.producto_presentacion_id}`"
							class="align-middle"
						>
							<td class="ps-4" colspan="3">
								<span class="small text-body-secondary">
									<i class="fa-solid fa-turn-up fa-rotate-90 me-2 opacity-50" aria-hidden="true" />{{ i.nombre }}
								</span>
							</td>
							<td>
								<span class="badge rounded-1 border bg-primary-subtle text-primary-emphasis border-primary-subtle">{{ pre.nombre }}</span>
							</td>
							<td class="text-end fw-semibold text-body">{{ formatoCantidad(pre.existencia) }}</td>
							<td class="text-end text-body-secondary">—</td>
							<td class="text-end text-body-secondary">{{ formatoMonto(pre.costo) }}</td>
							<td class="text-end fw-semibold text-nowrap">{{ formatoMonto(valorDe(pre)) }}</td>
							<td class="pe-3 text-nowrap">
								<span v-if="pre.proximo_vence" :class="{ 'text-danger fw-semibold': porVencer(pre.proximo_vence) }">
									{{ formatoFecha(pre.proximo_vence) }}
								</span>
								<span v-else class="text-body-secondary">—</span>
							</td>
						</tr>
						</template>

						<tr v-if="btnBuscar">
							<td colspan="9" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="visibles.length === 0">
							<td colspan="9" class="text-center text-body-secondary py-4">
								{{ filtrando ? 'Sin resultados para la búsqueda' : 'No hay productos registrados' }}
							</td>
						</tr>
					</tbody>
					<!-- Totales de lo filtrado: una línea con el gris del encabezado (estilo en app.css) -->
					<tfoot v-if="visibles.length > 0">
						<tr>
							<td colspan="4" class="ps-3">
								Total
								<span class="fw-normal text-body-secondary">
									· {{ visibles.length }} {{ visibles.length === 1 ? 'producto' : 'productos' }}
								</span>
							</td>
							<td class="text-end">{{ formatoCantidad(totales.unidades) }}</td>
							<td colspan="2" />
							<td class="text-end text-nowrap">{{ simbolo }} {{ formatoMonto(totales.valor) }}</td>
							<td class="pe-3" />
						</tr>
					</tfoot>
				</table>
			</div>
		</card-body>
	</card>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Accion from '@/mixins/Accion.js'
	import api from '@/services/api'
	import { estiloEtiqueta } from '@/config/etiquetas'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto } from '@/utils/numero'

	// Días antes del vencimiento en que la fecha se resalta
	const DIAS_AVISO_VENCE = 30

	export default {
		name: "Existencia",
		mixins: [Accion],
		data: () => ({
			categoria: null,
			marca: null,
			estado: null,
			fecha: null,
			simbolo: "",
			btnExcel: false,
			catalogo: {
				categorias: [],
				marcas: []
			},
			estados: [
				{ id: "disponible", nombre: "Con existencia", barra: "bg-success", texto: "text-success" },
				{ id: "minimo", nombre: "Bajo mínimo", barra: "bg-warning", texto: "text-warning" },
				{ id: "agotado", nombre: "Sin existencia", barra: "bg-danger", texto: "text-danger" }
			]
		}),
		created() {
			this.url = "inv/existencia"
			this.getDatos()
		},
		methods: {
			// Catálogos de los filtros, moneda de la empresa y fecha del reporte
			getDatos() {
				api
				.get(`/${this.url}/get_datos`)
				.then(result => {
					let res = result.data

					this.catalogo = { ...this.catalogo, ...(res.cat ?? {}) }
					this.simbolo  = res.simbolo ?? ""
					this.fecha    = res.fecha ?? null
				})
				.catch(() => {
					this.catalogo = { categorias: [], marcas: [] }
				})
			},
			// El Excel lo arma la API con los mismos filtros de la pantalla; se pide como blob
			// porque la petición lleva el token (un enlace directo no lo enviaría)
			descargarExcel() {
				this.btnExcel = true

				api
				.get(`/${this.url}/excel`, {
					params: {
						termino: this.termino,
						categoria: this.categoria,
						marca: this.marca,
						estado: this.estado
					},
					responseType: "blob"
				})
				.then(result => {
					let nombre = /filename="([^"]+)"/.exec(result.headers["content-disposition"] ?? "")?.[1] ?? "existencias.xlsx"
					let enlace = document.createElement("a")

					enlace.href = URL.createObjectURL(result.data)
					enlace.download = nombre
					enlace.click()
					URL.revokeObjectURL(enlace.href)
				})
				.catch(() => {
					this.$toast.error("No se pudo generar el archivo de Excel.")
				})
				.finally(() => {
					this.btnExcel = false
				})
			},
			porcentaje(estado) {
				return this.base.length ? this.conteo[estado] * 100 / this.base.length : 0
			},
			valorDe(obj) {
				return Number(obj.existencia) * Number(obj.costo)
			},
			conExistencia(producto) {
				return (producto.presentaciones ?? []).filter(e => Number(e.existencia) !== 0)
			},
			estadoDe(obj) {
				let existencia = Number(obj.existencia)
				let minimo = Number(obj.existencia_minima)

				if (existencia <= 0) {
					return "agotado"
				}

				return minimo > 0 && existencia < minimo ? "minimo" : "disponible"
			},
			claseFila(estado) {
				return {
					disponible: "fila-suave-success",
					minimo: "fila-suave-warning",
					agotado: "fila-suave-danger"
				}[estado]
			},
			// Vencido o dentro de los próximos DIAS_AVISO_VENCE días
			porVencer(fecha) {
				let limite = new Date()
				limite.setDate(limite.getDate() + DIAS_AVISO_VENCE)

				return new Date(String(fecha).replace(" ", "T")) <= limite
			},
			formatoMonto,
			// Cantidades sin decimales de sobra: 12 → "12", 0.5 → "0.5"
			formatoCantidad(valor) {
				return Number(valor ?? 0).toLocaleString("en-US", {
					maximumFractionDigits: 2
				})
			},
			// "2026-09-17 03:45:53" → "17/09/2026 03:45"; "2026-09-16" → "16/09/2026"
			formatoFecha(fecha, conHora) {
				if (!fecha) {
					return ""
				}

				let [dia, hora] = String(fecha).split(" ")
				let [a, m, d] = dia.split("-")

				return `${d}/${m}/${a}` + (conHora && hora ? ` ${hora.slice(0, 5)}` : "")
			},
			estiloEtiqueta
		},
		computed: {
			sucursal() {
				return useSesionStore().usuario?.sucursal?.nombre ?? ""
			},
			filtrando() {
				return Boolean(this.termino || this.categoria || this.marca || this.estado)
			},
			// Búsqueda del mixin más los filtros de categoría y marca (sin el de estado)
			base() {
				return this.filtrada.filter(e =>
					(!this.categoria || String(e.categoria_id) === this.categoria) &&
					(!this.marca || String(e.marca_id) === this.marca)
				)
			},
			visibles() {
				return this.estado ? this.base.filter(e => this.estadoDe(e) === this.estado) : this.base
			},
			// Por estado sobre la base: la barra no se vacía al elegir un estado
			conteo() {
				return this.base.reduce((t, e) => {
					t[this.estadoDe(e)]++

					return t
				}, { disponible: 0, minimo: 0, agotado: 0 })
			},
			totales() {
				return this.visibles.reduce((t, e) => {
					// Unidades: solo la unidad de medida; el valor incluye las presentaciones
					t.unidades += Number(e.existencia)
					t.valor += this.valorDe(e) + this.conExistencia(e).reduce((s, pre) => s + this.valorDe(pre), 0)

					return t
				}, { unidades: 0, valor: 0 })
			}
		},
		components: {
			PageHeader
		}
	}
</script>
