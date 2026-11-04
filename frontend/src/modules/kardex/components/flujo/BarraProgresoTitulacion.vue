<template>
  <div>
    <div class="flex justify-between text-xs text-slate-400 mb-1.5">
      <span class="font-semibold">Solicitud inicial</span>
      <span class="font-bold text-orange-300 text-sm">{{ progreso }}%</span>
      <span class="font-semibold">Titulado</span>
    </div>
    <ProgressBar :value="progreso" :bar-class="tone.bar" />

    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mt-3">
      <div
        v-for="m in modulos"
        :key="m.id"
        class="rounded-xl px-2 py-2 ring-1 flex flex-col items-center gap-1 text-center transition"
        :class="chipClass(m)"
      >
        <AppIcon :name="m.icon" :size="16" :class="chipIconClass(m)" />
        <span class="text-[10px] font-bold uppercase leading-tight" :class="chipTextClass(m)">
          {{ m.label }}
        </span>
        <span
          v-if="m.completado"
          class="text-[9px] font-bold uppercase tracking-wide text-emerald-400 inline-flex items-center gap-0.5"
        >
          <AppIcon name="check" :size="10" /> Hecho
        </span>
        <span
          v-else-if="m.enProgreso"
          class="text-[9px] font-bold uppercase tracking-wide text-orange-300 animate-pulse"
        >
          En curso
        </span>
        <span v-else class="text-[9px] font-bold uppercase tracking-wide text-slate-500">Pendiente</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { toneEstado } from '@/modules/tramites';
import AppIcon from '@/ui/AppIcon.vue';
import ProgressBar from '@/ui/ProgressBar.vue';

const props = defineProps({
  progreso: { type: Number, default: 0 },
  modulos: { type: Array, default: () => [] },
  estadoActual: { type: String, default: '' },
});

const tone = computed(() => toneEstado(props.estadoActual));

const chipClass = (m) => {
  if (m.completado) return 'bg-emerald-500/10 ring-emerald-500/40';
  if (m.enProgreso) return 'bg-orange-500/10 ring-orange-500/40 shadow-lg shadow-orange-900/20';
  return 'bg-white/5 ring-white/10';
};

const chipIconClass = (m) => {
  if (m.completado) return 'text-emerald-400';
  if (m.enProgreso) return 'text-orange-300';
  return 'text-slate-500';
};

const chipTextClass = (m) => {
  if (m.completado) return 'text-emerald-200';
  if (m.enProgreso) return 'text-orange-200';
  return 'text-slate-400';
};
</script>