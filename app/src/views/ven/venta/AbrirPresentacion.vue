<template>
	<!-- Abrir presentaciones desde el punto de venta: explosión a unidades sueltas, sin salir de la venta -->
	<Teleport to="body">
		<div
			ref="modal"
			class="modal fade"
			tabindex="-1"
			aria-labelledby="tituloAbrirPresentacion"
			aria-hidden="true"
		>
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 id="tituloAbrirPresentacion" class="modal-title fw-semibold mb-0">Abrir {{ articulo?.npresentacion }}</h5>
							<div class="small text-body-secondary">
								{{ articulo?.nombre }} · 1 {{ articulo?.npresentacion }} = {{ formatoCantidad(factor) }} {{ articulo?.nunidad }}
							</div>
						</div>
						<button type="button" class="btn-close" aria-label="Cerrar" @click="cerrar" />
					</div>

					<div class="modal-body">
						<Form
							v-if="articulo"
							ref="form"
							:key="`abrir-${apertura}`"
							:productos="productos"
							:fijo="fijo"
							:tope="tope"
							@actualizar="abierta"
							@cancelar="cerrar"
						/>
					</div>
				</div>
			</div>
		</div>
	</Teleport>
</template>

<script>
	import { Modal } from 'bootstrap'
	import Form from '../../inv/conversion/Form.vue'
	import { formatoCantidad } from '@/utils/numero'

	export default {
		name: "AbrirPresentacion",
		props: {
			// Productos del punto de venta (con presentaciones y existencia)
			productos: {
				type: Array,
				required: true,
			},
		},
		emits: ["abierta"],
		data: () => ({
			articulo: null,
			tope: null,
			apertura: 0
		}),
		mounted() {
			this.modal = new Modal(this.$refs.modal)
			this.$refs.modal.addEventListener("shown.bs.modal", () => this.$refs.form?.enfocar())
		},
		beforeUnmount() {
			this.modal?.dispose()
		},
		methods: {
			// articulo: la tarjeta de la presentación; tope: las que quedan sin contar las del ticket
			abrir(articulo, tope) {
				this.articulo = articulo
				this.tope = tope
				this.apertura++
				this.modal?.show()
			},
			cerrar() {
				this.modal?.hide()
			},
			abierta(linea) {
				this.cerrar()
				this.$emit("abierta", linea)
			},
			formatoCantidad
		},
		computed: {
			fijo() {
				return this.articulo ? {
					sentido: "EXPLOSION",
					producto_id: this.articulo.producto_id,
					producto_presentacion_id: this.articulo.producto_presentacion_id
				} : null
			},
			factor() {
				let p = this.productos.find(e => String(e.producto_id) === String(this.articulo?.producto_id))
				let pre = (p?.presentaciones ?? []).find(e => String(e.producto_presentacion_id) === String(this.articulo?.producto_presentacion_id))

				return Number(pre?.factor ?? 0)
			}
		},
		components: {
			Form
		}
	}
</script>
