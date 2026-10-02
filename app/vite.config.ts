import { fileURLToPath, URL } from 'node:url'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig(({ command }) => ({
  // En el servidor la interfaz vive en /app/ (la API en /api); en desarrollo, en la raíz
  base: command === 'build' ? '/app/' : '/',
  plugins: [vue()],
  resolve: {
    // '@/…' apunta a src/ (ej. '@/mixins/Accion.js')
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    // En desarrollo /api se envía a la API de XAMPP (mismo origen, sin CORS).
    // logy.local es el VirtualHost de Apache que sirve logy/api (archivo hosts → 127.0.0.1).
    // No usar http://localhost: sin un VirtualHost propio, Apache lo envía al primero (gacela.local).
    proxy: {
      '/api': {
        target: 'http://logy.local',
        changeOrigin: true,
        rewrite: (ruta) => ruta.replace(/^\/api/, '/index.php'),
      },
    },
  },
}))
