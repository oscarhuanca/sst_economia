<template>
  <ModuloTarjeta
    numero="4"
    titulo="Defensa"
    descripcion="Registre la defensa en tres pasos: entrega de empastados y CD, Resolución y fecha, y nota final."
    icono="award"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="space-y-3">
      <dl class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Empastados y CD</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ datos.empastados_cd_entregados ? 'Entregados' : 'Sin confirmar' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">N.º Resolución Final</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.numero_resolucion }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de Defensa</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_defensa) }}</dd>
        </div>
        <div
          class="rounded-xl px-3.5 py-2.5"
          :class="reprobada ? 'bg-red-500/10 border border-red-500/30' : 'bg-emerald-500/10 border border-emerald-500/30'"
        >
          <dt
            class="text-[11px] uppercase tracking-wider font-semibold"
            :class="reprobada ? 'text-red-300' : 'text-emerald-300'"
          >
            Nota Final
          </dt>
          <dd
            class="text-2xl font-extrabold mt-0.5"
            :class="reprobada ? 'text-red-300' : 'text-emerald-300'"
          >
            {{ datos.nota_final }}
            <span class="text-xs font-semibold" :class="reprobada ? 'text-red-500' : 'text-emerald-500'">/ 100</span>
          </dd>
        </div>
      </dl>

      <div
        v-if="reprobada"
        class="flex items-start gap-2.5 rounded-xl border border-red-500/40 bg-red-500/10 px-3.5 py-3"
      >
        <AppIcon name="alert-triangle" :size="16" class="shrink-0 mt-0.5 text-red-300" />
        <p class="text-xs text-red-200 leading-relaxed">
          <strong>DEFENSA REPROBADA</strong> — con {{ datos.nota_final }} puntos (mínimo de
          aprobación: {{ notaMinima }}) el flujo de titulación termina aquí. El estudiante
          deberá volver a solicitar la modalidad de graduación una vez cumplidos los plazos
          reglamentarios.
        </p>
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para registrar la defensa.
      </div>
    </div>

    <!-- La defensa se registra en TRES pasos consecutivos. -->
    <form v-else @submit.prevent="guardarPasoActual" class="space-y-5">
      <div class="grid sm:grid-cols-3 gap-3">
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso1"
          @click="irPaso(1)"
        >
          <span
            class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold"
            :class="dotPaso1"
          >
            <AppIcon v-if="empastadosEntregados" name="check" :size="14" />
            <template v-else>1</template>
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Empastados y CD</span>
            <span class="block text-xs" :class="empastadosEntregados ? 'text-emerald-300/80' : 'text-slate-400'">
              {{ empastadosEntregados ? 'Entrega confirmada' : 'Confirme la entrega de los 8 empastados y el CD' }}
            </span>
          </span>
        </button>

        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso2"
          @click="irPaso(2)"
          :disabled="!empastadosEntregados"
        >
          <span
            class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold"
            :class="dotPaso2"
          >
            <AppIcon v-if="paso2Completado" name="check" :size="14" />
            <template v-else>2</template>
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Resolución y fecha de defensa</span>
            <span class="block text-xs" :class="paso2Completado ? 'text-emerald-300/80' : 'text-slate-400'">
              {{ paso2Completado ? 'Completado' : 'Registre la Resolución de aprobación final y la fecha' }}
            </span>
          </span>
        </button>

        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso3"
          @click="irPaso(3)"
          :disabled="!puedePaso3"
        >
          <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold" :class="dotPaso3">
            3
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Nota final de la defensa</span>
            <span class="block text-xs text-slate-400">
              {{ puedePaso3 ? 'Registre la nota de la defensa' : 'Se habilita tras el paso 2' }}
            </span>
          </span>
        </button>
      </div>

      <!-- Paso 1: entrega de los 8 empastados y el CD -->
      <div v-if="paso === '1'" class="space-y-4">
        <div class="rounded-xl bg-white/5 border border-white/10 p-3.5 space-y-3">
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Requisito de entrega</p>
            <p class="text-sm text-slate-300 mt-0.5">
              Antes de registrar la Resolución de Aprobación Final, confirme que el estudiante presentó
              los 8 empastados y el CD de su tesis de grado.
            </p>
          </div>

          <label class="flex items-start gap-2.5 rounded-xl border border-orange-500/30 bg-orange-500/10 px-3.5 py-3 cursor-pointer">
            <input
              v-model="empastadosEntregados"
              type="checkbox"
              class="mt-0.5 h-4 w-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
            />
            <span class="text-xs text-orange-100 leading-relaxed">
              Confirmo que el estudiante presentó sus <strong>8 empastados</strong> y el
              <strong>CD</strong> de su tesis de grado.
            </span>
          </label>
        </div>

        <ul v-if="errores.length" class="space-y-1">
          <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
            <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
            {{ e }}
          </li>
        </ul>

        <div class="flex justify-end">
          <button type="submit" class="btn-warm px-6">
            <AppIcon name="check" :size="18" />
            <span class="uppercase tracking-wide">Continuar a resolución y fecha</span>
          </button>
        </div>
      </div>

      <!-- Paso 2: Resolución de Aprobación Final + fecha de la defensa -->
      <div v-else-if="paso === '2'" class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="defensa-resolucion">Número de Resolución de Aprobación Final</label>
          <input
            id="defensa-resolucion"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: HCC-0315/2026"
          />
        </div>
        <div>
          <label class="label" for="defensa-fecha">Fecha de Defensa</label>
          <input id="defensa-fecha" v-model="form.fecha_defensa" type="date" class="input" :max="hoyISO" />
        </div>

        <ul v-if="errores.length" class="col-span-2 space-y-1">
          <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
            <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
            {{ e }}
          </li>
        </ul>

        <div class="col-span-2 flex justify-end">
          <button type="submit" class="btn-warm px-6" :disabled="cargando">
            <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="calendar" :size="18" />
            <span class="uppercase tracking-wide">Guardar resolución y fecha</span>
          </button>
        </div>
      </div>

      <!-- Paso 3: nota final de la defensa -->
      <div v-else class="space-y-4">
        <dl class="grid sm:grid-cols-2 gap-2.5">
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">N.º Resolución Final</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.numero_resolucion }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de Defensa</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_defensa) }}</dd>
          </div>
        </dl>

        <div>
          <label class="label" for="defensa-nota">Nota Final de Defensa</label>
          <input
            id="defensa-nota"
            v-model="form.nota_final"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="input"
            placeholder="0 - 100"
          />
          <p v-if="notaReprobada(form.nota_final)" class="text-xs text-amber-300">
            Con {{ form.nota_final }} puntos la defensa queda REPROBADO (mínimo de aprobación: {{ notaMinima }}).
          </p>
          <p v-else class="dato-ayuda">Se aprueba con {{ notaMinima }} puntos o más.</p>
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

        <!-- Una nota reprobada es válida, pero deja el trámite en estado
             terminal: se pide confirmación explícita antes de registrarla. -->
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
            Confirmo registrar esta nota, sabiendo que la defensa queda
            <strong>REPROBADA</strong>, el flujo de titulación termina y el estudiante
            deberá volver a solicitar la modalidad de graduación.
          </span>
        </label>

        <div class="flex justify-end">
          <button type="submit" class="btn-warm px-6" :disabled="cargando || (hayReprobada && !confirmoReprobado)">
            <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="award" :size="18" />
            <span class="uppercase tracking-wide">Registrar nota final</span>
          </button>
        </div>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { NOTA_MINIMA_DEFENSA, validarModulo } from '@/modules/tramites/utils/flujoTitulacion';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
});

const emit = defineEmits(['guardar']);

const notaMinima = NOTA_MINIMA_DEFENSA;

function confirmado(valor) {
  return valor === true || valor === 1 || valor === '1';
}

const form = reactive({
  numero_resolucion: props.datos?.numero_resolucion || '',
  fecha_defensa: props.datos?.fecha_defensa || '',
  nota_final: props.datos?.nota_final ?? '',
});

const paso = ref('1');
const errores = ref([]);
const advertencias = ref([]);
const confirmoReprobado = ref(false);
const empastadosEntregados = ref(confirmado(props.datos?.empastados_cd_entregados));
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

/** ¿La nota cargada en el formulario reproba la defensa? */
function notaReprobada(nota) {
  if (nota === '' || nota === null || nota === undefined) return false;
  const n = Number(nota);
  return !Number.isNaN(n) && n < notaMinima;
}

/** El formulario apunta a registrar una defensa reprobada. */
const hayReprobada = computed(() => notaReprobada(form.nota_final));

/** Defensa ya registrada con nota insuficiente (módulo completado). */
const reprobada = computed(() => notaReprobada(props.datos?.nota_final));

// El paso 2 (resolución + fecha) ya fue guardado: se dispone del dato en `hitos`.
const paso2Completado = computed(
  () => Boolean(props.datos?.numero_resolucion) && Boolean(props.datos?.fecha_defensa),
);

const puedePaso3 = computed(() => empastadosEntregados.value && paso2Completado.value);

const DOT_ACTIVO = 'bg-orange text-white';
const DOT_PENDIENTE = 'bg-white/10 text-slate-400';

const chipPaso1 = computed(() =>
  empastadosEntregados.value
    ? 'border-emerald-500/30 bg-emerald-500/10'
    : paso.value === '1'
      ? 'border-orange-500 bg-orange-500/10'
      : 'border-white/10 bg-white/5',
);

const dotPaso1 = computed(() => {
  if (empastadosEntregados.value) return 'bg-emerald-500 text-white';
  return paso.value === '1' ? DOT_ACTIVO : DOT_PENDIENTE;
});

const chipPaso2 = computed(() => {
  if (!empastadosEntregados.value) return 'border-white/10 bg-white/5 opacity-50';
  if (paso2Completado.value) return 'border-emerald-500/30 bg-emerald-500/10';
  return paso.value === '2' ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5';
});

const dotPaso2 = computed(() => {
  if (paso2Completado.value) return 'bg-emerald-500 text-white';
  return paso.value === '2' ? DOT_ACTIVO : DOT_PENDIENTE;
});

const chipPaso3 = computed(() => {
  if (!puedePaso3.value) return 'border-white/10 bg-white/5 opacity-50';
  return paso.value === '3' ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5';
});

const dotPaso3 = computed(() => (paso.value === '3' ? DOT_ACTIVO : DOT_PENDIENTE));

/** Cambia de paso respetando las confirmaciones previas. */
function irPaso(n) {
  if (n >= 2 && !empastadosEntregados.value) {
    errores.value = ['Confirme que el estudiante presentó los 8 empastados y el CD de su tesis de grado para continuar.'];
    return;
  }
  if (n >= 3 && !paso2Completado.value) {
    errores.value = ['Registre la Resolución de Aprobación Final y la fecha de defensa para continuar.'];
    return;
  }
  paso.value = String(n);
  errores.value = [];
  advertencias.value = [];
}

watch(empastadosEntregados, (entregados) => {
  if (!entregados && paso.value !== '1') {
    paso.value = '1';
  }
});

watch(paso2Completado, (completado) => {
  if (!completado && paso.value === '3') {
    paso.value = '2';
  }
});

function obtenerDatos() {
  const base = { empastados_cd_entregados: empastadosEntregados.value };

  if (paso.value === '1') {
    return { ...base, paso: 1 };
  }

  if (!paso2Completado.value) {
    return {
      ...base,
      numero_resolucion: form.numero_resolucion.trim(),
      fecha_defensa: form.fecha_defensa,
      paso: 2,
    };
  }

  return {
    ...base,
    numero_resolucion: props.datos.numero_resolucion,
    fecha_defensa: props.datos.fecha_defensa,
    nota_final: form.nota_final === '' ? '' : Number(form.nota_final),
    paso: 3,
  };
}

function guardarPasoActual() {
  if (paso.value === '1') {
    errores.value = [];
    if (!empastadosEntregados.value) {
      errores.value = ['Confirme que el estudiante presentó los 8 empastados y el CD de su tesis de grado.'];
      return;
    }
    paso.value = '2';
    return;
  }

  const datos = obtenerDatos();
  const res = validarModulo('defensa', datos);
  if (!res.ok) {
    errores.value = res.errores;
    advertencias.value = res.advertencias || [];
    return;
  }
  // Una nota reprobada es válida, pero exige la confirmación explícita.
  if ((res.advertencias || []).length && !confirmoReprobado.value) {
    errores.value = [];
    advertencias.value = res.advertencias;
    return;
  }
  errores.value = [];
  advertencias.value = [];
  confirmoReprobado.value = false;
  emit('guardar', datos);
}
</script>