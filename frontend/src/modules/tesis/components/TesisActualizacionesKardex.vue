<template>
  <div v-if="actualizaciones.length" class="card overflow-hidden">
    <div class="h-2 bg-orange"></div>
    <div class="p-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-bold text-white inline-flex items-center gap-2">
          <span class="w-8 h-8 rounded-lg bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
            <AppIcon name="clipboard" :size="17" />
          </span>
          Flujo seguido por Kardex
        </h3>
        <span class="text-[11px] font-semibold text-slate-400 inline-flex items-center gap-1.5">
          <AppIcon name="check-square" :size="12" />
          Se actualiza automáticamente
        </span>
      </div>
      <p class="text-sm text-slate-400 mt-1">
        Todo lo que Kardex registró en tu trámite, módulo por módulo.
      </p>

      <ol class="mt-5 space-y-3">
        <li
          v-for="act in actualizaciones"
          :key="act.id"
          class="rounded-xl ring-1 px-4 py-3.5"
          :class="claseTarjeta(act)"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
              <span
                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ring-1"
                :class="act.completado ? (act.id === 'publicacion' ? 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/40' : 'bg-orange-500/10 text-orange-300 ring-orange-500/30') : 'bg-slate-500/10 text-slate-400 ring-slate-500/30'"
              >
                <AppIcon :name="act.completado ? act.icon : 'clock'" :size="16" />
              </span>
              <div class="min-w-0">
                <p class="text-sm font-bold truncate" :class="act.completado ? 'text-white' : 'text-slate-300'">{{ act.label }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-wide" :class="act.completado ? 'text-orange-300' : 'text-slate-500'">
                  {{ act.completado ? act.badge : 'Pendiente' }}
                </p>
              </div>
            </div>
            <span v-if="act.fecha" class="text-xs text-slate-400 inline-flex items-center gap-1 shrink-0">
              <AppIcon name="calendar" :size="12" />
              {{ fechaLegible(act.fecha) }}
            </span>
          </div>

          <dl v-if="act.campos.length" class="mt-3 grid sm:grid-cols-2 gap-x-6 gap-y-2">
            <div v-for="campo in act.campos" :key="campo.etiqueta" class="min-w-0">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ campo.etiqueta }}
              </dt>
              <dd class="text-sm font-semibold text-slate-100 break-words">
                <a v-if="campo.ruta" :href="assetUrl(campo.ruta)" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 text-orange-300 hover:text-orange-200 hover:underline">
                  <AppIcon name="file-text" :size="13" />
                  {{ campo.valor }} · Ver PDF
                </a>
                <template v-else>{{ campo.valor }}</template>
              </dd>
            </div>
          </dl>
          <p v-else class="mt-2 text-xs text-slate-500">Aún no registrado por Kardex.</p>
        </li>
      </ol>
    </div>
  </div>
</template>

<script setup>
// Flujo seguido por Kardex (lado del estudiante).
// Muestra, por cada módulo del flujo de titulación que Kardex ya registró, el
// estado alcanzado, la fecha y el detalle que quedó guardado (resoluciones,
// tribunal, tema, nota de defensa, publicación). Solo se alimenta del propio
// trámite del estudiante, así que no muestra nada de otros estudiantes ni de
// otras modalidades de titulación.
import { computed } from 'vue';
import { actualizacionesKardex } from '../utils/flujoTitulacion';
import { assetUrl } from '@/core/http/storage';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
  tramite: { type: Object, default: null },
});

const actualizaciones = computed(() => actualizacionesKardex(props.tramite));

function claseTarjeta(act) {
  if (!act.completado) return 'bg-white/[0.03] ring-white/10 opacity-80';
  return act.id === 'publicacion' ? 'bg-emerald-500/10 ring-emerald-500/30' : 'bg-white/5 ring-white/10';
}
</script>
