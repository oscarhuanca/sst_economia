<template>
  <ModuloTarjeta
    numero="2"
    titulo="Resolución del HCC y Lectura del Acta"
    descripcion="Registre la Resolución del HCC que fija la lectura del acta, la fecha de lectura ante los tribunales y el informe opcional."
    icono="shield"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Resolución y lectura ya registradas -->
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
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 sm:col-span-2">
          <dt class="dato-label">Fecha de lectura del acta</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(registro?.fecha_lectura_acta || '') || '—' }}
          </dd>
        </div>
      </dl>

      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
              Informe de lectura del acta
            </p>
            <p
              class="text-sm mt-0.5 truncate"
              :class="registro?.informe_lectura_acta?.nombre ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ registro?.informe_lectura_acta?.nombre || 'Sin PDF adjunto' }}
            </p>
          </div>
          <a
            v-if="registro?.informe_lectura_acta?.ruta"
            :href="assetUrl(registro.informe_lectura_acta.ruta)"
            target="_blank"
            rel="noopener"
            class="btn-ghost !py-1.5 text-xs shrink-0"
          >
            <AppIcon name="link" :size="13" />
            Ver PDF
          </a>
        </div>
      </div>

      <p class="dato-ayuda">El estudiante ya puede emitir su acta de titulación.</p>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      Complete la verificación y admisión del estudiante para registrar la Resolución.
    </div>

    <!-- Formulario de la resolución y lectura del acta -->
    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="excelencia-resolucion">Número de Resolución del HCC</label>
          <input
            id="excelencia-resolucion"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: HCC-0412/2026"
          />
        </div>
        <div>
          <label class="label" for="excelencia-fecha-resolucion">Fecha de la Resolución</label>
          <input id="excelencia-fecha-resolucion" v-model="form.fecha_resolucion" type="date" class="input" :max="hoyISO" />
        </div>
        <div class="sm:col-span-2">
          <label class="label" for="excelencia-fecha-lectura">Fecha de lectura del acta ante los tribunales</label>
          <input id="excelencia-fecha-lectura" v-model="form.fecha_lectura_acta" type="date" class="input" />
        </div>
      </div>

      <div>
        <label class="label" for="excelencia-informe-lectura">
          Informe de lectura del acta
          <span class="text-slate-500 font-normal normal-case">(PDF opcional)</span>
        </label>
        <label
          :class="[
            'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
            archivoLectura ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
          ]"
        >
          <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoLectura ? 'text-orange-200' : 'text-slate-400'">
            <AppIcon
              :name="archivoLectura ? 'check-circle' : 'file-text'"
              :size="18"
              :class="archivoLectura ? 'text-emerald-400 shrink-0' : 'shrink-0'"
            />
            <span class="truncate">{{ archivoLectura?.name || 'Seleccione el PDF del informe...' }}</span>
          </span>
          <input
            id="excelencia-informe-lectura"
            type="file"
            accept=".pdf,application/pdf"
            class="hidden"
            @change="handleArchivoLectura"
          />
          <span class="btn-ghost !py-2 pointer-events-none">
            <AppIcon name="plus" :size="15" />
            Subir
          </span>
        </label>
        <p v-if="registro?.informe_lectura_acta?.nombre" class="dato-ayuda mt-1.5">
          PDF actual: {{ registro.informe_lectura_acta.nombre }}. Si sube otro, se reemplazará.
        </p>
      </div>

      <div>
        <label class="label" for="excelencia-observaciones-resolucion">Observaciones (opcional)</label>
        <textarea
          id="excelencia-observaciones-resolucion"
          v-model="form.observaciones"
          rows="2"
          class="input resize-none"
          placeholder="Detalle de la resolución y de la lectura del acta..."
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
          Confirmo que verifiqué la Resolución del HCC, la fecha de lectura del acta y el informe adjunto.
        </span>
      </label>

      <div class="flex justify-end">
        <button type="submit" class="btn-warm px-6" :disabled="cargando">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="shield" :size="18" />
          <span class="uppercase tracking-wide">Registrar resolución</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
// Módulo 2 del flujo de Excelencia Académica: registro de la Resolución del
// HCC, la fecha de lectura del acta ante los tribunales y el informe opcional.
import { computed, reactive, ref } from 'vue';
import { validarModulo } from '@/modules/excelencia/utils/flujoExcelencia';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
    completado: { type: Boolean, default: false },
    enProgreso: { type: Boolean, default: false },
    bloqueado: { type: Boolean, default: false },
    cargando: { type: Boolean, default: false },
    datos: { type: Object, default: null },
    registro: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const form = reactive({
    numero_resolucion: props.datos?.numero_resolucion || '',
    fecha_resolucion: props.datos?.fecha_resolucion || '',
    fecha_lectura_acta: props.datos?.fecha_lectura_acta || '',
    confirmado: false,
    observaciones: props.datos?.observaciones || '',
});

const archivoLectura = ref(null);
const errores = ref([]);
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

// El historial es la fuente de verdad: guarda la resolución registrada, aunque
// el formulario ya se haya limpiado.
const registro = computed(() => props.registro || props.datos);

function esPdf(file) {
    return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

function handleArchivoLectura(event) {
    const file = event.target.files[0] || null;

    if (file && !esPdf(file)) {
        errores.value = ['El informe de lectura del acta debe ser un archivo PDF.'];
        archivoLectura.value = null;
        event.target.value = '';
        return;
    }

    archivoLectura.value = file;
    errores.value = [];
    event.target.value = '';
}

function guardar() {
    const datos = {
        numero_resolucion: form.numero_resolucion.trim(),
        fecha_resolucion: form.fecha_resolucion,
        fecha_lectura_acta: form.fecha_lectura_acta,
        confirmado: form.confirmado,
        observaciones: form.observaciones.trim(),
    };

    const res = validarModulo('resolucion_hcc', datos);

    if (!res.ok) {
        errores.value = res.errores;
        return;
    }

    errores.value = [];
    emit('guardar', datos, { informe_lectura_acta: archivoLectura.value });
}
</script>
