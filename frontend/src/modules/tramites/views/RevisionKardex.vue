<template>
  <AppShell title="Trámites de Titulación" :subtitle="subtituloVista">
    <div id="seccion-estadisticas" class="scroll-mt-28">
      <div v-if="!soloConcluidos" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <StatCard label="Trámites en Proceso" :value="tramitesStore.tramitesPendientes.length" icon="folder" tone="amber" />
        <StatCard label="Documentos por Revisar" :value="porRevisar.length" icon="inbox" tone="orange" sublabel="Estado: solicitud presentada" />
        <StatCard label="Con Tutor Asignado" :value="conTutor.length" icon="user-check" tone="rose" />
        <StatCard label="Sin Tutor" :value="sinTutor.length" icon="info" tone="amber" />
      </div>

      <EstadisticasModalidades />
    </div>

    <div id="seccion-busqueda" class="scroll-mt-28 card overflow-hidden mb-6">
          <div class="flex flex-wrap items-center gap-3 p-5 border-b border-white/10">
            <div>
              <h2 class="text-lg font-bold text-white">{{ tab === 'concluidos' ? 'Trámites Concluidos' : 'Solicitudes en Proceso' }}</h2>
              <p class="text-sm text-slate-400">{{ subtituloVista }}</p>
            </div>
            <div class="ml-auto w-full md:w-auto">
              <div class="relative md:w-64">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                  <AppIcon name="search" :size="17" />
                </span>
                <input v-model="buscar" class="input pl-9 md:w-64" :placeholder="tab === 'pendientes' ? 'Buscar trámite...' : 'Buscar trámite concluido...'" />
              </div>
            </div>
          </div>
          <div v-if="cargando" class="flex items-center justify-center gap-2 py-16 text-slate-400">
            <AppIcon name="loader" :size="20" class="animate-spin" />
            Cargando trámites...
          </div>
          <div v-else-if="filtrados.length === 0" class="p-6">
            <EmptyState
              :icon="estadoVacio.icon"
              :title="estadoVacio.title"
              :message="estadoVacio.message"
            >
              <button v-if="estadoVacio.hayRegistros && buscar" class="btn-ghost" @click="buscar = ''">Limpiar búsqueda</button>
            </EmptyState>
          </div>
        </div>

        <div id="seccion-tramites" class="scroll-mt-28">
          <div v-if="!cargando && filtrados.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="tramite in filtrados" :key="tramite.id_tramite" class="card overflow-hidden flex flex-col hover:shadow-2xl hover:-translate-y-0.5 transition-all duration-200">
              <div class="relative h-2 bg-orange"></div>
              <div class="p-5 flex-1 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <h3 class="text-lg font-bold text-orange-300 truncate">{{ tramite.modalidad.nombre }}</h3>
                      <span class="text-xs text-slate-400">#{{ tramite.id_tramite }}</span>
                    </div>
                    <div class="flex items-center gap-2.5 mt-2">
                      <Avatar :nombres="tramite.estudiante?.user?.nombres || ''" :apellidos="tramite.estudiante?.user?.apellidos || ''" :size="10" />
                      <div>
                        <p class="font-semibold text-white text-sm leading-tight">
                          {{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}
                        </p>
                        <p class="text-xs text-slate-400">
                          Cod: {{ tramite.estudiante.registro_universitario }} · Promedio: {{ tramite.estudiante.promedio_global }}
                        </p>
                      </div>
                    </div>
                  </div>
                  <EstadoBadge :estado="tramite.estado_actual" />
                </div>

                <div v-if="tramite.estado_actual === 'solicitud_fecha_defensa' && tramite.hitos?.fecha_defensa_solicitada"
                     class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-500/20 border border-orange-500/30 text-orange-300 text-xs font-semibold">
                  <AppIcon name="calendar" :size="13" />
                  Solicitud de fecha de defensa pendiente de programar
                </div>
                <div v-else-if="tramite.estado_actual === 'solicitud_fecha_defensa'" class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/5 border border-white/10 text-slate-400 text-xs font-semibold">
                  <AppIcon name="calendar" :size="13" />
                  El estudiante puede solicitar su fecha de defensa
                </div>

                <div class="mt-4 space-y-1.5 text-sm">
                  <div class="flex items-center gap-2 text-slate-300">
                    <AppIcon name="user-check" :size="14" class="text-slate-400 shrink-0" />
                    <span class="text-slate-400 font-medium">Tutor:</span>
                    <span v-if="tramite.tutor">{{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}</span>
                    <span v-else class="text-orange-400 font-semibold">No asignado</span>
                  </div>
                  <div class="flex items-center gap-2 text-slate-300">
                    <AppIcon name="trending-up" :size="14" class="text-slate-400 shrink-0" />
                    <span class="text-slate-400 font-medium">Progreso:</span>
                    <span class="font-bold text-orange-300">{{ progresoEstado(tramite) }}%</span>
                  </div>
                </div>

                <div class="mt-3 flex-1">
                  <ProgressBar :value="progresoEstado(tramite)" />
                </div>

                <div class="mt-4 flex flex-wrap gap-1.5">
                  <a v-for="doc in tramite.documentos" :key="doc.id_documento"
                     :href="assetUrl(doc.ruta_archivo)" target="_blank"
                     class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-orange-300 bg-orange-500/10 border border-orange-500/30 hover:bg-orange-500/20 transition-all duration-200">
                    <AppIcon name="link" :size="13" />
                    {{ etiquetaDocumento(doc.tipo_documento) }}
                  </a>
                </div>

                <div class="mt-4 pt-4 border-t border-white/10 flex flex-wrap gap-2">
                  <button v-if="esGestion && tramite.estado_actual === 'solicitud_presentada'"
                          class="btn-primary flex-1 py-2.5" @click="abrirAprobar(tramite)">
                    <AppIcon name="check" :size="15" />
                    Aprobar
                  </button>
                  <button v-if="esGestion && tramite.estado_actual === 'solicitud_presentada'"
                          class="btn-rose flex-1 py-2.5" @click="abrirRechazo(tramite)">
                    <AppIcon name="x" :size="15" />
                    Rechazar
                  </button>
                  <button class="btn-warm flex-1 py-2.5" @click="gestionar(tramite)">
                    <AppIcon name="chevron-right" :size="15" />
                    Ver / Gestionar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

    <UiModal v-model="showAprobar" :title="`Aprobar solicitud #${tramiteAprobar?.id_tramite || ''}`" max-width="460px">
      <div class="bg-emerald-500/20 border border-emerald-500/30 rounded-xl p-3.5 text-sm text-emerald-300 mb-4">
        <p class="font-bold mb-1">Estudiante:</p>
        <p>{{ tramiteAprobar?.estudiante?.user?.nombres }} {{ tramiteAprobar?.estudiante?.user?.apellidos }}</p>
        <p class="text-xs mt-1">{{ tramiteAprobar?.modalidad?.nombre }}</p>
      </div>
      <p class="text-sm text-slate-300">Se dará por aprobada la documentación inicial y el trámite avanzará al siguiente estado del flujo.</p>
      <div class="flex justify-end gap-2 mt-5">
        <button class="btn-ghost px-4 py-2.5" @click="showAprobar = false">Cancelar</button>
        <button class="btn-primary px-4 py-2.5" :disabled="aprobando" @click="aprobar(tramiteAprobar)">
          <AppIcon v-if="aprobando" name="loader" :size="15" class="animate-spin" />
          <AppIcon v-else name="check" :size="15" />
          {{ aprobando ? 'Aprobando...' : 'Confirmar Aprobación' }}
        </button>
      </div>
    </UiModal>

    <UiModal v-model="showRechazo" :title="`Rechazar solicitud #${tramiteRechazo?.id_tramite || ''}`" max-width="460px">
      <div class="bg-red-900/50 border border-red-500/30 rounded-xl p-3.5 text-sm text-red-400 mb-4">
        <p class="font-bold mb-1">Estudiante:</p>
        <p>{{ tramiteRechazo?.estudiante?.user?.nombres }} {{ tramiteRechazo?.estudiante?.user?.apellidos }}</p>
        <p class="text-xs mt-1">{{ tramiteRechazo?.modalidad?.nombre }}</p>
      </div>
      <label class="label">Motivo del rechazo (obligatorio)</label>
      <textarea v-model="motivoRechazo" rows="4" class="input resize-none" placeholder="Explique el motivo para que el estudiante pueda corregirlo..." required></textarea>
      <div class="flex justify-end gap-2 mt-5">
        <button class="btn-ghost px-4 py-2.5" @click="showRechazo = false">Cancelar</button>
        <button class="btn-rose px-4 py-2.5" :disabled="!motivoRechazo" @click="rechazar(tramiteRechazo)">
          <AppIcon name="x" :size="15" />
          Confirmar Rechazo
        </button>
      </div>
    </UiModal>
  </AppShell>
</template>

<script setup>
// Vista de revisión de trámites para el personal académico (Kardex, Secretaría,
// Dirección, Admin, Concejo y Docente).
// Muestra métricas, estadísticas por modalidad y el listado filtrable de
// solicitudes en proceso, permitiendo aprobar/rechazar la documentación inicial
// y acceder a la gestión completa de cada trámite.
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/modules/auth';
import { useTramitesStore } from '../stores/tramites';
import { useToastStore } from '@/core/stores/toast';
import { progresoEstado, etiquetaDocumento } from '../utils/estados';
import { assetUrl } from '@/core/http/storage';
import { ROLES_GESTION, perteneceRol } from '@/core/roles';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';
import EstadoBadge from '../components/EstadoBadge.vue';
import StatCard from '@/ui/StatCard.vue';
import EstadisticasModalidades from '../components/EstadisticasModalidades.vue';
import ProgressBar from '@/ui/ProgressBar.vue';
import EmptyState from '@/ui/EmptyState.vue';
import UiModal from '@/ui/UiModal.vue';

const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const toastStore = useToastStore();

// Cuando `soloConcluidos` es true (ruta /kardex/tramites/concluidos) la vista
// muestra únicamente los trámites que finalizaron su flujo; en caso contrario
// lista las solicitudes en proceso.
const props = defineProps({
  soloConcluidos: { type: Boolean, default: false },
});

// Estado de la lista y de los modales de aprobación/rechazo.
const cargando = ref(false);
const buscar = ref('');                 // Texto de búsqueda.
const showRechazo = ref(false);         // Modal de rechazo.
const showAprobar = ref(false);         // Modal de aprobación.
const tramiteRechazo = ref(null);       // Trámite en el modal de rechazo.
const tramiteAprobar = ref(null);       // Trámite en el modal de aprobación.
const motivoRechazo = ref('');          // Motivo obligatorio para rechazar.
const aprobando = ref(false);           // true mientras se aprueba.

// Lista mostrada según la prop: 'pendientes' (default) o 'concluidos'.
const tab = computed(() => (props.soloConcluidos ? 'concluidos' : 'pendientes'));

// La gestión académica (Kardex, Secretaría, Dirección y Admin) es quien valida
// la documentación inicial (aprobación/rechazo). El Concejo y el Docente solo
// consultan.
const esGestion = computed(() => perteneceRol(authStore.user?.rol, ROLES_GESTION));

// Encabezado de la vista según la lista visible.
const subtituloVista = computed(() =>
  tab.value === 'concluidos'
    ? 'Historial de trámites que finalizaron su flujo'
    : 'Revisión y gestión de solicitudes en proceso'
);

// Subconjuntos útiles para las tarjetas de métricas.
const porRevisar = computed(() => tramitesStore.tramitesPendientes.filter((t) => t.estado_actual === 'solicitud_presentada'));
const conTutor = computed(() => tramitesStore.tramitesPendientes.filter((t) => t.tutor));
const sinTutor = computed(() => tramitesStore.tramitesPendientes.filter((t) => !t.tutor));

// Lista base según la pestaña visible (pendientes o concluidos).
const listaActual = computed(() =>
  tab.value === 'concluidos' ? tramitesStore.tramitesConcluidos : tramitesStore.tramitesPendientes
);

// Filtra los trámites por estudiante, código, modalidad o estado.
const filtrados = computed(() => {
  const q = buscar.value.toLowerCase().trim();
  if (!q) return listaActual.value;
  return listaActual.value.filter((t) => {
    const estudiante = `${t.estudiante.user.nombres} ${t.estudiante.user.apellidos}`.toLowerCase();
    return estudiante.includes(q)
      || t.estudiante.registro_universitario.toLowerCase().includes(q)
      || t.modalidad.nombre.toLowerCase().includes(q)
      || t.estado_actual.toLowerCase().includes(q);
  });
});

// Estado vacío según la pestaña visible (mensaje distinto si hay filtro).
const estadoVacio = computed(() => {
  const esConcluidos = tab.value === 'concluidos';
  return {
    icon: esConcluidos ? 'check-circle' : 'folder',
    title: esConcluidos ? 'Sin trámites concluidos' : 'Sin trámites para mostrar',
    message: buscar.value
      ? 'Ningún trámite coincide con la búsqueda.'
      : esConcluidos
        ? 'No hay trámites que hayan finalizado su flujo todavía.'
        : 'No hay solicitudes pendientes de revisión.',
    hayRegistros: esConcluidos ? tramitesStore.tramitesConcluidos.length : tramitesStore.tramitesPendientes.length,
  };
});

// Carga la lista de la pestaña indicada y la muestra.
const cargarTab = async (pestana) => {
  const esConcluidos = pestana === 'concluidos';
  cargando.value = true;
  try {
    if (esConcluidos) await tramitesStore.cargarConcluidos();
    else await tramitesStore.cargarPendientes();
  } catch (error) {
    toastStore.error('No se pudieron cargar los trámites: ' + (error.response?.data?.message || 'Error del servidor'));
    if (esConcluidos) tramitesStore.tramitesConcluidos = [];
    else tramitesStore.tramitesPendientes = [];
  } finally {
    cargando.value = false;
  }
};

onMounted(async () => {
  await cargarTab(tab.value);
});

/** Navega a la gestión del trámite indicado. */
const gestionar = (tramite) => {
  router.push({ name: 'GestionTramite', params: { id: tramite.id_tramite } });
};

/** Abre el modal de aprobación para el trámite indicado. */
const abrirAprobar = (tramite) => {
  tramiteAprobar.value = tramite;
  showAprobar.value = true;
};

/** Aprueba la documentación inicial vía POST /api/tramites/{id}/revisar (accion=aprobar). */
const aprobar = async (tramite) => {
  aprobando.value = true;
  try {
    await tramitesStore.revisarTramite(tramite.id_tramite, 'aprobar', 'Documentación inicial aprobada.');
    showAprobar.value = false;
    tramiteAprobar.value = null;
    toastStore.success(`Documentación de ${tramite.estudiante.user.nombres} ${tramite.estudiante.user.apellidos} aprobada. El trámite avanza en la línea de tiempo.`);
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo aprobar.'));
  } finally {
    aprobando.value = false;
  }
};

/** Abre el modal de rechazo limpio para el trámite indicado. */
const abrirRechazo = (tramite) => {
  tramiteRechazo.value = tramite;
  motivoRechazo.value = '';
  showRechazo.value = true;
};

/** Rechaza la documentación inicial vía POST /api/tramites/{id}/revisar (accion=rechazar). */
const rechazar = async (tramite) => {
  try {
    await tramitesStore.revisarTramite(tramite.id_tramite, 'rechazar', motivoRechazo.value);
    showRechazo.value = false;
    tramiteRechazo.value = null;
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo rechazar.'));
  }
};
</script>
