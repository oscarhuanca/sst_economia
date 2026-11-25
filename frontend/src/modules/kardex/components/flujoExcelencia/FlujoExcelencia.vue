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

    <!-- 5 módulos del flujo, cada uno bloqueado hasta cerrar el anterior -->
    <div class="grid lg:grid-cols-2 gap-4">
      <template v-for="(m, i) in modulos" :key="m.id">
        <ModuloInicioTramite
          v-if="m.id === 'inicio_tramite'"
          :numero="i + 1"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="bloqueado(i)"
          :tramite="tramite"
        />
        <ModuloDesignacionTribunal
          v-else-if="m.id === 'designacion_tribunal'"
          :numero="i + 1"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos)"
        />
        <ModuloInformeTribunal
          v-else-if="m.id === 'informe_tribunal'"
          :numero="i + 1"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :docentes="docentes"
          @guardar="(datos, archivos) => guardar(m.id, datos, archivos)"
        />
        <ModuloResolucionHcc
          v-else-if="m.id === 'resolucion_hcc'"
          :numero="i + 1"
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
          :numero="i + 1"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :tramite="tramite"
          @guardar="(datos) => guardar(m.id, datos)"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useKardexStore } from '../../stores/kardex';
import {
    MODULOS_FLUJO_EXCELENCIA,
    modulosExcelencia,
    pctExcelencia,
    proximoModuloPendiente,
    resolucionAdmision as resolucionAdmisionHcc,
    validarModulo,
} from '@/modules/excelencia/utils/flujoExcelencia';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import BarraProgresoTitulacion from '../flujo/BarraProgresoTitulacion.vue';
import ModuloInicioTramite from './ModuloInicioTramite.vue';
import ModuloDesignacionTribunal from './ModuloDesignacionTribunal.vue';
import ModuloInformeTribunal from './ModuloInformeTribunal.vue';
import ModuloResolucionHcc from './ModuloResolucionHcc.vue';
import ModuloReporteExcelencia from './ModuloReporteExcelencia.vue';

const props = defineProps({
    tramite: { type: Object, default: null },
    docentes: { type: Array, default: () => [] },
});

const emit = defineEmits(['guardado']);

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const guardando = ref(false);

const estudiante = computed(() => props.tramite?.estudiante || {});
const progreso = computed(() => pctExcelencia(props.tramite));
const proximoPendiente = computed(() => proximoModuloPendiente(props.tramite));
const rechazado = computed(() => props.tramite?.estado_actual === 'rechazado');
const resolucionAdmision = computed(() => resolucionAdmisionHcc(props.tramite));

const modulos = computed(() =>
    modulosExcelencia(props.tramite).map((m) => ({
        ...m,
        datos: props.tramite?.hitos?.modulos?.[m.id] || null,
        registro: props.tramite?.hitos?.modulos?.[m.id] || null,
    })),
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

    toastStore.success(`Módulo completado — estado: ${modulo.badge}`);

    emit('guardado', actualizado);
}
</script>
