<script setup lang="ts">
// Primera instalación: datos de la empresa; la API carga los catálogos del sistema
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import FormField from '../components/ui/FormField.vue'
import api, { mensajeError } from '../services/api'
import { marcarInstalado } from '../router'

interface Departamento {
  id: number
  nombre: string
}

interface Municipio extends Departamento {
  departamento_id: number
}

const router = useRouter()

const form = reactive({
  nombre: '',
  razon_social: '',
  identificacion: '',
  telefono: '',
  correo: '',
  direccion: '',
  municipio_id: '',
})

const departamento = ref('')
const departamentos = ref<Departamento[]>([])
const municipios = ref<Municipio[]>([])
const cargando = ref(true)
const enviando = ref(false)
const error = ref('')
const terminado = ref(false)
const yaInstalado = ref(false)
const anio = new Date().getFullYear()

const municipiosDepartamento = computed(() =>
  municipios.value.filter((m) => String(m.departamento_id) === departamento.value),
)

// Lo que se deja listo al instalar
const puntos = [
  { icono: 'fa-solid fa-map-location-dot', texto: 'Departamentos y municipios de Guatemala' },
  { icono: 'fa-solid fa-right-left', texto: 'Tipos de movimiento y estados de documentos' },
  { icono: 'fa-solid fa-user-shield', texto: 'Rol, usuario y sucursal principal' },
]

onMounted(async () => {
  try {
    const { data } = await api.get('/instalacion/estado')
    if (data.instalado) {
      marcarInstalado()
      yaInstalado.value = true
      return
    }
    departamentos.value = data.departamentos
    municipios.value = data.municipios
  } catch (e) {
    error.value = mensajeError(e)
  } finally {
    cargando.value = false
  }
})

function cambiarDepartamento() {
  form.municipio_id = ''
}

async function instalar() {
  error.value = ''

  if (!form.nombre.trim() || !form.razon_social.trim() || !form.identificacion.trim() || !form.direccion.trim() || !form.municipio_id) {
    error.value = 'Complete los campos obligatorios.'
    return
  }

  enviando.value = true
  try {
    const { data } = await api.post('/instalacion/guardar', form)
    if (data.exito) {
      marcarInstalado()
      terminado.value = true
    } else {
      error.value = data.mensaje
    }
  } catch (e) {
    error.value = mensajeError(e)
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="login">
    <!-- Panel de marca (solo en escritorio) -->
    <aside class="login-marca" aria-hidden="true">
      <div class="login-marca-logo">
        <span class="login-marca-icono"><i class="fa-solid fa-cube" /></span>
        Logy
      </div>

      <div>
        <h2 class="login-marca-titulo">Preparemos el sistema para tu empresa</h2>
        <ul class="login-puntos">
          <li v-for="punto in puntos" :key="punto.texto">
            <span class="login-punto-icono"><i :class="punto.icono" /></span>
            {{ punto.texto }}
          </li>
        </ul>
      </div>

      <p class="login-marca-pie">© {{ anio }} Logy Logística</p>
    </aside>

    <main class="login-formulario">
      <div class="login-caja login-caja-ancha">
        <div class="login-marca-movil">
          <span class="login-marca-icono"><i class="fa-solid fa-cube" aria-hidden="true" /></span>
          Logy
        </div>

        <!-- Instalación completada -->
        <template v-if="terminado">
          <h1 class="login-titulo">Instalación completada</h1>
          <p class="login-subtitulo">Los catálogos del sistema quedaron cargados.</p>

          <div class="alert alert-success login-alerta" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true" />
            <span>
              Ingresa con el usuario <strong>admin</strong> y la contraseña <strong>admin</strong>.
              Cámbiala en Mi perfil y registra tu moneda en Catálogos → Moneda.
            </span>
          </div>

          <button type="button" class="btn btn-primary w-100 login-boton" @click="router.replace({ name: 'login' })">
            Ir a iniciar sesión
            <i class="fa-solid fa-arrow-right ms-2" aria-hidden="true" />
          </button>
        </template>

        <!-- Ya existe una empresa: no se vuelve a instalar -->
        <template v-else-if="yaInstalado">
          <h1 class="login-titulo">Instalación</h1>
          <p class="login-subtitulo">Este sistema ya fue instalado.</p>

          <div class="alert alert-info login-alerta" role="status">
            <i class="fa-solid fa-circle-info" aria-hidden="true" />
            <span>La empresa y los catálogos ya están cargados. Inicia sesión para continuar.</span>
          </div>

          <button type="button" class="btn btn-primary w-100 login-boton" @click="router.replace({ name: 'login' })">
            Ir a iniciar sesión
            <i class="fa-solid fa-arrow-right ms-2" aria-hidden="true" />
          </button>
        </template>

        <template v-else>
          <h1 class="login-titulo">Instalación</h1>
          <p class="login-subtitulo">Ingresa los datos de tu empresa. Los campos con * son obligatorios.</p>

          <div v-if="cargando" class="text-center text-body-secondary py-5">
            <i class="fa-solid fa-spinner fa-spin me-2" aria-hidden="true" />Cargando…
          </div>

          <form v-else novalidate autocomplete="off" @submit.prevent="instalar">
            <div v-if="error" class="alert alert-danger login-alerta" role="alert">
              <i class="fa-solid fa-circle-exclamation" aria-hidden="true" />
              <span>{{ error }}</span>
            </div>

            <div class="row g-3 mb-4">
              <FormField id="nombre" etiqueta="Nombre comercial" requerido class="col-12">
                <input id="nombre" v-model="form.nombre" type="text" class="form-control" maxlength="300">
              </FormField>

              <FormField id="razon_social" etiqueta="Razón social" requerido class="col-12">
                <input id="razon_social" v-model="form.razon_social" type="text" class="form-control" maxlength="300">
              </FormField>

              <FormField id="identificacion" etiqueta="NIT" requerido class="col-sm-6">
                <input id="identificacion" v-model="form.identificacion" type="text" class="form-control" maxlength="100">
              </FormField>

              <FormField id="telefono" etiqueta="Teléfono" class="col-sm-6">
                <input id="telefono" v-model="form.telefono" type="tel" class="form-control" maxlength="15">
              </FormField>

              <FormField id="correo" etiqueta="Correo" class="col-12">
                <input id="correo" v-model="form.correo" type="email" class="form-control" maxlength="250">
              </FormField>

              <!-- El departamento solo filtra los municipios -->
              <FormField id="departamento" etiqueta="Departamento" requerido class="col-sm-6">
                <select id="departamento" v-model="departamento" class="form-select" @change="cambiarDepartamento">
                  <option value="" disabled>Seleccione…</option>
                  <option v-for="d in departamentos" :key="d.id" :value="String(d.id)">{{ d.nombre }}</option>
                </select>
              </FormField>

              <FormField id="municipio" etiqueta="Municipio" requerido class="col-sm-6">
                <select id="municipio" v-model="form.municipio_id" class="form-select" :disabled="!departamento">
                  <option value="" disabled>Seleccione…</option>
                  <option v-for="m in municipiosDepartamento" :key="m.id" :value="String(m.id)">{{ m.nombre }}</option>
                </select>
              </FormField>

              <FormField id="direccion" etiqueta="Dirección" requerido class="col-12">
                <input id="direccion" v-model="form.direccion" type="text" class="form-control" maxlength="200">
              </FormField>
            </div>

            <button type="submit" class="btn btn-primary w-100 login-boton" :disabled="enviando">
              <i v-if="enviando" class="fa-solid fa-spinner fa-spin me-2" aria-hidden="true" />
              {{ enviando ? 'Instalando…' : 'Instalar' }}
              <i v-if="!enviando" class="fa-solid fa-arrow-right ms-2" aria-hidden="true" />
            </button>
          </form>
        </template>
      </div>
    </main>
  </div>
</template>
