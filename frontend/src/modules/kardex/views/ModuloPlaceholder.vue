<template>
  <div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-white uppercase">{{ modulo }}</h2>
        <p class="text-sm text-slate-400 mt-1">{{ descripcion }}</p>
      </div>
    </div>

    <div class="card flex flex-col items-center justify-center text-center py-20 border-dashed">
      <div class="w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/30 text-orange-300 flex items-center justify-center mb-4">
        <AppIcon :name="icono" :size="30" />
      </div>
      <h3 class="text-lg font-bold text-white">{{ modulo }}</h3>
      <p class="text-sm text-slate-400 max-w-md mt-2">
        Este módulo estará disponible en la siguiente etapa del sistema.
      </p>
      <span class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/15 border border-orange-500/30 text-orange-300 text-xs font-semibold">
        <AppIcon name="clock" :size="13" />
        En desarrollo
      </span>
    </div>
  </div>
</template>

<script setup>
// Vista placeholder para los módulos de la siguiente etapa (Trabajo Dirigido,
// Examen de Grado y Reporte de Modalidad). El contenido se inyecta por props.
import AppIcon from '@/ui/AppIcon.vue';

defineProps({
    modulo: { type: String, required: true },
    icono: { type: String, default: 'folder' },
    descripcion: { type: String, default: '' },
});
</script>