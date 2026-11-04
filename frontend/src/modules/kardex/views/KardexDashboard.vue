<template>
  <div class="h-screen w-full overflow-hidden bg-primary flex flex-col">
    <!-- Encabezado del dashboard -->
    <header class="shrink-0 z-40 bg-primary/70 backdrop-blur-xl border-b border-white/10">
      <div class="flex items-center gap-3 px-4 sm:px-6 py-3.5">
        <button
          class="p-2 rounded-lg text-slate-300 hover:bg-white/10 lg:hidden"
          title="Abrir menú"
          @click="abrirMenu"
        >
          <AppIcon name="menu" :size="22" />
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-orange flex items-center justify-center text-white shadow-lg shadow-orange-900/30">
            <AppIcon name="graduation" :size="22" />
          </div>
          <div class="leading-tight">
            <h1 class="text-lg sm:text-xl font-extrabold tracking-wide text-white uppercase">SSMTECO</h1>
            <p class="text-[11px] sm:text-xs text-slate-400">Sistema de Seguimiento de Titulación</p>
          </div>
        </div>

        <div class="ml-auto flex items-center gap-3">
          <div class="hidden sm:flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-white/5 border border-white/10">
            <Avatar :nombres="authStore.user?.nombres || ''" :apellidos="authStore.user?.apellidos || ''" :size="8" />
            <div class="leading-tight">
              <p class="text-sm font-semibold text-white">
                {{ authStore.user?.nombres }} {{ authStore.user?.apellidos }}
              </p>
              <p class="text-[11px] text-slate-400">{{ rolLabel(authStore.user?.rol) }}</p>
            </div>
          </div>
          <button
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-white/5 border border-white/10 text-slate-300 hover:bg-red-900/50 hover:text-red-400 hover:border-red-500/30 transition"
            title="Cerrar sesión"
            @click="cerrarSesion"
          >
            <AppIcon name="logout" :size="17" />
            <span class="hidden md:inline text-sm font-semibold">Cerrar sesión</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Aviso de modo demostración -->
    <div v-if="kardexStore.simulado" class="shrink-0 bg-orange-500/15 border-b border-orange-500/30 px-4 sm:px-6 py-2 text-xs sm:text-sm text-orange-300">
      <span class="inline-flex items-center gap-2">
        <AppIcon name="info" :size="15" />
        Modo demostración: la API no está disponible. Se muestran datos simulados.
      </span>
    </div>

    <div class="flex-1 flex min-h-0">
      <!-- Capa oscura del menú móvil -->
      <div
        v-if="abierto"
        class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"
        @click="abierto = false"
      ></div>

      <!-- Barra lateral con los 5 módulos -->
      <aside
        class="fixed inset-y-0 left-0 z-50 w-80 bg-primary/95 backdrop-blur-xl border-r border-white/10 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static lg:h-full"
        :class="abierto ? 'translate-x-0' : '-translate-x-full'"
      >
        <div v-if="abierto" class="flex items-center justify-between px-6 py-4 border-b border-white/10 lg:hidden">
          <p class="text-white font-bold uppercase tracking-wider">Módulos</p>
          <button class="p-2 rounded-lg text-slate-400 hover:bg-white/10" @click="abierto = false">
            <AppIcon name="x" :size="20" />
          </button>
        </div>

        <nav class="flex-1 px-5 py-6 space-y-4 overflow-y-auto">
          <p v-if="!abierto" class="px-1 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Módulos</p>
          <router-link
            v-for="(m, i) in modulos"
            :key="m.to"
            :to="m.to"
            :style="{ animationDelay: `${i * 40}ms` }"
            class="group flex items-center gap-3 px-3 py-3 rounded-2xl transition-all duration-200"
            :class="esActivo(m.to)
              ? 'bg-orange text-white shadow-lg shadow-orange-900/30'
              : 'bg-white/5 border border-white/10 text-slate-300 hover:ring-2 hover:ring-orange-500/40'"
            @click="abierto = false"
          >
            <span
              class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors duration-200"
              :class="esActivo(m.to)
                ? 'bg-white/20 text-white'
                : 'bg-orange-500/15 text-orange-300 group-hover:bg-orange-500/30'"
            >
              <AppIcon :name="m.icono" :size="20" />
            </span>
            <span
              class="text-sm uppercase tracking-wide font-bold transition-colors duration-200"
              :class="esActivo(m.to) ? '' : 'group-hover:text-orange-300'"
            >{{ m.label }}</span>
            <AppIcon
              name="chevron-right"
              :size="16"
              class="ml-auto shrink-0 transition-colors duration-200"
              :class="esActivo(m.to) ? 'text-white/70' : 'text-slate-500 group-hover:text-orange-300'"
            />
          </router-link>
        </nav>
      </aside>

      <!-- Contenido dinámico de cada módulo -->
      <main class="flex-1 overflow-y-auto min-w-0">
        <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">
          <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
// Shell del Dashboard de KARDEX: encabezado con marca "KARDEX", perfil y cierre
// de sesión; barra lateral oscura con los módulos, que se iluminan en naranja
// al pasar el cursor (mismo efecto que los botones del panel de administración);
// y un área de contenido dinámico donde cada módulo se monta en su ruta hija.
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/modules/auth';
import { useKardexStore } from '@/modules/kardex';
import { rolLabel } from '@/core/roles';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const kardexStore = useKardexStore();
const abierto = ref(false);

// Los módulos del dashboard (barra lateral) con su destino y color.
// El primer ítem es el panel general (estadísticas); el resto, los 5 módulos.
const modulos = [
    { to: '/kardex', label: 'Panel General', icono: 'dash-grid' },
    { to: '/kardex/postulante', label: 'Registro de Postulante', icono: 'user-plus' },
    { to: '/kardex/tesis', label: 'Tesis de Grado', icono: 'book' },
    { to: '/kardex/examen', label: 'Examen de Grado', icono: 'graduation' },
    { to: '/kardex/trabajo-dirigido', label: 'Trabajo Dirigido', icono: 'folder' },
    { to: '/kardex/excelencia-academica', label: 'Excelencia Academica', icono: 'award' }, 
    { to: '/kardex/reporte', label: 'Reporte de Modalidad', icono: 'chart' },
];

/** Determina si una ruta hija del módulo está activa.
 *  El panel general (/kardex) solo se activa con la ruta exacta, para que el
 *  resto de módulos conserve su propio resaltado. */
const esActivo = (to) => {
  if (to === '/kardex') return route.path === '/kardex';
  return route.path === to || route.path.startsWith(`${to}/`);
};

/** Abre el menú (móvil). */
const abrirMenu = () => { abierto.value = true; };

/** Cierra la sesión y redirige al login. */
const cerrarSesion = async () => {
    await authStore.logout();
    router.replace('/login');
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.fade-enter-from {
    opacity: 0;
    transform: translateY(6px);
}
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>