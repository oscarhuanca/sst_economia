<template>
  <span
    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border whitespace-nowrap"
    :class="tone.soft"
  >
    <span class="w-1.5 h-1.5 rounded-full" :class="tone.dot"></span>
    {{ formato }}
  </span>
</template>

<script setup>
// Etiqueta de estado de trámite.
// Muestra el estado formateado (formatoEstado) con un punto de color y clases
// de fondo/borde según su tono (toneEstado), opcionalmente en mayúsculas.
import { computed } from 'vue';
import { formatoEstado, toneEstado } from '../utils/estados';

const props = defineProps({
  estado: { type: String, required: true },
  upper: { type: Boolean, default: false }, // true para mostrar en mayúsculas.
});

const tone = computed(() => toneEstado(props.estado));
const formato = computed(() => {
  const t = formatoEstado(props.estado);
  return props.upper ? t.toUpperCase() : t;
});
</script>