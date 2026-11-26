<template>
  <ModuloTarjeta
    numero="4"
    titulo="Aprobación y Defensa"
    descripcion="Registre la defensa en tres pasos: Resolución HCC de aprobación, empastados y CD, y fecha y defensa."
    icono="award"
    :completado="completado && !editando"
    :en-progreso="enProgreso || editando"
    :bloqueado="bloqueado && !editando"
  >
    <div v-if="completado && datos && !editando" class="space-y-3">
        <dl class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Resolución HCC de aprobación</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.numero_resolucion }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de la resolución</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_resolucion) }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Empastados y CD</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">
              {{ datos.empastados_cd_entregados ? 'Entregados' : 'Sin confirmar' }}
            </dd>
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

        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">PDF de la Resolución HCC</p>
            <p
              class="text-sm font-semibold mt-0.5 truncate"
              :class="datos.resolucion_defensa?.nombre ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ datos.resolucion_defensa?.nombre || 'Sin PDF adjunto' }}
            </p>
          </div>
          <a
            v-if="datos.resolucion_defensa?.ruta"
            :href="assetUrl(datos.resolucion_defensa.ruta)"
            target="_blank"
            rel="noopener"
            class="btn-ghost !py-1.5 text-xs shrink-0"
          >
            <AppIcon name="link" :size="13" />
            Ver PDF
          </a>
        </div>

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

    <div v-else-if="bloqueado && !editando">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el paso anterior para registrar la defensa.
      </div>
    </div>

    <!-- La defensa se registra en TRES pasos consecutivos. -->
    <form v-else @submit.prevent="guardarPasoActual" class="space-y-5">
      <p v-if="editando" class="text-xs font-semibold text-orange-300 flex items-center gap-1.5">
        <AppIcon name="pencil" :size="14" />
        Editando información y documentos ya registrados.
      </p>
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
            <AppIcon v-if="paso1Completado" name="check" :size="14" />
            <template v-else>1</template>
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Resolución HCC de aprobación</span>
            <span class="block text-xs" :class="paso1Completado ? 'text-emerald-300/80' : 'text-slate-400'">
              {{ paso1Completado ? 'Resolución registrada' : 'Registre el número de Resolución del HCC' }}
            </span>
          </span>
        </button>

        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso2"
          @click="irPaso(2)"
          :disabled="!puedePaso2"
        >
          <span
            class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold"
            :class="dotPaso2"
          >
            <AppIcon v-if="empastadosEntregados" name="check" :size="14" />
            <template v-else>2</template>
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
          :class="chipPaso3"
          @click="irPaso(3)"
          :disabled="!puedePaso3"
        >
          <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold" :class="dotPaso3">
            3
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Fecha y defensa</span>
            <span class="block text-xs text-slate-400">
              {{ puedePaso3 ? 'Registre la fecha y la nota final de la defensa' : 'Se habilita tras los pasos 1 y 2' }}
            </span>
          </span>
        </button>
      </div>

      <!-- Paso 1: Resolución HCC de aprobación -->
      <div v-if="paso === '1'" class="space-y-4">
        <div class="rounded-xl bg-white/5 border border-white/10 p-3.5 space-y-3">
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Aprobación del HCC</p>
            <p class="text-sm text-slate-300 mt-0.5">
              Registre el número y la fecha de la Resolución del HCC que aprueba la defensa, y adjunte el PDF.
            </p>
          </div>

          <div class="grid sm:grid-cols-2 gap-4 items-start">
            <div class="min-w-0 flex flex-col">
              <label class="label sm:min-h-[2.5rem]" for="defensa-resolucion">Número de Resolución HCC de aprobación</label>
              <input
                id="defensa-resolucion"
                v-model="form.numero_resolucion"
                type="text"
                class="input"
                placeholder="Ej: HCC-0250/2026"
                @input="normalizarNumeroResolucion"
                @blur="normalizarNumeroResolucion"
              />
            </div>
            <div class="min-w-0 flex flex-col">
              <label class="label sm:min-h-[2.5rem]" for="defensa-resolucion-fecha">Fecha de la resolución</label>
              <input
                id="defensa-resolucion-fecha"
                v-model="form.fecha_resolucion"
                type="date"
                class="input"
                :max="hoyISO"
              />
            </div>
            <div class="sm:col-span-2 min-w-0">
              <label class="label" for="defensa-resolucion-pdf">
                Resolución HCC de aprobación
                <span class="text-slate-500 font-normal normal-case">(PDF opcional)</span>
              </label>
              <label
                :class="[
                  'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
                  archivoResolucion ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
                ]"
              >
                <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoResolucion ? 'text-orange-200' : 'text-slate-400'">
                  <AppIcon
                    :name="archivoResolucion ? 'check-circle' : 'file-text'"
                    :size="18"
                    :class="archivoResolucion ? 'text-emerald-400 shrink-0' : 'shrink-0'"
                  />
                  <span class="truncate">{{ archivoResolucion?.name || 'Seleccione el PDF de la resolución...' }}</span>
                </span>
                <input
                  id="defensa-resolucion-pdf"
                  type="file"
                  accept=".pdf,application/pdf"
                  class="hidden"
                  @change="handleArchivoResolucion"
                />
                <span class="btn-ghost !py-2 pointer-events-none">
                  <AppIcon name="plus" :size="15" />
                  Subir
                </span>
              </label>
              <p v-if="datos?.resolucion_defensa?.nombre" class="dato-ayuda mt-1.5">
                PDF actual: {{ datos.resolucion_defensa.nombre }}. Si sube otro, se reemplazará.
              </p>
            </div>
          </div>
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
            <span class="uppercase tracking-wide">Continuar a empastados y CD</span>
          </button>
        </div>
      </div>

      <!-- Paso 2: entrega de los 8 empastados y el CD -->
      <div v-else-if="paso === '2'" class="space-y-4">
        <div class="rounded-xl bg-white/5 border border-white/10 p-3.5 space-y-3">
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Requisito de entrega</p>
            <p class="text-sm text-slate-300 mt-0.5">
              Confirme que el estudiante presentó los 8 empastados y el CD de su tesis de grado.
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
          <button type="submit" class="btn-warm px-6" :disabled="cargando">
            <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="check" :size="18" />
            <span class="uppercase tracking-wide">Guardar empastados y CD</span>
          </button>
        </div>
      </div>

      <!-- Paso 3: fecha de la defensa y nota final -->
      <div v-else class="space-y-4">
        <dl class="grid sm:grid-cols-2 gap-2.5">
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Resolución HCC de aprobación</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ numeroResolucion }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Empastados y CD</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">
              {{ empastadosEntregados ? 'Entregados' : 'Sin confirmar' }}
            </dd>
          </div>
        </dl>

        <div class="space-y-4">
          <div class="min-w-0">
            <label class="label" for="defensa-fecha">Fecha de Defensa</label>
            <input id="defensa-fecha" v-model="form.fecha_defensa" type="date" class="input" :max="hoyISO" />
          </div>
          <div class="min-w-0">
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
            <AppIcon v-else name="save" :size="18" />
            <span class="uppercase tracking-wide">Guardar y finalizar</span>
          </button>
        </div>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { NOTA_MINIMA_DEFENSA, validarModulo } from '@/modules/tramites/utils/flujoTitulacion';
import { fechaLegible, prefijarNumeroResolucion } from '@/modules/kardex/utils/formato';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  editando: { type: Boolean, default: false },
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
  fecha_resolucion: props.datos?.fecha_resolucion || '',
  fecha_defensa: props.datos?.fecha_defensa || '',
  nota_final: props.datos?.nota_final ?? '',
});

function normalizarNumeroResolucion() {
  form.numero_resolucion = prefijarNumeroResolucion(form.numero_resolucion);
}

const paso = ref('1');
const errores = ref([]);
const advertencias = ref([]);
const confirmoReprobado = ref(false);
const empastadosEntregados = ref(confirmado(props.datos?.empastados_cd_entregados));
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

const archivoResolucion = ref(null);

function esPdf(file) {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

function handleArchivoResolucion(event) {
  const file = event.target.files[0] || null;
  if (file && !esPdf(file)) {
    errores.value = ['La Resolución HCC de aprobación debe ser un archivo PDF.'];
    archivoResolucion.value = null;
    event.target.value = '';
    return;
  }
  archivoResolucion.value = file;
  errores.value = [];
  event.target.value = '';
}

// Los datos de la Resolución HCC se mantienen en el formulario hasta que se guardan.
const numeroResolucion = computed(() => form.numero_resolucion || props.datos?.numero_resolucion || '');
const fechaResolucion = computed(() => form.fecha_resolucion || props.datos?.fecha_resolucion || '');

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

// La Resolución HCC se registra primero; los empastados se confirman después.
const paso1Completado = computed(() => Boolean(numeroResolucion.value) && Boolean(fechaResolucion.value));
const puedePaso2 = computed(() => paso1Completado.value);
const puedePaso3 = computed(() => paso1Completado.value && empastadosEntregados.value);

const DOT_ACTIVO = 'bg-orange text-white';
const DOT_PENDIENTE = 'bg-white/10 text-slate-400';

const chipPaso1 = computed(() =>
  paso1Completado.value
    ? 'border-emerald-500/30 bg-emerald-500/10'
    : paso.value === '1'
      ? 'border-orange-500 bg-orange-500/10'
      : 'border-white/10 bg-white/5',
);

const dotPaso1 = computed(() => {
  if (paso1Completado.value) return 'bg-emerald-500 text-white';
  return paso.value === '1' ? DOT_ACTIVO : DOT_PENDIENTE;
});

const chipPaso2 = computed(() => {
  if (!puedePaso2.value) return 'border-white/10 bg-white/5 opacity-50';
  if (empastadosEntregados.value) return 'border-emerald-500/30 bg-emerald-500/10';
  return paso.value === '2' ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5';
});

const dotPaso2 = computed(() => {
  if (empastadosEntregados.value) return 'bg-emerald-500 text-white';
  return paso.value === '2' ? DOT_ACTIVO : DOT_PENDIENTE;
});

const chipPaso3 = computed(() => {
  if (!puedePaso3.value) return 'border-white/10 bg-white/5 opacity-50';
  return paso.value === '3' ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5';
});

const dotPaso3 = computed(() => (paso.value === '3' ? DOT_ACTIVO : DOT_PENDIENTE));

/** Cambia de paso respetando las confirmaciones previas. */
function irPaso(n) {
  if (n >= 2 && !paso1Completado.value) {
    errores.value = ['Ingrese el número de Resolución HCC de aprobación para continuar.'];
    return;
  }
  if (n >= 3 && !empastadosEntregados.value) {
    errores.value = ['Confirme que el estudiante presentó los 8 empastados y el CD de su tesis de grado para continuar.'];
    return;
  }
  paso.value = String(n);
  errores.value = [];
  advertencias.value = [];
}

watch([numeroResolucion, fechaResolucion], ([resolucion, fecha]) => {
  if ((!resolucion || !fecha) && paso.value !== '1') {
    paso.value = '1';
  }
});

watch(empastadosEntregados, (entregados) => {
  if (!entregados && paso.value === '3') {
    paso.value = '2';
  }
});

watch(
  () => props.editando,
  (activo) => {
    if (activo && props.datos) {
      form.numero_resolucion = props.datos.numero_resolucion || '';
      form.fecha_resolucion = props.datos.fecha_resolucion || '';
      form.fecha_defensa = props.datos.fecha_defensa || '';
      form.nota_final = props.datos.nota_final ?? '';
      empastadosEntregados.value = confirmado(props.datos.empastados_cd_entregados);
      paso.value = '3';
    }
  },
  { immediate: true },
);

function obtenerDatos() {
  const base = {
    numero_resolucion: prefijarNumeroResolucion(numeroResolucion.value),
    fecha_resolucion: fechaResolucion.value,
    empastados_cd_entregados: empastadosEntregados.value,
  };

  if (props.datos?.resolucion_defensa) {
    base.resolucion_defensa = props.datos.resolucion_defensa;
  }

  if (paso.value === '1') {
    return {
      numero_resolucion: base.numero_resolucion,
      fecha_resolucion: base.fecha_resolucion,
      paso: 1,
    };
  }

  if (paso.value === '2') {
    return { ...base, paso: 2 };
  }

  return {
    ...base,
    fecha_defensa: form.fecha_defensa || props.datos?.fecha_defensa || '',
    nota_final: form.nota_final === '' ? '' : Number(form.nota_final),
    paso: 3,
  };
}

function obtenerArchivos() {
  const archivos = {};
  if (archivoResolucion.value) {
    archivos.resolucion_defensa = archivoResolucion.value;
  }
  return archivos;
}

function guardarPasoActual() {
  const datos = obtenerDatos();
  const res = validarModulo('defensa', datos);
  if (!res.ok) {
    errores.value = res.errores;
    advertencias.value = res.advertencias || [];
    return;
  }

  // El paso 1 solo valida la resolución (número y fecha) y habilita la siguiente actividad.
  if (paso.value === '1') {
    errores.value = [];
    paso.value = '2';
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
  emit('guardar', datos, obtenerArchivos());
  if (paso.value === '2') paso.value = '3';
}
</script>