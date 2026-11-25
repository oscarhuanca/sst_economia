<template>
  <ModuloTarjeta
    :numero="numero"
    titulo="Inicio de Trámite"
    descripcion="El estudiante presentó la solicitud con los tres documentos obligatorios."
    icono="inbox"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="documentos.length" class="space-y-2">
      <div
        v-for="doc in documentos"
        :key="doc.id_documento || doc.ruta_archivo"
        class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5"
      >
        <span class="text-sm font-semibold text-slate-200">{{ etiquetaDocumento(doc.tipo_documento) }}</span>
        <a
          :href="assetUrl(doc.ruta_archivo)"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-300 hover:text-orange-200 hover:underline"
        >
          <AppIcon name="file-text" :size="13" />
          Ver PDF
        </a>
      </div>
    </div>
    <p v-else class="rounded-xl border border-dashed border-white/20 bg-white/5 px-3.5 py-4 text-center text-xs text-slate-400">
      No hay documentos adjuntos a la solicitud.
    </p>

    <p v-if="fechaInicio" class="dato-ayuda mt-2">
      Solicitud presentada el {{ fechaInicio }}.
    </p>
  </ModuloTarjeta>
</template>

<script setup>
import { computed } from 'vue';
import { documentosSolicitud } from '@/modules/excelencia/utils/flujoExcelencia';
import { assetUrl } from '@/core/http/storage';
import { etiquetaDocumento } from '@/modules/tramites/utils/estados';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
  numero: { type: [Number, String], required: true },
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  tramite: { type: Object, default: null },
});

const documentos = computed(() => documentosSolicitud(props.tramite));
const fechaInicio = computed(() =>
  fechaLegible(props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at),
);
</script>
