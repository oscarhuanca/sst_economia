import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/main.css'

// Crea la instancia raíz de la aplicación y registra Pinia (estado global) y
// Vue Router antes de montar el componente raíz en el elemento #app.
const app = createApp(App)

app.use(createPinia())
app.use(router)

// Manejo de errores global: ningún error debe dejar la app congelada en
// silencio. Los errores de render se muestran en el GlobalErrorBoundary
// (App.vue); aquí queda el registro central (punto de conexión para Sentry,
// Bugsnag, etc.) y los rechazos de promesas no capturados.
app.config.errorHandler = (err, instance, info) => {
  console.error('[app] error de render no capturado:', err, info)
}

window.addEventListener('unhandledrejection', (event) => {
  console.error('[app] promesa rechazada sin control:', event.reason)
})

window.addEventListener('error', (event) => {
  console.error('[app] error global del navegador:', event.message, event.filename, event.lineno)
})

app.mount('#app')
