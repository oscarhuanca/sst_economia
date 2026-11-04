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
              CI {{ estudiante.ci }} · {{ tramite?.modalidad?.nombre }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <EstadoBadge :estado="tramite?.estado_actual" />
        </div>
      </div>

      <div class="mt-6">
        <BarraProgresoTitulacion
          :progreso="progreso"
          :modulos="modulos"
          :estado-actual="tramite?.estado_actual"
        />
      </div>

      <div class="mt-5 grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio declarado</dt>
          <dd class="text-2xl font-extrabold text-white mt-0.5">
            {{ declarado !== null ? declarado : '—' }}
            <span class="text-xs font-semibold text-slate-400">/ 100</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio verificado</dt>
          <dd class="text-2xl font-extrabold mt-0.5" :class="verificado !== null ? 'text-emerald-300' : 'text-slate-400'">
            {{ verificado !== null ? verificado : '—' }}
            <span class="text-xs font-semibold text-slate-400">/ 100</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la solicitud</dt>
          <dd class="text-sm font-semibold text-white mt-1">{{ fechaInicio || '—' }}</dd>
        </div>
      </div>
    </div>

    <!-- Hito inicial: la postulación del estudiante -->
    <div class="card p-5 border border-emerald-500/30">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
            <AppIcon name="inbox" :size="18" />
          </div>
          <div>
            <h4 class="font-bold text-white uppercase">Solicitud Presentada</h4>
            <p class="text-xs text-slate-400">Punto de partida de la Excelencia Académica</p>
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

    <!-- Solicitud rechazada: el flujo terminó -->
    <div v-if="rechazado" class="card p-5 border border-red-500/40">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-red-900/40 border border-red-500/30 flex items-center justify-center text-red-300">
          <AppIcon name="x-circle" :size="18" />
        </div>
        <div>
          <h4 class="font-bold text-white uppercase">Solicitud rechazada</h4>
          <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            KARDEX rechazó la solicitud de Excelencia Académica mediante la Resolución del HCC
            <span class="font-semibold text-slate-300">{{ resolucionAdmision.numero_resolucion || '—' }}</span>
            <template v-if="resolucionAdmision.fecha_resolucion">
              del {{ fechaLegible(resolucionAdmision.fecha_resolucion) }}
            </template>.
            El estudiante no habilita la modalidad y el flujo de titulación terminó; puede optar por otra modalidad.
          </p>
        </div>
      </div>
    </div>

    <!-- 3 módulos del flujo, cada uno bloqueado hasta cerrar el anterior -->
    <div class="grid lg:grid-cols-2 gap-4">
      <template v-for="(m, i) in modulos" :key="m.id">
        <ModuloVerificacionPromedio
          v-if="m.id === 'verificacion_promedio'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :nota-minima="notaMinima"
          :promedio-declarado="declarado"
          :promedio-verificado="verificado"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos)"
        />
        <ModuloResolucionHcc
          v-else-if="m.id === 'resolucion_hcc'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos)"
        />
        <ModuloReporteExcelencia
          v-else
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :tramite="tramite"
          :nota-minima="notaMinima"
          @guardar="(datos) => guardar(m.id, datos)"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useKardexStore } from '../../stores/kardex';
import { kardexService } from '../../services/kardex';
import {
    MODULOS_FLUJO_EXCELENCIA,
    NOTA_MINIMA,
    modulosExcelencia,
    pctExcelencia,
    promedioDeclarado,
    promedioVerificado,
    proximoModuloPendiente,
    resolucionAdmision as resolucionAdmisionHcc,
    validarModulo,
} from '@/modules/excelencia/utils/flujoExcelencia';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import BarraProgresoTitulacion from '../flujo/BarraProgresoTitulacion.vue';
import ModuloVerificacionPromedio from './ModuloVerificacionPromedio.vue';
import ModuloResolucionHcc from './ModuloResolucionHcc.vue';
import ModuloReporteExcelencia from './ModuloReporteExcelencia.vue';

const props = defineProps({
    tramite: { type: Object, default: null },
});

const emit = defineEmits(['guardado']);

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const guardando = ref(false);

// El mínimo reglamentario lo define la administración en el backend
// (`config/excelencia.php`): se toma de la API y, si no responde, se usa el
// valor por defecto del espejo frontend.
const notaMinima = ref(NOTA_MINIMA);

onMounted(async () => {
    try {
        const { data } = await kardexService.configuracionExcelencia();
        if (data?.nota_minima) notaMinima.value = Number(data.nota_minima);
    } catch (error) {
        // Se conserva el valor por defecto.
    }
});

const estudiante = computed(() => props.tramite?.estudiante || {});
const progreso = computed(() => pctExcelencia(props.tramite));
const proximoPendiente = computed(() => proximoModuloPendiente(props.tramite));
const declarado = computed(() => promedioDeclarado(props.tramite));
const verificado = computed(() => promedioVerificado(props.tramite));
const rechazado = computed(() => props.tramite?.estado_actual === 'rechazado');
const resolucionAdmision = computed(() => resolucionAdmisionHcc(props.tramite));

const modulos = computed(() =>
    modulosExcelencia(props.tramite).map((m) => ({
        ...m,
        datos: props.tramite?.hitos?.modulos?.[m.id] || null,
        registro: props.tramite?.hitos?.modulos?.[m.id] || null,
    })),
);

const fechaInicio = computed(() =>
    fechaLegible(props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at),
);

/**
 * Un módulo sólo se habilita cuando es el siguiente pendiente. Con el trámite
 * rechazado o ya titulado no hay ningún módulo disponible.
 */
function bloqueado(indice) {
    return indice !== proximoPendiente.value;
}

async function guardar(moduloId, datos, archivos = {}) {
    const modulo = MODULOS_FLUJO_EXCELENCIA.find((m) => m.id === moduloId);
    const res = validarModulo(moduloId, datos);

    if (!res.ok) {
        toastStore.error(res.errores.join(' · '));
        return;
    }

    guardando.value = true;
    const actualizado = await kardexStore.guardarModuloExcelencia(moduloId, datos, archivos);
    guardando.value = false;

    if (!actualizado) {
        toastStore.error(kardexStore.errorMessage || 'No se pudo guardar el módulo.');
        return;
    }

    if (moduloId === 'verificacion_promedio' && actualizado.estado_actual === 'rechazado') {
        toastStore.warning('Solicitud rechazada mediante Resolución del HCC.');
    } else {
        toastStore.success(`Módulo completado — estado: ${modulo.badge}`);
    }

    emit('guardado', actualizado);
}
</script>
