<template>
  <ModuloTarjeta
    :numero="numero"
    titulo="Calificación"
    descripcion="Registre las calificaciones por área; la calificación final se calcula automáticamente con los pesos 40% / 40% / 20%."
    icono="shield"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado" class="space-y-3">
      <dl class="grid sm:grid-cols-2 md:grid-cols-4 gap-2.5">
        <div
          v-for="area in areas"
          :key="area.campo"
          class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5"
        >
          <dt class="dato-label">{{ area.etiqueta }} ({{ area.porcentaje }}%)</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ formatoCalificacion(registro?.[area.campo]) }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Calificación final</dt>
          <dd class="text-sm font-semibold text-orange-200 mt-0.5">
            {{ formatoCalificacion(registro?.calificacion_final) }}
          </dd>
        </div>
      </dl>

      <p v-if="registro?.observaciones" class="dato-ayuda">
        Observaciones: {{ registro.observaciones }}
      </p>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      Complete el Informe de Evaluación del Tribunal para registrar la calificación.
    </div>

    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <div v-for="area in areas" :key="area.campo">
          <div class="flex h-16 flex-col justify-start">
            <label class="label text-center leading-tight" :for="`excelencia-${area.campo}`">
              {{ area.etiqueta }}
            </label>
            <span class="mt-1 text-center text-xs font-bold text-orange-300">{{ area.porcentaje }}%</span>
          </div>
          <input
            :id="`excelencia-${area.campo}`"
            v-model.number="form[area.campo]"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="input"
            placeholder="0.00"
          />
        </div>
        <div>
          <div class="flex h-16 flex-col justify-start">
            <label class="label text-center leading-tight" for="excelencia-calificacion-final">
              Calificación final
            </label>
            <span class="mt-1 text-center text-xs font-bold text-orange-300">100%</span>
          </div>
          <input
            id="excelencia-calificacion-final"
            :value="calificacionFinalTexto"
            type="text"
            class="input !text-orange-200 font-extrabold"
            readonly
          />
        </div>
      </div>

      <div>
        <label class="label" for="excelencia-observaciones-calificacion">Observaciones (opcional)</label>
        <textarea
          id="excelencia-observaciones-calificacion"
          v-model="form.observaciones"
          rows="2"
          class="input resize-none"
          placeholder="Detalle de la calificación obtenida..."
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
          Confirmo que verifiqué las calificaciones por área y la calificación final calculada.
        </span>
      </label>

      <div class="flex justify-end">
        <button type="submit" class="btn-warm px-6" :disabled="cargando">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="shield" :size="18" />
            <span class="uppercase tracking-wide">Guardar calificación</span>

        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import {
    AREAS_CALIFICACION,
    calificacionFinal,
    formatoCalificacion,
    validarModulo,
} from '@/modules/excelencia/utils/flujoExcelencia';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
    numero: { type: [Number, String], required: true },
    completado: { type: Boolean, default: false },
    enProgreso: { type: Boolean, default: false },
    bloqueado: { type: Boolean, default: false },
    cargando: { type: Boolean, default: false },
    datos: { type: Object, default: null },
    registro: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const areas = AREAS_CALIFICACION;
const errores = ref([]);

const form = reactive({
    calificacion_cuantitativa: props.datos?.calificacion_cuantitativa ?? '',
    calificacion_teoria_economica: props.datos?.calificacion_teoria_economica ?? '',
    calificacion_materias_complementarias: props.datos?.calificacion_materias_complementarias ?? '',
    confirmado: false,
    observaciones: props.datos?.observaciones || '',
});

const registro = computed(() => props.registro || props.datos);
const calificacion = computed(() => calificacionFinal(form));
const calificacionFinalTexto = computed(() => formatoCalificacion(calificacion.value));

function guardar() {
    const datos = {
        calificacion_cuantitativa: form.calificacion_cuantitativa,
        calificacion_teoria_economica: form.calificacion_teoria_economica,
        calificacion_materias_complementarias: form.calificacion_materias_complementarias,
        calificacion_final: calificacion.value,
        confirmado: form.confirmado,
        observaciones: form.observaciones.trim(),
    };

    const res = validarModulo('resolucion_hcc', datos);

    if (!res.ok) {
        errores.value = res.errores;
        return;
    }

    errores.value = [];
    emit('guardar', datos);
}
</script>
