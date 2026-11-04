<template>
  <div class="relative" ref="contenedor">
    <button
      class="relative p-2 rounded-full bg-white/5 border border-white/10 text-slate-300 hover:text-white hover:bg-white/10 transition"
      title="Notificaciones"
      @click="toggle"
    >
      <AppIcon name="bell" :size="20" />
      <span
        v-if="contador !== null"
        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-[10px] font-bold text-white flex items-center justify-center ring-2 ring-primary"
      >
        {{ contador }}
      </span>
    </button>

    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="abierto"
        class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-primary border border-white/10 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
          <p class="text-sm font-bold text-white">
            Notificaciones
            <span v-if="store.noLeidas > 0" class="ml-1 text-xs font-semibold text-slate-400">
              ({{ store.noLeidas }} pendiente{{ store.noLeidas === 1 ? '' : 's' }})
            </span>
          </p>
          <button
            v-if="store.noLeidas > 0"
            class="text-xs font-semibold text-orange-300 hover:text-orange-200"
            @click="marcarTodas"
          >
            Marcar todas leídas
          </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <template v-if="store.items.length">
            <button
              v-for="n in store.items"
              :key="n.id_notificacion"
              class="w-full text-left px-4 py-3 flex gap-3 hover:bg-white/5 transition border-b border-white/5 last:border-0"
              :class="n.leida ? 'opacity-60' : 'bg-orange-500/10'"
              @click="pulsarNotificacion(n)"
            >
              <span
                class="mt-1.5 shrink-0 w-2 h-2 rounded-full"
                :class="n.leida ? 'bg-white/20' : 'bg-red-500'"
              ></span>
              <span class="min-w-0">
                <span class="block text-sm font-semibold text-white truncate">{{ n.titulo }}</span>
                <span v-if="n.mensaje" class="block text-xs text-slate-400 line-clamp-2">{{ n.mensaje }}</span>
                <span class="block text-[11px] text-slate-500 mt-0.5">{{ tiempoRelativo(n.created_at) }}</span>
              </span>
            </button>
          </template>
          <div v-else class="px-4 py-10 text-center">
            <p class="text-sm font-medium text-slate-300">Sin notificaciones</p>
            <p class="text-xs text-slate-500 mt-1">Aquí verás los avisos de tus trámites.</p>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
// Campana de notificaciones.
// Muestra un botón con contador de no leídas y un desplegable con la lista de
// avisos. Actualiza la lista al abrir, al ganar foco de la ventana y cada 30 s
// (5 s mientras esté abierto). Marca como leída la notificación al pulsarla y
// navega a su enlace si lo tiene.
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificacionesStore } from '../stores/notificaciones';
import AppIcon from '@/ui/AppIcon.vue';

const abierto = ref(false);        // Controla el desplegable.
const contenedor = ref(null);      // Ref al contenedor para detectar clics fuera.
const router = useRouter();
const store = useNotificacionesStore();

// Contador mostrado en el badge ('99+' si supera 99, null si no hay pendientes).
const contador = computed(() => {
    if (store.noLeidas === 0) return null;
    return store.noLeidas > 99 ? '99+' : store.noLeidas;
});

/** Alterna el desplegable y fuerza una recarga fresca de notificaciones al abrir. */
const toggle = () => {
    abierto.value = !abierto.value;
    if (abierto.value) store.cargar(true);
};

/**
 * Maneja el clic en una notificación: la marca como leída, cierra el
 * desplegable y navega a su enlace si existe.
 *
 * @param {Object} n Notificación ({ id_notificacion, leida, enlace, ... }).
 */
const pulsarNotificacion = async (n) => {
    if (!n.leida) await store.marcarLeida(n.id_notificacion);
    abierto.value = false;
    if (n.enlace) router.push(n.enlace);
};

/** Marca todas las notificaciones como leídas. */
const marcarTodas = async () => {
    await store.marcarTodasLeidas();
};

/** Cierra el desplegable cuando se hace clic fuera del contenedor. */
const fuera = (event) => {
    if (contenedor.value && !contenedor.value.contains(event.target)) abierto.value = false;
};

/** Convierte una fecha ISO a texto relativo ('ahora', 'hace 5 min', ...). */
const tiempoRelativo = (fechaIso) => {
    const fecha = new Date(fechaIso);
    if (Number.isNaN(fecha.getTime())) return '';
    const seg = Math.floor((Date.now() - fecha.getTime()) / 1000);
    if (seg < 60) return 'ahora';
    const min = Math.floor(seg / 60);
    if (min < 60) return `hace ${min} min`;
    const hor = Math.floor(min / 60);
    if (hor < 24) return `hace ${hor} h`;
    const dias = Math.floor(hor / 24);
    if (dias === 1) return 'ayer';
    return `hace ${dias} días`;
};

let intervalo = null;        // Refresco global cada 30 s.
let intervaloAbierto = null; // Refresco acelerado (5 s) mientras el panel está abierto.

/** Refresca las notificaciones al recuperar el foco de la ventana. */
const refrescoEnFoco = () => store.cargar(true);

onMounted(async () => {
    await store.cargar();
    intervalo = setInterval(() => store.cargar(true), 30000);
    document.addEventListener('click', fuera);
    window.addEventListener('focus', refrescoEnFoco);
});

onUnmounted(() => {
    clearInterval(intervalo);
    clearInterval(intervaloAbierto);
    document.removeEventListener('click', fuera);
    window.removeEventListener('focus', refrescoEnFoco);
});

// Al abrir el panel: refresco acelerado; al cerrarlo se detiene.
watch(abierto, (value) => {
    clearInterval(intervaloAbierto);
    if (value) {
        store.cargar(true);
        intervaloAbierto = setInterval(() => store.cargar(true), 5000);
    }
});
</script>