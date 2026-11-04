<script setup>
// Componente raíz de la aplicación.
// Renderiza la vista correspondiente a la ruta activa dentro de una transición
// de entrada/salida y monta el contenedor global de toasts (notificaciones).
// La "frontera de error global" envuelve el árbol de rutas: si una vista falla
// al renderizar, se muestra un fallback visible en lugar de una pantalla en
// blanco. La clave `route.fullPath` fuerza el remontaje por navegación, lo que
// evita que las transiciones queden atascadas en la primera carga.
import { useRoute } from 'vue-router';
import GlobalErrorBoundary from '@/core/GlobalErrorBoundary.vue';
import ToastContainer from '@/ui/ToastContainer.vue';

const route = useRoute();
</script>

<template>
  <GlobalErrorBoundary>
    <router-view v-slot="{ Component }">
      <transition name="page" mode="out-in">
        <component :is="Component" :key="route.fullPath" />
      </transition>
    </router-view>
  </GlobalErrorBoundary>
  <ToastContainer />
</template>