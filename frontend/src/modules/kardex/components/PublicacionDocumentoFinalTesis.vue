<template>
  <section class="card p-5 space-y-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 class="font-bold text-white uppercase flex items-center gap-2">
          <AppIcon name="upload-cloud" :size="18" class="text-orange-300" />
          Publicación del documento final
        </h3>
        <p class="text-sm text-slate-400 mt-1">
          Suba el documento final de la tesis (PDF). Esta acción la realiza KARDEX fuera del flujo.
        </p>
      </div>
      <span v-if="archivoActual?.nombre" class="text-[11px] px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
        Publicado
      </span>
    </div>

    <div v-if="archivoActual?.nombre" class="rounded-xl bg-white/5 border border-white/10 px-4 py-3 flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Documento actual</p>
        <p class="text-sm font-semibold text-slate-200 truncate">{{ archivoActual.nombre }}</p>
      </div>
      <a v-if="archivoActual.ruta" :href="assetUrl(archivoActual.ruta)" target="_blank" rel="noopener" class="btn-ghost !py-1.5 text-xs shrink-0">
        <AppIcon name="link" :size="13" /> Ver PDF
      </a>
    </div>

    <div class="grid gap-3">
      <label class="label" for="tesis-publicacion-final">Documento final de tesis (PDF)</label>
      <input id="tesis-publicacion-final" type="file" accept=".pdf,application/pdf" class="input file:mr-3 file:rounded file:border-0 file:bg-orange file:px-3 file:py-1.5 file:text-white" @change="onFile" />
      <p v-if="archivo?.name" class="text-xs text-slate-300">Seleccionado: {{ archivo.name }}</p>
    </div>

    <div class="flex justify-end">
      <button type="button" class="btn-warm px-6" :disabled="!archivo || cargando" @click="publicar">
        <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
        <AppIcon v-else name="upload" :size="18" />
        <span class="uppercase tracking-wide">Publicar documento</span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppIcon from '@/ui/AppIcon.vue';
import { assetUrl } from '@/core/http/storage';
import { useKardexStore } from '../stores/kardex';
import { useToastStore } from '@/core/stores/toast';

const props = defineProps({ tramite: { type: Object, default: null } });
const kardexStore = useKardexStore();
const toastStore = useToastStore();
const archivo = ref(null);
const cargando = computed(() => kardexStore.cargando);
const archivoActual = computed(() => props.tramite?.hitos?.modulos?.publicacion?.archivo || null);

function onFile(e) {
  const file = e.target.files?.[0] || null;
  if (file && !(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))) {
    toastStore.error('El documento final debe ser un archivo PDF.');
    e.target.value = '';
    archivo.value = null;
    return;
  }
  archivo.value = file;
}

async function publicar() {
  if (!archivo.value) return;
  const tramite = await kardexStore.publicarDocumentoFinalTesis(archivo.value);
  if (tramite) {
    toastStore.success('Documento final de tesis publicado.');
    archivo.value = null;
    const input = document.getElementById('tesis-publicacion-final');
    if (input) input.value = '';
  } else {
    toastStore.error(kardexStore.errorMessage || 'No se pudo publicar el documento final.');
  }
}
</script>
