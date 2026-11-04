<template>
  <AppShell title="Portal del Estudiante" subtitle="Seguimiento de tu modalidad de titulación">
    <template v-if="sinPerfil">
      <div class="max-w-2xl mx-auto">
        <div class="card overflow-hidden">
          <div class="h-2 bg-orange"></div>
          <div class="p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
              <AppIcon name="user" :size="26" />
            </div>
            <h1 class="mt-4 text-xl font-extrabold text-white">Tu perfil aún no está registrado</h1>
            <p class="mt-2 text-sm text-slate-400 leading-relaxed max-w-md mx-auto">
              Si aún no tienes cuenta, regístrate desde la pantalla de ingreso
              (pestaña "Registrarse"). Una vez registrado, Kardex o la Dirección
              de Carrera generarán tu usuario y contraseña de acceso.
            </p>
            <p class="mt-4 text-xs font-semibold text-slate-500">Contacta a la Dirección de Carrera.</p>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <StatCard label="Registro Universitario" :value="perfilEstudiante.registro_universitario" icon="file-text" tone="amber" />
        <StatCard label="Email" :value="perfilEstudiante.email || '—'" icon="mail" tone="rose" />
        <StatCard label="" :value="perfilEstudiante.promedio_global ?? '—'" icon="chart" tone="orange" />
        <StatCard
          label="Estado del Trámite"
          :value="tramitesStore.tramiteActivo ? formatoEstado(tramitesStore.tramiteActivo.estado_actual) : '—'"
          :icon="tramitesStore.tramiteActivo ? 'trending-up' : 'inbox'"
          :tone="tramitesStore.tramiteActivo ? 'amber' : 'rose'"
          :sublabel="tramitesStore.tramiteActivo ? tramitesStore.tramiteActivo.modalidad.nombre : 'Sin trámite activo'"
        />
      </div>

      <div class="grid lg:grid-cols-2 gap-4 mb-6">
        <div class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-slate-500 to-slate-400"></div>
          <div class="p-6">
            <h2 class="text-base font-extrabold text-white flex items-center gap-2">
              <AppIcon name="user" :size="18" class="text-orange-300" />
              Mis Datos
            </h2>
            <p class="mt-1 text-xs text-slate-400">Registrados al crear tu cuenta · solo lectura</p>
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400 font-semibold">CI</dt>
                <dd class="mt-0.5 font-semibold text-white">{{ perfilEstudiante.ci }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Registro Universitario</dt>
                <dd class="mt-0.5 font-semibold text-white">{{ perfilEstudiante.registro_universitario }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Nombre Completo</dt>
                <dd class="mt-0.5 font-semibold text-white">{{ displayNombres }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Fecha de Nacimiento</dt>
                <dd class="mt-0.5 font-semibold text-white">{{ formatoFechaLarga(perfilEstudiante.fecha_nacimiento) }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <div class="card overflow-hidden">
          <div class="h-2 bg-orange"></div>
          <div class="p-6">
            <h2 class="text-base font-extrabold text-white flex items-center gap-2">
              <AppIcon name="mail" :size="18" class="text-orange-300" />
              Datos de Contacto y Cuenta
            </h2>
            <p class="mt-1 text-xs text-slate-400">
              Tu usuario y contraseña los genera la instancia académica (Kardex).
              No puedes editar tu perfil ni tus credenciales.
            </p>
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Email</dt>
                <dd class="mt-0.5 font-semibold text-white">{{ perfilEstudiante.email || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Teléfono</dt>
                <dd class="mt-0.5 font-semibold text-white">{{ perfilEstudiante.telefono || '—' }}</dd>
              </div>
              <div v-if="perfilEstudiante.promedio_global !== null">
                <dt class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Promedio Global</dt>
                <dd class="mt-0.5 font-semibold text-white">{{ perfilEstudiante.promedio_global }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <div v-if="cargandoTramite" class="card flex items-center justify-center gap-2 py-16 text-slate-400">
        <AppIcon name="loader" :size="20" class="animate-spin" />
        Cargando tu trámite...
      </div>

      <div v-else class="space-y-6">
        <!-- Examen de Grado: el seguimiento ya renderiza sus propias tarjetas -->
        <div v-if="tramitesStore.tramiteActivo && esExamenActivo">
          <SeguimientoExamenGrado :tramite="tramitesStore.tramiteActivo" />
        </div>

        <!-- Excelencia Académica: el seguimiento propio de la modalidad -->
        <div v-else-if="tramitesStore.tramiteActivo && esExcelenciaActivo">
          <SeguimientoExcelencia
            :tramite="tramitesStore.tramiteActivo"
            :nota-minima="excelenciaStore.config?.nota_minima ?? NOTA_MINIMA"
          />
          <router-link to="/estudiante/excelencia" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-orange-300 hover:text-orange-200">
            <AppIcon name="arrow-left" :size="15" class="rotate-180" />
            Ir al módulo de Excelencia Académica
          </router-link>
        </div>

        <router-link
          v-else-if="tramitesStore.tramiteActivo && esTrabajoDirigidoActivo"
          to="/estudiante/trabajo-dirigido"
          class="card overflow-hidden relative block group hover:ring-2 hover:ring-orange-500/40 transition-all duration-200"
        >
          <div class="absolute inset-x-0 top-0 h-2 bg-orange"></div>
          <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300 group-hover:bg-orange-500/20 group-hover:scale-105 transition-all duration-200">
                <AppIcon name="folder" :size="28" />
              </div>
              <div>
                <h2 class="text-lg font-extrabold text-white transition-colors duration-200 group-hover:text-orange-300">Trabajo Dirigido · En Curso</h2>
                <p class="text-sm text-slate-400">
                  Estado:
                  <span class="font-semibold text-orange-300">{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</span>
                </p>
              </div>
            </div>
            <span class="btn-warm px-5 py-2.5">
              <AppIcon name="arrow-left" :size="15" class="rotate-180" />
              Continuar con el seguimiento
            </span>
          </div>
        </router-link>

        <div v-else-if="tramitesStore.tramiteActivo && !esTesisActivo" class="card overflow-hidden">
          <div class="h-2 bg-orange"></div>
          <div class="p-6 sm:p-8">
            <TimelineTramite :tramite="tramitesStore.tramiteActivo" title="Seguimiento de mi Titulación" />
          </div>
        </div>

        <router-link v-else-if="tramitesStore.tramiteActivo && esTesisActivo" to="/estudiante/tesis" class="card overflow-hidden relative block group hover:ring-2 hover:ring-orange-500/40 transition-all duration-200">
          <div class="absolute inset-x-0 top-0 h-2 bg-orange"></div>
          <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300 group-hover:bg-orange-500/20 group-hover:scale-105 transition-all duration-200">
                <AppIcon name="graduation" :size="28" />
              </div>
              <div>
                <h2 class="text-lg font-extrabold text-white transition-colors duration-200 group-hover:text-orange-300">Tesis de Grado · En Curso</h2>
                <p class="text-sm text-slate-400">
                  Estado:
                  <span class="font-semibold text-orange-300">{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</span>
                </p>
              </div>
            </div>
            <span class="btn-warm px-5 py-2.5">
              <AppIcon name="arrow-left" :size="15" class="rotate-180" />
              Continuar con el seguimiento
            </span>
          </div>
        </router-link>

        <template v-if="mostrarInicioSolicitud">
          <div v-if="terminalAprobada" class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 p-5 flex items-start gap-3 text-emerald-300">
            <AppIcon name="award" :size="22" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              <strong>¡FELICIDADES APROBADO!</strong> Tu {{ articuloModalidad }} fue aprobada
              y ya no puedes realizar más solicitudes de trámite.
            </p>
          </div>

          <div v-else-if="terminalReprobada" class="rounded-xl bg-red-900/50 border border-red-500/30 p-4 flex items-start gap-3 text-red-400">
            <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu {{ articuloModalidad }} no fue aprobada.
              <template v-if="reoptar.puede">
                Ya puedes volver a presentar tu solicitud para optar por una modalidad.
              </template>
              <template v-else>
                Podrás volver a presentar tu solicitud a partir del
                <strong>{{ formatoFechaLarga(reoptar.fechaHabilitacion) }}</strong>.
              </template>
            </p>
          </div>

          <div v-else-if="tramiteTerminado" class="rounded-xl bg-orange-500/10 border border-orange-500/30 p-4 flex items-start gap-3 text-orange-200">
            <AppIcon name="check-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu último trámite finalizó con estado
              <strong>{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</strong>.
              Puedes iniciar una nueva {{ articuloModalidad }} cuando lo necesites.
            </p>
          </div>

          <router-link to="/estudiante/tesis" class="card overflow-hidden relative block group hover:ring-2 hover:ring-orange-500/40 transition-all duration-200">
            <div class="absolute inset-x-0 top-0 h-2 bg-orange"></div>
            <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300 group-hover:bg-orange-500/20 group-hover:scale-105 transition-all duration-200">
                  <AppIcon name="graduation" :size="28" />
                </div>
                <div>
                  <h2 class="text-lg font-extrabold text-white transition-colors duration-200 group-hover:text-orange-300">Tesis de Grado</h2>
                  <p class="text-sm text-slate-400">Aqui puedes solicitar la modalidad de graduación de Tesis de Grado y realizar el seguimiento correspondiente.</p>
                </div>
              </div>
              <span class="btn-warm px-5 py-2.5">
                <AppIcon name="arrow-left" :size="15" class="rotate-180" />
                Solicitar Modalidad
              </span>
            </div>
          </router-link>

          <router-link to="/estudiante/trabajo-dirigido" class="card overflow-hidden relative block group hover:ring-2 hover:ring-orange-500/40 transition-all duration-200">
            <div class="absolute inset-x-0 top-0 h-2 bg-orange"></div>
            <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300 group-hover:bg-orange-500/20 group-hover:scale-105 transition-all duration-200">
                  <AppIcon name="folder" :size="28" />
                </div>
                <div>
                  <h2 class="text-lg font-extrabold text-white transition-colors duration-200 group-hover:text-orange-300">Trabajo Dirigido</h2>
                  <p class="text-sm text-slate-400">Aquí puedes solicitar la modalidad de graduación de Trabajo Dirigido y realizar el seguimiento correspondiente.</p>
                </div>
              </div>
              <span class="btn-warm px-5 py-2.5">
                <AppIcon name="arrow-left" :size="15" class="rotate-180" />
                Solicitar Modalidad
              </span>
            </div>
          </router-link>

          <!-- Excelencia Académica: solicitud con el promedio general declarado -->
          <router-link to="/estudiante/excelencia" class="card overflow-hidden relative block group hover:ring-2 hover:ring-orange-500/40 transition-all duration-200">
            <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-amber-400 to-orange-500"></div>
            <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-300 group-hover:bg-amber-500/20 group-hover:scale-105 transition-all duration-200">
                  <AppIcon name="award" :size="28" />
                </div>
                <div>
                  <h2 class="text-lg font-extrabold text-white transition-colors duration-200 group-hover:text-amber-300">Excelencia Académica</h2>
                  <p class="text-sm text-slate-400">
                    Solicita la modalidad por promedio sobresaliente (mínimo
                    {{ notaMinimaExcelencia }} puntos) y sigue la verificación de Kardex.
                  </p>
                </div>
              </div>
              <span class="btn-warm px-5 py-2.5">
                <AppIcon name="arrow-left" :size="15" class="rotate-180" />
                Solicitar Modalidad
              </span>
            </div>
          </router-link>
        </template>
      </div>
    </template>
  </AppShell>
</template>

<script setup>
// Vista del portal del estudiante.
// El perfil es SOLO LECTURA: lo crea el propio estudiante al registrarse (o el
// administrador) y Kardex genera las credenciales de acceso. Aquí el estudiante
// consulta sus datos y utiliza los módulos de modalidades de graduación
// (seguimiento del trámite activo y acceso al módulo dedicado de Tesis).
import { TimelineTramite, useTramitesStore, formatoEstado, ESTADOS_TERMINALES } from '@/modules/tramites';
import { useTesisStore, reoptarInfo } from '@/modules/tesis';
import { useTrabajoDirigidoStore } from '@/modules/trabajodirigido';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useEstudianteStore } from '../stores/estudiante';
import SeguimientoExamenGrado from '../components/SeguimientoExamenGrado.vue';
import SeguimientoExcelencia from '@/modules/excelencia/components/SeguimientoExcelencia.vue';
import { esFlujoExamen } from '@/modules/examen/utils/flujoExamen';
import { esFlujoExcelencia, NOTA_MINIMA } from '@/modules/excelencia/utils/flujoExcelencia';
import { useExcelenciaStore } from '@/modules/excelencia';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import StatCard from '@/ui/StatCard.vue';

const tramitesStore = useTramitesStore();
const estudianteStore = useEstudianteStore();
const tesisStore = useTesisStore();
const trabajoDirigidoStore = useTrabajoDirigidoStore();
const excelenciaStore = useExcelenciaStore();

// Es null cuando el perfil aún no fue registrado.
const perfilEstudiante = computed(() => estudianteStore.perfilEstudiante);

// En el login se guardó el perfil (relación estudiante) si el usuario es
// estudiante; si no, la carga del perfil se hace al montar el portal.
const sinPerfil = ref(false);
const cargandoTramite = computed(() => tramitesStore.cargandoTramite);

// Nombre completo mostrado en la ficha de datos personales.
const displayNombres = computed(() =>
  [perfilEstudiante.value?.nombres, perfilEstudiante.value?.apellidos].filter(Boolean).join(' ') || '—',
);

// true si el trámite activo ya alcanzó un estado terminal.
const tramiteTerminado = computed(() => ESTADOS_TERMINALES.includes(tramitesStore.tramiteActivo?.estado_actual));

// Resultados terminales de la modalidad: aprobada bloquea nuevos trámites;
// reprobada habilita la re-opción de modalidad según los plazos reglamentarios.
const terminalAprobada = computed(() =>
  ['Tesis de Grado', 'Trabajo Dirigido'].includes(tramitesStore.tramiteActivo?.modalidad?.nombre)
  && tramitesStore.tramiteActivo?.estado_actual === 'aprobado'
);
const terminalReprobada = computed(() =>
  ['Tesis de Grado', 'Trabajo Dirigido'].includes(tramitesStore.tramiteActivo?.modalidad?.nombre)
  && ['reprobado'].includes(tramitesStore.tramiteActivo?.estado_actual)
);

// Plazos de re-opción: cada modalidad define los suyos (el backend los expone
// en su endpoint de configuración).
const reoptar = computed(() => {
  const esTrabajoDirigido = tramitesStore.tramiteActivo?.modalidad?.nombre === 'Trabajo Dirigido';
  const config = esTrabajoDirigido
    ? trabajoDirigidoStore.config
    : tesisStore.config;
  return reoptarInfo(tramitesStore.tramiteActivo, {
    diasCorreccion: config.dias_correccion,
    diasRemodalidad: config.dias_remodalidad,
  });
});

// Nombre del trabajo en minúscula para los mensajes del portal
// ("tesis de grado" / "trabajo dirigido").
const articuloModalidad = computed(() => {
  const nombre = tramitesStore.tramiteActivo?.modalidad?.nombre;
  if (nombre === 'Trabajo Dirigido') return 'trabajo dirigido';
  return 'tesis de grado';
});

// true si el trámite activo es de la modalidad Tesis de Grado: en ese caso el
// portal no duplica la línea de tiempo (la muestra el módulo dedicado), solo
// el resumen/estado con acceso al módulo.
const esTesisActivo = computed(() => tramitesStore.tramiteActivo?.modalidad?.nombre === 'Tesis de Grado');

// true si el trámite activo es de la modalidad Trabajo Dirigido: al igual que
// la Tesis, la modalidad tiene su propio módulo de seguimiento y el portal
// solo muestra el resumen/estado con acceso al módulo.
const esTrabajoDirigidoActivo = computed(
  () => tramitesStore.tramiteActivo?.modalidad?.nombre === 'Trabajo Dirigido',
);

// true si el trámite activo es de la modalidad Examen de Grado: el seguimiento
// muestra el avance en tarjetas, los intentos y la última actualización.
const esExamenActivo = computed(() => esFlujoExamen(tramitesStore.tramiteActivo));

// true si el trámite activo es de la modalidad Excelencia Académica: el
// seguimiento propio de la modalidad muestra el avance del flujo por promedio.
const esExcelenciaActivo = computed(() => esFlujoExcelencia(tramitesStore.tramiteActivo));

// Mínimo de promedio exigido por la modalidad (lo define el backend).
const notaMinimaExcelencia = computed(() => Number(excelenciaStore.config?.nota_minima) || NOTA_MINIMA);

// Seguimiento en vivo del Examen de Grado: mientras el portal esté abierto con
// esta modalidad, el trámite activo se refresca en segundo plano (silencioso,
// sin indicador de carga) para que los módulos que va registrando Kardex se
// reflejen al instante en las tarjetas del estudiante, sin recargar la página.
const INTERVALO_REFRESCO_MS = 60000;
let timerRefresco = null;
let refrescando = false;

async function refrescarTramiteSilencioso() {
    if (refrescando) return;
    refrescando = true;
    try {
        await tramitesStore.cargarTramiteActivo(true);
    } finally {
        refrescando = false;
    }
}

// Al volver a la pestaña del navegador se refresca de inmediato.
function alVolverPestana() {
    if (document.visibilityState === 'visible' && refrescoEnVivo.value) {
        refrescarTramiteSilencioso();
    }
}

// Seguimiento en vivo: los módulos que va registrando Kardex (Examen de Grado y
// Excelencia Académica) se reflejan al instante en las tarjetas del estudiante.
const refrescoEnVivo = computed(() => esExamenActivo.value || esExcelenciaActivo.value);

watch(refrescoEnVivo, (activo) => {
    clearInterval(timerRefresco);
    timerRefresco = null;
    if (activo) {
        timerRefresco = setInterval(refrescarTramiteSilencioso, INTERVALO_REFRESCO_MS);
    }
}, { immediate: true });

// Permite iniciar una nueva solicitud solo si no hay trámite activo o ya terminó.
const mostrarInicioSolicitud = computed(() => !tramitesStore.tramiteActivo || tramiteTerminado.value);

// Carga inicial: perfil y trámite activo. El listener de pestaña alimenta el
// refresco en vivo del Examen de Grado.
onMounted(async () => {
    await estudianteStore.cargarPerfil(true);
    sinPerfil.value = !estudianteStore.perfilEstudiante;
    await Promise.all([
        tramitesStore.cargarTramiteActivo(),
        // El mínimo de promedio de la Excelencia Académica lo define el backend.
        excelenciaStore.cargarConfig(),
        // Plazos de corrección y re-opción de la modalidad Trabajo Dirigido.
        trabajoDirigidoStore.cargarConfig(),
    ]);
    document.addEventListener('visibilitychange', alVolverPestana);
});

onUnmounted(() => {
    clearInterval(timerRefresco);
    document.removeEventListener('visibilitychange', alVolverPestana);
});

// El perfil pudo llegar ya cargado desde el login; en ese caso no es "sin perfil".
watch(perfilEstudiante, (p) => { sinPerfil.value = !p; }, { immediate: true });

/** Formatea una fecha (YYYY-MM-DD) en formato largo en español. */
function formatoFechaLarga(iso) {
    if (!iso) return '—';
    return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}
</script>