import { createApp } from 'vue'
import { createPinia } from 'pinia'

// Estilos: Bootstrap, iconos y ajustes propios (en este orden)
import 'bootstrap/dist/css/bootstrap.min.css'
import '@fortawesome/fontawesome-free/css/all.min.css'
import './styles/app.css'

// JS de Bootstrap: dropdowns y modales
import 'bootstrap'

import App from './App.vue'
import router from './router'
import { useTemaStore } from './stores/tema'
import { useAcentoStore } from './stores/acento'
import { useSesionStore } from './stores/sesion'
import { alCaducarSesion } from './services/api'
import toaster from './helpers/toaster'

import Card from './components/bootstrap/Card.vue'
import CardHeader from './components/bootstrap/CardHeader.vue'
import CardBody from './components/bootstrap/CardBody.vue'

const app = createApp(App)
const pinia = createPinia()

// Tarjetas de Bootstrap disponibles en todas las vistas: <card>, <card-header>, <card-body>
app.component('Card', Card)
app.component('CardHeader', CardHeader)
app.component('CardBody', CardBody)

// Mensajes emergentes en todos los componentes: this.$toast.success(...), this.$toast.error(...)
app.config.globalProperties.$toast = toaster

app.use(pinia)
app.use(router)

// Aplica el tema (claro/oscuro) y el color guardados desde el inicio, también en el login
useTemaStore(pinia)
useAcentoStore(pinia)

// Si la API avisa que la sesión caducó: cerrarla y llevar al login,
// que después de ingresar vuelve a la página donde estaba
alCaducarSesion(() => {
  useSesionStore(pinia).cerrar()

  const actual = router.currentRoute.value
  if (actual.name !== 'login') {
    router.replace({ name: 'login', query: { volver: actual.fullPath, caducada: '1' } })
  }
})

app.mount('#app')
