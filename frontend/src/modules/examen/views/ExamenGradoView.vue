<template>
  <AppShell title="Solicitud de Examen de Grado" subtitle="Titulación mediante examen teórico y de especialidad">
    <div v-if="cargando" class="card flex items-center justify-center gap-2 py-16 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando tu solicitud de Examen de Grado...
    </div>

    <div
      v-else-if="!estudianteStore.perfilEstudiante?.id_estudiante"
      class="card overflow-hidden"
    >
      <div class="h-2 bg-orange"></div>
      <div class="p-8 flex flex-col items-center text-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
          <AppIcon name="user" :size="24" />
        </div>
        <h2 class="text-lg font-extrabold text-white">Necesitas completar tu perfil académico</h2>
        <p class="text-sm text-slate-400 max-w-md">
          El módulo de Examen de Grado requiere tu información académica registrada
          para iniciar la solicitud.
        </p>
        <router-link to="/estudiante" class="btn-primary px-6 py-2.5 mt-1">
          <AppIcon name="arrow-left" :size="15" />
          Ir a mi Portal de Estudiante
        </router-link>
      </div>
    </div>

    <!-- Con solicitud activa: el seguimiento propio de la modalidad -->
    <div v-else-if="examenEnCurso" class="space-y-6">
      <SeguimientoExamenGrado :tramite="tramite" />
      <router-link to="/estudiante" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-300 hover:text-orange-200">
        <AppIcon name="arrow-left" :size="15" />
        Volver a mi Portal de Estudiante
      </router-link>
    </div>

    <!-- Sin solicitud activa: presentación de la solicitud -->
    <div v-else class="space-y-6">
      <div v-if="esExamen && tramite?.estado_actual === 'rechazado'" class="rounded-xl bg-red-900/50 border border-red-500/30 p-4 flex items-start gap-3 text-red-400">
        <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
        <p class="text-sm font-medium">
          Tu solicitud anterior de Examen de Grado fue rechazada por KARDEX.
          Puedes presentar una nueva solicitud siempre que te queden intentos.
        </p>
      </div>

      <div v-else-if="esExamen && tramite?.estado_actual === 'titulado'" class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 p-6 text-center">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
          <AppIcon name="award" :size="28" />
        </div>
        <h2 class="mt-3 text-lg font-extrabold text-emerald-300">¡FELICIDADES!</h2>
        <p class="mt-1 text-sm text-emerald-400">
          Tu titulación por Examen de Grado concluyó con éxito. Ya no puedes realizar más solicitudes de trámite.
        </p>
      </div>

      <div v-if="otroTramiteEnCurso" class="rounded-xl bg-orange-500/10 border border-orange-500/30 p-4 flex items-start gap-3 text-orange-200">
        <AppIcon name="alert-triangle" :size="20" class="mt-0.5 shrink-0" />
        <p class="text-sm font-medium">
          Tienes un trámite en curso de otra modalidad. Finalízalo antes de solicitar el Examen de Grado.
        </p>
      </div>

      <div v-if="sinIntentos" class="rounded-xl bg-red-900/50 border border-red-500/30 p-4 flex items-start gap-3 text-red-400">
        <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
        <p class="text-sm font-medium">
          Agotaste los {{ maximo }} intentos permitidos. Debes optar por otra modalidad de titulación.
        </p>
      </div>

      <div class="card overflow-hidden">
        <div class="h-2 bg-gradient-to-r from-amber-400 to-orange-500"></div>
        <div class="p-6 sm:p-8">
          <div class="flex flex-wrap items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-300">
              <AppIcon name="clipboard" :size="28" />
            </div>
            <div>
              <h2 class="text-lg font-extrabold text-white">Solicitud de Examen de Grado</h2>
              <p class="text-sm text-slate-400">
                Presenta tu solicitud; KARDEX verificará tus intentos, registrará la
                Resolución del HCC y acompañará el sorteo y la asignación de notas.
              </p>
            </div>
          </div>

          <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-2">
            <div
              v-for="fase in FASES_EXAMEN"
              :key="fase.id"
              class="rounded-xl px-3 py-3 ring-1 ring-white/10 bg-white/5 text-center"
            >
              <AppIcon :name="fase.icon" :size="18" class="mx-auto text-orange-300 mb-1" />
              <p class="text-[11px] font-bold text-white uppercase tracking-wide">{{ fase.label }}</p>
            </div>
          </div>

          <div v-if="esExamen" class="mt-6 rounded-xl bg-white/5 ring-1 ring-white/10 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Intentos utilizados</p>
            <p class="text-sm font-extrabold text-white">
              {{ usados }} de {{ maximo }}
              <span class="ml-1 text-xs font-semibold text-slate-400">({{ restantes }} disponibles)</span>
            </p>
          </div>

          <form class="mt-7 flex flex-wrap items-center justify-between gap-3" @submit.prevent="enviarSolicitud">
            <p class="text-xs text-slate-400 inline-flex items-center gap-1.5">
              <AppIcon name="info" :size="14" />
              Al enviar, tu trámite queda pendiente de la verificación de KARDEX.
            </p>
            <button
              type="submit"
              class="btn-primary px-6 py-2.5 shrink-0"
              :disabled="examenStore.enviando || !puedeSolicitar"
            >
              <AppIcon v-if="examenStore.enviando" name="loader" :size="15" class="animate-spin" />
              <AppIcon v-else name="send" :size="15" />
              {{ examenStore.enviando ? 'Enviando...' : 'Enviar Solicitud' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
// Vista del Módulo de Examen de Grado (lado del estudiante).
//
// Dos modos:
//   - Sin solicitud activa: el estudiante presenta la solicitud; KARDEX verifica
//     los intentos y registra el resto del flujo por tarjetas.
//   - Con solicitud activa: se reutiliza el seguimiento del portal del
//     estudiante (avance, intentos y última actualización).
import { ref, computed, onMounted } from 'vue';
import { ESTADOS_TERMINALES, useTramitesStore } from '@/modules/tramites';
import { useEstudianteStore } from '@/modules/estudiantes';
import { useToastStore } from '@/core/stores/toast';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import SeguimientoExamenGrado from '@/modules/estudiantes/components/SeguimientoExamenGrado.vue';
import { useExamenStore } from '../stores/examen';
import { MAXIMO_INTENTOS, esFlujoExamen, intentosUsados, intentosRestantes } from '../utils/flujoExamen';

const tramitesStore = useTramitesStore();
const estudianteStore = useEstudianteStore();
const examenStore = useExamenStore();
const toastStore = useToastStore();

const cargando = ref(true);
const maximo = MAXIMO_INTENTOS;

const FASES_EXAMEN = [
  { id: 'intentos', label: 'Verificación de intentos', icon: 'clipboard' },
  { id: 'habilitacion', label: 'Habilitación HCC', icon: 'shield' },
  { id: 'examenes', label: 'Sorteo y asignación de notas', icon: 'award' },
  { id: 'reporte', label: 'Reporte final', icon: 'file-text' },
];

const tramite = computed(() => tramitesStore.tramiteActivo);
const esExamen = computed(() => esFlujoExamen(tramite.value));
const terminal = computed(() => esExamen.value && ESTADOS_TERMINALES.includes(tramite.value?.estado_actual));
const examenEnCurso = computed(() => esExamen.value && !terminal.value);
const otroTramiteEnCurso = computed(
  () => Boolean(tramite.value) && !esExamen.value && !ESTADOS_TERMINALES.includes(tramite.value?.estado_actual),
);
const usados = computed(() => intentosUsados(tramite.value));
const restantes = computed(() => intentosRestantes(tramite.value));
const sinIntentos = computed(() => esExamen.value && restantes.value === 0);
const puedeSolicitar = computed(
  () => !examenEnCurso.value
    && !otroTramiteEnCurso.value
    && !sinIntentos.value
    && !(esExamen.value && tramite.value?.estado_actual === 'titulado'),
);

/** Detalle legible de un fallo HTTP para el toast de error. */
function detalleDeError(error, defecto) {
  if (!error.response) {
    return 'No hay conexión con el servidor. Verifique que el backend esté activo e intente de nuevo.';
  }
  const errores = error.response.data?.errors;
  const primero = errores && Object.values(errores).find((lista) => Array.isArray(lista) && lista.length);
  return primero?.[0] || error.response.data?.message || defecto;
}

/** Presenta la solicitud y refresca el trámite activo para mostrar su seguimiento. */
async function enviarSolicitud() {
  if (!puedeSolicitar.value) return;
  try {
    await examenStore.crearSolicitud();
    toastStore.success('Solicitud enviada. Queda pendiente de la verificación de KARDEX.');
    await tramitesStore.cargarTramiteActivo();
  } catch (error) {
    toastStore.error('Error al enviar: ' + detalleDeError(error, 'Verifique los datos'));
  }
}

onMounted(async () => {
  cargando.value = true;
  try {
    await Promise.all([
      estudianteStore.cargarPerfil(false),
      tramitesStore.cargarTramiteActivo(),
    ]);
  } finally {
    cargando.value = false;
  }
});
</script>
