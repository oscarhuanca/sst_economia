<template>
  <ModuloTarjeta
    :numero="String(numero)"
    titulo="Resolución de Aprobación del Resultado"
    descripcion="Registre la Resolución del Consejo Universitario y su fecha con la que se aprueba el resultado del examen teórico. Este registro habilita el sorteo del examen de especialidad o, si ya está aprobado, la Resolución de asignación de notas."
    icono="file-text"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado" class="space-y-3">
      <dl class="grid sm:grid-cols-2 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">N.º de Resolución del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ aprobado?.numero_resolucion || '-' }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la resolución</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(aprobado?.fecha_resolucion || '') || '-' }}
          </dd>
        </div>
      </dl>
      <p class="dato-ayuda">
        Resultado del examen teórico aprobado. El estudiante ya puede ser sorteado para el examen de especialidad.
      </p>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="file-text" :size="15" />
      Registre primero el sorteo del examen teórico para aprobar su resultado.
    </div>

    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="examen-resultado-resolucion">Número de Resolución del HCC</label>
          <input
            id="examen-resultado-resolucion"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: HCC-0316/2026"
          />
        </div>
        <div>
          <label class="label" for="examen-resultado-fecha">Fecha de la Resolución</label>
          <input id="examen-resultado-fecha" v-model="form.fecha_resolucion" type="date" class="input" :max="hoyISO" />
        </div>
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
          <AppIcon v-else name="file-text" :size="18" />
          <span class="uppercase tracking-wide">Aprobar resultado</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { validarModulo } from '@/modules/examen/utils/flujoExamen';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
  numero: { type: [Number, String], default: 4 },
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
});

const errores = ref([]);
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

// El historial es la fuente de verdad: conserva la Resolución con la que se
// aprobó el resultado del teórico, incluso si el formulario ya se limpió.
const aprobado = computed(() => props.registro || props.datos);

function guardar() {
  const datos = {
    numero_resolucion: form.numero_resolucion.trim(),
    fecha_resolucion: form.fecha_resolucion,
  };
  const res = validarModulo('resolucion_resultado_teorico', datos);

  if (!res.ok) {
    errores.value = res.errores;
    return;
  }

  errores.value = [];
  emit('guardar', datos);
}
</script>
