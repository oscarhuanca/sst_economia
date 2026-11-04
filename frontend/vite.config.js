import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Lee las variables de entorno del archivo activo (.env.development /
  // .env.production). VITE_PORT define el puerto dinámico de dev y preview.
  const env = loadEnv(mode, process.cwd(), '')
  const port = Number(env.VITE_PORT) || 5173

  return {
    plugins: [
      vue(),
      // Optimización de performance: devtools solo en desarrollo; descarta el plugin del build de producción.
      ...(mode !== 'production' ? [vueDevTools()] : []),
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
        // html2pdf.js usa internamente html2canvas, que no soporta los colores
        // oklch()/oklab() de Tailwind v4 y lanza "unsupported color function".
        // html2canvas-pro es un fork con la misma API que SÍ los parsea.
        'html2canvas': 'html2canvas-pro',
      },
    },
    server: {
      port,
      host: true,
    },
    preview: {
      port,
      host: true,
    },
    build: {
      // Optimización de performance: separa CSS por chunk y desactiva sourcemaps (valores por defecto de Vite, explícitos).
      cssCodeSplit: true,
      sourcemap: false,
    },
  }
})