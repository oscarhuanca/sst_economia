<template>
  <ModuloTarjeta
    numero="7"
    titulo="Asignación de Notas"
    descripcion="Registre la nota del examen teórico y del examen de especialidad para cerrar ambos exámenes. Requiere la Resolución de asignación de notas."
    icono="award"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Notas ya asignadas: el historial cierra los dos exámenes. -->
    <div v-if="completado" class="space-y-3">
      <div
        class="flex items-center gap-3 rounded-xl border px-4 py-3"
        :class="aprobado ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-red-500/30 bg-red-900/30'"
      >
        <AppIcon
          :name="aprobado ? 'check-circle' : 'x-circle'"
          :size="18"
          :class="aprobado ? 'text-emerald-300' : 'text-red-400'"
        />
        <p class="text-sm font-semibold" :class="aprobado ? 'text-emerald-200' : 'text-red-200'">
          {{ aprobado ? 'Ambos exámenes APROBADOS' : 'Notas asignadas con al menos un examen REPROBADO' }}
        </p>
      </div>

      <dl class="grid sm:grid-cols-2 gap-2.5">
        <div
          class="rounded-xl border px-3.5 py-2.5"
          :class="teoricoAprobado ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-red-900/30 border-red-500/30'"
        >
          <dt class="dato-label" :class="teoricoAprobado ? 'text-emerald-300' : 'text-red-300'">
            Examen teórico
          </dt>
          <dd class="flex items-baseline justify-between gap-2 mt-0.5">
            <span
              class="text-2xl font-extrabold"
              :class="teoricoAprobado ? 'text-emerald-300' : 'text-red-400'"
            >
              {{ notaMostrada(notaTeorico, asistioTeorico) }}
              <span class="text-xs font-semibold opacity-70">/ 100</span>
            </span>
            <span class="text-[10px] font-bold uppercase tracking-wide" :class="teoricoAprobado ? 'text-emerald-300' : 'text-red-300'">
              {{ etiquetaResultado(asistioTeorico, teoricoAprobado) }}
            </span>
          </dd>
        </div>

        <div
          class="rounded-xl border px-3.5 py-2.5"
          :class="especialidadAprobado ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-red-900/30 border-red-500/30'"
        >
          <dt class="dato-label" :class="especialidadAprobado ? 'text-emerald-300' : 'text-red-300'">
            Examen de especialidad
          </dt>
          <dd class="flex items-baseline justify-between gap-2 mt-0.5">
            <span
              class="text-2xl font-extrabold"
              :class="especialidadAprobado ? 'text-emerald-300' : 'text-red-400'"
            >
              {{ notaMostrada(notaEspecialidad, asistioEspecialidad) }}
              <span class="text-xs font-semibold opacity-70">/ 100</span>
            </span>
            <span class="text-[10px] font-bold uppercase tracking-wide" :class="especialidadAprobado ? 'text-emerald-300' : 'text-red-300'">
              {{ etiquetaResultado(asistioEspecialidad, especialidadAprobado) }}
            </span>
          </dd>
        </div>
      </dl>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      Primero registre los sorteos del examen teórico y del examen de especialidad, la Resolución de aprobación del resultado teórico y la Resolución de asignación de notas.
    </div>

    <!-- Formulario de asignación de notas de los dos exámenes. -->
    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Examen teórico -->
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 space-y-3">
          <p class="text-xs font-bold uppercase tracking-wide text-orange-300">
            Examen teórico
          </p>
          <template v-if="asistioTeorico">
            <!-- El examen ya fue aprobado en un intento anterior: su nota se
                 conserva y no se vuelve a asignar. -->
            <div v-if="conservadoTeorico" class="flex items-center justify-between gap-2">
              <div>
                <dt class="dato-label text-emerald-300">Nota conservada</dt>
                <dd class="text-3xl font-extrabold text-emerald-300 mt-0.5">
                  {{ notaTeorico }} <span class="text-xs font-semibold opacity-70">/ 100</span>
                </dd>
              </div>
              <span class="text-[10px] font-bold uppercase tracking-wide bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full px-2.5 py-1">
                Conservado
              </span>
            </div>
            <template v-else>
              <div>
                <label class="label" for="asignacion-nota-teorico">Nota (0 a 100)</label>
                <input
                  id="asignacion-nota-teorico"
                  v-model="form.nota_teorico"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="input"
                  placeholder="0 - 100"
                />
              </div>
              <p v-if="notaReprobada(form.nota_teorico)" class="text-xs text-amber-300">
                Con {{ form.nota_teorico }} puntos el examen teórico queda reprobado.
              </p>
              <p v-else class="dato-ayuda">Se aprueba con {{ notaMinima }} puntos o más.</p>
            </template>
          </template>
          <p v-else class="text-xs text-slate-400 leading-relaxed">
            El estudiante <strong class="text-red-300">NO ASISTIÓ</strong> al sorteo: el examen
            teórico queda reprobado sin nota.
          </p>
        </div>

        <!-- Examen de especialidad -->
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 space-y-3">
          <p class="text-xs font-bold uppercase tracking-wide text-orange-300">
            Examen de especialidad
          </p>
          <template v-if="asistioEspecialidad">
            <!-- El examen ya fue aprobado en un intento anterior: su nota se
                 conserva y no se vuelve a asignar. -->
            <div v-if="conservadoEspecialidad" class="flex items-center justify-between gap-2">
              <div>
                <dt class="dato-label text-emerald-300">Nota conservada</dt>
                <dd class="text-3xl font-extrabold text-emerald-300 mt-0.5">
                  {{ notaEspecialidad }} <span class="text-xs font-semibold opacity-70">/ 100</span>
                </dd>
              </div>
              <span class="text-[10px] font-bold uppercase tracking-wide bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full px-2.5 py-1">
                Conservado
              </span>
            </div>
            <template v-else>
              <div>
                <label class="label" for="asignacion-nota-especialidad">Nota (0 a 100)</label>
                <input
                  id="asignacion-nota-especialidad"
                  v-model="form.nota_especialidad"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="input"
                  placeholder="0 - 100"
                />
              </div>
              <p v-if="notaReprobada(form.nota_especialidad)" class="text-xs text-amber-300">
                Con {{ form.nota_especialidad }} puntos el examen de especialidad queda reprobado.
              </p>
              <p v-else class="dato-ayuda">Se aprueba con {{ notaMinima }} puntos o más.</p>
            </template>
          </template>
          <p v-else class="text-xs text-slate-400 leading-relaxed">
            El estudiante <strong class="text-red-300">NO ASISTIÓ</strong> al sorteo: el examen
            de especialidad queda reprobado sin nota.
          </p>
        </div>
      </div>

      <ul v-if="errores.length" class="space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <ul v-if="advertencias.length" class="space-y-1">
        <li v-for="(a, i) in advertencias" :key="i" class="flex items-start gap-2 text-sm text-amber-300">
          <AppIcon name="info" :size="15" class="shrink-0 mt-0.5" />
          {{ a }}
        </li>
      </ul>

      <!-- Una nota reprobada es válida, pero deja el trámite reabrible:
           se pide confirmación explícita para evitar el error. -->
      <label
        v-if="hayReprobada"
        class="flex items-start gap-2.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 py-3 cursor-pointer"
      >
        <input
          v-model="confirmoReprobado"
          type="checkbox"
          class="mt-0.5 h-4 w-4 accent-amber-500"
        />
        <span class="text-xs text-amber-200 leading-relaxed">
          Confirmo registrar estas notas, sabiendo que el estudiante queda
          <strong>REPROBADO</strong> y deberá reabrir el examen de grado consumiendo otro intento.
        </span>
      </label>

      <div class="flex justify-end">
        <button
          type="submit"
          class="btn-warm px-6"
          :disabled="cargando || (hayReprobada && !confirmoReprobado)"
        >
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="award" :size="18" />
          <span class="uppercase tracking-wide">Asignar notas</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { NOTA_MINIMA, validarModulo } from '@/modules/examen/utils/flujoExamen';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

/**
 * Módulo 7 - Asignación de Notas.
 *
 * Se completa DESPUÉS de los dos sorteos (módulos 3 y 5), de la Resolución de
 * aprobación del resultado teórico (módulo 4) y de la Resolución de asignación
 * de notas (módulo 6): aquí se registran las notas del examen teórico y del
 * examen de especialidad y se cierran los dos exámenes juntos. Si alguno queda
 * por debajo del mínimo (o no se asistió), el trámite pasa a un estado reabrible
 * y el estudiante puede re-solicitar el examen consumiendo otro intento.
 */
const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  cargando: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  registro: { type: Object, default: null },
  sorteoTeorico: { type: Object, default: null },
  sorteoEspecialidad: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const notaMinima = NOTA_MINIMA;

/** ¿El examen se rindió (asistió al sorteo)? `no_asistio` deja el examen sin nota. */
const asistioTeorico = computed(() => ['asistio', 'aprobado', 'reprobado']
  .includes(props.sorteoTeorico?.resultado));
const asistioEspecialidad = computed(() => ['asistio', 'aprobado', 'reprobado']
  .includes(props.sorteoEspecialidad?.resultado));

/** ¿El examen fue aprobado en un intento anterior y su nota se conserva? */
const conservadoTeorico = computed(() => props.sorteoTeorico?.conservado === true);
const conservadoEspecialidad = computed(() => props.sorteoEspecialidad?.conservado === true);

const form = reactive({
  nota_teorico: props.datos?.nota_teorico
    ?? props.sorteoTeorico?.nota
    ?? '',
  nota_especialidad: props.datos?.nota_especialidad
    ?? props.sorteoEspecialidad?.nota
    ?? '',
});

watch(() => props.datos, (d) => {
  if (d?.nota_teorico !== undefined) form.nota_teorico = d.nota_teorico;
  if (d?.nota_especialidad !== undefined) form.nota_especialidad = d.nota_especialidad;
});

const errores = ref([]);
const advertencias = ref([]);
const confirmoReprobado = ref(false);

const notaTeorico = computed(() => props.registro?.nota_teorico
  ?? props.datos?.nota_teorico
  ?? props.sorteoTeorico?.nota
  ?? null);
const notaEspecialidad = computed(() => props.registro?.nota_especialidad
  ?? props.datos?.nota_especialidad
  ?? props.sorteoEspecialidad?.nota
  ?? null);

const teoricoAprobado = computed(() => examenAprobado(notaTeorico.value, asistioTeorico.value));
const especialidadAprobado = computed(() => examenAprobado(notaEspecialidad.value, asistioEspecialidad.value));
const aprobado = computed(() => teoricoAprobado.value && especialidadAprobado.value);

const hayReprobada = computed(() => notaReprobada(form.nota_teorico)
  || notaReprobada(form.nota_especialidad)
  || !asistioTeorico.value
  || !asistioEspecialidad.value);

function examenAprobado(nota, asistio) {
  if (!asistio) return false;
  const n = Number(nota);
  return !Number.isNaN(n) && n >= NOTA_MINIMA;
}

function notaReprobada(valor) {
  if (valor === '' || valor === null || valor === undefined) return false;
  const n = Number(valor);
  return !Number.isNaN(n) && n < NOTA_MINIMA;
}

function notaMostrada(nota, asistio) {
  if (!asistio) return '—';
  return nota ?? '—';
}

function etiquetaResultado(asistio, aprueba) {
  if (!asistio) return 'No asistió';
  return aprueba ? 'Aprobado' : 'Reprobado';
}

function guardar() {
  const datos = {
    nota_teorico: conservadoTeorico.value ? '' : (asistioTeorico.value && form.nota_teorico !== ''
      ? Number(form.nota_teorico)
      : ''),
    nota_especialidad: conservadoEspecialidad.value ? '' : (asistioEspecialidad.value && form.nota_especialidad !== ''
      ? Number(form.nota_especialidad)
      : ''),
  };

  const res = validarModulo('asignacion_notas', datos, {
    // Un examen conservado ya está aprobado: el validador no pide su nota.
    asistio_teorico: conservadoTeorico.value ? 'conservado' : asistioTeorico.value,
    asistio_especialidad: conservadoEspecialidad.value ? 'conservado' : asistioEspecialidad.value,
  });

  advertencias.value = res.advertencias;

  if (!res.ok) {
    errores.value = res.errores;
    return;
  }

  if (hayReprobada.value && !confirmoReprobado.value) return;

  errores.value = [];
  emit('guardar', datos);
}
</script>
