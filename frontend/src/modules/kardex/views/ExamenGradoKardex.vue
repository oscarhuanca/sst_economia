<template>
  <div class="space-y-6">
    <!-- Encabezado del módulo -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-orange text-white flex items-center justify-center shadow-lg shadow-orange-900/30">
        <AppIcon name="graduation" :size="24" />
      </div>
      <div>
        <h2 class="text-2xl font-bold text-white uppercase">Examen de Grado</h2>
        <p class="text-sm text-slate-400 mt-0.5">
          Habilite al estudiante, registre los sorteos y la Resolución de aprobación del resultado teórico, y asigne las notas.
        </p>
      </div>
    </div>

    <!-- Barra de filtro por CI/RU -->
    <form class="card p-4 sm:p-5" @submit.prevent="buscar">
      <label class="label" for="examen-filtro-ci-ru">Filtrar por CI/RU</label>
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
            <AppIcon name="search" :size="18" />
          </span>
          <input
            id="examen-filtro-ci-ru"
            v-model="identificador"
            type="text"
            class="input pl-10"
            placeholder=""
            autocomplete="off"
          />
        </div>
        <button
          type="submit"
          class="btn-primary shrink-0 justify-center"
          :disabled="!identificador.trim() || kardexStore.cargando"
        >
          <AppIcon v-if="kardexStore.cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="search" :size="18" />
          <span class="uppercase tracking-wide">Buscar</span>
        </button>
      </div>
    </form>

    <!-- Error -->
    <div
      v-if="kardexStore.errorMessage && !kardexStore.postulante"
      class="flex items-start gap-3 rounded-xl bg-red-900/50 border border-red-500/30 px-4 py-3 text-sm text-red-400"
    >
      <AppIcon name="alert-triangle" :size="18" class="shrink-0 mt-0.5" />
      {{ kardexStore.errorMessage }}
    </div>

    <!-- Estado vacío inicial -->
    <EmptyState
      v-if="!kardexStore.postulante && !kardexStore.errorMessage"
      icon="search"
      title="Consultar flujo del postulante al examen de grado"
      message="Filtre por Cédula de Idenitdad  o Registro Universitario para habilitar al estudiante y registrar sus sorteos."
    />

    <template v-if="kardexStore.postulante">
      <!-- Resumen del postulante -->
      <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="w-14 h-14 shrink-0 rounded-2xl bg-orange text-white font-bold text-lg flex items-center justify-center shadow-lg">
            {{ iniciales(kardexStore.postulante) }}
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-white truncate">
              {{ kardexStore.postulante.nombres }} {{ kardexStore.postulante.apellidos }}
            </h3>
            <div class="flex flex-wrap gap-2 mt-1.5">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/5 border border-white/10 text-slate-300 rounded-full px-2.5 py-1">
                <AppIcon name="user" :size="12" /> CI: {{ kardexStore.postulante.ci }}
              </span>
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/5 border border-white/10 text-slate-300 rounded-full px-2.5 py-1">
                <AppIcon name="file-text" :size="12" /> R.U.: {{ kardexStore.postulante.registro_universitario }}
              </span>
            </div>
          </div>
          <button class="btn-ghost shrink-0" @click="reiniciar">
            <AppIcon name="x" :size="16" />
            Limpiar
          </button>
        </div>
      </div>

      <!-- Sin trámite asignado -->
      <EmptyState
        v-if="!kardexStore.tramite"
        icon="inbox"
        title="El postulante aún no tiene un trámite asignado"
        message="Regístrelo en el módulo de Registro de Postulante para asignarle la modalidad de Examen de Grado."
      >
        <router-link :to="{ name: 'KardexRegistroPostulante' }" class="btn-warm">
          <AppIcon name="user-plus" :size="16" />
          Ir a Registro de Postulante
        </router-link>
      </EmptyState>

      <!-- Aviso cuando la modalidad asignada no es Examen de Grado -->
      <div
        v-else-if="!esExamen"
        class="flex items-start gap-3 rounded-xl bg-orange-500/15 border border-orange-500/30 px-4 py-3 text-sm text-orange-200"
      >
        <AppIcon name="info" :size="18" class="shrink-0 mt-0.5" />
        El postulante tiene asignada la modalidad
        <strong>{{ kardexStore.tramite.modalidad?.nombre }}</strong>. Este módulo gestiona el flujo del
        Examen de Grado; use el módulo correspondiente a su modalidad.
      </div>

      <FlujoExamen v-else :tramite="kardexStore.tramite" />
    </template>
  </div>
</template>

<script setup>
// Módulo del Dashboard de KARDEX: Examen de Grado.
//
// A diferencia de la Tesis, aquí no hay perfil, tutor ni tribunal. El flujo son
// ocho tarjetas secuenciales (intentos, habilitación, dos sorteos, Resolución
// de aprobación del resultado teórico, Resolución de asignación de notas,
// asignación de notas y reporte) y el estudiante puede reabrir el trámite
// cuando quedó sin aprobar alguno de los dos exámenes, consumiendo un intento
// (de 3). No asistir al sorteo de bolos de un examen lo reprueba
// automáticamente, pero tras el teórico el estudiante puede registrar la
// Resolución de aprobación y pasar al sorteo del examen de especialidad; las
// notas de ambos se asignan después y solo se titula aprobándolas con 51 o más.
//
// Reprobar UN SOLO examen reabre únicamente ese sorteo y conserva la nota del
// examen aprobado (junto con la Resolución del HCC en curso); reprobar AMBOS
// exige una nueva Resolución y volver a rendir los dos exámenes.
//
// El avance de las tarjetas se deriva del historial especializado del examen,
// no del estado actual, porque una reapertura devuelve el trámite atrás.
import { computed, ref } from 'vue';
import { useKardexStore } from '@/modules/kardex';
import { iniciales } from '@/modules/kardex/utils/formato';
import { esFlujoExamen } from '@/modules/examen/utils/flujoExamen';
import AppIcon from '@/ui/AppIcon.vue';
import EmptyState from '@/ui/EmptyState.vue';
import FlujoExamen from '../components/flujoExamen/FlujoExamen.vue';

const kardexStore = useKardexStore();

const identificador = ref('');

// El flujo solo aplica a trámites de la modalidad Examen de Grado.
const esExamen = computed(() => esFlujoExamen(kardexStore.tramite));

/** Filtra el flujo del postulante por CI/RU. */
const buscar = async () => {
  if (!identificador.value.trim() || kardexStore.cargando) return;
  await kardexStore.consultar(identificador.value.trim());
};

/** Limpia la consulta para empezar de nuevo. */
const reiniciar = () => {
  kardexStore.limpiar();
  identificador.value = '';
};
</script>
