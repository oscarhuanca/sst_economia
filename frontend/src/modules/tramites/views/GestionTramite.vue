<template>
  <AppShell :title="`Trámite #${tramite?.id_tramite || ''}`" :subtitle="tramite ? `${tramite.estudiante.user.nombres} ${tramite.estudiante.user.apellidos} · ${tramite.modalidad.nombre}` : ''">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <p class="text-sm text-slate-400">
        Estado actual:
        <EstadoBadge v-if="tramite" :estado="tramite.estado_actual" class="ml-1" />
      </p>
      <button class="btn-ghost" @click="volver">
        <AppIcon name="arrow-left" :size="16" />
        {{ esDocente ? 'Ir a Mis Tutorías' : 'Volver a Trámites' }}
      </button>
    </div>

    <div v-if="cargando" class="card flex items-center justify-center gap-2 py-16 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando trámite...
    </div>

    <div v-else-if="errorCarga" class="card p-8 text-center">
      <AppIcon name="alert-triangle" :size="28" class="mx-auto text-orange-300 mb-3" />
      <p class="text-white font-semibold">{{ errorCarga }}</p>
      <button class="btn-ghost mt-4" @click="volver">
        <AppIcon name="arrow-left" :size="16" />
        Volver
      </button>
    </div>

    <div v-else-if="tramite" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="card p-6">
        <h3 class="font-bold text-white mb-4 inline-flex items-center gap-2">
          <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
            <AppIcon name="user" :size="17" />
          </span>
          Datos del Postulante
        </h3>
        <div class="flex items-center gap-4">
          <Avatar :nombres="tramite.estudiante?.user?.nombres || ''" :apellidos="tramite.estudiante?.user?.apellidos || ''" :size="14" />
          <div>
            <p class="text-lg font-bold text-white">
              {{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}
            </p>
            <p class="text-sm text-slate-400 capitalize">{{ rolLabel(tramite.estudiante.user.rol) }}</p>
          </div>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-xl bg-white/5 border border-white/10 p-3">
            <dt class="text-xs text-slate-400 font-semibold uppercase">Código</dt>
            <dd class="font-semibold text-white">{{ tramite.estudiante.registro_universitario }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 p-3">
            <dt class="text-xs text-slate-400 font-semibold uppercase">Promedio</dt>
            <dd class="font-semibold text-white">{{ tramite.estudiante.promedio_global ?? '—' }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 p-3">
            <dt class="text-xs text-slate-400 font-semibold uppercase">Email</dt>
            <dd class="font-semibold text-white">{{ tramite.estudiante.email || '—' }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 p-3">
            <dt class="text-xs text-slate-400 font-semibold uppercase">Teléfono</dt>
            <dd class="font-semibold text-white">{{ tramite.estudiante.telefono || '—' }}</dd>
          </div>
        </dl>
      </div>

      <div class="card p-6">
        <h3 class="font-bold text-white mb-4 inline-flex items-center gap-2">
          <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
            <AppIcon name="graduation" :size="17" />
          </span>
          Modalidad
        </h3>
        <p class="text-lg font-bold text-orange-300">{{ tramite.modalidad.nombre }}</p>
        <p class="mt-2 text-sm text-slate-300">{{ tramite.modalidad.descripcion || '' }}</p>
        <div class="mt-4 rounded-xl bg-orange-500/10 border border-orange-500/30 p-3.5 text-sm text-orange-300">
          <p class="font-bold mb-1 inline-flex items-center gap-1.5">
            <AppIcon name="info" :size="15" />
            Requisitos mínimos
          </p>
          <p class="mt-0.5">{{ tramite.modalidad.requisitos_minimos || 'Sin requisitos registrados.' }}</p>
        </div>
      </div>

      <div class="lg:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card p-6">
          <h3 class="font-bold text-white mb-4 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="user-check" :size="17" />
            </span>
            Tutor Asignado
          </h3>
          <div v-if="tramite.tutor" class="flex items-center gap-3">
            <Avatar :nombres="tramite.tutor?.nombres || ''" :apellidos="tramite.tutor?.apellidos || ''" :size="12" />
            <div>
              <p class="font-semibold text-white">{{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}</p>
              <p class="text-xs text-slate-400">{{ tramite.tutor.email }}</p>
            </div>
          </div>
          <div v-else class="text-sm text-orange-400 font-medium">No hay tutor asignado todavía.</div>

          <div v-if="esGestion && tramite.tutor" class="mt-4 pt-4 border-t border-white/10">
            <label class="label">Actualizar / Cambiar tutor</label>
            <div class="flex flex-col sm:flex-row gap-2">
              <select v-model="tutorSeleccionado" class="input flex-1 min-w-0">
                <option value="" disabled>Seleccione un docente tutor...</option>
                <option v-for="doc in tramitesStore.docentes" :key="doc.id_docente" :value="doc.id_docente">
                  {{ doc.nombres }} {{ doc.apellidos }}
                </option>
              </select>
              <button class="btn-primary w-full sm:w-auto shrink-0" :disabled="asignando || !tutorSeleccionado" @click="asignarTutor">
                <AppIcon v-if="asignando" name="loader" :size="15" class="animate-spin" />
                <AppIcon v-else name="check" :size="15" />
                Cambiar
              </button>
            </div>
          </div>
          <div v-else-if="esGestion && !tramite.tutor" class="mt-4 pt-4 border-t border-white/10">
            <p class="text-sm text-slate-400">
              La asignación del tutor se realiza desde el paso
              <span class="font-semibold text-slate-200">“Tutor Asignado”</span>
              de la línea de tiempo.
            </p>
          </div>
        </div>

        <div class="card p-6">
          <h3 class="font-bold text-white mb-4 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="file-text" :size="17" />
            </span>
            Documentos Presentados
          </h3>
          <div v-if="tramite.documentos.length" class="space-y-2">
            <a v-for="doc in tramite.documentos" :key="doc.id_documento"
               :href="assetUrl(doc.ruta_archivo)" target="_blank"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-orange-300 bg-orange-500/10 border border-orange-500/30 hover:bg-orange-500/20 transition-all duration-200">
              <AppIcon name="link" :size="16" />
              {{ etiquetaDocumento(doc.tipo_documento) }}
              <span class="ml-auto text-xs text-slate-400">{{ new Date(doc.created_at).toLocaleDateString('es-BO') }}</span>
            </a>
          </div>
          <p v-else class="text-sm text-slate-400">Sin documentos registrados.</p>
        </div>
      </div>

      <div class="lg:col-span-2 card overflow-hidden">
        <div class="h-2 bg-orange"></div>
        <div class="p-6 sm:p-8">
          <TimelineTramite
            :tramite="tramite"
            :title="esDocente ? 'Seguimiento de la Tutoría' : 'Historial de Seguimiento'"
            :asignar-tutor-en-flujo="esGestion"
            :docentes="tramitesStore.docentes"
            :asignando-tutor="asignando"
            @asignar-tutor="asignarTutor"
          />
        </div>
      </div>

      <div v-if="esGestion" class="lg:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Programación de la fecha de defensa de TESIS: llega la solicitud del estudiante -->
        <div v-if="esGestion && tramite.estado_actual === 'solicitud_fecha_defensa' && esTesis" class="card p-6">
          <h3 class="font-bold text-white mb-2 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="calendar" :size="17" />
            </span>
            Programación de la Fecha de Defensa
          </h3>
          <p class="text-sm text-slate-400 mb-4">
            El estudiante solicitó una fecha para su defensa. Requiere que se le programe una fecha.
          </p>
          <div v-if="tramite.hitos?.fecha_defensa_sugerida"
               class="rounded-xl bg-orange-500/10 border border-orange-500/30 p-3.5 text-sm text-orange-300 mb-4">
            <p class="font-bold mb-0.5">Fecha sugerida por el estudiante</p>
            <p class="font-semibold">{{ formatoFechaLarga(tramite.hitos.fecha_defensa_sugerida) }}</p>
          </div>
          <div class="flex flex-col sm:flex-row gap-2">
            <input v-model="fechaDefensa" type="date" :min="hoyISO" class="input flex-1 min-w-0" />
            <button class="btn-primary w-full sm:w-auto shrink-0" :disabled="programandoFecha || !fechaDefensa" @click="programarFechaDefensa">
              <AppIcon v-if="programandoFecha" name="loader" :size="15" class="animate-spin" />
              <AppIcon v-else name="calendar-check" :size="15" />
              {{ programandoFecha ? 'Programando...' : 'Programar Fecha' }}
            </button>
          </div>
          <p class="text-xs text-slate-400 mt-2">Al programar la fecha, el trámite pasa a "Defensa programada" y el estudiante la verá en su seguimiento.</p>
        </div>

        <!-- Validación de la solicitud de TESIS: 3 documentos obligatorios -->
        <div v-if="esGestion && tramite.estado_actual === 'solicitud_presentada' && esTesis" class="card p-6">
          <h3 class="font-bold text-white mb-2 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="graduation" :size="17" />
            </span>
            Validación de Solicitud de Tesis
          </h3>
          <p class="text-sm text-slate-400 mb-4">Verifica los 3 documentos obligatorios. Aprobar envía la solicitud al Consejo Universitario.</p>
          <ul class="space-y-2 mb-4">
            <li v-for="doc in tramite.documentos" :key="doc.id_documento"
                class="flex items-center gap-2.5 text-sm">
              <AppIcon name="check-circle" :size="16" class="text-emerald-400 shrink-0" />
              <a :href="assetUrl(doc.ruta_archivo)" target="_blank" class="font-semibold text-orange-300 hover:text-orange-200 hover:underline">
                {{ etiquetaDocumento(doc.tipo_documento) }}
              </a>
              <span class="ml-auto text-xs text-slate-400">{{ new Date(doc.created_at).toLocaleDateString('es-BO') }}</span>
            </li>
          </ul>
          <textarea v-model="observacionesRev" rows="3" class="input resize-none mb-3" placeholder="Observaciones (obligatorio para rechazar)"></textarea>
          <div class="flex gap-2">
            <button class="btn-primary flex-1" @click="revisar('aprobar')">
              <AppIcon name="check" :size="15" />
              Aprobar y Enviar al Consejo
            </button>
            <button class="btn-rose flex-1" @click="revisar('rechazar')">
              <AppIcon name="x" :size="15" />
              Rechazar
            </button>
          </div>
        </div>

        <!-- Validación inicial genérica (resto de modalidades) -->
        <div v-else-if="esGestion && tramite.estado_actual === 'solicitud_presentada'" class="card p-6">
          <h3 class="font-bold text-white mb-2 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="inbox" :size="17" />
            </span>
            Revisión de Documentación Inicial
          </h3>
          <p class="text-sm text-slate-400 mb-4">Aprobar inicia el flujo de estados según la modalidad. Indica observaciones para registrar en el historial.</p>
          <textarea v-model="observacionesRev" rows="3" class="input resize-none mb-3" placeholder="Observaciones (obligatorio para rechazar)"></textarea>
          <div class="flex gap-2">
            <button class="btn-primary flex-1" @click="revisar('aprobar')">
              <AppIcon name="check" :size="15" />
              Aprobar
            </button>
            <button class="btn-rose flex-1" @click="revisar('rechazar')">
              <AppIcon name="x" :size="15" />
              Rechazar
            </button>
          </div>
        </div>

        <div class="card p-6">
          <h3 class="font-bold text-white mb-2 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="trending-up" :size="17" />
            </span>
            Avanzar Trámite
          </h3>
          <p class="text-sm text-slate-400 mb-4">Mueve el trámite al siguiente estado del flujo correspondiente.</p>
          <div v-if="siguientesEstados.length" class="space-y-3">
            <select v-model="nuevoEstado" class="input">
              <option value="" disabled>Seleccione el siguiente estado...</option>
              <option v-for="estado in siguientesEstados" :key="estado" :value="estado">
                {{ formatoEstado(estado).toUpperCase() }}
              </option>
            </select>
            <input v-model="observaciones" class="input" placeholder="Observaciones (opcional)">
            <p v-if="bloqueadoInvestSinTutor" class="flex items-center gap-1.5 text-xs font-semibold text-orange-300 bg-orange-500/10 border border-orange-500/30 rounded-xl px-3 py-2.5">
              <AppIcon name="user-plus" :size="15" class="shrink-0" />
              Asigne el tutor desde el paso “Tutor Asignado” de la línea de tiempo antes de pasar a Investigación en Desarrollo.
            </p>
            <button class="btn-primary w-full" :disabled="ejecutando || !nuevoEstado || bloqueadoInvestSinTutor" @click="ejecutarTransicion">
              <AppIcon v-if="ejecutando" name="loader" :size="15" class="animate-spin" />
              <AppIcon v-else name="send" :size="15" />
              {{ ejecutando ? 'Ejecutando...' : 'Ejecutar Transición' }}
            </button>
          </div>
          <p v-else class="text-sm text-slate-400">No hay más transiciones permitidas desde este estado.</p>
        </div>
      </div>

      <div v-else class="lg:col-span-2 rounded-xl bg-white/5 border border-white/10 p-4 flex items-start gap-3 text-orange-200 text-sm">
        <AppIcon name="info" :size="18" class="mt-0.5 shrink-0" />
        Estás viendo el seguimiento en modo lectura. Las validaciones de la solicitud las realizan Kardex, Secretaría y Dirección.
      </div>
    </div>
  </AppShell>
</template>

<script setup>
// Vista de gestión de un trámite concreto.
// Muestra los datos del postulante, modalidad, tutor, documentos y la línea de
// tiempo. La gestión académica (Kardex, Secretaría, Dirección, Admin) valida la
// documentación inicial (con sección dedicada para Tesis), asigna tutor y
// ejecuta transiciones; el docente ve en modo lectura la tutoría.
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { tramitesService } from '../services/tramites';
import { useAuthStore } from '@/modules/auth';
import { useTramitesStore } from '../stores/tramites';
import { useToastStore } from '@/core/stores/toast';
import { rolLabel, ROLES_GESTION, perteneceRol } from '@/core/roles';
import { formatoEstado, etiquetaDocumento } from '../utils/estados';
import { assetUrl } from '@/core/http/storage';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';
import EstadoBadge from '../components/EstadoBadge.vue';
import TimelineTramite from '../components/TimelineTramite.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const toastStore = useToastStore();

// Estado del trámite cargado y de las acciones en curso.
const tramite = ref(null);
const cargando = ref(false);            // true mientras se carga el trámite.
const errorCarga = ref('');             // Mensaje de error si el trámite no se pudo cargar.
const ejecutando = ref(false);          // true al ejecutar una transición.
const siguientesEstados = ref([]);      // Estados permitidos desde el actual.
const nuevoEstado = ref('');            // Estado destino seleccionado.
const observaciones = ref('');          // Observaciones para transicionar.
const observacionesRev = ref('');       // Observaciones para aprobar/rechazar.
const tutorSeleccionado = ref('');      // Docente elegido como tutor.
const asignando = ref(false);           // true mientras se asigna tutor.
const fechaDefensa = ref('');           // Fecha de defensa a programar (YYYY-MM-DD).
const programandoFecha = ref(false);    // true mientras se programa la fecha.

// Fecha mínima seleccionable (hoy).
const hoyISO = new Date().toISOString().slice(0, 10);

// Grupos de rol del usuario: gestión valida la documentación inicial, avanza
// por la máquina de estados y asigna tutor; el docente ve solo lectura.
const esDocente = computed(() => authStore.user?.rol === 'docente');
const esGestion = computed(() => perteneceRol(authStore.user?.rol, ROLES_GESTION));

// true si la modalidad del trámite es Tesis de Grado (validación con 3 documentos).
const esTesis = computed(() => tramite.value?.modalidad?.nombre === 'Tesis de Grado');

// true cuando se intenta avanzar a "Invest. en Desarrollo" sin tutor asignado:
// el botón se deshabilita y se indica que el tutor se asigna en la línea de tiempo.
const bloqueadoInvestSinTutor = computed(() =>
  nuevoEstado.value === 'investigacion_en_desarrollo' && !tramite.value?.tutor
);

/** Carga el detalle del trámite desde GET /api/tramites/{id}. */
const cargarTramite = async () => {
  cargando.value = true;
  try {
    const { data } = await tramitesService.detalle(route.params.id);
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
  } catch (error) {
    const mensaje = error.response?.data?.message || 'Verifique el acceso';
    toastStore.error('Error al cargar el trámite: ' + mensaje);
    errorCarga.value = mensaje;
  } finally {
    cargando.value = false;
  }
};

onMounted(async () => {
  // La gestión también carga el catálogo de docentes para el selector de tutor.
  if (esGestion.value) {
    await tramitesStore.cargarDocentes();
  }
  await cargarTramite();
});

/** Regresa a la vista principal según el rol del usuario. */
const volver = () => {
  if (esDocente.value) return router.push('/docente');
  if (authStore.user?.rol === 'admin') return router.push('/admin');
  router.push('/kardex/tramites');
};

/** Asigna el tutor seleccionado al trámite vía POST /api/tramites/{id}/asignar-tutor. */
const asignarTutor = async (idTutor = null) => {
  const id = idTutor || tutorSeleccionado.value;
  if (!id) return toastStore.warning('Seleccione un docente');
  asignando.value = true;
  try {
    tramite.value = await tramitesStore.asignarTutor(tramite.value.id_tramite, id);
    tutorSeleccionado.value = '';
    toastStore.success('Tutor asignado correctamente.');
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo asignar el tutor'));
  } finally {
    asignando.value = false;
  }
};

/** Formatea una fecha (YYYY-MM-DD) en formato largo en español. */
const formatoFechaLarga = (iso) => {
  if (!iso) return 'â€”';
  return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  });
};

/**
 * Ejecuta una transición al estado seleccionado vía POST /api/tramites/{id}/transicionar.
 */
const ejecutarTransicion = async () => {
  if (!nuevoEstado.value) return toastStore.warning('Seleccione un estado');
  ejecutando.value = true;
  try {
    const { data } = await tramitesService.transicionar(tramite.value.id_tramite, nuevoEstado.value, observaciones.value);
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
    nuevoEstado.value = '';
    observaciones.value = '';
    toastStore.success('Estado actualizado correctamente.');
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'Transición no permitida'));
  } finally {
    ejecutando.value = false;
  }
};

/**
 * Programa la fecha de defensa: avanza el trámite a `defensa_programada`
 * pasando la fecha en observaciones para que el backend calcule el hito.
 */
const programarFechaDefensa = async () => {
  if (!fechaDefensa.value) return toastStore.warning('Seleccione la fecha de defensa');
  programandoFecha.value = true;
  try {
    const { data } = await tramitesService.transicionar(
      tramite.value.id_tramite,
      'defensa_programada',
      fechaDefensa.value
    );
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
    fechaDefensa.value = '';
    toastStore.success('Fecha de defensa programada correctamente.');
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo programar la fecha'));
  } finally {
    programandoFecha.value = false;
  }
};

/**
 * Aprueba o rechaza la documentación inicial vía POST /api/tramites/{id}/revisar.
 * Para rechazar exige un motivo en `observacionesRev`.
 *
 * @param {string} accion 'aprobar' | 'rechazar'.
 */
const revisar = async (accion) => {
  if (accion === 'rechazar' && !observacionesRev.value) {
    return toastStore.warning('Debe ingresar un motivo para rechazar.');
  }
  try {
    const { data } = await tramitesService.revisar(
      tramite.value.id_tramite,
      accion,
      observacionesRev.value || (accion === 'aprobar' ? 'Documentación inicial aprobada.' : 'Solicitud rechazada.')
    );
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
    observacionesRev.value = '';
    toastStore.success(accion === 'aprobar' ? 'Documentación aprobada. El trámite avanza en la línea de tiempo.' : 'Solicitud rechazada.');
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo completar la acción'));
  }
};
</script>
