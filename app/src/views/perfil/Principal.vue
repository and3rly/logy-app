<template>
	<PageHeader />

	<div v-if="cargando" class="text-center text-body-secondary py-5">
		<span class="spinner-border spinner-border-sm me-1" aria-hidden="true" />Cargando...
	</div>

	<template v-else-if="perfil">
		<!-- Portada: avatar, nombre, rol y empresa -->
		<card class="mb-4 overflow-hidden">
			<div class="perfil-portada" aria-hidden="true" />
			<card-body class="pt-0">
				<div class="d-flex flex-column flex-md-row align-items-center align-items-md-end gap-3 perfil-cabecera">
					<span class="perfil-avatar" aria-hidden="true">{{ iniciales }}</span>

					<div class="flex-grow-1 text-center text-md-start pb-md-1">
						<h2 class="h4 fw-bold mb-1">{{ perfil.nombre }}</h2>
						<div class="d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-2 text-body-secondary">
							<span><i class="fa-solid fa-at fa-fw" aria-hidden="true" />{{ perfil.alias }}</span>
							<span v-if="perfil.rol" class="badge rounded-pill bg-body-tertiary text-body border">
								<i class="fa-solid fa-user-shield me-1" aria-hidden="true" />{{ perfil.rol }}
							</span>
							<span v-if="perfil.administrador" class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
								<i class="fa-solid fa-crown me-1" aria-hidden="true" />Administrador
							</span>
						</div>
					</div>

					<div class="d-flex flex-wrap justify-content-center gap-4 small pb-md-1">
						<div class="text-center text-md-end">
							<div class="text-body-secondary">Empresa</div>
							<div class="fw-semibold">{{ perfil.empresa }}</div>
						</div>
						<div v-if="perfil.fecha" class="text-center text-md-end">
							<div class="text-body-secondary">Miembro desde</div>
							<div class="fw-semibold">{{ fechaTexto(perfil.fecha) }}</div>
						</div>
					</div>
				</div>
			</card-body>
		</card>

		<!-- Actividad del mes -->
		<div class="d-flex align-items-baseline justify-content-between mb-2">
			<h3 class="h6 fw-semibold mb-0">Mi actividad de {{ mesTexto }}</h3>
			<small class="text-body-secondary">En todas mis sucursales</small>
		</div>
		<div class="row g-3 mb-4">
			<div v-for="ind in indicadores" :key="ind.titulo" class="col-sm-6 col-xl-3">
				<StatCard v-bind="ind" />
			</div>
		</div>

		<div class="row g-4 align-items-start">
			<!-- Resumen de la cuenta -->
			<div class="col-lg-4 d-flex flex-column gap-4">
				<card>
					<card-header>
						<span class="fw-semibold">Cuenta</span>
					</card-header>
					<ul class="list-group list-group-flush">
						<li v-for="d in detalles" :key="d.titulo" class="list-group-item d-flex align-items-center gap-3 py-3">
							<span class="icono-suave" :class="`icono-suave-${d.color}`" aria-hidden="true">
								<i :class="d.icono" />
							</span>
							<div class="lh-sm text-truncate">
								<small class="d-block text-body-secondary">{{ d.titulo }}</small>
								<span class="fw-semibold" :class="{ 'text-body-secondary fw-normal fst-italic': !d.valor }">{{ d.valor || 'Sin registrar' }}</span>
							</div>
						</li>
					</ul>
				</card>

				<card>
					<card-header>
						<div class="d-flex align-items-center justify-content-between">
							<span class="fw-semibold">Mis sucursales</span>
							<span class="badge rounded-pill bg-body-tertiary text-body border">{{ perfil.sucursales.length }}</span>
						</div>
					</card-header>
					<ul class="list-group list-group-flush">
						<li
							v-for="s in perfil.sucursales"
							:key="s.id"
							class="list-group-item d-flex align-items-center gap-3 py-3"
						>
							<span class="icono-suave" :class="s.id === sucursalActual ? 'icono-suave-primario' : 'icono-suave-info'" aria-hidden="true">
								<i class="fa-solid fa-store" />
							</span>
							<span class="flex-grow-1 fw-semibold text-truncate">{{ s.nombre }}</span>
							<span v-if="s.principal" class="badge rounded-pill bg-warning-subtle text-warning-emphasis" title="Sucursal con la que inicia sesión">
								<i class="fa-solid fa-star me-1" aria-hidden="true" />Principal
							</span>
							<span v-if="s.id === sucursalActual" class="badge rounded-pill bg-success-subtle text-success-emphasis">Actual</span>
						</li>
						<li v-if="!perfil.sucursales.length" class="list-group-item text-body-secondary py-3">
							Sin sucursales asignadas.
						</li>
					</ul>
				</card>
			</div>

			<div class="col-lg-8 d-flex flex-column gap-4">
				<!-- Datos personales -->
				<card>
					<card-header>
						<div class="d-flex align-items-center gap-3">
							<span class="icono-suave icono-suave-primario" aria-hidden="true">
								<i class="fa-solid fa-id-card" />
							</span>
							<div class="lh-sm">
								<div class="fw-semibold">Datos personales</div>
								<small class="text-body-secondary fw-normal">Así aparece su nombre en ventas, cotizaciones y compras.</small>
							</div>
						</div>
					</card-header>
					<card-body>
						<form @submit.prevent="guardar" autocomplete="off">
							<div class="row g-3">
								<div class="col-12">
									<label for="perfil_nombre" class="form-label">Nombre completo <span class="text-danger">*</span></label>
									<input id="perfil_nombre" v-model="form.nombre" type="text" class="form-control" maxlength="200" required>
								</div>
								<div class="col-md-6">
									<label for="perfil_correo" class="form-label">Correo electrónico</label>
									<div class="input-group">
										<span class="input-group-text"><i class="fa-regular fa-envelope" aria-hidden="true" /></span>
										<input id="perfil_correo" v-model="form.correo" type="email" class="form-control" maxlength="250" placeholder="nombre@empresa.com">
									</div>
								</div>
								<div class="col-md-6">
									<label for="perfil_telefono" class="form-label">Teléfono</label>
									<div class="input-group">
										<span class="input-group-text"><i class="fa-solid fa-phone" aria-hidden="true" /></span>
										<input id="perfil_telefono" v-model="form.telefono" type="tel" class="form-control" maxlength="15">
									</div>
								</div>
								<div class="col-md-6">
									<label for="perfil_alias" class="form-label">Usuario</label>
									<input id="perfil_alias" :value="perfil.alias" type="text" class="form-control" disabled>
									<div class="form-text">Lo cambia un administrador en Configuración → Usuarios.</div>
								</div>
							</div>

							<div class="d-flex justify-content-end gap-2 mt-4">
								<button type="button" class="btn btn-outline-secondary" :disabled="!cambios || guardando" @click="descartar">
									Descartar
								</button>
								<button type="submit" class="btn btn-primary" :disabled="!cambios || guardando">
									<span v-if="guardando" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
									<i v-else class="fa-solid fa-floppy-disk me-1" aria-hidden="true" />{{ guardando ? 'Guardando' : 'Guardar' }}
								</button>
							</div>
						</form>
					</card-body>
				</card>

				<!-- Seguridad -->
				<card>
					<card-header>
						<div class="d-flex align-items-center gap-3">
							<span class="icono-suave icono-suave-warning" aria-hidden="true">
								<i class="fa-solid fa-lock" />
							</span>
							<div class="lh-sm">
								<div class="fw-semibold">Contraseña</div>
								<small class="text-body-secondary fw-normal">Use al menos 6 caracteres; combine letras, números y símbolos.</small>
							</div>
						</div>
					</card-header>
					<card-body>
						<form @submit.prevent="cambiarClave" autocomplete="off">
							<div class="row g-3">
								<div class="col-12 col-md-6">
									<label for="perfil_actual" class="form-label">Contraseña actual <span class="text-danger">*</span></label>
									<div class="input-group">
										<input
											id="perfil_actual"
											v-model="clave.actual"
											:type="ver.actual ? 'text' : 'password'"
											class="form-control"
											autocomplete="current-password"
											required
										>
										<button type="button" class="btn btn-outline-secondary" :aria-label="ver.actual ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="ver.actual = !ver.actual">
											<i class="fa-regular" :class="ver.actual ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true" />
										</button>
									</div>
								</div>
								<div class="w-100 m-0" />
								<div class="col-md-6">
									<label for="perfil_clave" class="form-label">Nueva contraseña <span class="text-danger">*</span></label>
									<div class="input-group">
										<input
											id="perfil_clave"
											v-model="clave.clave"
											:type="ver.clave ? 'text' : 'password'"
											class="form-control"
											autocomplete="new-password"
											minlength="6"
											required
										>
										<button type="button" class="btn btn-outline-secondary" :aria-label="ver.clave ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="ver.clave = !ver.clave">
											<i class="fa-regular" :class="ver.clave ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true" />
										</button>
									</div>
									<div v-if="clave.clave" class="mt-2">
										<div class="progress" style="height: 6px" role="progressbar" :aria-valuenow="fuerza.nivel" aria-valuemin="0" aria-valuemax="4" aria-label="Seguridad de la contraseña">
											<div class="progress-bar" :class="`bg-${fuerza.color}`" :style="{ width: `${fuerza.nivel * 25}%` }" />
										</div>
										<small :class="`text-${fuerza.color}-emphasis`">{{ fuerza.texto }}</small>
									</div>
								</div>
								<div class="col-md-6">
									<label for="perfil_clave2" class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
									<input
										id="perfil_clave2"
										v-model="clave.clave2"
										:type="ver.clave ? 'text' : 'password'"
										class="form-control"
										:class="{ 'is-invalid': noCoinciden, 'is-valid': clave.clave2 && !noCoinciden }"
										autocomplete="new-password"
										required
									>
									<div class="invalid-feedback">Las contraseñas no coinciden.</div>
								</div>
							</div>

							<div class="d-flex justify-content-end mt-4">
								<button type="submit" class="btn btn-outline-primary" :disabled="guardandoClave || !clave.actual || !clave.clave || noCoinciden">
									<span v-if="guardandoClave" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
									<i v-else class="fa-solid fa-key me-1" aria-hidden="true" />{{ guardandoClave ? 'Cambiando' : 'Cambiar contraseña' }}
								</button>
							</div>
						</form>
					</card-body>
				</card>
			</div>
		</div>
	</template>
</template>

<script>
	import PageHeader from '../../components/layout/PageHeader.vue'
	import StatCard from '../../components/dashboard/StatCard.vue'
	import api, { mensajeError } from '@/services/api'
	import { useSesionStore } from '@/stores/sesion'
	import { formatoMonto } from '@/utils/numero'

	const claveVacia = () => ({ actual: "", clave: "", clave2: "" })

	export default {
		name: "Perfil",
		data: () => ({
			cargando: true,
			guardando: false,
			guardandoClave: false,
			perfil: null,
			actividad: null,
			simbolo: "",
			form: { nombre: "", correo: "", telefono: "" },
			clave: claveVacia(),
			ver: { actual: false, clave: false }
		}),
		created() {
			this.getDatos()
		},
		computed: {
			sesion() {
				return useSesionStore()
			},
			sucursalActual() {
				return this.sesion.usuario?.sucursal?.id ?? null
			},
			// Iniciales para el avatar (ej. "Ana López" → "AL")
			iniciales() {
				return (this.perfil?.nombre ?? "")
					.split(" ")
					.filter(p => p)
					.slice(0, 2)
					.map(p => p[0])
					.join("")
					.toUpperCase()
			},
			mesTexto() {
				return new Date().toLocaleDateString("es", { month: "long" })
			},
			indicadores() {
				const a = this.actividad ?? {}
				const promedio = a.ventas ? a.total_ventas / a.ventas : 0

				return [
					{
						titulo: "Ventas",
						valor: this.cantidad(a.ventas),
						icono: "fa-solid fa-cart-shopping",
						color: "primario",
						detalle: "Registradas por mí este mes"
					},
					{
						titulo: "Monto vendido",
						valor: this.dinero(a.total_ventas),
						icono: "fa-solid fa-sack-dollar",
						color: "success",
						detalle: a.ventas ? `Ticket promedio ${this.dinero(promedio)}` : "Sin ventas este mes"
					},
					{
						titulo: "Cotizaciones",
						valor: this.cantidad(a.cotizaciones),
						icono: "fa-solid fa-file-invoice",
						color: "info",
						detalle: "Elaboradas este mes"
					},
					{
						titulo: "Compras",
						valor: this.cantidad(a.compras),
						icono: "fa-solid fa-truck-ramp-box",
						color: "warning",
						detalle: "Órdenes de compra este mes"
					}
				]
			},
			detalles() {
				const p = this.perfil

				return [
					{ titulo: "Usuario", valor: p.alias, icono: "fa-solid fa-user", color: "primario" },
					{ titulo: "Correo", valor: p.correo, icono: "fa-regular fa-envelope", color: "info" },
					{ titulo: "Teléfono", valor: p.telefono, icono: "fa-solid fa-phone", color: "success" },
					{ titulo: "Rol", valor: p.rol, icono: "fa-solid fa-user-shield", color: "warning" }
				]
			},
			cambios() {
				const p = this.perfil ?? {}

				return this.form.nombre.trim() !== (p.nombre ?? "") ||
					this.form.correo.trim() !== (p.correo ?? "") ||
					this.form.telefono.trim() !== (p.telefono ?? "")
			},
			noCoinciden() {
				return this.clave.clave2 !== "" && this.clave.clave !== this.clave.clave2
			},
			// Nivel 0-4 según largo y variedad de caracteres
			fuerza() {
				const c = this.clave.clave
				let nivel = 0

				if (c.length >= 6) nivel++
				if (c.length >= 10) nivel++
				if (/[a-z]/.test(c) && /[A-Z]/.test(c)) nivel++
				if (/\d/.test(c) && /[^A-Za-z0-9]/.test(c)) nivel++
				if (c.length < 6) nivel = Math.min(nivel, 1) || 1

				const niveles = [
					null,
					{ texto: "Débil", color: "danger" },
					{ texto: "Aceptable", color: "warning" },
					{ texto: "Buena", color: "info" },
					{ texto: "Muy segura", color: "success" }
				]

				return { nivel, ...niveles[nivel] }
			}
		},
		methods: {
			getDatos() {
				this.cargando = true

				api
				.get("/perfil/get_datos")
				.then(result => {
					this.actividad = result.data.actividad ?? null
					this.simbolo = result.data.simbolo ?? ""
					this.setPerfil(result.data.perfil ?? null)
					this.cargando = false
				})
				.catch(e => {
					this.cargando = false
					this.$toast.error(mensajeError(e))
				})
			},
			setPerfil(perfil) {
				this.perfil = perfil
				this.descartar()
			},
			descartar() {
				this.form = {
					nombre: this.perfil?.nombre ?? "",
					correo: this.perfil?.correo ?? "",
					telefono: this.perfil?.telefono ?? ""
				}
			},
			guardar() {
				this.guardando = true

				api
				.post("/perfil/guardar", this.form)
				.then(result => {
					if (result.data.exito) {
						this.$toast.success(result.data.mensaje)
						this.setPerfil(result.data.perfil)
						// El nombre de la barra superior sale de la sesión
						this.sesion.refrescar().catch(() => {})
					} else {
						this.$toast.error(result.data.mensaje)
					}
				})
				.catch(e => this.$toast.error(mensajeError(e)))
				.finally(() => (this.guardando = false))
			},
			cambiarClave() {
				this.guardandoClave = true

				api
				.post("/perfil/cambiar_clave", this.clave)
				.then(result => {
					if (result.data.exito) {
						this.$toast.success(result.data.mensaje)
						this.clave = claveVacia()
						this.ver = { actual: false, clave: false }
					} else {
						this.$toast.error(result.data.mensaje)
					}
				})
				.catch(e => this.$toast.error(mensajeError(e)))
				.finally(() => (this.guardandoClave = false))
			},
			cantidad(n) {
				return Number(n ?? 0).toLocaleString("en-US")
			},
			dinero(n) {
				const monto = formatoMonto(n)
				return `${this.simbolo} ${monto}`.trim()
			},
			fechaTexto(fecha) {
				return new Date(fecha.replace(" ", "T")).toLocaleDateString("es", { day: "numeric", month: "long", year: "numeric" })
			}
		},
		components: {
			PageHeader,
			StatCard
		}
	}
</script>
