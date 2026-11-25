<template>
  <div class="space-y-4">
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
            <dt class="dato-label">Resolución de designación</dt>
            <dd class="text-sm font-semibold text-white mt-1">{{ designacion.numero_resolucion || '—' }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Fecha de designación</dt>
            <dd class="text-sm font-semibold text-white mt-1">
              {{ designacion.fecha_resolucion ? fechaLegible(designacion.fecha_resolucion) : '—' }}
            </dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Tribunal 1 / Tribunal 2</dt>
            <dd class="text-sm font-semibold text-white mt-1">
              {{ nombreMiembro(informe.tribunal_1) }} / {{ nombreMiembro(informe.tribunal_2) }}
            </dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Lectura del acta</dt>
            <dd class="text-sm font-semibold text-white mt-1">
              {{ resolucion.fecha_lectura_acta ? fechaLegible(resolucion.fecha_lectura_acta) : '—' }}
            </dd>
          </div>
        </dl>

        <p v-if="titulado" class="mt-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-sm text-emerald-300 leading-relaxed">
          ¡Felicidades! Tu Excelencia Académica concluyó con el acta de titulación emitida.
        </p>
      </div>
    </div>

    <div class="card overflow-hidden">
      <div class="h-2 bg-gradient-to-r from-slate-500 to-slate-400"></div>
      <div class="p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="trending-up" :size="18" class="text-orange-300" />
          Mi avance
        </h3>

        <div class="mt-4 grid sm:grid-cols-2 xl:grid-cols-5 gap-3">
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

    <div v-if="documentos.length" class="card overflow-hidden">
      <div class="p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="file-text" :size="18" class="text-orange-300" />
          Documentos del trámite
        </h3>
        <ul class="mt-3 space-y-2">
          <li
            v-for="doc in documentos"
            :key="doc.id_documento || doc.ruta_archivo"
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white/5 ring-1 ring-white/10 px-3.5 py-2.5"
          >
            <span class="text-sm font-semibold text-slate-200">{{ etiquetaDocumento(doc.tipo_documento) }}</span>
            <a :href="assetUrl(doc.ruta_archivo)" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-300 hover:text-orange-200 hover:underline">
              <AppIcon name="file-text" :size="13" />
              Ver PDF
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { EstadoBadge, formatoEstado, toneEstado } from '@/modules/tramites';
import {
    designacionTribunal,
    documentosSolicitud,
    informeTribunal,
    modulosExcelencia,
    nombreDocente,
    resolucionHcc,
} from '../utils/flujoExcelencia';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { assetUrl } from '@/core/http/storage';
import { etiquetaDocumento } from '@/modules/tramites/utils/estados';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
    tramite: { type: Object, required: true },
});

const estadoActual = computed(() => props.tramite?.estado_actual);
const designacion = computed(() => designacionTribunal(props.tramite));
const informe = computed(() => informeTribunal(props.tramite));
const resolucion = computed(() => resolucionHcc(props.tramite));
const titulado = computed(() => estadoActual.value === 'titulado');

function nombreMiembro(miembro) {
    return nombreDocente(miembro) || '—';
}

const ultimaActualizacion = computed(() => {
    const lista = [...(props.tramite?.estados || [])]
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    const ultima = lista[0];
    return ultima ? { ...ultima, tone: toneEstado(ultima.nombre_estado) } : null;
});

const documentos = computed(() => props.tramite?.documentos || []);

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

function estadoModulo(m) {
    if (m.completado) {
        return { label: 'Listo', cls: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30', ring: 'ring-emerald-500/30' };
    }
    if (m.enProgreso) {
        return { label: 'En curso', cls: 'bg-orange-500/15 text-orange-200 ring-orange-500/30', ring: 'ring-orange-500/40' };
    }
    return { label: 'Pendiente', cls: 'bg-white/5 text-slate-400 ring-white/10', ring: 'ring-white/10' };
}

function datosModulo(m) {
    switch (m.id) {
        case 'inicio_tramite': {
            const cantidad = documentosSolicitud(props.tramite).length;
            return cantidad ? `${cantidad} documentos presentados` : '';
        }
        case 'designacion_tribunal': {
            const d = designacion.value;
            if (!d.numero_resolucion) return '';
            return `Resolución HCC N° ${d.numero_resolucion}${d.fecha_resolucion ? ` · ${fechaLegible(d.fecha_resolucion)}` : ''}`;
        }
        case 'informe_tribunal': {
            const i = informe.value;
            if (!i.tribunal_1 && !i.tribunal_2) return '';
            return `${nombreMiembro(i.tribunal_1)} / ${nombreMiembro(i.tribunal_2)}`;
        }
        case 'resolucion_hcc': {
            const r = resolucion.value;
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

function fechaHora(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}
</script>
