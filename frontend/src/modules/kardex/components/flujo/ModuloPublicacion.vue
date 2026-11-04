<template>
  <ModuloTarjeta
    numero="5"
    titulo="Publicación"
    :descripcion="`Verifique el ${etiquetaPublicacion.toLowerCase()} (PDF) subido por el estudiante para concluir la publicación.`"
    icono="graduation"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="space-y-3">
      <div v-if="datos.archivo?.nombre" class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 flex items-center gap-2">
        <AppIcon name="file-text" :size="15" class="text-emerald-400 shrink-0" />
        <span class="text-sm font-semibold text-emerald-200 truncate">
          {{ datos.archivo.nombre }}
        </span>
      </div>
      <div v-else class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="file" :size="15" />
        Sin archivo adjunto
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para registrar la publicación.
      </div>
    </div>

    <div v-else-if="!enProgreso && !bloqueado" class="rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
      <AppIcon name="clock" :size="15" class="inline mr-1.5" />
      Este módulo se habilitará cuando el estudiante suba su {{ etiquetaPublicacion.toLowerCase() }} tras aprobar la defensa.
    </div>

    <div v-else class="space-y-3">
      <div class="rounded-xl bg-white/5 border border-white/10 p-3.5">
        <p class="text-sm font-semibold text-white">{{ etiquetaPublicacion }} subido por el estudiante</p>
        <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-slate-900/40 px-3.5 py-2.5">
          <span class="flex items-center gap-2 text-sm min-w-0" :class="archivo?.nombre ? 'text-slate-200' : 'text-slate-500'">
            <AppIcon :name="archivo?.nombre ? 'file-text' : 'alert-triangle'" :size="16" class="shrink-0 text-orange-300" />
            <span class="truncate">{{ archivo?.nombre || 'El estudiante aún no ha adjuntado el archivo.' }}</span>
          </span>
          <a
            v-if="archivo?.ruta"
            :href="assetUrl(archivo.ruta)"
            target="_blank"
            rel="noopener"
            class="btn-ghost !py-1.5 text-xs shrink-0"
          >
            <AppIcon name="link" :size="13" />
            Ver PDF
          </a>
        </div>
      </div>

      <ul v-if="errores.length" class="space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <div class="flex justify-end">
        <button type="button" class="btn-warm px-6" :disabled="cargando || !archivo?.nombre" @click="guardar">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="check" :size="18" />
          <span class="uppercase tracking-wide">Validar y cerrar publicación</span>
        </button>
      </div>
    </div>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { configDeTramite, validarModulo } from '@/modules/tramites/utils/flujoTitulacion';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
  tramite: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

/**
 * Cómo se llama el documento que el estudiante publica tras la defensa
 * ("Trabajo de Investigación" / "Trabajo Dirigido"): depende de la modalidad
 * del trámite.
 */
const etiquetaPublicacion = computed(
  () => configDeTramite(props.tramite)?.etiquetaPublicacion || 'Trabajo de Investigación',
);

const form = reactive({
  archivo: null,
});

const errores = ref([]);

const archivo = computed(() => props.datos?.archivo || null);

function handleArchivo(event) {
  form.archivo = event.target.files[0] || null;
}

function eliminarArchivo() {
  form.archivo = null;
}

function obtenerDatos() {
  return {
    archivo: form.archivo ? { nombre: form.archivo.name, tamanio: form.archivo.size } : (props.datos?.archivo || null),
  };
}

function guardar() {
  const datos = obtenerDatos();
  const res = validarModulo('publicacion', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  emit('guardar', datos);
}
</script>