<template>
  <ModuloTarjeta
    numero="1"
    titulo="Verificación Documental e Intentos"
    :descripcion="descripcion"
    icono="clipboard"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Módulo ya resuelto: se muestra el veredicto registrado. -->
    <div v-if="completado && registro" class="space-y-3">
      <div
        class="flex items-center gap-3 rounded-xl border px-4 py-3"
        :class="aprobado ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-red-500/30 bg-red-900/30'"
      >
        <AppIcon :name="aprobado ? 'check-circle' : 'x-circle'" :size="18" :class="aprobado ? 'text-emerald-300' : 'text-red-400'" />
        <p class="text-sm font-semibold" :class="aprobado ? 'text-emerald-200' : 'text-red-200'">
          {{ aprobado ? 'Solicitud habilitada para seguir el flujo.' : 'Solicitud rechazada por intentos agotados.' }}
        </p>
      </div>
      <p v-if="aprobado" class="text-sm text-slate-300 leading-relaxed">
        Intento
        <span class="font-semibold text-slate-100">{{ usados }}</span> de {{ maximo }}.
        El estudiante habilita su solicitud de examen de grado.
      </p>
      <p v-else-if="registro.observaciones" class="text-sm text-slate-300 leading-relaxed">
        {{ registro.observaciones }}
      </p>
      <div class="flex justify-end">
        <button type="button" class="btn-warm px-5" @click="emit('continuar')">
          <AppIcon name="chevron-right" :size="18" />
          <span class="uppercase tracking-wide">Continuar a habilitación</span>
        </button>
      </div>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      La solicitud del estudiante ya fue revisada.
    </div>

    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="rounded-xl bg-white/5 border border-white/10 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold">1. Documentación recepcionada</p>
        <p class="dato-ayuda mt-1">
          {{ documentosVerificados
            ? 'KARDEX verificó estos PDF al recepcionar el trámite. Ahora confirme los intentos.'
            : 'Los PDF se verificarán automáticamente al guardar la revisión de intentos.' }}
        </p>
        <span
          class="mt-2 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
          :class="documentosVerificados
            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
            : 'border-amber-500/30 bg-amber-500/10 text-amber-300'"
        >
          <AppIcon :name="documentosVerificados ? 'check-circle' : 'clock'" :size="12" />
          {{ documentosVerificados ? 'Documentación verificada' : 'Se verificará al guardar' }}
        </span>
        <div v-if="!documentos.length" class="mt-3 text-sm text-slate-400">
          El trámite no registra PDFs adjuntos. La recepción documental se gestiona en Registro de Postulante.
        </div>
        <div v-else class="mt-3 space-y-2">
          <div v-for="doc in TIPOS_DOCUMENTO_EXAMEN" :key="doc.tipo" class="flex flex-wrap items-center gap-3 rounded-lg bg-white/5 px-3 py-2.5">
            <AppIcon name="file-text" :size="16" class="shrink-0 text-orange-300" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-white">{{ etiquetaDocumento(doc.tipo) }}</p>
              <p v-if="archivoDe(doc.tipo)" class="text-xs text-slate-400 truncate">
                {{ archivoDe(doc.tipo).nombre_archivo }}
              </p>
              <p v-else class="text-xs text-slate-500">Sin archivo registrado</p>
            </div>
            <a
              v-if="archivoDe(doc.tipo)?.ruta_archivo"
              :href="assetUrl(archivoDe(doc.tipo).ruta_archivo)"
              target="_blank"
              rel="noopener"
              class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-300 hover:text-orange-200"
            >
              <AppIcon name="file-text" :size="14" />
              Ver PDF
            </a>
          </div>
        </div>
      </div>

      <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold">2. Verificación de intentos</p>

      <!-- Contador de intentos: es la información que KARDEX necesita para decidir. -->
      <div class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos utilizados</dt>
          <dd class="text-2xl font-extrabold text-white mt-0.5">
            {{ usados }}
            <span class="text-xs font-semibold text-slate-400">de {{ maximo }}</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos restantes</dt>
          <dd class="text-2xl font-extrabold mt-0.5" :class="restantes > 0 ? 'text-emerald-300' : 'text-red-400'">
            {{ restantes }}
          </dd>
        </div>
        <div
          class="rounded-xl border px-3.5 py-2.5"
          :class="agotados ? 'border-red-500/30 bg-red-900/30' : 'border-emerald-500/30 bg-emerald-500/10'"
        >
          <dt class="dato-label">Criterio</dt>
          <dd class="text-sm font-bold mt-1 leading-snug" :class="agotados ? 'text-red-200' : 'text-emerald-200'">
            {{ agotados ? 'Agotados: corresponde rechazar' : 'Dentro del límite: corresponde habilitar' }}
          </dd>
        </div>
      </div>

      <fieldset>
        <legend class="label">Veredicto</legend>
        <div class="grid sm:grid-cols-2 gap-3">
          <button
            type="button"
            class="opcion"
            :class="accion === 'aprobar' ? 'opcion-activa-verde' : 'opcion-inactiva'"
            :disabled="agotados"
            @click="accion = 'aprobar'"
          >
            <AppIcon name="check-circle" :size="18" />
            <span class="min-w-0">
              <span class="block font-bold uppercase tracking-wide">Verificar y continuar</span>
              <span class="block text-xs text-slate-400">
                {{ agotados ? 'No disponible: no le quedan intentos' : 'El estudiante sigue el flujo' }}
              </span>
            </span>
          </button>
          <button
            type="button"
            class="opcion"
            :class="accion === 'rechazar' ? 'opcion-activa-roja' : 'opcion-inactiva'"
            :disabled="!agotados"
            @click="accion = 'rechazar'"
          >
            <AppIcon name="x-circle" :size="18" />
            <span class="min-w-0">
              <span class="block font-bold uppercase tracking-wide">Rechazar la solicitud</span>
              <span class="block text-slate-400" :class="accion === 'rechazar' ? 'text-red-200/80' : ''">
                {{ agotados ? 'Agotó los intentos permitidos' : 'Solo si los intentos están agotados' }}
              </span>
            </span>
          </button>
        </div>
      </fieldset>

      <div v-if="accion === 'rechazar'">
        <label class="label" for="examen-motivo">Motivo del rechazo</label>
        <textarea
          id="examen-motivo"
          v-model="form.observaciones"
          rows="3"
          class="input"
          placeholder="Agotó los tres intentos permitidos para el examen de grado."
        />
        <p class="dato-ayuda">Es lo que el estudiante verá en su seguimiento.</p>
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
          <AppIcon v-else name="clipboard" :size="18" />
          <span class="uppercase tracking-wide">
            {{ accion === 'rechazar' ? 'Rechazar solicitud' : 'Verificar e ir a habilitación' }}
          </span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { MAXIMO_INTENTOS, TIPOS_DOCUMENTO_EXAMEN, validarModulo } from '@/modules/examen/utils/flujoExamen';
import { etiquetaDocumento } from '@/modules/tramites/utils/estados';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  cargando: { type: Boolean, default: false },
  intentos: { type: Number, default: 0 },
  maximo: { type: Number, default: MAXIMO_INTENTOS },
  documentos: { type: Array, default: () => [] },
  documentosVerificados: { type: Boolean, default: false },
  registro: { type: Object, default: null },
});

const emit = defineEmits(['guardar', 'continuar']);

const form = reactive({ observaciones: '' });
const errores = ref([]);
const accion = ref('aprobar');

const usados = computed(() => Number(props.intentos || 0));
const restantes = computed(() => Math.max(0, props.maximo - usados.value));
const agotados = computed(() => usados.value >= props.maximo);
const aprobado = computed(() => props.registro?.resultado === 'verificado');
const descripcion = computed(() =>
  `La documentación fue recepcionada en Registro de Postulante. Revise los intentos: cada postulación consume uno; con ${props.maximo} o más se rechaza.`,
);

const archivoDe = (tipo) => props.documentos.find((doc) => doc.tipo_documento === tipo) || null;

function guardar() {
  const datos = {
    accion: accion.value,
    observaciones: form.observaciones.trim(),
  };
  const res = validarModulo('verificacion_intentos', datos, {
    intentos: usados.value,
    maximo: props.maximo,
  });

  if (!res.ok) {
    errores.value = res.errores;
    return;
  }

  errores.value = [];
  emit('guardar', datos);
}
</script>
