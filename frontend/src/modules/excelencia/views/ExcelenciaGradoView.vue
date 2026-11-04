<template>
  <AppShell title="Solicitud de Excelencia Académica" subtitle="Titulación por promedio sobresaliente verificado por el HCC">
    <div v-if="cargando" class="card flex items-center justify-center gap-2 py-16 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando tu solicitud de Excelencia Académica...
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
          El módulo de Excelencia Académica requiere tu información académica registrada
          para iniciar la solicitud.
        </p>
        <router-link to="/estudiante" class="btn-primary px-6 py-2.5 mt-1">
          <AppIcon name="arrow-left" :size="15" />
          Ir a mi Portal de Estudiante
        </router-link>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SIN SOLICITUD ACTIVA: formulario de presentación -->
    <!-- ============================================================== -->
    <div v-else-if="!excelenciaActiva" class="space-y-6">
      <!-- Titulado: el flujo concluded con éxito -->
      <div
        v-if="titulado"
        class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 p-6 sm:p-8 text-center"
      >
        <div class="mx-auto w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-300 mb-4">
          <AppIcon name="award" :size="34" />
        </div>
        <h2 class="text-2xl font-extrabold text-emerald-300">¡FELICIDADES!</h2>
        <p class="mt-2 text-sm font-medium text-emerald-400 max-w-md mx-auto">
          Tu titulación por Excelencia Académica concluyó con éxito. Ya no puedes
          realizar más solicitudes de trámite.
        </p>
      </div>

      <!-- Rechazado mediante Resolución del HCC: puede optar por otra modalidad -->
      <div
        v-else-if="rechazado"
        class="rounded-xl bg-red-900/50 border border-red-500/30 p-4 flex items-start gap-3 text-red-400"
      >
        <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
        <p class="text-sm font-medium">
          Tu solicitud de Excelencia Académica fue rechazada mediante la Resolución del HCC
          <strong>{{ resolucionAdmision.numero_resolucion || '—' }}</strong>
          <template v-if="resolucionAdmision.fecha_resolucion">
            del {{ fechaLegible(resolucionAdmision.fecha_resolucion) }}
          </template>.
          <template v-if="promedioVerificado !== null && promedioVerificado < notaMinima">
            El promedio verificado ({{ promedioVerificado }}) es inferior al mínimo de
            <strong>{{ notaMinima }}</strong> exigido por la modalidad.
          </template>
          Puedes optar por otra modalidad de titulación.
        </p>
      </div>

      <!-- Formulario de solicitud -->
      <div v-else class="card overflow-hidden">
        <div class="h-2 bg-orange"></div>
        <div class="p-6 sm:p-8">
          <div class="flex flex-wrap items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="award" :size="28" />
            </div>
            <div>
              <h2 class="text-lg font-extrabold text-white">Solicitud de Excelencia Académica</h2>
              <p class="text-sm text-slate-400">
                Presenta tu solicitud declarando tu promedio general; KARDEX lo verifica
                y, si cumple el mínimo, registra la Resolución del HCC que te habilita.
              </p>
            </div>
          </div>

          <!-- Resumen de las etapas del flujo -->
          <div class="mt-6 grid sm:grid-cols-3 gap-2">
            <div
              v-for="fase in FASES_EXCELENCIA"
              :key="fase.id"
              class="rounded-xl px-3 py-3 ring-1 ring-white/10 bg-white/5 text-center"
            >
              <AppIcon :name="fase.icon" :size="18" class="mx-auto text-orange-300 mb-1" />
              <p class="text-[11px] font-bold text-white uppercase tracking-wide">{{ fase.label }}</p>
            </div>
          </div>

          <form @submit.prevent="enviarSolicitud" class="mt-7 grid sm:grid-cols-2 gap-5">
            <div>
              <label class="label" for="excelencia-promedio">Promedio general · obligatorio</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                  <AppIcon name="chart" :size="18" />
                </span>
                <input
                  id="excelencia-promedio"
                  v-model="promedio"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="input pl-10"
                  placeholder="Ej: 87.50"
                />
              </div>
              <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-400">
                <AppIcon name="info" :size="13" />
                Se habilita con {{ notaMinima }} o más. KARDEX verifica la nota y puede
                corregirla antes de consolidarla en tu perfil.
              </p>
              <p v-if="promedioDebajoDelMinimo" class="mt-1.5 flex items-center gap-1.5 text-xs text-amber-300">
                <AppIcon name="alert-triangle" :size="13" />
                Con {{ promedio }} no alcanzarías el mínimo: KARDEX rechazará la solicitud.
              </p>
            </div>

            <div>
              <label class="label" for="excelencia-observaciones">Observaciones (opcional)</label>
              <textarea
                id="excelencia-observaciones"
                v-model="observaciones"
                rows="3"
                class="input resize-none"
                placeholder="Indica algún dato adicional para KARDEX..."
              ></textarea>
            </div>

            <div class="sm:col-span-2 flex items-center justify-between gap-3 pt-2">
              <p v-if="!promedioValido" class="text-xs text-slate-400 inline-flex items-center gap-1.5">
                <AppIcon name="info" :size="14" />
                Ingrese su promedio general (entre 0 y 100).
              </p>
              <p v-else class="text-xs font-semibold text-emerald-300 inline-flex items-center gap-1.5">
                <AppIcon name="check" :size="14" />
                Listo para enviar.
              </p>
              <button
                type="submit"
                class="btn-primary px-6 py-2.5 shrink-0"
                :disabled="excelenciaStore.enviando || !promedioValido"
              >
                <AppIcon v-if="excelenciaStore.enviando" name="loader" :size="15" class="animate-spin" />
                <AppIcon v-else name="send" :size="15" />
                {{ excelenciaStore.enviando ? 'Enviando...' : 'Enviar Solicitud' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- CON SOLICITUD ACTIVA: seguimiento del flujo -->
    <!-- ============================================================== -->
    <div v-else class="space-y-6">
      <SeguimientoExcelencia :tramite="tramite" :nota-minima="notaMinima" />
    </div>
  </AppShell>
</template>

<script setup>
// Vista del Módulo de Excelencia Académica (lado del estudiante).
//
// Dos modos:
//   - Sin solicitud activa: formulario para presentar la solicitud declarando
//     el promedio general (KARDEX lo verifica después).
//   - Con solicitud activa: seguimiento del avance que registra KARDEX
//     (verificación y admisión, Resolución del HCC con lectura del acta y
//     reporte final).
import { ref, computed, onMounted } from 'vue';
import { useTramitesStore } from '@/modules/tramites';
import { useEstudianteStore } from '@/modules/estudiantes';
import { useToastStore } from '@/core/stores/toast';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import SeguimientoExcelencia from '../components/SeguimientoExcelencia.vue';
import { useExcelenciaStore } from '../stores/excelencia';
import {
    NOTA_MINIMA,
    esFlujoExcelencia,
    promedioVerificado as promedioVerificadoDelTramite,
    resolucionAdmision as resolucionAdmisionDelTramite,
} from '../utils/flujoExcelencia';
import { fechaLegible } from '@/modules/kardex/utils/formato';

const tramitesStore = useTramitesStore();
const estudianteStore = useEstudianteStore();
const excelenciaStore = useExcelenciaStore();
const toastStore = useToastStore();

const cargando = ref(true);
const promedio = ref('');
const observaciones = ref('');

// Etapas del flujo para mostrar al estudiante el camino que sigue su solicitud.
const FASES_EXCELENCIA = [
    { id: 'verificacion_promedio', label: 'Verificación y admisión', icon: 'clipboard' },
    { id: 'resolucion_hcc', label: 'Resolución y lectura', icon: 'shield' },
    { id: 'reporte', label: 'Reporte final', icon: 'file-text' },
];

const notaMinima = computed(() => Number(excelenciaStore.config?.nota_minima ?? NOTA_MINIMA));

const tramite = computed(() => tramitesStore.tramiteActivo);
const promedioVerificado = computed(() => promedioVerificadoDelTramite(tramite.value));
const resolucionAdmision = computed(() => resolucionAdmisionDelTramite(tramite.value));

// El trámite activo solo interesa si pertenece a la modalidad Excelencia
// Académica: si es de otra modalidad, el estudiante puede optics por otra.
const esExcelencia = computed(() => esFlujoExcelencia(tramite.value));

const terminal = computed(() => ['rechazado', 'titulado'].includes(tramite.value?.estado_actual));

// Mientras el trámite sigue vivo se muestra el seguimiento; al terminar, la
// vista explica el resultado y ofrece presentar una nueva solicitud.
const excelenciaActiva = computed(() => esExcelencia.value && !terminal.value);

const titulado = computed(() => esExcelencia.value && tramite.value?.estado_actual === 'titulado');
const rechazado = computed(() => esExcelencia.value && tramite.value?.estado_actual === 'rechazado');

const promedioNumero = computed(() => Number(promedio.value));
const promedioValido = computed(() =>
    promedio.value !== ''
    && !Number.isNaN(promedioNumero.value)
    && promedioNumero.value >= 0
    && promedioNumero.value <= 100,
);
const promedioDebajoDelMinimo = computed(() =>
    promedioValido.value && promedioNumero.value < notaMinima.value,
);

/**
 * Detalle legible de un fallo HTTP para los toasts de error: la primera
 * validación que reportó el backend, su mensaje, o un aviso de conexión.
 *
 * @param {Error} error    Error de axios.
 * @param {string} defecto Mensaje si la respuesta no trae detalles.
 * @returns {string} Texto para el toast.
 */
function detalleDeError(error, defecto) {
    if (!error.response) {
        return 'No hay conexión con el servidor. Verifique que el backend esté activo e intente de nuevo.';
    }
    const errores = error.response.data?.errors;
    const primero = errores && Object.values(errores).find((lista) => Array.isArray(lista) && lista.length);
    return primero?.[0] || error.response.data?.message || defecto;
}

/**
 * Envía la solicitud con el promedio general declarado. Al crearse, el
 * trámite queda pendiente de la verificación de KARDEX.
 */
async function enviarSolicitud() {
    if (!promedioValido.value) {
        toastStore.warning('Ingrese su promedio general (entre 0 y 100).');
        return;
    }

    try {
        await excelenciaStore.crearSolicitud(promedioNumero.value, observaciones.value.trim() || null);
        toastStore.success('Solicitud enviada. Queda pendiente de la verificación de KARDEX.');
        promedio.value = '';
        observaciones.value = '';
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
            excelenciaStore.cargarConfig(),
            tramitesStore.cargarTramiteActivo(),
        ]);
    } finally {
        cargando.value = false;
    }
});
</script>
