<script setup lang="ts">
// Inicio de sesión contra la API (usuario = alias)
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '../components/ui/FormField.vue'
import { useSesionStore } from '../stores/sesion'
import { mensajeError } from '../services/api'

const router = useRouter()
const route = useRoute()
const sesion = useSesionStore()

const credenciales = reactive({
  usuario: '',
  clave: '',
})

const enviando = ref(false)
const error = ref('')
const mostrarClave = ref(false)
const anio = new Date().getFullYear()

// Llegó aquí porque la API avisó que la sesión caducó (ver main.ts)
const sesionCaducada = computed(() => route.query.caducada === '1')

// Puntos clave del panel de marca
const puntos = [
  { icono: 'fa-solid fa-dolly', texto: 'Inventario siempre al día' },
  { icono: 'fa-solid fa-cart-shopping', texto: 'Compras y ventas en un solo lugar' },
  { icono: 'fa-solid fa-chart-column', texto: 'Reportes claros para decidir' },
]

async function ingresar() {
  error.value = ''

  if (!credenciales.usuario.trim() || !credenciales.clave) {
    error.value = 'Ingrese usuario y contraseña.'
    return
  }

  enviando.value = true
  try {
    await sesion.iniciar(credenciales.usuario.trim(), credenciales.clave)
    // Vuelve a la página que pidió el login, si la hay
    const destino = typeof route.query.volver === 'string' ? route.query.volver : '/'
    router.replace(destino)
  } catch (e) {
    error.value = mensajeError(e)
    credenciales.clave = ''
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
        <h2 class="login-marca-titulo">Gestiona tu operación de principio a fin</h2>
        <ul class="login-puntos">
          <li v-for="punto in puntos" :key="punto.texto">
            <span class="login-punto-icono"><i :class="punto.icono" /></span>
            {{ punto.texto }}
          </li>
        </ul>
      </div>

      <p class="login-marca-pie">© {{ anio }} Logy Logística</p>
    </aside>

    <!-- Formulario -->
    <main class="login-formulario">
      <div class="login-caja">
        <!-- Marca en móvil (el panel de la izquierda se oculta) -->
        <div class="login-marca-movil">
          <span class="login-marca-icono"><i class="fa-solid fa-cube" aria-hidden="true" /></span>
          Logy
        </div>

        <h1 class="login-titulo">Bienvenido de nuevo</h1>
        <p class="login-subtitulo">Ingresa tus credenciales para continuar</p>

        <form novalidate @submit.prevent="ingresar">
          <div v-if="error" class="alert alert-danger login-alerta" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true" />
            <span>{{ error }}</span>
          </div>
          <div v-else-if="sesionCaducada" class="alert alert-warning login-alerta" role="status">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true" />
            <span>Tu sesión caducó. Vuelve a iniciar sesión para continuar donde estabas.</span>
          </div>

          <FormField id="usuario" etiqueta="Usuario" class="mb-3">
            <div class="campo-con-icono">
              <i class="fa-regular fa-user" aria-hidden="true" />
              <input
                id="usuario"
                v-model="credenciales.usuario"
                type="text"
                class="form-control"
                autocomplete="username"
                placeholder="Tu usuario"
              >
            </div>
          </FormField>

          <FormField id="clave" etiqueta="Contraseña" class="mb-2">
            <div class="campo-con-icono">
              <i class="fa-solid fa-lock" aria-hidden="true" />
              <input
                id="clave"
                v-model="credenciales.clave"
                :type="mostrarClave ? 'text' : 'password'"
                class="form-control campo-con-boton"
                autocomplete="current-password"
                placeholder="Tu contraseña"
              >
              <button
                type="button"
                class="campo-boton"
                :aria-label="mostrarClave ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                :title="mostrarClave ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                @click="mostrarClave = !mostrarClave"
              >
                <i class="fa-regular" :class="mostrarClave ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true" />
              </button>
            </div>
          </FormField>

          <div class="text-end mb-4">
            <a href="#" class="login-enlace" @click.prevent>¿Olvidaste tu contraseña?</a>
          </div>

          <button type="submit" class="btn btn-primary w-100 login-boton" :disabled="enviando">
            <i v-if="enviando" class="fa-solid fa-spinner fa-spin me-2" aria-hidden="true" />
            {{ enviando ? 'Ingresando…' : 'Ingresar' }}
            <i v-if="!enviando" class="fa-solid fa-arrow-right ms-2" aria-hidden="true" />
          </button>
        </form>
      </div>
    </main>
  </div>
</template>
