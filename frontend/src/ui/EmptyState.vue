<template>
  <div class="mx-auto text-center py-10">
    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center text-orange-200 bg-white/5 ring-1 ring-white/10">
      <AppIcon :name="icon" :size="30" />
    </div>
    <h3 class="mt-4 text-lg font-bold text-white">{{ title }}</h3>
    <p v-if="message" class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">{{ message }}</p>
    <div v-if="$slots.default" class="mt-5">
      <slot />
    </div>
  </div>
</template>

<script setup>
// Estado vacío reutilizable (ochenta/vacío).
// Muestra un icono centrado, un título, un mensaje opcional y slot de acciones.
import AppIcon from './AppIcon.vue';

defineProps({
  icon: { type: String, default: 'inbox' },
  title: { type: String, required: true },
  message: { type: String, default: '' },
});
</script>