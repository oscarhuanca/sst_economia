<template>
  <ModuloTarjeta
    :numero="numero"
    titulo="Designación de Tribunal de Evaluación"
    descripcion="Registre la Resolución del HCC que designa al Tribunal de Evaluación, su fecha y el PDF de la resolución."
    icono="users"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado" class="space-y-3">
      <dl class="grid sm:grid-cols-2 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">N.º de Resolución del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ registro?.numero_resolucion || '—' }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la resolución</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(registro?.fecha_resolucion || '') || '—' }}
          </dd>
        </div>
      </dl>

      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
              Resolución de designación
            </p>
            <p
              class="text-sm mt-0.5 truncate"
              :class="registro?.resolucion_tribunal?.nombre ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ registro?.resolucion_tribunal?.nombre || 'Sin PDF adjunto' }}
            </p>
          </div>
          <a
            v-if="registro?.resolucion_tribunal?.ruta"
            :href="assetUrl(registro.resolucion_tribunal.ruta)"
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
      Finalice la recepción de documentos para designar al Tribunal de Evaluación.
    </div>

    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="excelencia-designacion-resolucion">Número de Resolución del HCC</label>
          <input
            id="excelencia-designacion-resolucion"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: 250/2026"
            @input="normalizarNumeroResolucion"
            @blur="normalizarNumeroResolucion"
          />
        </div>
        <div>
          <label class="label" for="excelencia-designacion-fecha">Fecha de la resolución</label>
          <input
            id="excelencia-designacion-fecha"
            v-model="form.fecha_resolucion"
            type="date"
            class="input"
            :max="hoyISO"
          />
        </div>
      </div>

      <div>
        <label class="label" for="excelencia-designacion-pdf">
          Resolución de designación
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
            <span class="truncate">{{ archivo?.name || 'Seleccione el PDF de la resolución...' }}</span>
          </span>
          <input
            id="excelencia-designacion-pdf"
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
        <p v-if="registro?.resolucion_tribunal?.nombre" class="dato-ayuda mt-1.5">
          PDF actual: {{ registro.resolucion_tribunal.nombre }}. Si sube otro, se reemplazará.
        </p>
      </div>

      <div>
        <label class="label" for="excelencia-designacion-observaciones">Observaciones (opcional)</label>
        <textarea
          id="excelencia-designacion-observaciones"
          v-model="form.observaciones"
          rows="2"
          class="input resize-none"
          placeholder="Detalle de la designación del tribunal..."
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
          Confirmo que verifiqué la Resolución del HCC de designación, su fecha y el PDF adjunto.
        </span>
      </label>

      <div class="flex justify-end">
        <button type="submit" class="btn-warm px-6" :disabled="cargando">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="users" :size="18" />
          <span class="uppercase tracking-wide">Guardar designación</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { validarModulo } from '@/modules/excelencia/utils/flujoExcelencia';
import { fechaLegible, prefijarNumeroResolucion } from '@/modules/kardex/utils/formato';
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
});

const emit = defineEmits(['guardar']);

const form = reactive({
  numero_resolucion: props.datos?.numero_resolucion || '',
  fecha_resolucion: props.datos?.fecha_resolucion || '',
  confirmado: false,
  observaciones: props.datos?.observaciones || '',
});

const archivo = ref(null);
const errores = ref([]);
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));
const registro = computed(() => props.datos);

function esPdf(file) {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

function handleArchivo(event) {
  const file = event.target.files[0] || null;

  if (file && !esPdf(file)) {
    errores.value = ['La resolución de designación debe ser un archivo PDF.'];
    archivo.value = null;
    event.target.value = '';
    return;
  }

  archivo.value = file;
  errores.value = [];
  event.target.value = '';
}

function normalizarNumeroResolucion() {
  form.numero_resolucion = prefijarNumeroResolucion(form.numero_resolucion);
}

function guardar() {
  normalizarNumeroResolucion();
  const datos = {
    numero_resolucion: form.numero_resolucion.trim(),
    fecha_resolucion: form.fecha_resolucion,
    confirmado: form.confirmado,
    observaciones: form.observaciones.trim(),
  };

  const res = validarModulo('designacion_tribunal', datos);

  if (!res.ok) {
    errores.value = res.errores;
    return;
  }

  errores.value = [];
  emit('guardar', datos, { resolucion_tribunal: archivo.value });
}
</script>
