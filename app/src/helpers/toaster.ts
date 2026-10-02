import { createToaster } from '@meforma/vue-toaster'

// Mensajes emergentes (éxito, error...). Se registra como this.$toast en main.ts
export default createToaster({
  position: 'top-right',
})
