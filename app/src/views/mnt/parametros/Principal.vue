<template>
	<PageHeader />

	<div v-if="cargando" class="text-center text-body-secondary py-5">
		<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
	</div>

	<Form
		v-else
		:parametro="parametro"
		:pk="parametro ? String(parametro.id) : ''"
		:monedas="monedas"
		@actualizar="actualizar"
	/>
</template>

<script>
	import PageHeader from '../../../components/layout/PageHeader.vue'
	import Form from './Form.vue'
	import api, { mensajeError } from '@/services/api'
	import { useSesionStore } from '@/stores/sesion'

	export default {
		name: "Parametros",
		data: () => ({
			cargando: true,
			parametro: null,
			monedas: []
		}),
		created() {
			this.getDatos()
		},
		methods: {
			// Parámetros de la empresa de la sesión y el catálogo de monedas
			getDatos() {
				this.cargando = true

				api
				.get("/mnt/parametro/get_datos")
				.then(result => {
					this.parametro = result.data.parametro ?? null
					this.monedas   = result.data.cat?.monedas ?? []
					this.cargando  = false
				})
				.catch(e => {
					this.cargando = false
					this.$toast.error(mensajeError(e))
				})
			},
			actualizar(reg) {
				this.parametro = { ...this.parametro, ...reg }

				// Los decimales de montos viajan con el usuario de la sesión: se toman al momento
				useSesionStore().refrescar().catch(() => {})
			}
		},
		components: {
			PageHeader,
			Form
		}
	}
</script>
