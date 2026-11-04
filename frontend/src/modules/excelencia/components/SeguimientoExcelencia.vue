<template>
  <div class="space-y-4">
    <!-- Resumen del trámite: estado y promedio -->
    <div class="card overflow-hidden">
      <div class="h-2 bg-orange"></div>
      <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-extrabold text-white">Excelencia Académica</h2>
            <p class="text-xs text-slate-400">
              Estado:
              <span class="font-bold text-orange-300">{{ formatoEstado(estadoActual) }}</span>
            </p>
          </div>
          <EstadoBadge :estado="estadoActual" />
        </div>

        <dl class="mt-5 grid sm:grid-cols-2 xl:grid-cols-4 gap-2.5">
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Promedio declarado</dt>
            <dd v-if="puedeDeclararPromedio" class="mt-1.5">
              <form class="flex items-center gap-2" @submit.prevent="guardarPromedio">
                <input
                  v-model="promedioInput"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="input flex-1 min-w-0 text-lg font-extrabold"
                  placeholder="Ej: 87.50"
                  aria-label="Promedio declarado"
                  :disabled="guardandoPromedio"
                />
                <button
                  type="submit"
                  class="btn-warm px-3 py-2 shrink-0"
                  title="Guardar promedio declarado"
                  :disabled="guardandoPromedio || !promedioValido"
                >
                  <AppIcon v-if="guardandoPromedio" name="loader" :size="16" class="animate-spin" />
                  <AppIcon v-else name="check" :size="16" />
                </button>
              </form>
              <p class="mt-1.5 text-[11px] text-slate-400 leading-snug">
                KARDEX revisará esta nota para verificarla.
              </p>
            </dd>
            <dd v-else class="text-2xl font-extrabold text-white mt-0.5">
              {{ declarado !== null ? declarado : '—' }}
              <span class="text-xs font-semibold text-slate-400">/ 100</span>
            </dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Promedio verificado</dt>
            <dd class="text-2xl font-extrabold mt-0.5" :class="verificado !== null ? 'text-emerald-300' : 'text-slate-400'">
              {{ verificado !== null ? verificado : '—' }}
              <span class="text-xs font-semibold text-slate-400">/ 100</span>
            </dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Resolución de admisión del HCC</dt>
            <dd class="text-sm font-semibold text-white mt-1">
              {{ resolucionAdmision.numero_resolucion || '—' }}
            </dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Lectura del acta</dt>
            <dd class="text-sm font-semibold text-white mt-1">
              {{ resolucion.fecha_lectura_acta ? fechaLegible(resolucion.fecha_lectura_acta) : '—' }}
            </dd>
          </div>
        </dl>

        <p v-if="rechazado" class="mt-4 rounded-xl bg-red-900/50 border border-red-500/30 px-4 py-3 text-sm text-red-300 leading-relaxed">
          Tu solicitud de Excelencia Académica fue rechazada mediante la Resolución del HCC
          <span class="font-bold">{{ resolucionAdmision.numero_resolucion || '—' }}</span>
          <template v-if="resolucionAdmision.fecha_resolucion">
            del {{ fechaLegible(resolucionAdmision.fecha_resolucion) }}
          </template>.
          <template v-if="verificado !== null && verificado < notaMinima">
            El promedio verificado ({{ verificado }}) es inferior al mínimo de {{ notaMinima }} exigido por la modalidad.
          </template>
          Puedes optar por otra modalidad de titulación.
        </p>

        <p v-else-if="titulado" class="mt-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-sm text-emerald-300 leading-relaxed">
          ¡Felicidades! Tu Excelencia Académica concluyó con el acta de titulación emitida.
        </p>
      </div>
    </div>

    <!-- Avance del flujo, paso a paso -->
    <div class="card overflow-hidden">
      <div class="h-2 bg-gradient-to-r from-slate-500 to-slate-400"></div>
      <div class="p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="trending-up" :size="18" class="text-orange-300" />
          Mi avance
        </h3>

        <div class="mt-4 grid sm:grid-cols-3 gap-3">
          <div
            v-for="m in modulos"
            :key="m.id"
            class="rounded-xl bg-white/5 p-3.5 ring-1"
            :class="m.ring"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="inline-flex items-center gap-2 min-w-0">
                <AppIcon :name="m.icon" :size="15" class="shrink-0" :class="m.icono" />
                <span class="text-sm font-bold text-white truncate">{{ m.label }}</span>
              </span>
              <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide ring-1" :class="m.badge.cls">
                {{ m.badge.label }}
              </span>
            </div>
            <p v-if="m.datos" class="mt-2 text-xs text-slate-300 leading-snug">
              {{ m.datos }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Última actualización registrada por KARDEX -->
    <div class="card overflow-hidden">
      <div class="p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="clock" :size="18" class="text-orange-300" />
          Última actualización
        </h3>

        <div v-if="ultimaActualizacion" class="mt-3 rounded-xl bg-white/5 ring-1 ring-white/10 p-3.5 flex items-start gap-3">
          <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0" :class="ultimaActualizacion.tone.dot"></span>
          <div class="min-w-0">
            <p class="text-sm font-bold text-white">{{ formatoEstado(ultimaActualizacion.nombre_estado) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">
              <span v-if="ultimaActualizacion.created_at">{{ fechaHora(ultimaActualizacion.created_at) }}</span>
            </p>
          </div>
        </div>

        <div
          v-else
          class="mt-3 rounded-xl border border-dashed border-white/20 bg-white/5 px-3 py-4 text-center text-sm text-slate-400"
        >
          Aún no hay actualizaciones registradas.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Seguimiento del estudiante para la modalidad de Excelencia Académica.
//
// A diferencia del Examen de Grado, aquí el flujo es lineal: el estudiante
// declara su promedio, KARDEX lo verifica (y lo corrige si hace falta) mediante
// una Resolución de admisión del HCC, se registra la Resolución de lectura del
// acta y, por último, se emite el acta que lo titula. El único desenlace
// alternativo es el RECHAZO, que cierra el trámite sin habilitar la modalidad.
import { computed, ref, watch } from 'vue';
import { EstadoBadge, formatoEstado, toneEstado, useTramitesStore } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import {
    NOTA_MINIMA,
    modulosExcelencia,
    promedioDeclarado,
    promedioVerificado,
    resolucionAdmision as resolucionAdmisionHcc,
    resolucionHcc,
} from '../utils/flujoExcelencia';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { useExcelenciaStore } from '../stores/excelencia';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
    tramite: { type: Object, required: true },
    // Mínimo reglamentario: lo define el backend (`config/excelencia.php`).
    notaMinima: { type: Number, default: NOTA_MINIMA },
});

const notaMinima = computed(() => Number(props.notaMinima) || NOTA_MINIMA);

const estadoActual = computed(() => props.tramite?.estado_actual);
const declarado = computed(() => promedioDeclarado(props.tramite));
const verificado = computed(() => promedioVerificado(props.tramite));
const resolucion = computed(() => resolucionHcc(props.tramite));
const resolucionAdmision = computed(() => resolucionAdmisionHcc(props.tramite));
const rechazado = computed(() => estadoActual.value === 'rechazado');
const titulado = computed(() => estadoActual.value === 'titulado');

const tramitesStore = useTramitesStore();
const excelenciaStore = useExcelenciaStore();
const toastStore = useToastStore();

const promedioInput = ref('');
const guardandoPromedio = computed(() => excelenciaStore.guardandoPromedio);

// El estudiante declara (o corrige) su promedio mientras KARDEX no lo haya
// verificado: es la nota que KARDEX revisa en su panel.
const puedeDeclararPromedio = computed(() =>
    estadoActual.value === 'solicitud_presentada' && verificado.value === null,
);

const promedioValido = computed(() => {
    if (promedioInput.value === '' || promedioInput.value === null) return false;
    const nota = Number(promedioInput.value);
    return !Number.isNaN(nota) && nota >= 0 && nota <= 100;
});

watch(
    declarado,
    (valor) => {
        promedioInput.value = valor !== null ? String(valor) : '';
    },
    { immediate: true },
);

/** Guarda el promedio declarado para que KARDEX lo revise y verifique. */
async function guardarPromedio() {
    if (!promedioValido.value) {
        toastStore.warning('Ingrese un promedio entre 0 y 100.');
        return;
    }

    try {
        const nota = Number(promedioInput.value);
        const actualizado = await excelenciaStore.actualizarPromedioDeclarado(
            props.tramite.id_tramite,
            nota,
        );

        // El padre muestra el trámite desde este store: se actualiza en el acto.
        tramitesStore.tramiteActivo = actualizado;
        promedioInput.value = String(promedioDeclarado(actualizado) ?? nota);
        toastStore.success('Promedio declarado. KARDEX lo revisará para verificarlo.');
    } catch (error) {
        toastStore.error('No se pudo guardar el promedio: ' + detalleDeError(error));
    }
}

/** Última actualización del trámite (la más reciente de `tramite.estados`). */
const ultimaActualizacion = computed(() => {
    const lista = [...(props.tramite?.estados || [])]
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    const ultima = lista[0];
    return ultima ? { ...ultima, tone: toneEstado(ultima.nombre_estado) } : null;
});

/** Módulos del flujo con su estado visual y el dato esencial de cada uno. */
const modulos = computed(() =>
    modulosExcelencia(props.tramite).map((m) => {
        const badge = estadoModulo(m);
        return {
            ...m,
            badge,
            datos: datosModulo(m),
            ring: badge.ring,
            icono: m.completado ? 'text-emerald-300' : m.enProgreso ? 'text-orange-300' : 'text-slate-400',
        };
    }),
);

/** Badge de estado de un módulo: Listo, En curso, Rechazado o Pendiente. */
function estadoModulo(m) {
    if (m.completado) {
        return { label: 'Listo', cls: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30', ring: 'ring-emerald-500/30' };
    }
    if (m.enProgreso) {
        return { label: 'En curso', cls: 'bg-orange-500/15 text-orange-200 ring-orange-500/30', ring: 'ring-orange-500/40' };
    }
    return { label: 'Pendiente', cls: 'bg-white/5 text-slate-400 ring-white/10', ring: 'ring-white/10' };
}

/** Dato esencial de cada módulo para la tarjeta (sin párrafos largos). */
function datosModulo(m) {
    switch (m.id) {
        case 'verificacion_promedio': {
            const nota = promedioVerificado(props.tramite);
            const admision = resolucionAdmisionHcc(props.tramite);
            const resultado = props.tramite?.hitos?.modulos?.verificacion_promedio?.resultado;

            if (resultado === 'rechazado') {
                return admision.numero_resolucion
                    ? `Rechazado mediante Resolución HCC N° ${admision.numero_resolucion}`
                    : 'Solicitud rechazada';
            }
            if (nota !== null) {
                const detalle = nota >= notaMinima.value
                    ? `Verificado: ${nota} (mínimo ${notaMinima.value})`
                    : `Verificado: ${nota} — por debajo del mínimo`;
                return admision.numero_resolucion
                    ? `${detalle} · Resolución N° ${admision.numero_resolucion}`
                    : detalle;
            }
            return promedioDeclarado(props.tramite) !== null
                ? `Pendiente de verificar (declarado: ${promedioDeclarado(props.tramite)})`
                : '';
        }
        case 'resolucion_hcc': {
            const r = resolucionHcc(props.tramite);
            if (!r.numero_resolucion) return '';
            const fechaResolucion = r.fecha_resolucion ? ` · ${fechaLegible(r.fecha_resolucion)}` : '';
            const lectura = r.fecha_lectura_acta ? ` · Lectura: ${fechaLegible(r.fecha_lectura_acta)}` : '';
            return `Resolución HCC N° ${r.numero_resolucion}${fechaResolucion}${lectura}`;
        }
        case 'reporte':
            return m.completado ? 'Acta de Excelencia Académica emitida' : '';
        default:
            return '';
    }
}

/** Detalle legible del fallo al guardar el promedio declarado. */
function detalleDeError(error) {
    if (!error.response) {
        return 'no hay conexión con el servidor.';
    }
    const errores = error.response.data?.errors;
    const primero = errores && Object.values(errores).find((lista) => Array.isArray(lista) && lista.length);
    return primero?.[0] || error.response.data?.message || 'verifique los datos e intente de nuevo.';
}

/** Formatea una fecha ISO en formato corto local (dd mmm yyyy, hh:mm). */
function fechaHora(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}
</script>
