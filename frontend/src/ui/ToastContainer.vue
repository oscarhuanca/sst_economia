<template>
  <div class="fixed top-5 inset-x-0 z-[100] flex flex-col items-center gap-3 pointer-events-none px-4">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        role="alert"
        class="pointer-events-auto relative overflow-hidden w-full max-w-md rounded-2xl ring-1 shadow-xl backdrop-blur-sm flex items-start gap-3 px-4 py-3.5"
        :class="toastClasses[toast.type].card"
      >
        <span
          class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center"
          :class="toastClasses[toast.type].icon"
        >
          <AppIcon :name="iconFor(toast.type)" :size="19" />
        </span>
        <p class="text-sm font-semibold leading-snug flex-1 text-white">{{ toast.message }}</p>
        <button
          class="shrink-0 p-1 rounded-lg text-slate-400 hover:bg-white/10 hover:text-white transition"
          @click="toastStore.remove(toast.id)"
        >
          <AppIcon name="x" :size="15" />
        </button>
        <span class="absolute bottom-0 left-0 h-1 toast-progress" :class="toastClasses[toast.type].progress" :style="{ animationDuration: toast.duration + 'ms' }"></span>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
// Contenedor global de toasts.
// Renderiza en la parte superior de la pantalla todas las notificaciones del
// store de toasts, con animación de entrada/salida y barra de progreso de vida.
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';

const toastStore = useToastStore();

// Clases Tailwind por tipo de toast (tarjeta, icono y barra de progreso).
const toastClasses = {
  success: {
    card: 'bg-slate-800/90 ring-emerald-500/30',
    icon: 'bg-emerald-500/20 text-emerald-300',
    progress: 'bg-emerald-400',
  },
  error: {
    card: 'bg-slate-800/90 ring-red-500/40',
    icon: 'bg-red-900/50 text-red-400',
    progress: 'bg-red-500',
  },
  warning: {
    card: 'bg-slate-800/90 ring-amber-500/30',
    icon: 'bg-amber-500/20 text-amber-300',
    progress: 'bg-amber-400',
  },
  info: {
    card: 'bg-slate-800/90 ring-orange-500/30',
    icon: 'bg-orange-500/20 text-orange-300',
    progress: 'bg-orange-400',
  },
};

/** Devuelve el nombre del icono según el tipo de toast. */
const iconFor = (type) => ({
  success: 'check-circle',
  error: 'x-circle',
  warning: 'alert-triangle',
  info: 'info',
}[type] || 'info');
</script>

<style scoped>
.toast-move,
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-16px) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(28px);
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  transform-origin: left;
  animation-name: toast-progress;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;
}

@keyframes toast-progress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}
</style>