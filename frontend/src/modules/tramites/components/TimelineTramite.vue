<template>
  <div v-if="tramite">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <div>
        <h1 class="text-xl font-bold text-white">{{ title }}</h1>
        <p class="text-sm text-slate-400">
          Modalidad:
          <span class="font-bold text-orange-300">{{ tramite.modalidad?.nombre }}</span>
        </p>
      </div>
      <span
        class="px-3 py-1.5 rounded-full text-xs font-bold border inline-flex items-center gap-1.5"
        :class="statusGlobal(tramite.estado_actual).tone"
      >
        <AppIcon :name="statusIcon" :size="13" />
        {{ statusGlobal(tramite.estado_actual).label }}
      </span>
    </div>

    <div class="mb-6">
      <div class="flex justify-between text-xs text-slate-400 mb-1.5">
        <span class="font-semibold">Inicio</span>
        <span class="font-bold text-orange-300">{{ progreso }}%</span>
        <span class="font-semibold">Final</span>
      </div>
      <ProgressBar :value="progreso" :bar-class="tone.bar" />
    </div>

    <ol class="relative ml-3 space-y-4">
      <li v-for="paso in pasosConHistoria" :key="paso.id" class="relative pl-9">
        <span
          class="absolute -left-[9px] top-3 w-4.5 h-4.5 rounded-full ring-4 ring-primary border flex items-center justify-center"
          :class="dotClass(paso)"
        >
          <svg v-if="paso.completado" class="w-2 h-2 text-white" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span v-else-if="paso.actual" class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
        </span>

        <div
          class="rounded-xl p-3.5 ring-1 transition"
          :class="cardClass(paso)"
        >
          <div class="flex items-center gap-2">
            <h4 class="font-bold" :class="titleClass(paso)">
              {{ formatoEstado(paso.id) }}
            </h4>
            <span v-if="paso.actual" class="bg-orange-500/90 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">Actual</span>
            <span v-if="paso.completado" class="bg-emerald-500/20 text-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">Completado</span>
          </div>

          <p v-if="paso.historial" class="text-sm text-slate-300 mt-1">
            <span v-if="paso.historial.observaciones">“{{ paso.historial.observaciones }}”</span>
            <span v-else>Estado registrado.</span>
          </p>

          <div v-if="paso.historial" class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-400 mt-2">
            <span v-if="paso.historial.responsable" class="inline-flex items-center gap-1">
              <AppIcon name="user" :size="12" />
              {{ paso.historial.responsable.nombres }} {{ paso.historial.responsable.apellidos }}
              ({{ rolLabel(paso.historial.responsable.rol) }})
            </span>
            <span v-if="paso.historial.created_at" class="inline-flex items-center gap-1">
              <AppIcon name="calendar" :size="12" />
              {{ fecha(paso.historial.created_at) }}
            </span>
          </div>

          <div
            v-if="paso.id === 'tutor_asignado' && asignarTutorEnFlujo && enProceso"
            class="mt-3 pt-3 border-t border-white/10"
          >
            <p v-if="asignacionForzada" class="mb-2 flex items-center gap-2 text-sm font-semibold text-orange-300">
              <AppIcon name="user-plus" :size="16" class="shrink-0" />
              Asigna el tutor en este paso para poder avanzar a “Investigación en Desarrollo”.
            </p>

            <p v-else-if="tramite.tutor" class="flex items-center gap-1.5 text-sm font-semibold text-emerald-300">
              <AppIcon name="user-check" :size="15" />
              {{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}
              <span class="ml-auto text-xs font-medium text-slate-400 normal-case">Cambiar desde el panel “Tutor Asignado”</span>
            </p>

            <button
              v-if="!tramite.tutor && !asignacionForzada"
              class="w-full flex items-center justify-between gap-2 rounded-xl border border-dashed border-white/20 bg-white/5 px-3 py-2.5 text-sm font-semibold text-slate-300 hover:border-orange-500/50 hover:bg-orange-500/10 hover:text-orange-200 transition"
              @click="asignacionAbierta = !asignacionAbierta"
            >
              <span class="inline-flex items-center gap-1.5">
                <AppIcon name="user" :size="15" />
                Asignar tutor en este paso
              </span>
              <AppIcon
                name="chevron-down"
                :size="15"
                class="transition-transform"
                :class="asignacionAbierta ? 'rotate-180' : ''"
              />
            </button>

            <div v-if="!tramite.tutor && (asignacionForzada || asignacionAbierta)" class="mt-2 flex flex-col sm:flex-row gap-2">
              <select v-model="tutorSeleccionado" class="input flex-1 min-w-0 text-sm" :disabled="asignandoTutor">
                <option value="" disabled>Seleccione un docente tutor...</option>
                <option v-for="doc in docentes" :key="doc.id_docente" :value="doc.id_docente">
                  {{ doc.nombres }} {{ doc.apellidos }}
                </option>
              </select>
              <button class="btn-primary w-full sm:w-auto shrink-0" :disabled="asignandoTutor || !tutorSeleccionado" @click="asignarTutor">
                <AppIcon v-if="asignandoTutor" name="loader" :size="14" class="animate-spin" />
                <AppIcon v-else name="check" :size="14" />
                {{ tramite.tutor ? 'Cambiar' : 'Asignar' }}
              </button>
            </div>
          </div>
        </div>
      </li>
    </ol>

    <div v-if="siguientes.length" class="mt-8 rounded-xl bg-slate-800/60 ring-1 ring-white/10 p-4">
      <h4 class="font-bold text-orange-200 mb-2 inline-flex items-center gap-2">
        <AppIcon name="trending-up" :size="16" />
        Próximos pasos en tu trámite:
      </h4>
      <div class="flex flex-wrap gap-2 mt-2">
        <span v-for="siguiente in siguientes" :key="siguiente"
              class="bg-orange-500/10 text-orange-200 text-sm font-semibold px-3 py-1.5 rounded-full ring-1 ring-orange-500/30 inline-flex items-center gap-1">
          <AppIcon name="chevron-right" :size="13" />
          {{ formatoEstado(siguiente) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
// Línea de tiempo de un trámite académico.
// Recibe un trámite completo (con secuencia, estado actual, estados históricos
// y próximos estados) y renderiza el progreso, cada paso de la secuencia con
// su historia (responsable, observaciones, fecha) y los próximos pasos.
import { computed, ref } from 'vue';
import { rolLabel } from '@/core/roles';
import { ESTADOS_TERMINALES, formatoEstado, toneEstado, statusGlobal, progresoEstado } from '../utils/estados';
import AppIcon from '@/ui/AppIcon.vue';
import ProgressBar from '@/ui/ProgressBar.vue';

const props = defineProps({
  tramite: { type: Object, required: true },
  title: { type: String, default: 'Seguimiento de mi Titulación' },
  asignarTutorEnFlujo: { type: Boolean, default: false },
  docentes: { type: Array, default: () => [] },
  asignandoTutor: { type: Boolean, default: false },
});
const emit = defineEmits(['asignar-tutor']);

// Docente elegido en el selector de tutor integrado en el flujo.
const tutorSeleccionado = ref('');

// true cuando el panel de asignación del paso "tutor_asignado" está desplegado.
const asignacionAbierta = ref(false);

// true cuando el trámite se encuentra EN "tutor_asignado" y aún no tiene tutor:
// el selector se muestra abierto de inmediato porque es requisito para avanzar
// a "Investigación en Desarrollo".
const asignacionForzada = computed(() =>
  props.asignarTutorEnFlujo
  && estadoActual.value === 'tutor_asignado'
  && !props.tramite?.tutor
);

// Datos derivados del trámite.
const secuencia = computed(() => props.tramite.secuencia || []);        // Orden de estados posible.
const estadoActual = computed(() => props.tramite.estado_actual);       // Estado en curso.
const siguientes = computed(() => props.tramite.siguientes_estados || []); // Estados alcanzables.
const historicos = computed(() => props.tramite.estados || []);         // Historial de transiciones.
const progreso = computed(() => progresoEstado(props.tramite));         // % de avance.
const tone = computed(() => toneEstado(estadoActual.value));            // Tono para la barra/badge.

// Índice del estado actual dentro de la secuencia (-1 si no está).
const currentIndex = computed(() => secuencia.value.indexOf(estadoActual.value));

// true mientras el trámite no haya concluido (permite asignar tutor).
const enProceso = computed(() => !ESTADOS_TERMINALES.includes(estadoActual.value));

// Icono del badge de estado según el resultado del trámite.
const statusIcon = computed(() => {
  const actual = estadoActual.value;
  if (actual === 'aprobado') return 'award';
  if (['reprobado', 'rechazado'].includes(actual)) return 'x-circle';
  return 'clock';
});

// Construye cada paso de la secuencia con su último registro histórico.
// Marca el paso como 'actual' si coincide con el estado vigente y como
// 'completado' si su índice es anterior al actual.
const pasosConHistoria = computed(() => {
  const pasos = secuencia.value.map((id) => {
    const index = secuencia.value.indexOf(id);
    const historial = historicos.value
      .filter((h) => h.nombre_estado === id)
      .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
      .pop();

    return {
      id,
      index,
      historial,
      actual: id === estadoActual.value,
      completado: currentIndex.value !== -1 && index < currentIndex.value,
    };
  });

  // Modalidades sin el paso "tutor_asignado" en su flujo (no son Tesis): se
  // agrega el paso de forma sintética antes de los estados terminales para que
  // la gestión pueda asignar tutor desde la línea de tiempo.
  if (
    props.asignarTutorEnFlujo
    && !props.tramite.tutor
    && enProceso.value
    && !secuencia.value.includes('tutor_asignado')
  ) {
    const idxTerminal = pasos.findIndex((p) => ESTADOS_TERMINALES.includes(p.id));
    const sintetico = {
      id: 'tutor_asignado',
      index: idxTerminal === -1 ? pasos.length : idxTerminal,
      historial: null,
      actual: false,
      completado: false,
    };
    if (idxTerminal === -1) pasos.push(sintetico);
    else pasos.splice(idxTerminal, 0, sintetico);
  }

  return pasos;
});

// Clases del punto (dot) según el estado del paso.
const dotClass = (paso) => {
  if (paso.actual) return 'bg-white/10 border-2 border-orange-500';
  if (paso.completado) return 'bg-emerald-500 border-emerald-500';
  if (paso.id === 'rechazado') return 'bg-red-500/30 border-red-500';
  return 'bg-white/5 border-white/20';
};

// Clases de la tarjeta según el estado del paso.
const cardClass = (paso) => {
  if (paso.actual) return 'bg-orange-500/10 ring-orange-500/40 shadow-lg';
  if (paso.completado) return 'ring-emerald-500/30 bg-white/5';
  return 'ring-white/10 bg-white/5 opacity-70';
};

// Clases del título según el estado del paso.
const titleClass = (paso) => {
  if (paso.actual) return 'text-orange-200';
  if (paso.completado) return 'text-emerald-300';
  return 'text-slate-400';
};

/** Formatea una fecha ISO en formato corto local (dd mm yyyy, hh:mm). */
const fecha = (fechaISO) => {
  if (!fechaISO) return '';
  return new Date(fechaISO).toLocaleString('es-BO', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
  });
};

/** Emite el docente seleccionado para que la gestión lo asigne como tutor. */
const asignarTutor = () => {
  if (!tutorSeleccionado.value) return;
  emit('asignar-tutor', tutorSeleccionado.value);
  tutorSeleccionado.value = '';
};
</script>