<template>
  <button
    v-if="esAdmin"
    type="button"
    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-slate-200 hover:bg-orange-500/15 hover:text-orange-200 hover:border-orange-500/40 transition-all duration-200"
    title="Volver atrás"
    @click="volver"
  >
    <AppIcon name="arrow-left" :size="16" />
    <span class="hidden sm:inline text-sm font-semibold">Volver atrás</span>
  </button>
</template>

<script setup>
// Botón de retorno jerárquico del panel administrativo.
// En las vistas de primer nivel (Docentes, Dashboard Kardex, Trámites en
// proceso y Trámites Concluidos) vuelve a Usuarios, primera opción del menú.
// En las vistas hijas (módulos del Dashboard Kardex) retrocede al nivel
// inmediato anterior; un nuevo clic desde ese nivel lleva a Usuarios.
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/modules/auth';
import AppIcon from '@/ui/AppIcon.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const esAdmin = computed(() => authStore.user?.rol === 'admin');

const VISTAS_PRIMER_NIVEL = [
  '/admin/docentes',
  '/kardex',
  '/kardex/tramites',
  '/kardex/tramites/concluidos',
];

const volver = () => {
  if (VISTAS_PRIMER_NIVEL.includes(route.path)) {
    router.push({ name: 'AdminDashboard' });
    return;
  }

  const anterior = router.options.history.state.back;
  if (typeof anterior === 'string' && anterior.startsWith('/')) {
    router.back();
    return;
  }

  router.push(route.path.startsWith('/kardex/') ? '/kardex' : { name: 'AdminDashboard' });
};
</script>
