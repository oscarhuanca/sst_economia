<template>
  <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="close"></div>
    <div class="relative flex items-center justify-center min-h-full p-4">
      <div class="card w-full max-h-[calc(100vh-2rem)] overflow-y-auto p-8 shadow-2xl" :style="{ maxWidth: maxWidth }">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-white">{{ title }}</h3>
          <button class="p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-white transition" @click="close">
            <AppIcon name="x" :size="20" />
          </button>
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
// Modal genérico reutilizable.
// Aparece con fondo oscurecido al hacer `v-model` true, muestra un título con
// botón de cierre y el contenido del slot.
import AppIcon from './AppIcon.vue';

const props = defineProps({
  title: { type: String, required: true },
  maxWidth: { type: String, default: '480px' },
});

// Prop 'v-model' que controla la visibilidad del modal.
const open = defineModel({ type: Boolean, default: false });

/** Cierra el modal (poniendo el v-model a false). */
const close = () => {
  open.value = false;
};
</script>