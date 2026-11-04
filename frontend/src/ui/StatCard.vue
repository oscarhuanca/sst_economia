<template>
  <div class="card p-5 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full blur-2xl opacity-20" :class="blobTone"></div>
    <div class="relative flex items-start justify-between gap-4">
      <div>
        <p class="text-sm font-semibold text-slate-400">{{ label }}</p>
        <p class="mt-1 text-3xl font-bold text-white leading-none">{{ value }}</p>
        <p v-if="sublabel" class="mt-2 text-xs text-slate-400">{{ sublabel }}</p>
      </div>
      <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg shrink-0" :class="iconTone">
        <AppIcon :name="icon" :size="24" />
      </div>
    </div>
  </div>
</template>

<script setup>
// Tarjeta de métrica (KPI).
// Muestra un label, un valor grande, un sublabel opcional y un icono en un
// bloque con degradado. El tono de color se define con el prop `tone`.
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  sublabel: { type: String, default: '' },
  icon: { type: String, default: 'chart' },
  tone: { type: String, default: 'indigo' }, // Clave en `tones`.
});

// Catálogo de tonos: clases del icono (degradado) y del "blob" decorativo.
const tones = {
  indigo: { icon: 'bg-orange', blob: 'bg-orange-500' },
  violet: { icon: 'bg-orange', blob: 'bg-red-500' },
  sky: { icon: 'bg-orange', blob: 'bg-orange-400' },
  emerald: { icon: 'bg-gradient-to-br from-emerald-500 to-emerald-600', blob: 'bg-emerald-400' },
  amber: { icon: 'bg-orange', blob: 'bg-amber-400' },
  orange: { icon: 'bg-orange', blob: 'bg-orange-400' },
  rose: { icon: 'bg-gradient-to-br from-red-500 to-red-600', blob: 'bg-red-400' },
};

// Clases resultantes del tono seleccionado (con respaldo a indigo).
const iconTone = computed(() => tones[props.tone]?.icon || tones.indigo.icon);
const blobTone = computed(() => tones[props.tone]?.blob || tones.indigo.blob);
</script>