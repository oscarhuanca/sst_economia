<template>
  <ModuloTarjeta
    numero="1"
    titulo="Verificación y Admisión"
    descripcion="Verifique el promedio general, registre la Resolución del HCC que admite o rechaza la solicitud y confirme la revisión para avanzar."
    icono="clipboard"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Promedio y admisión ya verificados -->
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
          {{ aprobado ? 'Solicitud aceptada: el estudiante habilita la modalidad' : 'Solicitud rechazada por Resolución del HCC' }}
        </p>
      </div>

      <dl class="grid sm:grid-cols-2 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio declarado</dt>
          <dd class="text-2xl font-extrabold text-white mt-0.5">
            {{ declarado !== null ? declarado : '—' }}
            <span class="text-xs font-semibold text-slate-400">/ 100</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio verificado</dt>
          <dd class="text-2xl font-extrabold mt-0.5" :class="aprobado ? 'text-emerald-300' : 'text-red-400'">
            {{ verificado !== null ? verificado : '—' }}
            <span class="text-xs font-semibold text-slate-400">/ 100</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Resolución de admisión del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ datos?.numero_resolucion || '—' }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la Resolución</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(datos?.fecha_resolucion || '') || '—' }}
          </dd>
        </div>
      </dl>

      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
              Informe de verificación
            </p>
            <p
              class="text-sm mt-0.5 truncate"
              :class="datos?.informe_verificacion?.nombre ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ datos?.informe_verificacion?.nombre || 'Sin PDF adjunto' }}
            </p>
          </div>
          <a
            v-if="datos?.informe_verificacion?.ruta"
            :href="assetUrl(datos.informe_verificacion.ruta)"
            target="_blank"
            rel="noopener"
            class="btn-ghost !py-1.5 text-xs shrink-0"
          >
            <AppIcon name="link" :size="13" />
            Ver PDF
          </a>
        </div>
      </div>

      <p class="dato-ayuda">
        El promedio verificado se consolidó en el perfil del estudiante
        (<span class="font-semibold text-slate-300">promedio global</span>).
      </p>
    </div>

    <!-- Formulario de verificación y admisión -->
    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="excelencia-promedio-declarado">Promedio declarado por el estudiante</label>
          <input
            id="excelencia-promedio-declarado"
            :value="declarado ?? ''"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="input"
            disabled
          />
          <p class="dato-ayuda">Valor registrado en la solicitud; solo informativo.</p>
        </div>
        <div>
          <label class="label" for="excelencia-promedio-verificado">Promedio verificado · editable</label>
          <input
            id="excelencia-promedio-verificado"
            v-model="form.promedio"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="input"
            placeholder="Ej: 87.50"
          />
          <p class="dato-ayuda">Este valor queda en el perfil del estudiante.</p>
        </div>
      </div>

      <div>
        <span class="label">Resultado de la admisión</span>
        <div class="grid sm:grid-cols-2 gap-2.5">
          <label
            class="flex items-start gap-2.5 rounded-xl border px-3.5 py-3 cursor-pointer transition"
            :class="form.resultado === 'aceptado'
              ? 'border-emerald-500/50 bg-emerald-500/10'
              : 'border-white/10 bg-white/5 hover:border-emerald-500/40'"
          >
            <input v-model="form.resultado" type="radio" value="aceptado" class="mt-0.5 h-4 w-4 accent-emerald-500" />
            <span class="text-xs leading-relaxed" :class="form.resultado === 'aceptado' ? 'text-emerald-200' : 'text-slate-400'">
              <strong class="block uppercase tracking-wide">Aceptar solicitud</strong>
              El estudiante habilita la titulación por Excelencia Académica.
            </span>
          </label>
          <label
            class="flex items-start gap-2.5 rounded-xl border px-3.5 py-3 cursor-pointer transition"
            :class="form.resultado === 'rechazado'
              ? 'border-red-500/50 bg-red-900/30'
              : 'border-white/10 bg-white/5 hover:border-red-500/40'"
          >
            <input v-model="form.resultado" type="radio" value="rechazado" class="mt-0.5 h-4 w-4 accent-red-500" />
            <span class="text-xs leading-relaxed" :class="form.resultado === 'rechazado' ? 'text-red-200' : 'text-slate-400'">
              <strong class="block uppercase tracking-wide">Rechazar solicitud</strong>
              El flujo termina y el estudiante deberá optar por otra modalidad.
            </span>
          </label>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="excelencia-resolucion-admision">Número de Resolución del HCC</label>
          <input
            id="excelencia-resolucion-admision"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: HCC-0158/2026"
          />
        </div>
        <div>
          <label class="label" for="excelencia-fecha-resolucion-admision">Fecha de la Resolución</label>
          <input
            id="excelencia-fecha-resolucion-admision"
            v-model="form.fecha_resolucion"
            type="date"
            class="input"
            :max="hoyISO"
          />
        </div>
      </div>

      <div>
        <label class="label" for="excelencia-informe-verificacion">
          Informe de verificación
          <span class="text-slate-500 font-normal normal-case">(PDF opcional)</span>
        </label>
        <label
          :class="[
            'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
            archivoVerificacion ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
          ]"
        >
          <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoVerificacion ? 'text-orange-200' : 'text-slate-400'">
            <AppIcon
              :name="archivoVerificacion ? 'check-circle' : 'file-text'"
              :size="18"
              :class="archivoVerificacion ? 'text-emerald-400 shrink-0' : 'shrink-0'"
            />
            <span class="truncate">{{ archivoVerificacion?.name || 'Seleccione el PDF del informe...' }}</span>
          </span>
          <input
            id="excelencia-informe-verificacion"
            type="file"
            accept=".pdf,application/pdf"
            class="hidden"
            @change="handleArchivoVerificacion"
          />
          <span class="btn-ghost !py-2 pointer-events-none">
            <AppIcon name="plus" :size="15" />
            Subir
          </span>
        </label>
        <p v-if="datos?.informe_verificacion?.nombre" class="dato-ayuda mt-1.5">
          PDF actual: {{ datos.informe_verificacion.nombre }}. Si sube otro, se reemplazará.
        </p>
      </div>

      <div>
        <label class="label" for="excelencia-observaciones">Observaciones (opcional)</label>
        <textarea
          id="excelencia-observaciones"
          v-model="form.observaciones"
          rows="2"
          class="input resize-none"
          placeholder="Detalle la verificación realizada..."
        ></textarea>
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

      <label class="flex items-start gap-2.5 rounded-xl border border-orange-500/30 bg-orange-500/10 px-3.5 py-3 cursor-pointer">
        <input
          v-model="form.confirmado"
          type="checkbox"
          class="mt-0.5 h-4 w-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
        />
        <span class="text-xs text-orange-100 leading-relaxed">
          Confirmo que verifiqué el promedio, la Resolución del HCC y el informe adjunto, y que el resultado
          de la admisión es <strong>{{ form.resultado === 'rechazado' ? 'RECHAZADO' : 'ACEPTADO' }}</strong>.
        </span>
      </label>

      <div class="flex justify-end">
        <button type="submit" class="btn-warm px-6" :disabled="cargando">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="clipboard" :size="18" />
          <span class="uppercase tracking-wide">Guardar verificación</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
// Módulo 1 del flujo de Excelencia Académica: verificación del promedio,
// Resolución de admisión del HCC, informe opcional y confirmación de KARDEX.
//
// El estudiante declara su promedio al presentar la solicitud; KARDEX lo
// verifica aquí y puede corregirlo (es la nota que se consolida en el perfil
// del estudiante). La decisión ACEPTADO/RECHAZADO es explícita: aceptar exige
// alcanzar el mínimo; rechazar cierra el flujo de titulación.
import { computed, reactive, ref, watch } from 'vue';
import {
    NOTA_MINIMA,
    validarModulo,
} from '@/modules/excelencia/utils/flujoExcelencia';
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
    // Mínimo reglamentario: lo define el backend (`config/excelencia.php`).
    notaMinima: { type: Number, default: NOTA_MINIMA },
    promedioDeclarado: { type: [Number, String], default: null },
    promedioVerificado: { type: [Number, String], default: null },
});

const emit = defineEmits(['guardar']);

const notaMinima = computed(() => Number(props.notaMinima) || NOTA_MINIMA);

const declarado = computed(() =>
    props.promedioDeclarado === null || props.promedioDeclarado === undefined
        ? null
        : Number(props.promedioDeclarado),
);

const verificado = computed(() =>
    props.promedioVerificado === null || props.promedioVerificado === undefined
        ? null
        : Number(props.promedioVerificado),
);

const form = reactive({
    promedio: props.promedioVerificado ?? props.promedioDeclarado ?? '',
    resultado: props.datos?.resultado || '',
    numero_resolucion: props.datos?.numero_resolucion || '',
    fecha_resolucion: props.datos?.fecha_resolucion || '',
    confirmado: false,
    observaciones: props.datos?.observaciones || '',
});

const archivoVerificacion = ref(null);
const errores = ref([]);
const advertencias = ref([]);

const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

watch(
    () => props.promedioVerificado,
    (valor) => {
        if (valor !== null && valor !== undefined) form.promedio = valor;
    },
);

/** Resultado registrado en el historial (fallback para datos antiguos). */
const aprobado = computed(() => {
    const resultado = props.datos?.resultado;

    if (resultado === 'aceptado' || resultado === 'rechazado') return resultado === 'aceptado';

    return verificado.value !== null && verificado.value >= notaMinima.value;
});

function esPdf(file) {
    return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

function handleArchivoVerificacion(event) {
    const file = event.target.files[0] || null;

    if (file && !esPdf(file)) {
        errores.value = ['El informe de verificación debe ser un archivo PDF.'];
        archivoVerificacion.value = null;
        event.target.value = '';
        return;
    }

    archivoVerificacion.value = file;
    errores.value = [];
    event.target.value = '';
}

function guardar() {
    const datos = {
        promedio: form.promedio === '' ? '' : Number(form.promedio),
        resultado: form.resultado,
        numero_resolucion: form.numero_resolucion.trim(),
        fecha_resolucion: form.fecha_resolucion,
        confirmado: form.confirmado,
        observaciones: form.observaciones.trim(),
    };

    const res = validarModulo('verificacion_promedio', datos);
    advertencias.value = res.advertencias;

    if (!res.ok) {
        errores.value = res.errores;
        return;
    }

    errores.value = [];
    emit('guardar', datos, { informe_verificacion: archivoVerificacion.value });
}
</script>
