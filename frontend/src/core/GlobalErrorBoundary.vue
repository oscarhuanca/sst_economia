<script setup>
// Frontera de error global (Error Boundary) para Vue.
//
// Captura cualquier error de RENDER lanzado por los componentes que envuelve
// (el árbol de <router-view>) y, en lugar de dejar la aplicación en blanco o
// congelada, muestra un fallback visible con opción de reintentar o recargar.
// Los errores asíncronos (promesas rechazadas, carga de chunks) no pasan por
// aquí: se cubren con router.onError y window 'unhandledrejection'.
import { onErrorCaptured, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

defineProps({
    titulo: { type: String, default: 'Algo salió mal' },
});

const error = ref(null);
const route = useRoute();

onErrorCaptured((err, _instance, info) => {
    console.error('[ErrorBoundary]', err, info);
    error.value = {
        message: err?.message || 'Error inesperado',
        info: String(info || '').replaceAll(/^[\w\s:.]+?:\s*/g, ''),
    };
    // Detén la propagación: ya renderizamos nuestro propio fallback.
    return false;
});

// La frontera envuelve al <router-view>, así que vive por encima de TODAS las
// vistas y nunca se desmonta al navegar. Sin esto, un error de render en una
// vista dejaba la aplicación entera bloqueada en el fallback y "Reintentar"
// volvía a montar la misma vista que fallaba: la única salida era "Recargar
// página" (F5). Al cambiar de ruta se limpia el error para que el usuario pueda
// seguir trabajando en el resto de vistas sin recargar.
watch(
    () => route.fullPath,
    () => {
        error.value = null;
    }
);

function reintentar() {
    error.value = null;
}

function recargar() {
    window.location.reload();
}
</script>

<template>
    <div v-if="error" class="min-h-screen flex items-center justify-center p-6 bg-slate-900">
        <div class="w-full max-w-md card p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-900/50 flex items-center justify-center mb-5 border border-red-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.9 5h13.8a2 2 0 001.7-3L13.7 4.2a2 2 0 00-3.4 0L2.3 18a2 2 0 001.7 3z" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-white">{{ titulo }}</h2>
            <p class="mt-2 text-sm text-slate-300">{{ error.message }}</p>
            <p v-if="error.info" class="mt-2 text-[11px] text-slate-500 font-mono">{{ error.info }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <button
                    class="btn-warm px-5 py-2.5"
                    @click="reintentar"
                >
                    Reintentar
                </button>
                <button
                    class="btn-ghost px-5 py-2.5"
                    @click="recargar"
                >
                    Recargar página
                </button>
            </div>
        </div>
    </div>
    <slot v-else />
</template>