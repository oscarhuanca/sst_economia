<template>
  <ModuloTarjeta
    :numero="numero"
    titulo="Informe de Evaluación del Tribunal"
    descripcion="Seleccione al Tribunal 1 y al Tribunal 2 desde el catálogo de docentes y adjunte el informe de evaluación en PDF."
    icono="clipboard"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado" class="space-y-3">
      <div class="grid sm:grid-cols-2 gap-2.5">
        <div v-for="(miembro, clave) in seleccion" :key="clave" class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">{{ clave === 'tribunal_1' ? 'Tribunal 1' : 'Tribunal 2' }}</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ miembro?.nombre || '—' }}</dd>
        </div>
      </div>

      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
          Resultado de la evaluación
        </p>
        <p class="text-sm font-semibold text-white mt-0.5">
          {{ resultadoSeleccionado?.etiqueta || '—' }}
        </p>
        <p v-if="resultadoSeleccionado" class="text-xs mt-0.5" :class="resultadoTextoTono">
          {{ resultadoSeleccionado.texto }}
        </p>
      </div>

      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
              Informe de evaluación
            </p>
            <p
              class="text-sm mt-0.5 truncate"
              :class="registro?.informe_evaluacion_tribunal?.nombre ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ registro?.informe_evaluacion_tribunal?.nombre || 'Sin PDF adjunto' }}
            </p>
          </div>
          <a
            v-if="registro?.informe_evaluacion_tribunal?.ruta"
            :href="assetUrl(registro.informe_evaluacion_tribunal.ruta)"
            target="_blank"
            rel="noopener"
            class="btn-ghost !py-1.5 text-xs shrink-0"
          >
            <AppIcon name="link" :size="13" />
            Ver PDF
          </a>
        </div>
      </div>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="users" :size="15" />
      Registre la designación del tribunal antes de registrar el informe de evaluación.
    </div>

    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="label" for="excelencia-tribunal-1">Tribunal 1</label>
          <select
            id="excelencia-tribunal-1"
            v-model="seleccionIds.tribunal_1"
            class="input"
            @change="sincronizar(1)"
          >
            <option :value="null">Seleccione un docente</option>
            <option
              v-for="docente in docentes"
              :key="docente.id_docente"
              :value="docente.id_docente"
            >
              {{ nombreDocente(docente) }}
            </option>
          </select>
        </div>
        <div>
          <label class="label" for="excelencia-tribunal-2">Tribunal 2</label>
          <select
            id="excelencia-tribunal-2"
            v-model="seleccionIds.tribunal_2"
            class="input"
            @change="sincronizar(2)"
          >
            <option :value="null">Seleccione un docente</option>
            <option
              v-for="docente in docentes"
              :key="docente.id_docente"
              :value="docente.id_docente"
            >
              {{ nombreDocente(docente) }}
            </option>
          </select>
        </div>
      </div>
      <p v-if="!docentes.length" class="dato-ayuda">No hay docentes disponibles para integrar el tribunal.</p>
      <p v-if="mismoDocenteSeleccionado" class="dato-ayuda text-red-400">
        Un mismo docente no puede ocupar los dos cargos.
      </p>

      <div>
        <label class="label" for="excelencia-resultado-evaluacion">Resultado de la evaluación</label>
        <select
          id="excelencia-resultado-evaluacion"
          v-model="form.resultado_evaluacion"
          class="input"
        >
          <option value="">Seleccione el resultado</option>
          <option v-for="opcion in resultados" :key="opcion.valor" :value="opcion.valor">
            {{ opcion.etiqueta }}
          </option>
        </select>
        <div
          v-if="resultadoSeleccionado"
          class="mt-2 rounded-xl border px-3.5 py-2.5"
          :class="resultadoCajaTono"
        >
          <p class="text-sm" :class="resultadoTextoTono">{{ resultadoSeleccionado.texto }}</p>
        </div>
      </div>

      <div>
        <label class="label" for="excelencia-informe-tribunal">
          Informe de evaluación
          <span class="text-slate-500 font-normal normal-case">(PDF opcional)</span>
        </label>
        <label
          :class="[
            'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
            archivo ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
          ]"
        >
          <span class="flex items-center gap-2 text-sm min-w-0" :class="archivo ? 'text-orange-200' : 'text-slate-400'">
            <AppIcon
              :name="archivo ? 'check-circle' : 'file-text'"
              :size="18"
              :class="archivo ? 'text-emerald-400 shrink-0' : 'shrink-0'"
            />
            <span class="truncate">{{ archivo?.name || 'Seleccione el PDF del informe...' }}</span>
          </span>
          <input
            id="excelencia-informe-tribunal"
            type="file"
            accept=".pdf,application/pdf"
            class="hidden"
            @change="handleArchivo"
          />
          <span class="btn-ghost !py-2 pointer-events-none">
            <AppIcon name="plus" :size="15" />
            Subir
          </span>
        </label>
        <p v-if="registro?.informe_evaluacion_tribunal?.nombre" class="dato-ayuda mt-1.5">
          PDF actual: {{ registro.informe_evaluacion_tribunal.nombre }}. Si sube otro, se reemplazará.
        </p>
      </div>

      <div>
        <label class="label" for="excelencia-informe-observaciones">Observaciones (opcional)</label>
        <textarea
          id="excelencia-informe-observaciones"
          v-model="form.observaciones"
          rows="2"
          class="input resize-none"
          placeholder="Detalle de la evaluación realizada..."
        ></textarea>
      </div>

      <ul v-if="errores.length" class="space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <label class="flex items-start gap-2.5 rounded-xl border border-orange-500/30 bg-orange-500/10 px-3.5 py-3 cursor-pointer">
        <input
          v-model="form.confirmado"
          type="checkbox"
          class="mt-0.5 h-4 w-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
        />
        <span class="text-xs text-orange-100 leading-relaxed">
          Confirmo que verifiqué el Informe de Evaluación, el resultado seleccionado, al Tribunal 1, al Tribunal 2 y el PDF adjunto.
        </span>
      </label>

      <div class="flex justify-end">
        <button type="submit" class="btn-warm px-6" :disabled="cargando">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="clipboard" :size="18" />
          <span class="uppercase tracking-wide">Guardar informe</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import {
  RESULTADOS_EVALUACION,
  nombreDocente,
  resultadoEvaluacion,
  validarModulo,
} from '@/modules/excelencia/utils/flujoExcelencia';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
  numero: { type: [Number, String], required: true },
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  cargando: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
});

const emit = defineEmits(['guardar']);

const form = reactive({
  tribunal_1: props.datos?.tribunal_1 ? { ...props.datos.tribunal_1 } : null,
  tribunal_2: props.datos?.tribunal_2 ? { ...props.datos.tribunal_2 } : null,
  resultado_evaluacion: props.datos?.resultado_evaluacion || '',
  confirmado: false,
  observaciones: props.datos?.observaciones || '',
});

const archivo = ref(null);
const errores = ref([]);

const seleccionIds = reactive({
  tribunal_1: props.datos?.tribunal_1?.id_docente ?? props.datos?.tribunal?.[0]?.id_docente ?? null,
  tribunal_2: props.datos?.tribunal_2?.id_docente ?? props.datos?.tribunal?.[1]?.id_docente ?? null,
});

const registro = computed(() => props.datos);
const seleccion = computed(() => ({
  tribunal_1: props.datos?.tribunal_1 || props.datos?.tribunal?.[0] || null,
  tribunal_2: props.datos?.tribunal_2 || props.datos?.tribunal?.[1] || null,
}));

const resultados = RESULTADOS_EVALUACION;

const tonosResultado = {
  correcto: {
    caja: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200',
    texto: 'text-emerald-300',
  },
  faltante: {
    caja: 'border-amber-500/30 bg-amber-500/10 text-amber-200',
    texto: 'text-amber-300',
  },
  incorrecto: {
    caja: 'border-red-500/30 bg-red-900/30 text-red-200',
    texto: 'text-red-300',
  },
};

const mismoDocenteSeleccionado = computed(() => {
  const tribunal1 = seleccionIds.tribunal_1;
  const tribunal2 = seleccionIds.tribunal_2;

  return tribunal1 !== null && tribunal2 !== null && String(tribunal1) === String(tribunal2);
});

const resultadoSeleccionado = computed(() =>
  resultadoEvaluacion(props.completado ? props.datos?.resultado_evaluacion : form.resultado_evaluacion),
);
const resultadoCajaTono = computed(() =>
  tonosResultado[resultadoSeleccionado.value?.valor]?.caja || 'border-white/10 bg-white/5 text-slate-300',
);
const resultadoTextoTono = computed(() =>
  tonosResultado[resultadoSeleccionado.value?.valor]?.texto || 'text-slate-300',
);

function docentePorId(id) {
  return props.docentes.find((docente) => String(docente.id_docente) === String(id));
}

function sincronizar(numero) {
  const clave = `tribunal_${numero}`;
  const docente = docentePorId(seleccionIds[clave]);

  form[clave] = docente
    ? {
        id_docente: docente.id_docente,
        nombre: nombreDocente(docente),
      }
    : null;
  errores.value = [];
}

function esPdf(file) {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

function handleArchivo(event) {
  const file = event.target.files[0] || null;

  if (file && !esPdf(file)) {
    errores.value = ['El informe de evaluación debe ser un archivo PDF.'];
    archivo.value = null;
    event.target.value = '';
    return;
  }

  archivo.value = file;
  errores.value = [];
  event.target.value = '';
}

function guardar() {
  const datos = {
    tribunal_1: form.tribunal_1,
    tribunal_2: form.tribunal_2,
    resultado_evaluacion: form.resultado_evaluacion,
    confirmado: form.confirmado,
    observaciones: form.observaciones.trim(),
  };

  const res = validarModulo('informe_tribunal', datos);

  if (!res.ok) {
    errores.value = res.errores;
    return;
  }

  errores.value = [];
  emit('guardar', datos, { informe_evaluacion_tribunal: archivo.value });
}
</script>
