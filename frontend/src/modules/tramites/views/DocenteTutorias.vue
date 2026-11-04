<template>
  <AppShell title="Mis Tutorías" subtitle="Estudiantes a los que acompañas en su titulación">
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
      <StatCard label="Estudiantes en Proceso" :value="activos.length" icon="book" tone="amber" />
      <StatCard label="Tutorías Finalizadas" :value="finalizados.length" icon="award" tone="orange" />
      <StatCard label="Carga Académica Global" :value="promedioGlobal" icon="trending-up" tone="rose" sublabel="Promedio de avance de mis trámites" />
    </div>

    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-lg font-bold text-white">Mis Estudiantes</h2>
        <p class="text-sm text-slate-400">Seguimiento completo de cada tutoría asignada.</p>
      </div>
      <button class="btn-ghost" :disabled="cargando" @click="recargar">
        <AppIcon name="loader" v-if="cargando" :size="15" class="animate-spin" />
        <AppIcon name="chevron-down" v-else :size="15" class="rotate-0" />
        Actualizar
      </button>
    </div>

    <div v-if="cargando" class="card flex items-center justify-center gap-2 py-16 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando tutorías...
    </div>

    <div v-else-if="activos.length === 0 && finalizados.length === 0" class="card p-6">
      <EmptyState
        icon="book"
        title="Aún no tienes estudiantes asignados"
        message="Cuando Kardex o Dirección te asignen una tutoría, el estudiante aparecerá aquí con su seguimiento completo."
      />
    </div>

    <template v-else>
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3 inline-flex items-center gap-2">
        <AppIcon name="clock" :size="15" />
        En Proceso ({{ activos.length }})
      </h3>
      <div v-if="activos.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <div v-for="tramite in activos" :key="tramite.id_tramite" class="card overflow-hidden flex flex-col hover:shadow-2xl hover:-translate-y-0.5 transition-all duration-200">
          <div class="relative h-2 bg-orange"></div>
          <div class="p-5 flex-1 flex flex-col">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <Avatar :nombres="tramite.estudiante?.user?.nombres || ''" :apellidos="tramite.estudiante?.user?.apellidos || ''" :size="12" />
                <div>
                  <h4 class="text-lg font-bold text-white leading-tight">
                    {{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}
                  </h4>
                  <p class="text-sm text-orange-300 font-semibold">{{ tramite.modalidad.nombre }}</p>
                </div>
              </div>
              <EstadoBadge :estado="tramite.estado_actual" />
            </div>

            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-400">
              <span class="inline-flex items-center gap-1">
                <AppIcon name="file-text" :size="12" />
                Cod: {{ tramite.estudiante.registro_universitario }}
              </span>
              <span class="inline-flex items-center gap-1">
                <AppIcon name="chart" :size="12" />
                Promedio: {{ tramite.estudiante.promedio_global }}
              </span>
              <span v-if="tramite.estados?.length" class="inline-flex items-center gap-1">
                <AppIcon name="calendar" :size="12" />
                Último: {{ fechaUltimo(tramite) }}
              </span>
            </div>

            <div class="mt-4">
              <div class="flex justify-between text-xs text-slate-400 mb-1">
                <span class="font-semibold">Avance de la modalidad</span>
                <span class="font-bold text-orange-300">{{ porcentaje(tramite) }}%</span>
              </div>
              <ProgressBar :value="porcentaje(tramite)" />
            </div>

            <button class="btn-primary w-full mt-4" @click="gestionar(tramite)">
              <AppIcon name="trending-up" :size="15" />
              Ver Seguimiento del Trámite
            </button>
          </div>
        </div>
      </div>
      <div v-else class="rounded-xl bg-white/5 border border-white/10 p-4 text-orange-200 text-sm mb-8">
        No tienes tutorías activas en este momento.
      </div>

      <h3 v-if="finalizados.length" class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3 inline-flex items-center gap-2">
        <AppIcon name="award" :size="15" />
        Finalizados ({{ finalizados.length }})
      </h3>
      <div v-if="finalizados.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div v-for="tramite in finalizados" :key="tramite.id_tramite" class="card overflow-hidden opacity-80">
          <div class="relative h-2 bg-gradient-to-r from-emerald-500/60 to-red-600/60"></div>
          <div class="p-5 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <Avatar :nombres="tramite.estudiante?.user?.nombres || ''" :apellidos="tramite.estudiante?.user?.apellidos || ''" :size="10" />
              <div>
                <h4 class="font-bold text-white">{{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}</h4>
                <p class="text-sm text-slate-400">{{ tramite.modalidad.nombre }}</p>
              </div>
            </div>
            <EstadoBadge :estado="tramite.estado_actual" />
            <button class="btn-ghost" @click="gestionar(tramite)">
              <AppIcon name="chevron-right" :size="15" />
            </button>
          </div>
        </div>
      </div>
    </template>
  </AppShell>
</template>

<script setup>
// Vista del panel del docente tutor.
// Lista los trámites donde el docente autenticado es tutor, divididos en
// "En Proceso" y "Finalizados", con resumen de carga académica y acceso a la
// gestión de cada trámite.
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useTramitesStore } from '../stores/tramites';
import { ESTADOS_TERMINALES, progresoEstado, formatoEstado } from '../utils/estados';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';
import EstadoBadge from '../components/EstadoBadge.vue';
import StatCard from '@/ui/StatCard.vue';
import ProgressBar from '@/ui/ProgressBar.vue';
import EmptyState from '@/ui/EmptyState.vue';

const router = useRouter();
const tramitesStore = useTramitesStore();
const cargando = ref(false); // true mientras se cargan las tutorías.

// Tutorías con estado no terminal (en curso).
const activos = computed(() => tramitesStore.tutorias.filter((t) => !ESTADOS_TERMINALES.includes(t.estado_actual)));
// Tutorías que alcanzaron un estado terminal (finalizadas).
const finalizados = computed(() => tramitesStore.tutorias.filter((t) => ESTADOS_TERMINALES.includes(t.estado_actual)));
// Promedio de avance de las tutorías activas (porcentaje).
const promedioGlobal = computed(() => {
  if (!activos.value.length) return 'â€”';
  const total = activos.value.reduce((acc, t) => acc + progresoEstado(t), 0);
  return total / activos.value.length + '%';
});

onMounted(recargar);

/** Recarga las tutorías del docente desde la API (GET /api/tutorias). */
async function recargar() {
  cargando.value = true;
  await tramitesStore.cargarTutorias();
  cargando.value = false;
}

/** Navega a la gestión del trámite indicado. */
const gestionar = (tramite) => router.push({ name: 'GestionTramite', params: { id: tramite.id_tramite } });

/** Porcentaje de avance de un trámite según su secuencia. */
const porcentaje = (tramite) => progresoEstado(tramite);

/** Formatea el último estado registrado con su fecha ('Nombre · dd/MM/yyyy'). */
const fechaUltimo = (tramite) => {
  const estados = tramite.estados || [];
  const ultimo = estados[estados.length - 1];
  if (!ultimo?.created_at) return '';
  return `${formatoEstado(ultimo.nombre_estado)} · ${new Date(ultimo.created_at).toLocaleDateString('es-BO')}`;
};
</script>
