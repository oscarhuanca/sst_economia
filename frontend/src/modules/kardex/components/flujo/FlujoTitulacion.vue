<template>
  <div class="space-y-5">
    <!-- Resumen del trámite + barra de progreso -->
    <div class="card p-5">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-full bg-orange-500/15 ring-1 ring-orange-500/40 flex items-center justify-center text-orange-300 font-extrabold"
          >
            {{ iniciales(estudiante) }}
          </div>
          <div>
            <h3 class="font-extrabold text-white">
              {{ estudiante.nombres }} {{ estudiante.apellidos }}
            </h3>
            <p class="text-xs text-slate-400">
              CI {{ estudiante.ci }} · {{ tramite.modalidad?.nombre }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <EstadoBadge :estado="estadoBadge" />
        </div>
      </div>

      <div class="mt-6">
        <BarraProgresoTitulacion
          :progreso="progreso"
          :modulos="modulos"
          :estado-actual="estadoBadge"
        />
      </div>
    </div>

    <!-- Hito inicial: Solicitud Presentada -->
    <div class="card p-5 border border-emerald-500/30">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
            <AppIcon name="inbox" :size="18" />
          </div>
          <div>
            <h4 class="font-bold text-white uppercase">Solicitud Presentada</h4>
            <p class="text-xs text-slate-400">Punto de partida del flujo de titulación</p>
          </div>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border bg-emerald-500/20 text-emerald-300 border-emerald-500/30"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          Completado
        </span>
      </div>
      <p v-if="fechaInicio" class="mt-3 text-xs text-slate-400">
        Trámite presentado el <span class="font-semibold text-slate-300">{{ fechaInicio }}</span>
      </p>
    </div>

    <!-- 6 módulos del flujo -->
    <div class="grid lg:grid-cols-2 gap-4">
      <template v-for="(m, i) in modulos" :key="m.id">
        <ModuloPerfil
          v-if="m.id === config?.moduloPerfil"
          id="modulo-perfil"
          ref="moduloPerfil"
          :config="config"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :cargando="i === proximoPendiente && guardando"
          :tramite="tramite"
          :rechazo="rechazoPerfil"
          @guardar="(datos) => guardar(m.id, datos)"
          @consultar-coincidencia="(c) => emit('consultar-coincidencia', c)"
        />
        <ModuloAprobacionTema
          v-else-if="m.id === 'aprobacion_tema'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :docentes="docentes"
          :cargando="i === proximoPendiente && guardando"
          :titulo-perfil="tituloPerfilTramite"
          @guardar="(datos, archivo) => guardar(m.id, datos, archivo ? { resolucion_tutor: archivo } : {})"
        />
        <ModuloTribunalRevisor
          v-else-if="m.id === 'tribunal_revisor'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :tutor="tramite.tutor"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos)"
        />
        <ModuloDefensa
          v-else-if="m.id === 'defensa'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloReporte
          v-else-if="m.id === 'reporte'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :tramite="tramite"
          :docentes="docentes"
          :simulado="kardexStore.simulado"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloPublicacion
          v-else
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :cargando="i === proximoPendiente && guardando"
          :tramite="tramite"
          :simulado="kardexStore.simulado"
          @guardar="(datos) => guardar(m.id, datos)"
        />
      </template>
    </div>

    <!-- Acta de reprobación (área imprimible fuera de pantalla, PDF en navegador) -->
    <ActaReprobacionDefensa ref="actaReprobacion" :tramite="tramiteActa || tramite" :defensa="defensaActa" />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useKardexStore } from '../../stores/kardex';
import {
  configDeTramite,
  modulosDe,
  modulosTitulacion,
  proximoModuloPendiente,
  pctTitulacion,
  estadoBadgeTitulacion,
  rechazoPerfil as rechazoPerfilDe,
  validarModulo,
  NOTA_MINIMA_DEFENSA,
  tituloPerfil,
} from '@/modules/tramites/utils/flujoTitulacion';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import BarraProgresoTitulacion from './BarraProgresoTitulacion.vue';
import ModuloPerfil from './ModuloPerfil.vue';
import ModuloAprobacionTema from './ModuloAprobacionTema.vue';
import ModuloTribunalRevisor from './ModuloTribunalRevisor.vue';
import ModuloDefensa from './ModuloDefensa.vue';
import ModuloReporte from './ModuloReporte.vue';
import ModuloPublicacion from './ModuloPublicacion.vue';
import ActaReprobacionDefensa from './ActaReprobacionDefensa.vue';

const props = defineProps({
  tramite: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
});

const emit = defineEmits(['guardado', 'consultar-coincidencia']);

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const guardando = ref(false);
const moduloPerfil = ref(null);
const actaReprobacion = ref(null);

// Datos de la defensa reprobada con la que se emite el acta en PDF.
const defensaActa = ref(null);

// Trámite actualizado con el que se emite el acta en PDF (incluye el
// historial con la reprobación recién registrada).
const tramiteActa = ref(null);

const estudiante = computed(() => props.tramite?.estudiante || {});
const estadoBadge = computed(() => estadoBadgeTitulacion(props.tramite));
const progreso = computed(() => pctTitulacion(props.tramite));
const proximoPendiente = computed(() => proximoModuloPendiente(props.tramite));

// Configuración de la modalidad del trámite (Tesis de Grado, Trabajo Dirigido):
// define el módulo de perfil, sus documentos y sus textos.
const config = computed(() => configDeTramite(props.tramite));

// Motivo y observación del último rechazo del perfil (los ve el estudiante).
const rechazoPerfil = computed(() => rechazoPerfilDe(props.tramite));

const tituloPerfilTramite = computed(() => tituloPerfil(props.tramite));

const modulos = computed(() => {
  const proximo = proximoPendiente.value;
  return modulosTitulacion(props.tramite).map((m, i) => ({
    ...m,
    enProgreso: !m.completado && i === proximo,
    bloqueado: !m.completado && i !== proximo,
  }));
});

const fechaInicio = computed(() =>
  fechaLegible(props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at),
);

async function guardar(moduloId, datos, archivos = {}) {
  const modulo = modulosDe(config.value).find((m) => m.id === moduloId);
  const res = validarModulo(moduloId, datos, config.value?.nombre);
  if (!res.ok) {
    toastStore.error(res.errores.join(' · '));
    return;
  }
  guardando.value = true;
  const actualizado = await kardexStore.guardarModuloFlujo(moduloId, datos, archivos);
  guardando.value = false;
  if (actualizado) {
    // Defensa con nota insuficiente: el trámite queda REPROBADO, el flujo
    // termina y se genera el acta en PDF para documentación.
    if (moduloId === 'defensa' && defensaReprobada(datos)) {
      toastStore.warning(
        `Defensa REPROBADA con ${datos.nota_final} puntos (mínimo ${NOTA_MINIMA_DEFENSA}). `
        + 'El flujo de titulación terminó; el estudiante deberá volver a solicitar la '
        + 'modalidad de graduación.',
        7000,
      );
      defensaActa.value = { ...(actualizado?.hitos?.modulos?.defensa || {}), ...datos };
      tramiteActa.value = actualizado;
      const generada = await actaReprobacion.value?.generar();
      if (generada) {
        toastStore.success('Acta de reprobación de defensa descargada en PDF.');
      } else {
        toastStore.error('No se pudo generar el acta de reprobación PDF.');
      }
      emit('guardado', actualizado);
      return;
    }
    toastStore.success(
      moduloId === 'defensa' && Number(datos.paso ?? 3) < 3
        ? 'Resolución y fecha de defensa registradas.'
        : datos.accion === 'rechazar'
          ? 'Perfil rechazado. Se notificó al estudiante para que presente otro perfil.'
          : `Módulo completado — estado: ${modulo.badge}`,
    );
    emit('guardado', actualizado);
  } else {
    toastStore.error(kardexStore.errorMessage || 'No se pudo guardar el módulo.');
  }
}

/** ¿La nota de la defensa registrada reproba (por debajo del mínimo)? */
function defensaReprobada(datos) {
  const nota = Number(datos?.nota_final);
  return !Number.isNaN(nota) && nota < NOTA_MINIMA_DEFENSA;
}

// Verificación de unicidad disparada desde el resumen del postulante.
function verificarTituloPerfil() {
  // El ref vive dentro de un v-for: puede llegar como instancia única o como
  // arreglo según how Vue lo collecting (se toma la primera de ambas formas).
  const referencia = moduloPerfil.value;
  const instancia = Array.isArray(referencia) ? referencia[0] : referencia;
  instancia?.verificarTitulo?.();
  document.getElementById('modulo-perfil')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

defineExpose({ verificarTituloPerfil });
</script>