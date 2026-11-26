<template>
  <div class="space-y-5">
    <!-- Resumen del trÃ¡mite + barra de progreso -->
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
              CI {{ estudiante.ci }} Â· {{ tramite.modalidad?.nombre }}
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
          :editable="editable"
          @seleccionar="seleccionarModulo"
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
            <p class="text-xs text-slate-400">Punto de partida del flujo de titulaciÃ³n</p>
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
        TrÃ¡mite presentado el <span class="font-semibold text-slate-300">{{ fechaInicio }}</span>
      </p>
    </div>

    <!-- Pasos del flujo -->
    <div class="grid lg:grid-cols-2 gap-4">
      <template v-for="(m, i) in modulos" :key="m.id">
        <ModuloPerfil
          v-if="m.id === config?.moduloPerfil"
          :id="`modulo-${m.id}`"
          ref="moduloPerfil"
          :config="config"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :editando="editandoId === m.id && !(perfilRemitido && m.id === config?.moduloPerfil)"
          :datos="m.datos"
          :cargando="guardandoId === m.id"
          :tramite="tramite"
          :rechazo="rechazoPerfil"
          @guardar="(datos) => guardar(m.id, datos)"
          @consultar-coincidencia="(c) => emit('consultar-coincidencia', c)"
        />
        <ModuloAprobacionTema
          v-else-if="m.id === 'aprobacion_tema'"
          :id="`modulo-${m.id}`"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :editando="editandoId === m.id"
          :datos="m.datos"
          :docentes="docentes"
          :cargando="guardandoId === m.id"
          :titulo-perfil="tituloPerfilTramite"
          :tramite="tramite"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos || {})"
        />
        <ModuloTribunalRevisor
          v-else-if="m.id === 'tribunal_revisor'"
          :id="`modulo-${m.id}`"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :editando="editandoId === m.id"
          :datos="m.datos"
          :tutor="tramite.tutor"
          :docentes="docentes"
          :config="config"
          :cargando="guardandoId === m.id"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos)"
        />
        <ModuloDefensa
          v-else-if="m.id === 'defensa'"
          :id="`modulo-${m.id}`"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :editando="editandoId === m.id"
          :datos="m.datos"
          :cargando="guardandoId === m.id"
          :modalidad-nombre="config?.nombre || tramite?.modalidad?.nombre || ''"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos)"
        />
        <ModuloPublicacion
          v-else-if="m.id === 'publicacion'"
          :id="`modulo-${m.id}`"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :editando="editandoId === m.id"
          :datos="m.datos"
          :cargando="guardandoId === m.id"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloReporte
          v-else-if="m.id === 'reporte'"
          :id="`modulo-${m.id}`"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :tramite="tramite"
          :cargando="guardandoId === m.id"
          @guardar="(datos) => guardar(m.id, datos)"
        />
      </template>
    </div>

    <!-- Acta de reprobaciÃ³n (Ã¡rea imprimible fuera de pantalla, PDF en navegador) -->
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
import ModuloPublicacion from './ModuloPublicacion.vue';
import ModuloReporte from './ModuloReporte.vue';
import ActaReprobacionDefensa from './ActaReprobacionDefensa.vue';

const props = defineProps({
  tramite: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
});

const emit = defineEmits(['guardado', 'consultar-coincidencia']);

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const guardando = ref(false);
const guardandoId = ref(null);
const editandoId = ref(null);
const moduloPerfil = ref(null);
const actaReprobacion = ref(null);

// Datos de la defensa reprobada con la que se emite el acta en PDF.
const defensaActa = ref(null);

// TrÃ¡mite actualizado con el que se emite el acta en PDF (incluye el
// historial con la reprobaciÃ³n reciÃ©n registrada).
const tramiteActa = ref(null);

const estudiante = computed(() => props.tramite?.estudiante || {});
const estadoBadge = computed(() => estadoBadgeTitulacion(props.tramite));
const progreso = computed(() => pctTitulacion(props.tramite));
const proximoPendiente = computed(() => proximoModuloPendiente(props.tramite));

// ConfiguraciÃ³n de la modalidad del trÃ¡mite (Tesis de Grado, Trabajo Dirigido):
// define el mÃ³dulo de perfil, sus documentos y sus textos.
const config = computed(() => configDeTramite(props.tramite));

// Una vez remitido, el mÃ³dulo 1 queda solo para consulta: el HCC gestiona el tema y el tutor.
const perfilRemitido = computed(() => props.tramite?.estado_actual === 'pendiente_concejo_universitario');

// Motivo y observaciÃ³n del Ãºltimo rechazo del perfil (los ve el estudiante).
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

const editable = computed(() => config.value?.nombre === 'Tesis de Grado');

function seleccionarModulo(moduloId) {
  if (!editable.value) return;
  if (perfilRemitido.value && moduloId === config.value?.moduloPerfil) return;
  editandoId.value = editandoId.value === moduloId ? null : moduloId;
  requestAnimationFrame(() => {
    document.getElementById(`modulo-${moduloId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
}

async function guardar(moduloId, datos, archivos = {}) {
  const modulo = modulosDe(config.value).find((m) => m.id === moduloId);
  const datosValidar = (moduloId === 'aprobacion_tema' && props.tramite?.estado_actual === 'pendiente_concejo_universitario')
    ? { ...datos, requiere_aprobacion_perfil: true }
    : datos;
  const res = validarModulo(moduloId, datosValidar, config.value?.nombre);
  if (!res.ok) {
    toastStore.error(res.errores.join(' Â· '));
    return;
  }
  guardando.value = true;
  guardandoId.value = moduloId;
  const actualizado = await kardexStore.guardarModuloFlujo(moduloId, datos, archivos);
  guardando.value = false;
  guardandoId.value = null;
  if (actualizado) {
    if (moduloId === 'defensa' && defensaReprobada(datos)) {
      toastStore.warning(
        `Defensa REPROBADA con ${datos.nota_final} puntos (mÃ­nimo ${NOTA_MINIMA_DEFENSA}). `
        + 'El flujo de titulaciÃ³n terminÃ³; el estudiante deberÃ¡ volver a solicitar la '
        + 'modalidad de graduaciÃ³n.',
        7000,
      );
      defensaActa.value = { ...(actualizado?.hitos?.modulos?.defensa || {}), ...datos };
      tramiteActa.value = actualizado;
      const generada = await actaReprobacion.value?.generar();
      if (generada) {
        toastStore.success('Acta de reprobaciÃ³n de defensa descargada en PDF.');
      } else {
        toastStore.error('No se pudo generar el acta de reprobaciÃ³n PDF.');
      }
      editandoId.value = null;
      emit('guardado', actualizado);
      return;
    }
    const defensaPasoIntermedio = moduloId === 'defensa' && Number(datos.paso ?? 3) < 3;
    const defensaFinalizada = moduloId === 'defensa' && !defensaPasoIntermedio && !defensaReprobada(datos);
    toastStore.success(
      defensaPasoIntermedio
        ? Number(datos.paso) === 2
          ? 'Empastados y CD registrados.'
          : 'Resolución HCC registrada.'
        : defensaFinalizada
          ? `¡Felicidades! Usted concluyó con satisfacción la titulación en la modalidad ${config.value?.nombre || 'de titulación'}.`
        : moduloId === 'aprobacion_tema' && Number(datos.paso) === 2
          ? 'Designación del tutor registrada.'
          : moduloId === 'aprobacion_tema' && Number(datos.paso) === 3
            ? 'Nota de suficiencia del tutor registrada.'
            : datos.accion === 'remitir'
              ? 'Documentación verificada y remitida al HCC.'
              : datos.accion === 'rechazar'
                ? 'Perfil rechazado. Se notificó al estudiante para que presente otro perfil.'
                : editandoId.value === moduloId
                  ? `Paso actualizado — ${modulo.badge}`
                  : `Paso completado — ${modulo.badge}`,
    );
    if (!defensaPasoIntermedio) editandoId.value = null;
    emit('guardado', actualizado);
  } else {
    toastStore.error(kardexStore.errorMessage || 'No se pudo guardar el paso.');
  }
}

/** Â¿La nota de la defensa registrada reproba (por debajo del mÃ­nimo)? */
function defensaReprobada(datos) {
  const nota = Number(datos?.nota_final);
  return !Number.isNaN(nota) && nota < NOTA_MINIMA_DEFENSA;
}

// VerificaciÃ³n de unicidad disparada desde el resumen del postulante.
function verificarTituloPerfil() {
  // El ref vive dentro de un v-for: puede llegar como instancia Ãºnica o como
  // arreglo segÃºn how Vue lo collecting (se toma la primera de ambas formas).
  const referencia = moduloPerfil.value;
  const instancia = Array.isArray(referencia) ? referencia[0] : referencia;
  instancia?.verificarTitulo?.();
  document.getElementById('modulo-perfil')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

defineExpose({ verificarTituloPerfil });
</script>
