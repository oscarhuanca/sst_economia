<template>
  <div class="space-y-6">
    <!-- Encabezado del panel general -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-orange text-white flex items-center justify-center shadow-lg shadow-orange-900/30">
        <AppIcon name="dash-grid" :size="24" />
      </div>
      <div>
        <h2 class="text-2xl font-bold text-white uppercase">Panel General</h2>
        <p class="text-sm text-slate-400 mt-0.5">
          Resumen de estadísticas de modalidades, estudiantes y tutores.
        </p>
      </div>
    </div>

    <!-- Carga del resumen -->
    <div v-if="cargando && !resumen" class="card p-8 flex items-center justify-center gap-2 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando estadísticas...
    </div>

    <div v-else-if="!resumen" class="card p-8 text-center">
      <AppIcon name="alert-triangle" :size="28" class="mx-auto text-orange-300 mb-3" />
      <p class="text-white font-semibold">No se pudieron cargar las estadísticas</p>
      <p class="text-sm text-slate-400 mt-1">
        {{ kardexStore.errorMessage || 'Verifica tu sesión o intenta de nuevo.' }}
      </p>
      <button class="btn-ghost mt-4" @click="recargar">
        <AppIcon name="loader" :size="16" />
        Reintentar
      </button>
    </div>

    <template v-else>
      <!-- Alerta de postulantes en espera de credenciales -->
      <div
        v-if="pendientes > 0"
        class="rounded-2xl border border-orange-500/40 bg-orange-500/10 p-4 sm:p-5"
      >
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-xl bg-orange-500/20 text-orange-300 flex items-center justify-center">
            <AppIcon name="alert-triangle" :size="22" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-white">
              {{ pendientes }} {{ pendientes === 1 ? 'postulante está' : 'postulantes están' }} en espera de credenciales
            </p>
            <p class="text-sm text-slate-300 mt-0.5">
              Seleccione un postulante para generar sus credenciales en el módulo de registro.
            </p>
            <div v-if="listaPendientes.length" class="flex flex-wrap gap-2 mt-3">
              <button
                v-for="p in listaPendientes"
                :key="p.id_estudiante || p.ci"
                type="button"
                class="inline-flex items-center gap-2 rounded-full border border-orange-500/30 bg-primary/60 px-3 py-1.5 text-xs font-semibold text-white hover:border-orange-400 hover:bg-orange-500/20 transition"
                @click="abrirRegistro(p)"
              >
                <AppIcon name="user-plus" :size="13" />
                {{ p.nombres }} {{ p.apellidos }}
                <span class="text-slate-400">CI {{ p.ci }}</span>
              </button>
            </div>
          </div>
          <button class="btn-ghost shrink-0" type="button" @click="irARegistro">
            <AppIcon name="user-plus" :size="16" />
            Ir al registro
          </button>
        </div>
      </div>

      <!-- Alerta de solicitudes nuevas de modalidad de titulación -->
      <div
        v-if="conModalidad > 0"
        class="rounded-2xl border border-sky-500/40 bg-sky-500/10 p-4 sm:p-5"
      >
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-xl bg-sky-500/20 text-sky-300 flex items-center justify-center">
            <AppIcon name="file-text" :size="22" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-white">
              {{ conModalidad }} {{ conModalidad === 1 ? 'estudiante optó recientemente' : 'estudiantes optaron recientemente' }} por una modalidad de titulación
            </p>
            <p class="text-sm text-slate-300 mt-0.5">
              Solicitudes nuevas pendientes de verificación. Seleccione un estudiante para continuar el flujo en el módulo de su modalidad.
            </p>
            <div v-if="listaTitulaciones.length" class="flex flex-wrap gap-2 mt-3 max-h-32 overflow-y-auto pr-1">
              <button
                v-for="t in listaOtrasTitulaciones"
                :key="t.id_tramite || t.id_estudiante"
                type="button"
                class="inline-flex items-center gap-2 rounded-full border border-sky-500/30 bg-primary/60 px-3 py-1.5 text-xs font-semibold text-white hover:border-sky-400 hover:bg-sky-500/20 transition"
                @click="abrirModalidad(t)"
              >
                <AppIcon name="user" :size="13" />
                {{ t.nombres }} {{ t.apellidos }}
                <span class="text-slate-400">{{ t.modalidad }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Alerta de solicitudes nuevas de Excelencia Académica -->
      <div
        v-if="conExcelencia > 0"
        class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 sm:p-5"
      >
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center">
            <AppIcon name="award" :size="22" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-white">
              {{ conExcelencia }} {{ conExcelencia === 1 ? 'estudiante solicitó' : 'estudiantes solicitaron' }}
              la modalidad de Excelencia Académica
            </p>
            <p class="text-sm text-slate-300 mt-0.5">
              Seleccione al estudiante para verificar en Registro de Postulante la documentación que presentó.
            </p>
            <div v-if="listaExcelencia.length" class="flex flex-wrap gap-2 mt-3 max-h-32 overflow-y-auto pr-1">
              <button
                v-for="t in listaExcelencia"
                :key="t.id_tramite || t.id_estudiante"
                type="button"
                class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-primary/60 px-3 py-1.5 text-xs font-semibold text-white hover:border-emerald-400 hover:bg-emerald-500/20 transition"
                @click="abrirRegistroExcelencia(t)"
              >
                <AppIcon name="user" :size="13" />
                {{ t.nombres }} {{ t.apellidos }}
                <span class="text-slate-400">CI {{ t.ci }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- KPIs principales protegidos con optional chaining -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Estudiantes" :value="resumen?.estudiantes?.total || 0" sublabel="Postulantes registrados" icon="users" tone="indigo" />
        <StatCard label="Trámites" :value="resumen?.totales?.tramites || 0" sublabel="Solicitudes de titulación" icon="file-text" tone="sky" />
        <StatCard label="Aprobados" :value="resumen?.totales?.aprobados || 0" sublabel="Titulaciones concluidas" icon="award" tone="emerald" />
        <StatCard label="Tutores" :value="resumen?.tutores?.total || 0" sublabel="Docentes disponibles" icon="user-check" tone="orange" />
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Estadísticas de modalidades -->
        <div class="card p-5 sm:p-6">
          <h3 class="font-bold text-white uppercase flex items-center gap-2 mb-4">
            <AppIcon name="chart" :size="18" class="text-orange-300" />
            Estadísticas de Modalidades
          </h3>

          <div v-if="!resumen?.modalidades || resumen.modalidades.length === 0">
            <EmptyState icon="folder" title="Sin modalidades" message="Aún no hay modalidades registradas." />
          </div>

          <div v-else class="space-y-4">
            <div v-for="m in resumen.modalidades" :key="m.id_modalidad || m.nombre">
              <div class="flex items-center justify-between mb-1.5 text-sm">
                <span class="font-semibold text-white">{{ m.nombre }}</span>
                <span class="text-xs text-slate-400">{{ m.total || 0 }} {{ m.total === 1 ? 'trámite' : 'trámites' }}</span>
              </div>

              <div class="flex items-center gap-3">
                <div class="flex-1 h-4 rounded-full bg-white/10 overflow-hidden flex">
                  <div
                    v-if="(m.en_curso || 0) > 0"
                    class="h-full bg-orange-500 transition-all duration-500"
                    :style="{ width: porcentaje(m.en_curso, m.total) + '%' }"
                  ></div>
                  <div
                    v-if="(m.aprobados || 0) > 0"
                    class="h-full bg-emerald-500 transition-all duration-500"
                    :style="{ width: porcentaje(m.aprobados, m.total) + '%' }"
                  ></div>
                </div>
                <div class="flex items-center gap-3 text-xs font-bold shrink-0">
                  <span class="inline-flex items-center gap-1.5 text-orange-300">
                    <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                    {{ m.en_curso || 0 }}
                  </span>
                  <span class="inline-flex items-center gap-1.5 text-emerald-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    {{ m.aprobados || 0 }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <p class="text-xs text-slate-400 mt-4">
            El segmento naranja indica trámites en curso y el verde los que finalizaron de forma aprobatoria.
          </p>
        </div>

        <!-- Estadísticas de estudiantes y tutores -->
        <div class="space-y-6">
          <div class="card p-5 sm:p-6">
            <h3 class="font-bold text-white uppercase flex items-center gap-2 mb-4">
              <AppIcon name="users" :size="18" class="text-orange-300" />
              Estadísticas de Estudiantes
            </h3>

            <div class="grid grid-cols-3 gap-3 text-center">
              <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-4">
                <p class="text-2xl font-bold text-white">{{ resumen?.estudiantes?.total || 0 }}</p>
                <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold mt-0.5">Registrados</p>
              </div>
              <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-4">
                <p class="text-2xl font-bold text-white">{{ resumen?.estudiantes?.con_cuenta || 0 }}</p>
                <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold mt-0.5">Con cuenta</p>
              </div>
              <div class="rounded-xl bg-emerald-500/15 border border-emerald-500/30 px-3 py-4">
                <p class="text-2xl font-bold text-emerald-300">{{ resumen?.estudiantes?.con_tramite || 0 }}</p>
                <p class="text-[11px] uppercase tracking-wider text-emerald-400/80 font-semibold mt-0.5">Con trámite</p>
              </div>
            </div>
          </div>

          <div class="card p-5 sm:p-6">
            <h3 class="font-bold text-white uppercase flex items-center gap-2 mb-4">
              <AppIcon name="user-check" :size="18" class="text-orange-300" />
              Estadísticas de Tutores
            </h3>

            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-xl bg-orange-500/15 border border-orange-500/30 px-3 py-4">
                <p class="text-2xl font-bold text-orange-300">{{ resumen?.tutores?.total || 0 }}</p>
                <p class="text-[11px] uppercase tracking-wider text-orange-400/80 font-semibold mt-0.5">Docentes</p>
              </div>
              <div class="rounded-xl bg-emerald-500/15 border border-emerald-500/30 px-3 py-4">
                <p class="text-2xl font-bold text-emerald-300">{{ resumen?.tutores?.activos || 0 }}</p>
                <p class="text-[11px] uppercase tracking-wider text-emerald-400/80 font-semibold mt-0.5">Con trámites</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { rutaKardexDeModalidad, useKardexStore } from '@/modules/kardex';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import StatCard from '@/ui/StatCard.vue';
import EmptyState from '@/ui/EmptyState.vue';

const kardexStore = useKardexStore();
const router = useRouter();
const toastStore = useToastStore();

const cargando = ref(true);
const resumen = computed(() => kardexStore.resumen);
const pendientes = computed(() => resumen.value?.estudiantes?.pendientes_credenciales || 0);
const listaPendientes = computed(() =>
  Array.isArray(resumen.value?.estudiantes?.pendientes) ? resumen.value.estudiantes.pendientes : [],
);
const listaTitulaciones = computed(() => {
  const titulaciones = resumen.value?.estudiantes?.titulaciones;
  return Array.isArray(titulaciones)
    ? titulaciones.filter((t) => t.estado_actual === 'solicitud_presentada')
    : [];
});
const listaExcelencia = computed(() =>
  listaTitulaciones.value.filter((t) => t.modalidad === 'Excelencia Académica'),
);
const listaOtrasTitulaciones = computed(() =>
  listaTitulaciones.value.filter((t) => t.modalidad !== 'Excelencia Académica'),
);
const conExcelencia = computed(() => listaExcelencia.value.length);
const conModalidad = computed(() => listaOtrasTitulaciones.value.length);

const porcentaje = (valor, total) => (total > 0 ? Math.round(((valor || 0) / total) * 100) : 0);

/** Abre el módulo de registro en la etapa de generación de credenciales. */
const abrirRegistro = (postulante) => {
  router.push({
    name: 'KardexRegistroPostulante',
    query: {
      identificador: postulante.ci || postulante.registro_universitario,
      etapa: 'credenciales',
    },
  });
};

/** Abre Registro de Postulante en la verificación documental de Excelencia. */
const abrirRegistroExcelencia = (titulacion) => {
  router.push({
    name: 'KardexRegistroPostulante',
    query: {
      identificador: titulacion.ci || titulacion.registro_universitario,
      etapa: 'verificacion',
    },
  });
};

const irARegistro = () => {
  router.push({ name: 'KardexRegistroPostulante', query: { etapa: 'credenciales' } });
};

/** Abre el módulo de la modalidad elegida con el estudiante ya filtrado. */
const abrirModalidad = (titulacion) => {
  const nombreRuta = rutaKardexDeModalidad(titulacion.modalidad);
  if (!nombreRuta) return;
  router.push({
    name: nombreRuta,
    query: { identificador: titulacion.ci || titulacion.registro_universitario },
  });
};

/** Muestra la alerta visual cuando hay postulantes en espera. */
const avisarPendientes = () => {
  if (pendientes.value === 0) return;
  toastStore.warning(
    `${pendientes.value} ${pendientes.value === 1 ? 'postulante está' : 'postulantes están'} en espera de credenciales.`,
  );
};

/** Avisa de las solicitudes nuevas de titulación pendientes de verificación. */
const avisarTitulaciones = () => {
  if (conModalidad.value === 0) return;
  toastStore.info(
    `${conModalidad.value} ${conModalidad.value === 1 ? 'solicitud nueva' : 'solicitudes nuevas'} de titulación pendiente${conModalidad.value === 1 ? '' : 's'} de verificación.`,
  );
};

onMounted(async () => {
  cargando.value = true;
  try {
    await kardexStore.cargarResumen();
    avisarPendientes();
    avisarTitulaciones();
  } catch (e) {
    console.error("Error al cargar resumen:", e);
  } finally {
    cargando.value = false;
  }
});

const recargar = async () => {
  cargando.value = true;
  try {
    await kardexStore.cargarResumen(true);
    avisarPendientes();
    avisarTitulaciones();
  } finally {
    cargando.value = false;
  }
};
</script>