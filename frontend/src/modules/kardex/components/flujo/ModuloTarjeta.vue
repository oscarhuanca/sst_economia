<template>
  <div class="card p-5 flex flex-col">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-xl flex items-center justify-center ring-1"
          :class="iconoContenedor"
        >
          <AppIcon :name="icono" :size="18" />
        </div>
        <div class="min-w-0">
          <h4 class="font-bold text-white uppercase flex items-center gap-2">
            <span class="text-orange-300 text-xs font-extrabold">Módulo {{ numero }}</span>
            {{ titulo }}
          </h4>
          <p class="text-xs text-slate-400 mt-0.5">{{ descripcion }}</p>
        </div>
      </div>
      <span
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border whitespace-nowrap"
        :class="chip.clase"
      >
        <span class="w-1.5 h-1.5 rounded-full" :class="chip.dot"></span>
        {{ chip.etiqueta }}
      </span>
    </div>

    <div class="mt-5 flex-1 flex flex-col" :class="{ 'opacity-60 pointer-events-none': bloqueado && !completado }">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
  numero: { type: [Number, String], required: true },
  titulo: { type: String, required: true },
  descripcion: { type: String, default: '' },
  icono: { type: String, default: 'file-text' },
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
});

const chip = computed(() => {
  if (props.completado) {
    return {
      etiqueta: 'Completado',
      clase: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
      dot: 'bg-emerald-400',
    };
  }
  if (props.enProgreso) {
    return {
      etiqueta: 'En proceso',
      clase: 'bg-orange-500/90 text-white border-orange-500',
      dot: 'bg-white',
    };
  }
  return {
    etiqueta: 'Pendiente',
    clase: 'bg-white/10 text-slate-400 border-white/15',
    dot: 'bg-slate-400',
  };
});

const iconoContenedor = computed(() => {
  if (props.completado) return 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300';
  if (props.enProgreso) return 'bg-orange-500/15 border-orange-500/40 text-orange-300';
  return 'bg-orange-500/10 border-orange-500/30 text-orange-300';
});
</script>