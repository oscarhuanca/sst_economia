<template>
  <div class="space-y-5">
    <!-- Resumen del trámite + barra de progreso -->
    <div class="card p-5">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-full bg-orange-500/15 ring-1 ring-orange-500/40 flex items-center justify-center text-orange-300 font-extrabold"
          >
            {{ iniciales(estudiante) }}
          </div>
          <div>
            <h3 class="font-extrabold text-white">
              {{ estudiante.nombres }} {{ estudiante.apellidos }}
            </h3>
            <p class="text-xs text-slate-400">
              CI {{ estudiante.ci }} · {{ tramite?.modalidad?.nombre }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <EstadoBadge :estado="tramite?.estado_actual" />
        </div>
      </div>

      <div class="mt-6">
        <BarraProgresoTitulacion
          :progreso="progreso"
          :modulos="modulos"
          :estado-actual="tramite?.estado_actual"
        />
      </div>

      <div class="mt-5 grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos utilizados</dt>
          <dd class="text-2xl font-extrabold text-white mt-0.5">
            {{ usados }}
            <span class="text-xs font-semibold text-slate-400">de {{ maximoIntentos }}</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos restantes</dt>
          <dd class="text-2xl font-extrabold mt-0.5" :class="restantes > 0 ? 'text-emerald-300' : 'text-red-400'">
            {{ restantes }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la solicitud</dt>
          <dd class="text-sm font-semibold text-white mt-1">{{ fechaInicio || '—' }}</dd>
        </div>
      </div>
    </div>

    <!-- Hito inicial: la postulación del estudiante -->
    <div class="card p-5 border border-emerald-500/30">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
            <AppIcon name="inbox" :size="18" />
          </div>
          <div>
            <h4 class="font-bold text-white uppercase">Solicitud Presentada</h4>
            <p class="text-xs text-slate-400">Punto de partida del examen de grado</p>
          </div>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border bg-emerald-500/20 text-emerald-300 border-emerald-500/30"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          Completado
        </span>
      </div>
      <p v-if="fechaInicio" class="mt-3 text-xs text-slate-400">
        Trámite presentado el <span class="font-semibold text-slate-300">{{ fechaInicio }}</span>
      </p>
    </div>

    <!-- Reapertura: el estudiante reprobó y puede volver a rendir el examen -->
    <div v-if="reabrible" class="card p-5 border border-amber-500/40">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-300">
            <AppIcon name="alert-triangle" :size="18" />
          </div>
          <div>
            <h4 class="font-bold text-white uppercase">EL ESTUDIANTE DEBE SOLICITAR OTRA MODALIDAD DE TITULACION</h4>
            <p class="text-xs text-slate-400 mt-1 max-w-xl leading-relaxed">
              {{ motivoReapertura }} Al reabrir, el trámite vuelve a
              <span class="font-semibold text-slate-300">{{ reapertura.destino }}</span>,
              {{ reapertura.texto }}, y se consume un intento
              <span class="font-semibold text-slate-300">(quedan {{ restantes }} de {{ maximoIntentos }})</span>.
              <span v-if="restantes === 0" class="block mt-1 text-red-300">
                Ya no quedan intentos: para continuar debe registrarse una nueva postulación.
              </span>
            </p>
          </div>
        </div>
        <button
          type="button"
          class="btn-warm px-5"
          :disabled="cargando || restantes === 0"
          @click="reabrir"
        >
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="pencil" :size="18" />
          <span class="uppercase tracking-wide">Reabrir examen</span>
        </button>
      </div>
    </div>

    <!-- 8 módulos del flujo, cada uno bloqueado hasta cerrar el anterior -->
    <div class="grid lg:grid-cols-2 gap-4">
      <template v-for="(m, i) in modulos" :key="m.id">
        <ModuloIntentosExamen
          v-if="m.id === 'verificacion_intentos'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :intentos="usados"
          :maximo="maximoIntentos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloHabilitacionExamen
          v-else-if="m.id === 'habilitacion_examen'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloResolucionResultado
          v-else-if="m.id === 'resolucion_resultado_teorico'"
          :numero="i + 1"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloSorteoExamen
          v-else-if="m.id === 'sorteo_teorico' || m.id === 'sorteo_especialidad'"
          :numero="i + 1"
          :titulo="m.label"
          :descripcion="descripcionDe(m)"
          :icono="m.icon"
          :modulo-id="m.id"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloResolucionAsignacionNotas
          v-else-if="m.id === 'resolucion_asignacion_notas'"
          :numero="i + 1"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloAsignacionNotas
          v-else-if="m.id === 'asignacion_notas'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          :sorteo-teorico="registroDe('sorteo_teorico')"
          :sorteo-especialidad="registroDe('sorteo_especialidad')"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloReporteExamen
          v-else
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :tramite="tramite"
          @guardar="(datos) => guardar(m.id, datos)"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useKardexStore } from '../../stores/kardex';
import {
  MAXIMO_INTENTOS,
  MODULOS_FLUJO_EXAMEN,
  esReabrible,
  intentosRestantes,
  intentosUsados,
  modulosExamen,
  pctExamen,
  proximoModuloPendiente,
  validarModulo,
} from '@/modules/examen/utils/flujoExamen';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import BarraProgresoTitulacion from '../flujo/BarraProgresoTitulacion.vue';
import ModuloIntentosExamen from './ModuloIntentosExamen.vue';
import ModuloHabilitacionExamen from './ModuloHabilitacionExamen.vue';
import ModuloResolucionResultado from './ModuloResolucionResultado.vue';
import ModuloSorteoExamen from './ModuloSorteoExamen.vue';
import ModuloResolucionAsignacionNotas from './ModuloResolucionAsignacionNotas.vue';
import ModuloAsignacionNotas from './ModuloAsignacionNotas.vue';
import ModuloReporteExamen from './ModuloReporteExamen.vue';

const props = defineProps({
  tramite: { type: Object, default: null },
});

const emit = defineEmits(['guardado']);

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const guardando = ref(false);

const estudiante = computed(() => props.tramite?.estudiante || {});
const progreso = computed(() => pctExamen(props.tramite));
const proximoPendiente = computed(() => proximoModuloPendiente(props.tramite));
const reabrible = computed(() => esReabrible(props.tramite));
const usados = computed(() => intentosUsados(props.tramite));
const restantes = computed(() => intentosRestantes(props.tramite));
const maximoIntentos = MAXIMO_INTENTOS;

const modulos = computed(() =>
  modulosExamen(props.tramite).map((m) => ({
    ...m,
    datos: props.tramite?.hitos?.modulos?.[m.id] || null,
  })),
);

const fechaInicio = computed(() =>
  fechaLegible(props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at),
);

/**
 * Texto que explica por qué el trámite quedó reabrible.
 *
 * Según el estado en el que quedó la asignación de notas, el estudiante debe
 * volver a rendir solo el examen que no aprobó (conservando la nota del otro y
 * la Resolución del HCC en curso) o ambos exámenes con una nueva Resolución.
 */
const motivoReapertura = computed(() => {
  switch (props.tramite?.estado_actual) {
    case 'examen_teorico_reprobado':
      return 'Reprobó el examen teórico; la nota de especialidad se conserva.';
    case 'examen_especialidad_reprobado':
      return 'Reprobó el examen de especialidad; la nota del teórico se conserva.';
    case 'examen_ambos_reprobados':
      return 'Ninguno de los dos exámenes fue aprobado.';
    default:
      return '';
  }
});

/**
 * Detalle de la reapertura según el alcance.
 *
 * Reprobar un SOLO examen vuelve al punto en que se reabre únicamente ese
 * sorteo; reprobar AMBOS vuelve a la verificación de intentos y exige una nueva
 * Resolución del HCC antes de los sorteos.
 */
const reapertura = computed(() => {
  switch (props.tramite?.estado_actual) {
    case 'examen_teorico_reprobado':
      return {
        destino: 'Habilitado',
        texto: 'se reabre únicamente el examen teórico, conservando la nota de especialidad y la Resolución del HCC en curso; deberá registrarse de nuevo la Resolución de aprobación del resultado teórico',
        observacion: 'El estudiante solicitó volver a rendir únicamente el examen teórico; la especialidad aprobada se conserva.',
        toast: 'Examen reabierto: el estudiante vuelve a sortear el examen teórico.',
      };
    case 'examen_especialidad_reprobado':
      return {
        destino: 'Teórico aprobado',
        texto: 'se reabre únicamente el examen de especialidad, conservando la nota del teórico, su Resolución de aprobación y la Resolución del HCC en curso',
        observacion: 'El estudiante solicitó volver a rendir únicamente el examen de especialidad; el teórico aprobado se conserva.',
        toast: 'Examen reabierto: el estudiante vuelve a sortear el examen de especialidad.',
      };
    default:
      return {
        destino: 'Verificación de Intentos',
        texto: 'se reinician los dos sorteos, la Resolución de aprobación del resultado teórico, la Resolución de asignación de notas y la asignación de notas, y se exigirá una NUEVA Resolución del HCC para habilitar de nuevo al estudiante',
        observacion: 'El estudiante solicitó volver a rendir ambos exámenes con una nueva Resolución del HCC.',
        toast: 'Examen reabierto: registre una nueva Resolución del HCC para habilitar de nuevo al estudiante.',
      };
  }
});

/** Último registro del historial de una etapa del flujo. */
function registroDe(etapa) {
  return modulos.value.find((m) => m.id === etapa)?.registro || null;
}

function asistioA(registro) {
  return ['asistio', 'aprobado', 'reprobado'].includes(registro?.resultado);
}

/** ¿El registro es un sorteo de un examen ya aprobado que se conserva? */
function esConservado(etapa) {
  return registroDe(etapa)?.conservado === true;
}

/**
 * Un módulo sólo se habilita cuando es el siguiente pendiente, salvo el Reporte
 * en un estado reprobado: ahí ninguna tarjeta avanza (primero se reabre el
 * examen), pero SÍ se emite el acta de resultados de la resolución en curso.
 */
function bloqueado(indice) {
  if (reabrible.value) return modulos.value[indice]?.id !== 'reporte';
  return indice !== proximoPendiente.value;
}

function descripcionDe(m) {
  return m.id === 'sorteo_teorico'
    ? 'Sorteo de bolo y fecha del examen teórico. No asistir reprueba el examen, pero el estudiante puede seguir al sorteo de especialidad; la nota se registra en Asignación de Notas.'
    : 'Sorteo de bolo y fecha del examen de especialidad. Requiere la Resolución de aprobación del resultado del teórico. No asistir reprueba el examen; la nota se registra en Asignación de Notas.';
}

async function guardar(moduloId, datos) {
  const modulo = MODULOS_FLUJO_EXAMEN.find((m) => m.id === moduloId);
  const contexto = moduloId === 'verificacion_intentos'
    ? { intentos: usados.value, maximo: maximoIntentos }
    : moduloId === 'asignacion_notas'
      ? {
          // Un examen conservado ya está aprobado: el validador no pide su nota.
          asistio_teorico: esConservado('sorteo_teorico') ? 'conservado' : asistioA(registroDe('sorteo_teorico')),
          asistio_especialidad: esConservado('sorteo_especialidad') ? 'conservado' : asistioA(registroDe('sorteo_especialidad')),
        }
      : {};

  const res = validarModulo(moduloId, datos, contexto);

  if (!res.ok) {
    toastStore.error(res.errores.join(' · '));
    return;
  }

  guardando.value = true;
  const actualizado = await kardexStore.guardarModuloExamen(moduloId, datos);
  guardando.value = false;

  if (!actualizado) {
    toastStore.error(kardexStore.errorMessage || 'No se pudo guardar el módulo.');
    return;
  }

  if (moduloId === 'asignacion_notas' && esReabrible(actualizado)) {
    toastStore.warning('Notas asignadas: al menos un examen quedó REPROBADO.');
  } else {
    toastStore.success(`Módulo completado — estado: ${modulo.badge}`);
  }
  emit('guardado', actualizado);
}

async function reabrir() {
  guardando.value = true;
  const actualizado = await kardexStore.reabrirExamen(reapertura.value.observacion);
  guardando.value = false;

  if (!actualizado) {
    toastStore.error(kardexStore.errorMessage || 'No se pudo reabrir el examen.');
    return;
  }

  toastStore.success(reapertura.value.toast);
  emit('guardado', actualizado);
}
</script>
