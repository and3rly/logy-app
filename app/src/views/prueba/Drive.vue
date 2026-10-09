<template>
	<PageHeader />

	<div class="row g-4 align-items-start">
		<!-- Conexión -->
		<div class="col-lg-5 d-flex flex-column gap-4">
			<card>
				<card-header>
					<div class="d-flex align-items-center gap-3">
						<span class="icono-suave icono-suave-primario" aria-hidden="true">
							<i class="fa-brands fa-google-drive" />
						</span>
						<div class="lh-sm">
							<div class="fw-semibold">Conexión</div>
							<small class="text-body-secondary fw-normal">libraries/Drive.php + helpers/archivo_helper.php</small>
						</div>
					</div>
				</card-header>
				<card-body>
					<form @submit.prevent="probar" autocomplete="off">
						<p class="small text-body-secondary">
							Librería <code>Drive</code> con delegación de dominio sobre <strong>team@innovasys.com.gt</strong>.
							Los archivos van a la subcarpeta indicada dentro de la carpeta raíz (o a "varios").
						</p>
						<div class="mb-3">
							<label for="drive_subcarpeta" class="form-label">Subcarpeta (opcional)</label>
							<input id="drive_subcarpeta" v-model.trim="form.carpeta" type="text" class="form-control" placeholder="productos">
							<div class="form-text">Se crea si no existe. Puede tener varios niveles: productos/fotos.</div>
						</div>
						<div class="d-flex justify-content-end">
							<button type="submit" class="btn btn-outline-primary" :disabled="probando">
								<span v-if="probando" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
								<i v-else class="fa-solid fa-plug me-1" aria-hidden="true" />Probar conexión
							</button>
						</div>
					</form>
				</card-body>

				<ul v-if="estado" class="list-group list-group-flush small">
					<li class="list-group-item" :class="estado.exito ? 'text-success-emphasis bg-success-subtle' : 'text-danger-emphasis bg-danger-subtle'">
						<i class="fa-solid me-1" :class="estado.exito ? 'fa-circle-check' : 'fa-circle-xmark'" aria-hidden="true" />{{ estado.mensaje }}
					</li>
					<li v-if="estado.cuenta" class="list-group-item">
						<div class="text-body-secondary">Cuenta de servicio</div>
						<div class="fw-semibold text-break">{{ estado.cuenta }}</div>
						<div class="text-body-secondary">Client ID {{ estado.client_id }}</div>
					</li>
					<li v-if="estado.usuario" class="list-group-item">
						<div class="text-body-secondary">Trabaja como</div>
						<div class="fw-semibold text-break">{{ estado.usuario }}</div>
						<div v-if="estado.cuota" class="text-body-secondary">
							Cuota: {{ tamano(estado.cuota.uso) }} de {{ estado.cuota.limite ? tamano(estado.cuota.limite) : 'ilimitada' }}
							<span v-if="estado.cuota.limite === '0' || estado.cuota.limite === 0" class="text-danger-emphasis">(sin espacio propio)</span>
						</div>
					</li>
					<li v-if="estado.carpeta" class="list-group-item">
						<div class="text-body-secondary">Carpeta</div>
						<div class="fw-semibold text-break">Mi unidad / {{ estado.carpeta }}</div>
					</li>
				</ul>
			</card>

			<!-- Subir -->
			<card>
				<card-header>
					<span class="fw-semibold">Subir archivo</span>
				</card-header>
				<card-body>
					<form @submit.prevent="subir" autocomplete="off">
						<div class="mb-3">
							<label for="drive_archivo" class="form-label">Archivo <span class="text-danger">*</span></label>
							<input id="drive_archivo" ref="archivo" type="file" class="form-control" @change="elegir">
							<div class="form-text">Hasta 10 MB.</div>
						</div>
						<p class="small text-body-secondary">Queda compartido con cualquiera que tenga el enlace, como en la guía.</p>
						<div class="d-flex justify-content-end">
							<button type="submit" class="btn btn-primary" :disabled="subiendo || !archivo">
								<span v-if="subiendo" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
								<i v-else class="fa-solid fa-cloud-arrow-up me-1" aria-hidden="true" />{{ subiendo ? 'Subiendo' : 'Subir' }}
							</button>
						</div>
					</form>
				</card-body>
			</card>
		</div>

		<!-- Archivos de la carpeta -->
		<div class="col-lg-7">
			<card>
				<card-header>
					<div class="d-flex align-items-center justify-content-between">
						<span class="fw-semibold">Archivos de la carpeta</span>
						<button type="button" class="btn btn-sm btn-outline-secondary" :disabled="listando" @click="listar">
							<span v-if="listando" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
							<i v-else class="fa-solid fa-rotate me-1" aria-hidden="true" />Actualizar
						</button>
					</div>
				</card-header>
				<ul class="list-group list-group-flush">
					<li v-for="a in lista" :key="a.id" class="list-group-item d-flex align-items-center gap-3 py-3">
						<img
							v-if="a.imagen"
							:src="a.imagen"
							:alt="a.nombre"
							class="rounded border object-fit-cover flex-shrink-0"
							width="56"
							height="56"
							referrerpolicy="no-referrer"
						>
						<span v-else class="icono-suave icono-suave-info flex-shrink-0" aria-hidden="true">
							<i class="fa-regular fa-file" />
						</span>
						<div class="flex-grow-1 lh-sm text-truncate">
							<div class="fw-semibold text-truncate">{{ a.nombre }}</div>
							<small class="text-body-secondary">{{ tamano(a.tamano) }} · {{ fecha(a.fecha) }}</small>
							<small class="d-block text-body-secondary text-truncate">ID {{ a.id }}</small>
						</div>
						<a v-if="a.enlace" :href="a.enlace" target="_blank" rel="noopener" class="btn btn-sm btn-suave-info" title="Abrir en Drive">
							<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true" />
						</a>
						<button type="button" class="btn btn-sm btn-suave-danger" title="Enviar a la papelera" :disabled="eliminando === a.id" @click="eliminar(a)">
							<span v-if="eliminando === a.id" class="spinner-border spinner-border-sm" aria-hidden="true" />
							<i v-else class="fa-solid fa-trash" aria-hidden="true" />
						</button>
					</li>
					<li v-if="!lista.length" class="list-group-item text-body-secondary py-4 text-center">
						{{ listando ? 'Cargando...' : 'Sin archivos. Pruebe la conexión o suba uno.' }}
					</li>
				</ul>
			</card>
		</div>
	</div>
</template>

<script>
	import PageHeader from '../../components/layout/PageHeader.vue'
	import api, { mensajeError } from '@/services/api'

	// La subcarpeta se recuerda en este navegador para no escribirla en cada prueba
	const CLAVE = "logy-prueba-gdrive"

	function leerGuardado() {
		try {
			return JSON.parse(localStorage.getItem(CLAVE) ?? "{}")
		} catch {
			return {}
		}
	}

	export default {
		name: "PruebaDrive",
		components: { PageHeader },
		data: () => ({
			form: { carpeta: "", ...leerGuardado() },
			estado: null,
			lista: [],
			archivo: null,
			probando: false,
			listando: false,
			subiendo: false,
			eliminando: null
		}),
		created() {
			this.probar()
		},
		methods: {
			guardarForm() {
				try {
					localStorage.setItem(CLAVE, JSON.stringify(this.form))
				} catch {
					// Sin almacenamiento: no se recuerda
				}
			},
			params() {
				return { carpeta: this.form.carpeta }
			},
			probar() {
				this.guardarForm()
				this.probando = true

				api
				.get("/prueba/gdrive/estado", { params: this.params() })
				.then(result => {
					this.estado = result.data

					if (result.data.exito) {
						this.listar()
					}
				})
				.catch(e => {
					this.estado = { exito: 0, mensaje: mensajeError(e) }
				})
				.finally(() => {
					this.probando = false
				})
			},
			listar() {
				this.listando = true

				api
				.get("/prueba/gdrive/listar", { params: this.params() })
				.then(result => {
					if (result.data.exito) {
						this.lista = result.data.lista
					} else {
						this.$toast.error(result.data.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.listando = false
				})
			},
			elegir(e) {
				this.archivo = e.target.files?.[0] ?? null
			},
			subir() {
				this.guardarForm()

				let datos = new FormData()
				datos.append("archivo", this.archivo)
				datos.append("carpeta", this.form.carpeta)

				this.subiendo = true

				api
				.post("/prueba/gdrive/subir", datos, {
					headers: { "Content-Type": "multipart/form-data" }
				})
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.$toast.success(res.mensaje)
						this.lista.unshift(res.linea)
						this.archivo = null
						this.$refs.archivo.value = ""
					} else {
						this.$toast.error(res.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.subiendo = false
				})
			},
			eliminar(archivo) {
				this.eliminando = archivo.id

				api
				.post("/prueba/gdrive/eliminar", { id: archivo.id }, { params: this.params() })
				.then(result => {
					if (result.data.exito) {
						this.$toast.success(result.data.mensaje)
						this.lista = this.lista.filter(a => a.id !== archivo.id)
					} else {
						this.$toast.error(result.data.mensaje)
					}
				})
				.catch(e => {
					this.$toast.error(mensajeError(e))
				})
				.finally(() => {
					this.eliminando = null
				})
			},
			tamano(bytes) {
				if (bytes === null || bytes === undefined || bytes === "") {
					return "—"
				}

				const n = Number(bytes)
				if (n < 1024) return `${n} B`
				if (n < 1048576) return `${(n / 1024).toFixed(1)} KB`
				if (n < 1073741824) return `${(n / 1048576).toFixed(1)} MB`
				return `${(n / 1073741824).toFixed(1)} GB`
			},
			fecha(valor) {
				return valor ? new Date(valor).toLocaleString("es-GT") : ""
			}
		}
	}
</script>
