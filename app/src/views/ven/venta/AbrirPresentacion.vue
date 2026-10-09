<template>
	<!-- Abrir desde el punto de venta, sin salir de la venta: una presentación en unidades sueltas,
		o la unidad en una presentación más pequeña (1 Quintal → 100 Libras) -->
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
							<h5 id="tituloAbrirPresentacion" class="modal-title fw-semibold mb-0">Abrir {{ articulo?.npresentacion || articulo?.nunidad }}</h5>
							<div class="small text-body-secondary">
								{{ articulo?.nombre }}<template v-if="articulo?.npresentacion"> · {{ equivalencia(articulo.npresentacion, articulo.factor, articulo.nunidad) }}</template>
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
	import { equivalencia } from '@/utils/numero'

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
			// articulo: la tarjeta (presentación o unidad); tope: lo que queda sin contar lo del ticket
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
			equivalencia
		},
		computed: {
			// Sin presentación se abre la unidad: el formulario pide en cuál de las más pequeñas
			fijo() {
				return this.articulo ? {
					producto_id: this.articulo.producto_id,
					producto_presentacion_id: this.articulo.producto_presentacion_id ?? null
				} : null
			}
		},
		components: {
			Form
		}
	}
</script>
