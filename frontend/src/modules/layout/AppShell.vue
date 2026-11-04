<template>
  <div class="h-screen w-full overflow-hidden relative bg-gradient-to-br from-primary via-primary to-primary">
    <div class="absolute top-[-20%] right-[-10%] w-[500px] h-[500px] bg-orange-600 rounded-full blur-[120px] opacity-30 pointer-events-none"></div>
    <div class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] bg-red-600 rounded-full blur-[120px] opacity-20 pointer-events-none"></div>
    <div class="flex h-full relative z-10">
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm lg:hidden"
        @click="open = false"
      ></div>

      <aside
        class="fixed inset-y-0 left-0 z-50 w-72 bg-primary/90 backdrop-blur-xl border-r border-white/10 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static lg:h-full"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
      >
        <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
          <div class="w-10 h-10 rounded-xl bg-orange flex items-center justify-center text-white shadow-lg">
            <AppIcon name="graduation" :size="22" />
          </div>
          <div>
            <p class="text-white font-bold leading-tight">Titulación UPEA</p>
            <p class="text-xs text-slate-400">Seguimiento de Modalidades</p>
          </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1">
          <p class="px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Menú</p>
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            @click="open = false"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
            :class="isActive(item.to) ? 'bg-orange text-white shadow-lg shadow-orange-900/40' : 'text-slate-300 hover:bg-white/5 hover:text-white'"
          >
            <AppIcon :name="item.icon" :size="19" />
            {{ item.label }}
          </router-link>
        </nav>
      </aside>

      <div class="flex-1 flex flex-col min-w-0 h-full">
        <header class="shrink-0 z-30 bg-primary/70 backdrop-blur-xl border-b border-white/10">
          <div class="flex items-center gap-3 px-4 sm:px-6 lg:px-8 py-4">
            <button class="p-2 rounded-lg text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 lg:hidden" @click="open = true">
              <AppIcon name="menu" :size="22" />
            </button>
            <div class="min-w-0">
              <h1 class="text-lg sm:text-xl font-bold text-white truncate">{{ title }}</h1>
              <p v-if="subtitle" class="text-xs sm:text-sm text-slate-400 truncate">{{ subtitle }}</p>
            </div>
            <div class="ml-auto flex items-center gap-3">
              <NotificationBell />
              <div class="hidden sm:flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-white/5 border border-white/10">
                <Avatar :nombres="authStore.user?.nombres || ''" :apellidos="authStore.user?.apellidos || ''" :size="8" />
                <span class="text-sm font-semibold text-slate-300">
                  {{ authStore.user?.nombres }} {{ authStore.user?.apellidos }}
                </span>
              </div>
              <button
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white/5 border border-white/10 text-slate-300 hover:bg-red-600/80 hover:text-white hover:border-red-500/40 transition-all duration-200"
                title="Cerrar Sesión"
                @click="logout"
              >
                <AppIcon name="logout" :size="17" />
                <span class="hidden md:inline text-sm font-semibold">Cerrar Sesión</span>
              </button>
            </div>
          </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
// Shell de layout principal de la aplicación (usado por todas las vistas).
// Renderiza la barra lateral con el menú según el rol, el encabezado con el
// usuario/notificaciones/logout y un <main> con <slot> para el contenido.
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/modules/auth';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';
import { NotificationBell } from '@/modules/notificaciones';

// Props: título y subtítulo mostrados en el encabezado.
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
});

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const open = ref(false); // Controla el drawer del sidebar en móviles.

// Bloquea el scroll del body mientras el drawer móvil está abierto.
watch(open, (value) => {
  document.body.style.overflow = value ? 'hidden' : '';
});

// Ítems del menú configurados según el rol del usuario autenticado.
const navItems = computed(() => {
  const rol = authStore.user?.rol;
  const items = [];
  if (rol === 'admin') items.push({ to: '/admin', label: 'Usuarios', icon: 'users' });
  if (rol === 'admin') items.push({ to: '/admin/docentes', label: 'Docentes', icon: 'user-check' });
  if (['kardex', 'secretaria', 'direccion', 'admin'].includes(rol)) {
    items.push({ to: '/kardex', label: 'Dashboard Kardex', icon: 'dash-grid' });
    items.push({ to: '/kardex/tramites', label: 'Trámites en Proceso', icon: 'folder' });
    items.push({ to: '/kardex/tramites/concluidos', label: 'Trámites Concluidos', icon: 'folder' });
  }
  if (rol === 'docente') items.push({ to: '/docente', label: 'Mis Tutorías', icon: 'book' });
  if (rol === 'estudiante') items.push({ to: '/estudiante', label: 'Mi Trámite', icon: 'graduation' });
  return items;
});

/** Determina si una ruta está activa (para resaltar el ítem del menú). */
const isActive = (to) => {
  // Dashboard Kardex: sus módulos viven bajo /kardex/... sin invadir /kardex/tramites.
  if (to === '/kardex') {
    return route.path === '/kardex'
      || (route.path.startsWith('/kardex/') && !route.path.startsWith('/kardex/tramites'));
  }
  if (to === '/kardex/tramites') {
    return route.path === '/kardex/tramites' || route.path.startsWith('/tramites/');
  }
  return route.path === to;
};

/** Cierra la sesión y redirige a la pantalla de login. */
const logout = async () => {
  await authStore.logout();
  router.replace('/login');
};
</script>
