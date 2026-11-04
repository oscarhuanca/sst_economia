<template>
  <ModuloTarjeta
    numero="1"
    titulo="Verificación de Intentos"
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
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      La solicitud del estudiante ya fue revisada.
    </div>

    <form v-else class="space-y-5" @submit.prevent="guardar">
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
            {{ accion === 'rechazar' ? 'Rechazar solicitud' : 'Verificar intentos' }}
          </span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { MAXIMO_INTENTOS, validarModulo } from '@/modules/examen/utils/flujoExamen';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  cargando: { type: Boolean, default: false },
  intentos: { type: Number, default: 0 },
  maximo: { type: Number, default: MAXIMO_INTENTOS },
  registro: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const form = reactive({ observaciones: '' });
const errores = ref([]);
const accion = ref('aprobar');

const usados = computed(() => Number(props.intentos || 0));
const restantes = computed(() => Math.max(0, props.maximo - usados.value));
const agotados = computed(() => usados.value >= props.maximo);
const aprobado = computed(() => props.registro?.resultado === 'verificado');
const descripcion = computed(() =>
  `Cada postulación o reapertura consume un intento. Con ${props.maximo} o más la solicitud se rechaza.`,
);

function guardar() {
  const datos = { accion: accion.value, observaciones: form.observaciones.trim() };
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
