<template>
  <ModuloTarjeta
    :numero="numero"
    :titulo="titulo"
    :descripcion="descripcion"
    :icono="icono"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Sorteo ya registrado: el historial guarda asistencia, tema y fecha. -->
    <div v-if="completado" class="space-y-3">
      <div
        class="flex items-center gap-3 rounded-xl border px-4 py-3"
        :class="presente ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-red-500/30 bg-red-900/30'"
      >
        <AppIcon
          :name="presente ? 'check-circle' : 'x-circle'"
          :size="18"
          :class="presente ? 'text-emerald-300' : 'text-red-400'"
        />
        <p class="text-sm font-semibold flex-1" :class="presente ? 'text-emerald-200' : 'text-red-200'">
          {{ conservado
            ? 'Examen APROBADO en el intento anterior — nota conservada'
            : (presente ? 'Asistencia y sorteo registrados' : 'NO ASISTIÓ') }}
        </p>
        <span
          v-if="conservado"
          class="text-[10px] font-bold uppercase tracking-wide bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full px-2.5 py-1"
        >
          Conservado
        </span>
      </div>

      <dl v-if="presente" class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 sm:col-span-2">
          <dt class="dato-label">Tema sorteado</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ resumen?.tema || '—' }}</dd>
        </div>
        <div
          v-if="resumen?.nota !== null && resumen?.nota !== undefined"
          class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5"
        >
          <dt class="dato-label text-emerald-300">Nota registrada</dt>
          <dd class="text-2xl font-extrabold text-emerald-300 mt-0.5">
            {{ resumen?.nota }}
            <span class="text-xs font-semibold opacity-70">/ 100</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha del examen</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(resumen?.fecha_examen || '') || '—' }}</dd>
        </div>
      </dl>

      <p v-if="presente && !conservado" class="dato-ayuda">
        La nota de este examen se registra en el módulo
        <span class="font-semibold text-slate-300">Asignación de Notas</span>.
      </p>
      <p v-else-if="presente" class="dato-ayuda">
        Este examen ya fue aprobado: su nota no vuelve a sortearse ni a asignarse.
      </p>
      <p v-else class="dato-ayuda text-amber-300">
        La ausencia deja este examen REPROBADO sin nota; se confirmará al asignar las notas.
      </p>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      {{ mensajeBloqueo }}
    </div>

    <!-- El sorteo registra asistencia, tema y fecha en un solo paso. -->
    <form v-else class="space-y-5" @submit.prevent="guardar">
      <fieldset>
        <legend class="label">¿El estudiante asistió al sorteo de bolos del {{ etiqueta }}?</legend>
        <div class="grid sm:grid-cols-2 gap-3">
          <button
            type="button"
            class="opcion"
            :class="asistio === true ? 'opcion-activa-verde' : 'opcion-inactiva'"
            @click="asistio = true"
          >
            <AppIcon name="check-circle" :size="18" />
            <span class="min-w-0">
              <span class="block font-bold uppercase tracking-wide">Sí asistió</span>
              <span class="block text-xs text-slate-400">Se registra tema y fecha del examen</span>
            </span>
          </button>
          <button
            type="button"
            class="opcion"
            :class="asistio === false ? 'opcion-activa-roja' : 'opcion-inactiva'"
            @click="asistio = false"
          >
            <AppIcon name="x-circle" :size="18" />
            <span class="min-w-0">
              <span class="block font-bold uppercase tracking-wide">No asistió</span>
              <span class="block text-xs text-slate-400">Queda reprobado automáticamente</span>
            </span>
          </button>
        </div>
      </fieldset>

      <div v-if="asistio === true" class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="label" :for="`${moduloId}-tema`">Tema sorteado</label>
          <input
            :id="`${moduloId}-tema`"
            v-model="form.tema"
            type="text"
            class="input"
            placeholder="Ej: Normativa de gestión administrativa del Estado"
          />
        </div>
        <div>
          <label class="label" :for="`${moduloId}-fecha`">Fecha del examen</label>
          <input :id="`${moduloId}-fecha`" v-model="form.fecha_examen" type="date" class="input" />
        </div>
      </div>

      <!-- La ausencia reproba el examen automáticamente: se pide
           confirmación explícita para que no sea un clic accidental. -->
      <label
        v-if="asistio === false"
        class="flex items-start gap-2.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 py-3 cursor-pointer"
      >
        <input
          v-model="confirmoAusencia"
          type="checkbox"
          class="mt-0.5 h-4 w-4 accent-amber-500"
        />
        <span class="text-xs text-amber-200 leading-relaxed">
          Confirmo que el estudiante <strong>NO ASISTIÓ</strong> al sorteo de bolos del
          {{ etiqueta }} y que queda <strong>REPROBADO</strong> en este examen
          <template v-if="moduloId === 'sorteo_teorico'">
            (podrá continuar con la Resolución de aprobación del resultado y el sorteo del examen de especialidad).
          </template>
        </span>
      </label>

      <ul v-if="errores.length" class="space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <div class="flex justify-end">
        <button
          type="submit"
          class="btn-warm px-6"
          :disabled="cargando || asistio === null || (asistio === false && !confirmoAusencia)"
        >
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="calendar" :size="18" />
          <span class="uppercase tracking-wide">
            {{ asistio === false ? 'Registrar ausencia' : 'Guardar sorteo' }}
          </span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { ETIQUETAS_SORTEOS, validarModulo } from '@/modules/examen/utils/flujoExamen';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

/**
 * Tarjeta de sorteo de bolo, usada tanto para el examen teórico (módulo 3) como
 * para el de especialidad (módulo 5): registran asistencia, tema y fecha. La
 * nota de los dos exámenes se asigna después, en el módulo 7.
 */
const props = defineProps({
  numero: { type: [Number, String], required: true },
  titulo: { type: String, required: true },
  descripcion: { type: String, default: '' },
  icono: { type: String, default: 'calendar' },
  moduloId: { type: String, required: true },
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  cargando: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  registro: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const etiqueta = computed(() => ETIQUETAS_SORTEOS[props.moduloId] || 'examen');

const form = reactive({
  tema: props.datos?.tema || props.registro?.tema || '',
  fecha_examen: props.datos?.fecha_examen || props.registro?.fecha_examen || '',
});

const asistio = ref(null);
const errores = ref([]);
const confirmoAusencia = ref(false);

const resumen = computed(() => props.registro || props.datos);
const presente = computed(() => resumen.value?.resultado !== 'no_asistio');
/** Sorteo de un examen ya aprobado que se conserva entre los dos intentos. */
const conservado = computed(() => props.registro?.conservado === true);
const mensajeBloqueo = computed(
  () => (props.moduloId === 'sorteo_especialidad'
    ? 'Registre la Resolución de aprobación del resultado del examen teórico para sortear el examen de especialidad.'
    : `Habilite al estudiante para sortear el ${etiqueta.value}.`),
);

function guardar() {
  const datos = {
    asistio: asistio.value,
    tema: form.tema.trim(),
    fecha_examen: form.fecha_examen,
  };

  const res = validarModulo(props.moduloId, datos);

  if (!res.ok) {
    errores.value = res.errores;
    return;
  }

  // La ausencia reproba el examen automáticamente: también se confirma.
  if (asistio.value === false && !confirmoAusencia.value) return;

  errores.value = [];
  emit('guardar', datos);
}
</script>
