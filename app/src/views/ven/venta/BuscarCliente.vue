<template>
	<!-- Cliente elegido: ficha con opción de volver a consumidor final -->
	<div v-if="modelValue" class="d-flex align-items-center gap-2 border rounded-3 px-2 py-2 bg-body">
		<span
			class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary-emphasis fw-semibold flex-shrink-0"
			style="width: 2.25rem; height: 2.25rem"
			aria-hidden="true"
		>{{ iniciales(modelValue.nombre) }}</span>
		<div class="min-w-0 flex-grow-1 lh-sm">
			<div class="fw-semibold text-body text-truncate">{{ modelValue.nombre }}</div>
			<div class="small text-body-secondary text-truncate">
				NIT {{ modelValue.identificacion || 'CF' }}
				<span v-if="Number(modelValue.credito) === 1" class="badge rounded-1 bg-success-subtle text-success-emphasis ms-1">
					Crédito {{ modelValue.credito_dias }} días
				</span>
			</div>
		</div>
		<button type="button" class="btn-close flex-shrink-0" aria-label="Quitar cliente (consumidor final)" @click="quitar" />
	</div>

	<!-- Buscador: sin cliente elegido la venta es a consumidor final -->
	<div v-else class="position-relative">
		<div class="input-group">
			<span class="input-group-text bg-body">
				<span v-if="buscando" class="spinner-border spinner-border-sm text-primary" aria-hidden="true" />
				<i v-else class="fa-solid fa-user text-primary" aria-hidden="true" />
			</span>
			<input
				:id="id"
				ref="entrada"
				v-model="texto"
				type="search"
				class="form-control"
				placeholder="Consumidor final (CF) · busque por nombre o NIT"
				role="combobox"
				aria-autocomplete="list"
				:aria-expanded="abierto"
				:aria-controls="`${id}-lista`"
				autocomplete="off"
				@input="alEscribir"
				@focus="abierto = texto.trim().length >= 2"
				@blur="cerrarLuego"
				@keydown.down.prevent="mover(1)"
				@keydown.up.prevent="mover(-1)"
				@keydown.enter.prevent="elegirActivo"
				@keydown.esc="abierto = false"
			>
		</div>

		<ul
			v-if="abierto"
			:id="`${id}-lista`"
			class="dropdown-menu show w-100 shadow p-1 mt-1 overflow-auto"
			style="max-height: 18rem"
			role="listbox"
		>
			<li v-for="(c, i) in resultados" :key="c.id" role="option" :aria-selected="i === activo">
				<button
					type="button"
					class="dropdown-item rounded-2 d-flex align-items-center gap-2 py-2"
					:class="{ active: i === activo }"
					@mousedown.prevent="elegir(c)"
					@mouseenter="activo = i"
				>
					<span
						class="d-inline-flex align-items-center justify-content-center rounded-circle bg-body-tertiary border small fw-semibold flex-shrink-0"
						style="width: 2rem; height: 2rem"
						aria-hidden="true"
					>{{ iniciales(c.nombre) }}</span>
					<span class="min-w-0 flex-grow-1 lh-sm">
						<span class="d-block fw-semibold text-truncate">{{ c.nombre }}</span>
						<span class="d-block small opacity-75 text-truncate">
							NIT {{ c.identificacion || 'CF' }}<template v-if="c.codigo"> · {{ c.codigo }}</template>
						</span>
					</span>
					<span v-if="Number(c.credito) === 1" class="badge rounded-1 bg-success-subtle text-success-emphasis">Crédito</span>
				</button>
			</li>

			<li v-if="!buscando && resultados.length === 0" class="px-3 py-2 small text-body-secondary">
				Sin clientes para «{{ texto.trim() }}»
			</li>

			<li><hr class="dropdown-divider"></li>
			<li role="option" :aria-selected="activo === resultados.length">
				<button
					type="button"
					class="dropdown-item rounded-2 py-2 text-success-emphasis"
					:class="{ active: activo === resultados.length }"
					@mousedown.prevent="abrirNuevo"
					@mouseenter="activo = resultados.length"
				>
					<i class="fa-solid fa-user-plus me-2" aria-hidden="true" />Crear cliente «{{ texto.trim() }}»
				</button>
			</li>
		</ul>
	</div>

	<!-- Nuevo cliente: el mismo formulario del mantenimiento de clientes -->
	<Teleport to="body">
		<div
			ref="modal"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloNuevoClienteVenta"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloNuevoClienteVenta" class="modal-title fw-semibold mb-0">Nuevo cliente</h5>
							<div class="small text-body-secondary">Al guardarlo queda como cliente de esta venta</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrarNuevo" />
					</div>
					<div class="modal-body">
						<FormCliente
							v-if="nuevoAbierto"
							ref="formCliente"
							:key="apertura"
							pk=""
							:departamentos="departamentos"
							:municipios="municipios"
							@actualizar="clienteCreado"
							@cancelar="cerrarNuevo"
						/>
					</div>
				</div>
			</div>
		</div>
	</Teleport>
</template>

<script>
	import { Modal } from 'bootstrap'
	import FormCliente from '../../mnt/cliente/Form.vue'
	import api, { mensajeError } from '@/services/api'

	export default {
		name: "BuscarCliente",
		props: {
			modelValue: {
				type: Object,
				required: false,
				default: null,
			},
			departamentos: {
				type: Array,
				required: false,
				default: () => [],
			},
			municipios: {
				type: Array,
				required: false,
				default: () => [],
			},
			id: {
				type: String,
				required: false,
				default: "ventaCliente",
			},
		},
		emits: ["update:modelValue"],
		components: {
			FormCliente
		},
		data: () => ({
			texto: "",
			resultados: [],
			abierto: false,
			buscando: false,
			activo: 0,
			nuevoAbierto: false,
			apertura: 0,
			// Número de la última búsqueda: una respuesta vieja no pisa a una nueva
			consulta: 0
		}),
		mounted() {
			// Fondo estático: un clic fuera no pierde lo escrito
			this.modal = new Modal(this.$refs.modal, { backdrop: "static" })
			this.$refs.modal.addEventListener("hidden.bs.modal", () => {
				this.nuevoAbierto = false
			})
		},
		beforeUnmount() {
			clearTimeout(this.espera)
			clearTimeout(this.cierre)
			this.modal?.dispose()
		},
		methods: {
			// Busca en la API al dejar de escribir
			alEscribir() {
				clearTimeout(this.espera)

				let termino = this.texto.trim()

				if (termino.length < 2) {
					this.abierto = false
					this.resultados = []
					this.buscando = false
					return
				}

				this.buscando = true
				this.abierto = true
				this.espera = setTimeout(() => this.buscar(termino), 250)
			},
			buscar(termino) {
				let consulta = ++this.consulta

				api
				.get("/ven/venta/buscar_cliente", { params: { termino } })
				.then(result => {
					if (consulta === this.consulta) {
						this.resultados = result.data.lista ?? []
						this.activo = 0
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					if (consulta === this.consulta) {
						this.buscando = false
					}
				})
			},
			// Con ↑ ↓ se recorren los clientes y la opción de crear (la última)
			mover(paso) {
				if (!this.abierto) {
					return
				}

				let total = this.resultados.length + 1
				this.activo = (this.activo + paso + total) % total
			},
			elegirActivo() {
				if (!this.abierto || this.buscando) {
					return
				}

				if (this.activo < this.resultados.length) {
					this.elegir(this.resultados[this.activo])
				} else {
					this.abrirNuevo()
				}
			},
			elegir(cliente) {
				this.$emit("update:modelValue", cliente)
				this.texto = ""
				this.resultados = []
				this.abierto = false
			},
			quitar() {
				this.$emit("update:modelValue", null)
				this.$nextTick(() => this.$refs.entrada?.focus())
			},
			// Espera para que el clic en una opción llegue antes de cerrar la lista
			cerrarLuego() {
				this.cierre = setTimeout(() => {
					this.abierto = false
				}, 150)
			},
			// Lo escrito se pasa al formulario: al NIT si lo parece, si no al nombre
			abrirNuevo() {
				let termino = this.texto.trim()

				this.abierto = false
				this.apertura++
				this.nuevoAbierto = true
				this.modal?.show()

				this.$nextTick(() => {
					let form = this.$refs.formCliente?.form

					if (!form || !termino) {
						return
					}

					if (/^(cf|[0-9]+-?[0-9k])$/i.test(termino)) {
						form.identificacion = termino.toUpperCase()
					} else {
						form.nombre = termino
					}
				})
			},
			cerrarNuevo() {
				this.modal?.hide()
			},
			clienteCreado(cliente) {
				this.modal?.hide()
				this.elegir(cliente)
			},
			iniciales(nombre) {
				return String(nombre ?? "")
				.trim()
				.split(/\s+/)
				.slice(0, 2)
				.map(p => p.charAt(0).toUpperCase())
				.join("")
			}
		}
	}
</script>
