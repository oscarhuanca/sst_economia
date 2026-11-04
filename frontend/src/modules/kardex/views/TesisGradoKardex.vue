<template>
  <div class="space-y-6">
    <!-- Encabezado del módulo -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-orange text-white flex items-center justify-center shadow-lg shadow-orange-900/30">
        <AppIcon name="book" :size="24" />
      </div>
      <div>
        <h2 class="text-2xl font-bold text-white uppercase">Tesis de Grado</h2>
        <p class="text-sm text-slate-400 mt-0.5">Consulte el flujo de titulación del postulante y actualice el estado de su trámite.</p>
      </div>
    </div>

    <!-- Barra de filtro por CI/RU -->
    <form class="card p-4 sm:p-5" @submit.prevent="buscar">
      <label class="label" for="filtro-ci-ru">Filtrar por CI/RU</label>
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
            <AppIcon name="search" :size="18" />
          </span>
          <input
            id="filtro-ci-ru"
            v-model="identificador"
            type="text"
            class="input pl-10"
            placeholder=""
            autocomplete="off"
          />
        </div>
        <button
          type="submit"
          class="btn-primary shrink-0 justify-center"
          :disabled="!identificador.trim() || kardexStore.cargando"
        >
          <AppIcon v-if="kardexStore.cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="search" :size="18" />
          <span class="uppercase tracking-wide">Buscar</span>
        </button>
      </div>
    </form>

    <!-- Error -->
    <div
      v-if="kardexStore.errorMessage && !kardexStore.postulante"
      class="flex items-start gap-3 rounded-xl bg-red-900/50 border border-red-500/30 px-4 py-3 text-sm text-red-400"
    >
      <AppIcon name="alert-triangle" :size="18" class="shrink-0 mt-0.5" />
      {{ kardexStore.errorMessage }}
    </div>

    <!-- Estado vacío inicial -->
    <EmptyState
      v-if="!kardexStore.postulante && !kardexStore.errorMessage"
      icon="search"
      title="Consultar flujo de Tesis Grado del Estudiante"
      message="Filtre por Cédula de Identidad o Registro Universitario para actualizar el estado actual del flujo de titulación."
    />

    <template v-if="kardexStore.postulante">
      <!-- Resumen del postulante -->
      <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="w-14 h-14 shrink-0 rounded-2xl bg-orange text-white font-bold text-lg flex items-center justify-center shadow-lg">
            {{ iniciales(kardexStore.postulante) }}
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-white truncate">
              {{ kardexStore.postulante.nombres }} {{ kardexStore.postulante.apellidos }}
            </h3>
            <div class="flex flex-wrap gap-2 mt-1.5">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/5 border border-white/10 text-slate-300 rounded-full px-2.5 py-1">
                <AppIcon name="user" :size="12" /> CI: {{ kardexStore.postulante.ci }}
              </span>
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/5 border border-white/10 text-slate-300 rounded-full px-2.5 py-1">
                <AppIcon name="file-text" :size="12" /> R.U.: {{ kardexStore.postulante.registro_universitario }}
              </span>
            </div>
          </div>
          <button class="btn-ghost shrink-0" @click="reiniciar">
            <AppIcon name="x" :size="16" />
            Limpiar
          </button>
        </div>

        <!-- Título de tesis presentado por el estudiante (verificación en el módulo de perfil) -->
        <div v-if="esTesis && tituloRegistrado" class="mt-4 pt-4 border-t border-white/10">
          <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Título de tesis presentado</p>
          <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="text-sm text-white font-semibold min-w-0 flex-1">{{ tituloRegistrado }}</p>
            <button class="btn-warm shrink-0 justify-center" @click="verificarTituloRegistrado">
              <AppIcon name="shield" :size="16" />
              <span class="uppercase tracking-wide">Verificar unicidad</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Sin trámite asignado -->
      <EmptyState
        v-if="!kardexStore.tramite"
        icon="inbox"
        title="El postulante aún no tiene un trámite asignado"
        message="Regístrelo en el módulo de Registro de Postulante para asignarle una modalidad de titulación."
      >
        <router-link :to="{ name: 'KardexRegistroPostulante' }" class="btn-warm">
          <AppIcon name="user-plus" :size="16" />
          Ir a Registro de Postulante
        </router-link>
      </EmptyState>

      <template v-if="kardexStore.tramite">
        <!-- Aviso cuando la modalidad asignada no es Tesis de Grado: el módulo no
             muestra el flujo ni permite actualizar el trámite de otra modalidad. -->
        <div
          v-if="!esTesis"
          class="flex items-start gap-3 rounded-xl bg-orange-500/15 border border-orange-500/30 px-4 py-3 text-sm text-orange-200"
        >
          <AppIcon name="info" :size="18" class="shrink-0 mt-0.5" />
          El postulante tiene asignada la modalidad <strong>{{ kardexStore.tramite.modalidad?.nombre }}</strong>.
          Este módulo gestiona únicamente la Tesis de Grado; no es posible actualizar su trámite aquí.
          Use el módulo correspondiente a su modalidad.
        </div>

        <template v-else-if="esFlujoPorModulos">
          <FlujoTitulacion
            ref="flujoTitulacionRef"
            :tramite="kardexStore.tramite"
            :docentes="docentes"
            @guardado="sincronizarEstado"
            @consultar-coincidencia="consultarCoincidencia"
          />
        </template>

        <template v-else>
        <!-- Estado actual del flujo -->
        <div class="card p-5">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="font-bold text-white uppercase flex items-center gap-2">
              <AppIcon name="trending-up" :size="18" class="text-orange-300" />
              Estado actual del flujo
            </h3>
            <EstadoBadge :estado="kardexStore.tramite.estado_actual" :upper="true" />
          </div>

          <TimelineTramite
            :tramite="kardexStore.tramite"
            title="Flujo de titulación"
            :asignar-tutor-en-flujo="true"
            :docentes="docentes"
            :asignando-tutor="asignandoTutor"
            @asignar-tutor="asignarTutor"
          />

          <!-- Plazos y fechas (hitos) -->
          <div v-if="countdown || hitosList.length" class="mt-6 pt-5 border-t border-white/10">
            <h4 class="font-bold text-white mb-3 flex items-center gap-2">
              <AppIcon name="calendar" :size="16" class="text-orange-300" />
              Plazos y fechas
            </h4>

            <div v-if="countdown" class="rounded-xl bg-orange-500/15 border border-orange-500/30 p-4 mb-3">
              <p class="text-sm font-bold text-orange-200">{{ countdown.titulo }}</p>
              <p class="text-xs text-orange-300/80 mt-0.5">{{ countdown.descripcion }}</p>
              <p class="text-xs text-orange-200 mt-2">
                Límite: <strong>{{ fechaLegible(countdown.fechaLimite) }}</strong>
              </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
              <div v-for="hito in hitosList" :key="hito.k" class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
                <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">{{ hito.label }}</p>
                <p class="text-sm font-semibold text-white mt-0.5">{{ hito.fecha }}</p>
              </div>
            </div>
          </div>

          <div v-if="descripcionEstado" class="mt-5 rounded-xl bg-white/5 border border-white/10 p-4 text-sm text-slate-300">
            {{ descripcionEstado }}
          </div>
        </div>

        <!-- Asignación del tutor (modalidades con paso "Tutor Asignado") -->
        <div v-if="conPasoTutor" class="card p-5">
          <h3 class="font-bold text-white uppercase flex items-center gap-2">
            <AppIcon name="user-check" :size="18" class="text-orange-300" />
            Tutor Asignado
          </h3>

          <div v-if="kardexStore.tramite.tutor" class="mt-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 px-4 py-3">
            <p class="flex items-center gap-2 text-sm font-semibold text-emerald-300">
              <AppIcon name="user" :size="16" />
              {{ kardexStore.tramite.tutor.nombres }} {{ kardexStore.tramite.tutor.apellidos }}
            </p>
            <p class="text-xs text-emerald-400/80 mt-0.5">{{ kardexStore.tramite.tutor.email }}</p>
          </div>
          <p v-else class="mt-3 text-sm font-medium text-orange-300 flex items-center gap-2">
            <AppIcon name="alert-triangle" :size="16" />
            Aún no se ha asignado un tutor al postulante.
          </p>

          <div class="mt-4 flex flex-col sm:flex-row gap-2">
            <select
              v-model="tutorSeleccionado"
              class="input flex-1 min-w-0"
              :disabled="asignandoTutor"
            >
              <option value="" disabled>
                {{ kardexStore.tramite.tutor ? 'Seleccione un docente para cambiar' : 'Seleccione un docente tutor...' }}
              </option>
              <option v-for="doc in docentes" :key="doc.id_docente" :value="doc.id_docente">
                {{ doc.nombres }} {{ doc.apellidos }}
              </option>
            </select>
            <button
              class="btn-warm shrink-0 justify-center"
              :disabled="asignandoTutor || !tutorSeleccionado"
              @click="asignarTutor(tutorSeleccionado)"
            >
              <AppIcon v-if="asignandoTutor" name="loader" :size="18" class="animate-spin" />
              <AppIcon v-else name="user-check" :size="18" />
              <span class="uppercase tracking-wide">{{ kardexStore.tramite.tutor ? 'Cambiar tutor' : 'Asignar tutor' }}</span>
            </button>
          </div>
          <p class="text-xs text-slate-400 mt-2">
            El tutor debe designarse antes de pasar a <strong>Investigación en Desarrollo</strong>.
          </p>
        </div>

        <!-- Actualización del estado del trámite -->
        <div class="card p-5">
          <h3 class="font-bold text-white uppercase flex items-center gap-2">
            <AppIcon name="zap" :size="18" class="text-orange-300" />
            Actualizar estado del trámite
          </h3>

          <template v-if="siguientes.length">
            <p class="text-sm text-slate-400 mt-1">
              Seleccione el siguiente estado del flujo para el trámite del postulante.
            </p>
            <div class="grid md:grid-cols-2 gap-3 mt-4">
              <div>
                <label class="label" for="nuevo-estado">Nuevo estado</label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="s in siguientes"
                    :key="s"
                    type="button"
                    class="text-sm font-semibold rounded-full px-4 py-2 ring-1 transition"
                    :class="nuevoEstado === s
                      ? 'bg-orange text-white ring-orange-500 shadow-lg shadow-orange-900/30'
                      : 'bg-white/5 text-slate-300 ring-white/20 hover:ring-orange-500'"
                    @click="nuevoEstado = s"
                  >
                    {{ formatoEstado(s) }}
                  </button>
                </div>
              </div>
              <div>
                <label class="label" for="observaciones">Observaciones (opcional)</label>
                <textarea
                  id="observaciones"
                  v-model="observaciones"
                  rows="3"
                  class="input resize-none"
                  placeholder="Comentario sobre la actualización del estado..."
                ></textarea>
              </div>
            </div>
            <div class="flex justify-end mt-4">
              <button class="btn-warm px-6" :disabled="!nuevoEstado || kardexStore.cargando" @click="actualizar">
                <AppIcon v-if="kardexStore.cargando" name="loader" :size="18" class="animate-spin" />
                <AppIcon v-else name="check" :size="18" />
                <span class="uppercase tracking-wide">Actualizar estado</span>
              </button>
            </div>
          </template>

          <template v-else>
            <p class="flex items-center gap-2 text-sm text-slate-400 mt-1">
              <AppIcon name="check-circle" :size="18" class="text-emerald-400" />
              El flujo del trámite ha concluido
              <span v-if="terminal">({{ formatoEstado(kardexStore.tramite.estado_actual) }}).</span>
            </p>
          </template>

          <p v-if="kardexStore.errorMessage && kardexStore.tramite" class="mt-3 text-sm text-red-400">
            {{ kardexStore.errorMessage }}
          </p>
        </div>
        </template>
      </template>
    </template>
  </div>
</template>

<script setup>
// Módulo B del Dashboard de KARDEX: Tesis de Grado.
//
// Filtra por CI/RU, muestra el flujo del trámite del postulante (línea de
// tiempo, plazos y estado actual) y permite actualizar el estado del trámite
// usando los estados siguientes permitidos por la máquina de estados.
//
// En el flujo por módulos (Tesis de Grado) la verificación de unicidad del
// título vive dentro del Módulo 1 "Perfil de Tesis", donde KARDEX aprueba o
// rechaza el perfil del postulante.
//
// Mientras hay un postulante en pantalla la vista se auto-actualiza cada 15 s
// para reflejar sin re-buscar el título y los documentos que el estudiante
// guardó en su perfil.
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useKardexStore } from '@/modules/kardex';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { TimelineTramite, EstadoBadge, formatoEstado, ESTADOS_TERMINALES, useTramitesStore } from '@/modules/tramites';
import { DESCRIPCION_ESTADO, countdownDe, tituloPerfilTesis } from '@/modules/tesis';
import { DOCENTES_MOCK } from '@/modules/kardex/mock/data';
import { esFlujoTitulacion as esFlujoDeModulos } from '@/modules/tramites/utils/flujoTitulacion';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import EmptyState from '@/ui/EmptyState.vue';
import FlujoTitulacion from '../components/flujo/FlujoTitulacion.vue';

const kardexStore = useKardexStore();
const toastStore = useToastStore();
const tramitesStore = useTramitesStore();

const identificador = ref('');
const nuevoEstado = ref(null);
const observaciones = ref('');
const tutorSeleccionado = ref('');
const asignandoTutor = ref(false);
const flujoTitulacionRef = ref(null);

// Catálogo de docentes del registro académico para asignar como tutor (con
// respaldo demo cuando la API no está disponible en modo simulado).
const docentes = computed(() =>
  tramitesStore.docentes.length ? tramitesStore.docentes : DOCENTES_MOCK,
);

// true cuando la modalidad del trámite incluye el paso "Tutor Asignado".
const conPasoTutor = computed(() =>
  kardexStore.tramite?.secuencia?.includes('tutor_asignado') || false,
);

// true cuando el trámite pertenece a la modalidad Tesis de Grado. Solo en ese
// caso se muestra el flujo y se habilitan las acciones del módulo.
const esTesis = computed(() => kardexStore.tramite?.modalidad?.nombre === 'Tesis de Grado');

// true cuando el trámite usa el flujo de titulación por módulos (Tesis de Grado).
const esFlujoPorModulos = computed(() => esFlujoDeModulos(kardexStore.tramite));

// Descripción legible del estado actual (flujo oficial de tesis).
const descripcionEstado = computed(() =>
  DESCRIPCION_ESTADO[kardexStore.tramite?.estado_actual] || '',
);

// Estados siguientes permitidos en el estado actual del trámite.
const siguientes = computed(() => kardexStore.tramite?.siguientes_estados || []);

// true cuando el trámite ya finalizó su flujo.
const terminal = computed(() => ESTADOS_TERMINALES.includes(kardexStore.tramite?.estado_actual));

// Cuenta regresiva (plazo de presentación / corrección) si aplica.
const countdown = computed(() => countdownDe(kardexStore.tramite));

// Título de tesis presentado por el estudiante con su perfil.
const tituloRegistrado = computed(() => tituloPerfilTesis(kardexStore.tramite));

// Hitos (fechas clave) registrados en el trámite.
const HITOS_LABEL = {  perfil_aprobado: 'Perfil aprobado',
  tutor_asignado: 'Tutor asignado',
  investigacion_en_desarrollo: 'Investigación en desarrollo',
  documento_final_presentado: 'Documento final presentado',
  limite_correccion: 'Límite de correcciones',
  limite_presentacion: 'Límite de presentación',
  solicitud_fecha_defensa: 'Fecha de defensa solicitada',
  defensa_programada: 'Defensa programada',
  defensa_en_curso: 'Defensa en curso',
  aprobado: 'Aprobado',
  reprobado: 'Reprobado',
};
const hitosList = computed(() =>
  Object.entries(kardexStore.tramite?.hitos || {}).map(([k, v]) => ({
    k,
    label: HITOS_LABEL[k] || formatoEstado(k),
    fecha: fechaLegible(v),
  })),
);

// Al recibir un nuevo trámite (o actualizarlo), preseleccionar el primer estado
// siguiente permitido y reiniciar el selector de tutor.
const sincronizarEstado = () => {
  const lista = esTesis.value ? (kardexStore.tramite?.siguientes_estados || []) : [];
  nuevoEstado.value = lista[0] || null;
  tutorSeleccionado.value = '';
};
// Auto-actualización: mientras hay un postulante en pantalla se re-consulta su
// trámite para que el módulo "Perfil de Tesis" reciba el título y los PDFs que
// el estudiante guardó, sin necesidad de pulsar "Buscar" de nuevo. La consulta
// es silenciosa (acción `refrescar`): no altera la carga en curso ni sustituye
// los datos reales si la API falla.
const INTERVALO_ACTUALIZACION_MS = 15000;
let temporizadorActualizacion = null;

const actualizarPostulante = () => {
  if (document.hidden || kardexStore.cargando || kardexStore.simulado) return;
  const ci = kardexStore.postulante?.ci;
  if (ci) kardexStore.refrescar(String(ci));
};

onMounted(() => {
  sincronizarEstado();
  tramitesStore.cargarDocentes();
  temporizadorActualizacion = setInterval(actualizarPostulante, INTERVALO_ACTUALIZACION_MS);
});

onUnmounted(() => {
  clearInterval(temporizadorActualizacion);
});

/** Filtra el flujo del postulante por CI/RU. */
const buscar = async () => {
  if (!identificador.value.trim() || kardexStore.cargando) return;
  const resultado = await kardexStore.consultar(identificador.value.trim());
  // La verificación de título pertenece al postulante consultado: se reinicia
  // para no mostrar el resultado de una búsqueda anterior.
  kardexStore.limpiarVerificacionTitulo();
  if (resultado) sincronizarEstado();
};

/**
 * Verifica la unicidad del título presentado por el postulante desde el módulo
 * "Perfil de Tesis", que es donde se aprueba o se rechaza el perfil.
 */
const verificarTituloRegistrado = () => {
  if (!esTesis.value || !tituloRegistrado.value) return;
  flujoTitulacionRef.value?.verificarTituloPerfil();
};

/** Carga en la barra de CI/RU el postulante de una coincidencia encontrada. */
const consultarCoincidencia = async (coincidencia) => {
  identificador.value = coincidencia.estudiante.ci;
  await buscar();
};

/** Actualiza el estado del trámite. */
const actualizar = async () => {
  if (!esTesis.value || !nuevoEstado.value || kardexStore.cargando || !kardexStore.tramite) return;
  const actualizado = await kardexStore.actualizarEstado(
    kardexStore.tramite.id_tramite,
    nuevoEstado.value,
    observaciones.value.trim(),
  );
  if (actualizado) {
    toastStore.success('Estado del trámite actualizado correctamente.');
    observaciones.value = '';
    sincronizarEstado();
  } else {
    toastStore.error(kardexStore.errorMessage || 'No se pudo actualizar el estado del trámite.');
  }
};

/** Limpia la consulta para empezar de nuevo. */
const reiniciar = () => {
  kardexStore.limpiar();
  kardexStore.limpiarVerificacionTitulo();
  identificador.value = '';
  nuevoEstado.value = null;
  observaciones.value = '';
  tutorSeleccionado.value = '';
};
/** Asigna (o reasigna) el tutor seleccionado al trámite. */
const asignarTutor = async (idTutor) => {
  if (!esTesis.value || !kardexStore.tramite || !idTutor || kardexStore.cargando) return;
  asignandoTutor.value = true;
  const actualizado = await kardexStore.asignarTutor(kardexStore.tramite.id_tramite, idTutor);
  asignandoTutor.value = false;
  if (actualizado) {
    toastStore.success('Tutor asignado correctamente.');
    tutorSeleccionado.value = '';
  } else {
    toastStore.error(kardexStore.errorMessage || 'No se pudo asignar el tutor.');
  }
};
</script>