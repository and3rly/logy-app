<template>
	<card>
		<card-header>
			<span>Precios de <span class="text-primary">{{ lista.nombre }}</span></span>
			<span
				v-if="Number(lista.activo) !== 1"
				class="badge border rounded-1 fw-semibold bg-secondary-subtle text-secondary-emphasis border-secondary-subtle ms-2"
			>Inactiva</span>
			<span class="ms-auto small fw-normal text-body-secondary">{{ enLista }} de {{ filas.length }} en la lista</span>
		</card-header>
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
						placeholder="Buscar por código o nombre..."
						aria-label="Buscar productos"
					>
				</div>

				<select v-model="categoria" class="form-select w-auto" aria-label="Filtrar por categoría">
					<option :value="null">Todas las categorías</option>
					<option v-for="c in categorias" :key="c.id" :value="String(c.id)">{{ c.nombre }}</option>
				</select>

				<div class="form-check form-switch mb-0">
					<input
						id="checkSoloLista"
						v-model="soloLista"
						class="form-check-input"
						type="checkbox"
						role="switch"
					>
					<label class="form-check-label" for="checkSoloLista">Solo los de la lista</label>
				</div>
			</div>

			<!-- Llenar los vacíos (de lo que se ve) desde el precio general con un descuento -->
			<div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-body-tertiary small">
				<span class="text-body-secondary">Llenar los vacíos con el precio general menos</span>
				<div class="input-group input-group-sm w-auto">
					<input
						v-model="porcentaje"
						type="number"
						class="form-control text-end"
						min="0"
						max="99"
						step="0.01"
						style="width: 5rem"
						aria-label="Porcentaje bajo el precio general"
					>
					<span class="input-group-text">%</span>
				</div>
				<button type="button" class="btn btn-sm btn-suave-info" :disabled="btnGuardar" @click="llenarVacios">
					<i class="fa-solid fa-wand-magic-sparkles me-1" aria-hidden="true" />Llenar
				</button>
				<span class="text-body-secondary ms-sm-auto">Sin precio en la lista, el cliente paga el precio general.</span>
			</div>

			<div class="table-responsive tabla-pantalla">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th class="ps-3">Producto</th>
							<th>Presentación</th>
							<th class="text-end">Costo</th>
							<th class="text-end">Precio general</th>
							<th class="text-end">Precio de lista</th>
							<th class="text-end pe-3">Margen</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="f in filtradas" :key="f.llave" class="align-middle">
							<td class="ps-3">
								<div class="text-body lh-sm">{{ f.nombre }}</div>
								<div class="small text-body-secondary font-monospace">{{ f.codigo }}</div>
							</td>
							<td class="text-nowrap">
								<span
									v-if="f.npresentacion"
									class="badge rounded-1 border bg-primary-subtle text-primary-emphasis border-primary-subtle"
								>{{ f.npresentacion }}</span>
								<span v-else class="text-body-secondary">{{ f.nunidad }}</span>
							</td>
							<td class="text-end text-body-secondary text-nowrap">{{ formatoMonto(f.costo) }}</td>
							<td class="text-end text-nowrap">{{ formatoMonto(f.precio_general) }}</td>
							<td class="text-end">
								<div class="input-group input-group-sm flex-nowrap ms-auto" style="width: 9rem">
									<input
										v-model="f.valor"
										type="number"
										min="0"
										step="0.01"
										class="form-control text-end"
										:class="{ 'is-invalid': bajoCosto(f), 'border-warning': cambio(f) && !bajoCosto(f) }"
										placeholder="General"
										:aria-label="`Precio de lista de ${nombreArticulo(f)}`"
										:disabled="btnGuardar"
									>
									<button
										v-if="f.valor !== ''"
										type="button"
										class="btn btn-outline-secondary"
										:title="`Quitar ${nombreArticulo(f)} de la lista`"
										:aria-label="`Quitar ${nombreArticulo(f)} de la lista`"
										:disabled="btnGuardar"
										@click="f.valor = ''"
									>
										<i class="fa-solid fa-xmark" aria-hidden="true" />
									</button>
								</div>
								<div v-if="bajoCosto(f)" class="small text-danger-emphasis">Menor al costo</div>
							</td>
							<td class="text-end pe-3 text-nowrap" :class="bajoCosto(f) ? 'text-danger-emphasis' : 'text-body-secondary'">{{ margen(f) }}</td>
						</tr>

						<tr v-if="btnBuscar">
							<td colspan="6" class="text-center text-body-secondary">
								<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
							</td>
						</tr>
						<tr v-else-if="filtradas.length === 0">
							<td colspan="6" class="text-center text-body-secondary">{{ filas.length ? 'Sin productos para la búsqueda' : 'No hay productos activos' }}</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="d-flex flex-wrap align-items-center justify-content-end gap-2 p-3 border-top">
				<span v-if="cambios > 0" class="small text-warning-emphasis me-auto">
					<i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true" />{{ cambios }} {{ cambios === 1 ? 'cambio sin guardar' : 'cambios sin guardar' }}
				</span>
				<button
					type="button"
					class="btn btn-outline-secondary"
					:disabled="btnGuardar || cambios === 0"
					@click="descartar"
				>
					<i class="fa-solid fa-rotate-left me-1" aria-hidden="true" />Descartar
				</button>
				<button
					type="button"
					class="btn btn-primary"
					:disabled="btnGuardar || cambios === 0"
					@click="guardarPrecios"
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
</template>

<script>
	import api, { mensajeError } from '@/services/api'
	import { normalizar } from '@/utils/texto'
	import { formatoMonto } from '@/utils/numero'

	export default {
		name: "PreciosLista",
		props: {
			lista: {
				type: Object,
				required: true,
			},
		},
		emits: ["actualizar"],
		data: () => ({
			btnBuscar: false,
			btnGuardar: false,
			filas: [],
			categorias: [],
			termino: "",
			categoria: null,
			soloLista: false,
			porcentaje: 0
		}),
		created() {
			this.getArticulos()
		},
		methods: {
			// Cada fila es un producto en su unidad o en una presentación; valor = lo que se escribe ('' = no está)
			getArticulos() {
				this.btnBuscar = true

				api
				.get(`/mnt/lista_precio/get_articulos/${this.lista.id}`)
				.then(result => {
					this.categorias = result.data.cat?.categorias ?? []
					this.filas = (result.data.lista ?? []).map(f => {
						let valor = f.precio === null ? "" : Number(f.precio).toFixed(2)

						return {
							...f,
							llave: `${f.producto_id}-${f.producto_presentacion_id ?? 0}`,
							costo: Number(f.costo),
							precio_general: Number(f.precio_general),
							valor,
							original: valor
						}
					})
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.btnBuscar = false
				})
			},
			numero(f) {
				return f.valor === "" || f.valor === null ? null : Math.round(Number(f.valor) * 100) / 100
			},
			// El v-model de un input numérico entrega un número: se compara el valor, no el texto
			cambio(f) {
				return this.numero(f) !== (f.original === "" ? null : Number(f.original))
			},
			bajoCosto(f) {
				let precio = this.numero(f)
				return precio !== null && precio < f.costo
			},
			margen(f) {
				let precio = this.numero(f) ?? f.precio_general

				if (!(precio > 0)) {
					return ""
				}

				return (((precio - f.costo) / precio) * 100).toFixed(1) + " %"
			},
			nombreArticulo(f) {
				return f.npresentacion ? `${f.nombre} (${f.npresentacion})` : f.nombre
			},
			llenarVacios() {
				let porcentaje = Number(this.porcentaje || 0)

				if (porcentaje < 0 || porcentaje >= 100) {
					this.$toast.error("El porcentaje va de 0 a 99.")
					return
				}

				let llenas = 0

				for (let f of this.filtradas) {
					if (f.valor === "" && f.precio_general > 0) {
						f.valor = (Math.round(f.precio_general * (1 - porcentaje / 100) * 100) / 100).toFixed(2)
						llenas++
					}
				}

				if (llenas === 0) {
					this.$toast.error("No hay precios vacíos en lo que se muestra.")
				} else if (this.filtradas.some(f => this.bajoCosto(f))) {
					this.$toast.error("Algunos precios quedaron bajo el costo; corríjalos antes de guardar.")
				}
			},
			descartar() {
				for (let f of this.filas) {
					f.valor = f.original
				}
			},
			guardarPrecios() {
				let invalida = this.filas.find(f => f.valor !== "" && !(this.numero(f) > 0))

				if (invalida) {
					this.$toast.error(`El precio de ${this.nombreArticulo(invalida)} debe ser mayor a cero.`)
					return
				}

				let bajo = this.filas.find(f => this.bajoCosto(f))

				if (bajo) {
					this.$toast.error(`El precio de ${this.nombreArticulo(bajo)} no puede ser menor al costo (${this.formatoMonto(bajo.costo)}).`)
					return
				}

				this.btnGuardar = true

				api
				.post(`/mnt/lista_precio/guardar_precios/${this.lista.id}`, {
					lineas: this.filas
					.filter(f => f.valor !== "")
					.map(f => ({
						producto_id: f.producto_id,
						producto_presentacion_id: f.producto_presentacion_id,
						precio: this.numero(f)
					}))
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.$toast.success(res.mensaje)

						for (let f of this.filas) {
							f.valor    = f.valor === "" ? "" : this.numero(f).toFixed(2)
							f.original = f.valor
						}

						this.$emit("actualizar", res.linea)
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
			formatoMonto
		},
		computed: {
			filtradas() {
				let ter = normalizar(this.termino.trim())

				return this.filas.filter(f => {
					if (this.categoria && String(f.categoria_id) !== this.categoria) {
						return false
					}

					if (this.soloLista && f.valor === "") {
						return false
					}

					return ter === "" ||
						normalizar(String(f.nombre)).includes(ter) ||
						normalizar(String(f.codigo)).includes(ter)
				})
			},
			enLista() {
				return this.filas.filter(f => f.original !== "").length
			},
			cambios() {
				return this.filas.filter(f => this.cambio(f)).length
			},
			// Para avisar al volver con cambios sin guardar
			hayCambios() {
				return this.cambios > 0
			}
		}
	}
</script>
