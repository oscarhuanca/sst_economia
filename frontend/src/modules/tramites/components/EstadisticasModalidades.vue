<template>
  <div v-if="visible" class="card p-5 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-orange flex items-center justify-center text-white shadow-lg shrink-0">
          <AppIcon name="chart" :size="22" />
        </div>
        <div>
          <h2 class="text-lg font-bold text-white">Estadísticas de Graduación</h2>
          <p class="text-sm text-slate-400">Trámites aprobados y reprobados por modalidad</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-500/15 border border-emerald-500/30">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
          <span class="text-sm font-bold text-emerald-300">{{ totales.aprobados }} Aprobados</span>
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-red-900/50 border border-red-500/30">
          <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
          <span class="text-sm font-bold text-red-400">{{ totales.reprobados }} Reprobados</span>
        </div>
      </div>
    </div>

    <div v-if="cargando" class="flex items-center justify-center gap-2 py-10 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando estadísticas...
    </div>

    <div v-else-if="porModalidad.length === 0" class="py-10">
      <EmptyState icon="chart" title="Sin datos disponibles" message="Aún no hay trámites registrados para mostrar estadísticas." />
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="m in porModalidad"
        :key="m.id_modalidad"
        class="relative"
      >
        <div class="flex items-center justify-between mb-1.5 text-sm">
          <span class="font-semibold text-white">{{ m.nombre }}</span>
          <span class="text-xs font-medium text-slate-400">{{ m.total }} trámite{{ m.total !== 1 ? 's' : '' }}</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex-1 h-4 rounded-full bg-white/10 overflow-hidden flex">
            <div
              v-if="m.aprobados > 0"
              class="h-full bg-emerald-500 transition-all duration-500"
              :style="{ width: porcentaje(m.aprobados, m.total) + '%' }"
            ></div>
            <div
              v-if="m.reprobados > 0"
              class="h-full bg-red-500 transition-all duration-500"
              :style="{ width: porcentaje(m.reprobados, m.total) + '%' }"
            ></div>
          </div>
          <div class="flex items-center gap-3 text-xs font-bold shrink-0">
            <span class="inline-flex items-center gap-1.5 text-emerald-300">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              {{ m.aprobados }}
            </span>
            <span class="inline-flex items-center gap-1.5 text-red-400">
              <span class="w-2 h-2 rounded-full bg-red-400"></span>
              {{ m.reprobados }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Tarjeta de estadísticas de graduación (solo para roles con acceso a datos).
// Muestra totales de aprobados/reprobados y, por cada modalidad, una barra
// apilada con el porcentaje correspondiente. Los datos provienen del store de
// trámites (GET /api/estadisticas) y se cargan al montar el componente.
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/modules/auth';
import { useTramitesStore } from '../stores/tramites';
import AppIcon from '@/ui/AppIcon.vue';
import EmptyState from '@/ui/EmptyState.vue';

const authStore = useAuthStore();
const tramitesStore = useTramitesStore();

const cargando = ref(false); // true mientras se cargan las estadísticas.

// Solo los roles señalados pueden ver este bloque de estadísticas.
const visible = computed(() => ['admin', 'kardex', 'secretaria', 'direccion'].includes(authStore.user?.rol));
const totales = computed(() => tramitesStore.estadisticas?.totales || { aprobados: 0, reprobados: 0, total: 0 });
const porModalidad = computed(() => tramitesStore.estadisticas?.porModalidad || []);

/** Porcentaje redondeado de `valor` respecto al total (evita división por cero). */
const porcentaje = (valor, total) => (total > 0 ? Math.round((valor / total) * 100) : 0);

onMounted(async () => {
  if (!visible.value) return;
  cargando.value = true;
  await tramitesStore.cargarEstadisticas();
  cargando.value = false;
});
</script>