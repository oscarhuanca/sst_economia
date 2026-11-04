<template>
  <span
    class="inline-flex items-center justify-center rounded-full bg-orange text-white font-bold uppercase select-none"
    :class="[sizeClasses[sizeClave] || sizeClasses[10], extra]"
  >
    {{ iniciales }}
  </span>
</template>

<script setup>
// Avatar con las iniciales de una persona.
// Genera un círculo con degradado donde las primeras letras de nombres y
// apellidos aparecen en mayúsculas; admite distintos tamaños y clases extra.
import { computed } from 'vue';

const props = defineProps({
  nombres: { type: String, default: '' },
  apellidos: { type: String, default: '' },
  // Acepta Number o String para tolerar tanto :size="8" como size="8".
  size: {
    type: [Number, String],
    default: 10,                              // Clave de tamaño (8, 10, 12, 14, 16).
    validator: (v) => [8, 10, 12, 14, 16].includes(Number(v)),
  },
  extra: { type: [String, Array], default: '' },
});

// Clases Tailwind de dimensiones y fuente por tamaño.
const sizeClasses = {
  8: 'w-8 h-8 text-[11px]',
  10: 'w-10 h-10 text-sm',
  12: 'w-12 h-12 text-base',
  14: 'w-14 h-14 text-lg',
  16: 'w-16 h-16 text-xl',
};

// Normaliza el tamaño a número para indexar el mapa de clases.
const sizeClave = computed(() => Number(props.size) || 10);

// Iniciales (primera letra de nombres + apellidos); '?' si no hay datos.
const iniciales = computed(() => {
  const a = (props.nombres || '').trim().charAt(0);
  const b = (props.apellidos || '').trim().charAt(0);
  return `${a}${b}`.toUpperCase() || '?';
});
</script>