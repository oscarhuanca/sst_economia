<template>
  <span
    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border whitespace-nowrap"
    :class="tone"
  >
    <AppIcon :name="rol === 'docente' ? 'user-check' : 'user'" :size="12" />
    {{ label }}
  </span>
</template>

<script setup>
// Etiqueta de rol de usuario.
// Muestra el nombre legible del rol (rolLabel) con su color (toneRol) e un
// icono distinto para docentes. Fuente de datos: core/roles (transversal).
import { computed } from 'vue';
import { rolLabel, toneRol } from '@/core/roles';
import AppIcon from './AppIcon.vue';

const props = defineProps({ rol: { type: String, required: true } });

const tone = computed(() => toneRol(props.rol));
const label = computed(() => rolLabel(props.rol));
</script>