<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-orange text-white flex items-center justify-center shadow-lg shadow-orange-900/30">
          <AppIcon name="chart" :size="24" />
        </div>
        <div>
          <h2 class="text-2xl font-bold text-white uppercase">Reporte de Modalidad</h2>
          <p class="text-sm text-slate-400 mt-0.5">
            Estadísticas y reportes de avance por modalidad, mes y año.
          </p>
        </div>
      </div>

      <button
        class="btn-warm"
        :disabled="!reporte || generandoPdf"
        @click="descargarPdf"
      >
        <AppIcon v-if="generandoPdf" name="loader" :size="16" class="animate-spin" />
        <AppIcon v-else name="download" :size="16" />
        {{ generandoPdf ? 'Generando PDF...' : 'Generar PDF' }}
      </button>
    </div>

    <!-- Filtros -->
    <div class="card p-4 sm:p-5">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Modalidad</label>
          <select v-model="filtros.id_modalidad" class="input">
            <option value="">Todas las modalidades</option>
            <option v-for="m in modalidades" :key="m.id_modalidad" :value="m.id_modalidad">
              {{ m.nombre }}
            </option>
          </select>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Año</label>
          <select v-model="filtros.anio" class="input">
            <option value="">Todos los años</option>
            <option v-for="anio in aniosDisponibles" :key="anio" :value="anio">{{ anio }}</option>
          </select>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Mes</label>
          <select v-model="filtros.mes" class="input" :disabled="!filtros.anio">
            <option value="">Todos los meses</option>
            <option v-for="mes in meses" :key="mes.value" :value="mes.value">{{ mes.label }}</option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2 mt-4">
        <button class="btn-ghost" :disabled="cargando" @click="limpiarFiltros">
          <AppIcon name="refresh" :size="16" />
          Limpiar
        </button>
        <button class="btn-warm" :disabled="cargando" @click="generarReporte">
          <AppIcon v-if="cargando" name="loader" :size="16" class="animate-spin" />
          <AppIcon v-else name="search" :size="16" />
          Generar reporte
        </button>
      </div>
    </div>

    <!-- Carga -->
    <div v-if="cargando && !reporte" class="card p-8 flex items-center justify-center gap-2 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Generando reporte...
    </div>

    <!-- Sin resultados -->
    <div v-else-if="!reporte" class="card p-8 text-center">
      <AppIcon name="chart" :size="28" class="mx-auto text-orange-300 mb-3" />
      <p class="text-white font-semibold">Seleccione filtros y genere el reporte</p>
      <p class="text-sm text-slate-400 mt-1">
        Puede filtrar por modalidad, año, mes o rango de fechas para obtener estadísticas detalladas.
      </p>
    </div>

    <template v-else>
      <!-- KPIs -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Total Trámites" :value="reporte.totales.total" sublabel="Registros encontrados" icon="file-text" tone="sky" />
        <StatCard label="En Proceso" :value="reporte.totales.en_proceso" sublabel="En seguimiento" icon="clock" tone="orange" />
        <StatCard label="Aprobados / Titulados" :value="reporte.totales.finalizados" sublabel="Concluidos satisfactoriamente" icon="award" tone="emerald" />
        <StatCard label="Observados / Reprobados" :value="reporte.totales.fallos" sublabel="Con resolución desfavorable" icon="alert-triangle" tone="rose" />
      </div>

      <!-- Avance por modalidad -->
      <div class="card p-5 sm:p-6">
        <h3 class="font-bold text-white uppercase flex items-center gap-2 mb-4">
          <AppIcon name="layers" :size="18" class="text-orange-300" />
          Avance por modalidad
        </h3>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-white/10 text-left">
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">Modalidad</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400 text-right">Total</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400 text-right">En proceso</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400 text-right">Finalizados</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400 text-right">Fallos</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400 text-right">% Finalizados</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in reporte.por_modalidad" :key="item.id_modalidad" class="border-b border-white/5 hover:bg-white/5 transition">
                <td class="py-2.5 px-3 text-white font-medium">{{ item.nombre }}</td>
                <td class="py-2.5 px-3 text-slate-300 text-right">{{ item.total }}</td>
                <td class="py-2.5 px-3 text-orange-300 text-right">{{ item.en_proceso }}</td>
                <td class="py-2.5 px-3 text-emerald-300 text-right">{{ item.finalizados }}</td>
                <td class="py-2.5 px-3 text-rose-300 text-right">{{ item.fallos }}</td>
                <td class="py-2.5 px-3 text-slate-300 text-right">{{ item.porcentaje_finalizados }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5 sm:p-6">
          <h3 class="font-bold text-white uppercase flex items-center gap-2 mb-4">
            <AppIcon name="pie-chart" :size="18" class="text-orange-300" />
            Distribución por estado actual
          </h3>
          <div class="space-y-3">
            <div v-for="estado in reporte.por_estado" :key="estado.nombre_estado" class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :class="colorEstado(estado.nombre_estado)"></span>
                <span class="text-sm text-white">{{ formatoEstado(estado.nombre_estado) }}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-sm text-slate-400">{{ estado.cantidad }} trámites</span>
                <span class="text-xs font-semibold text-slate-300 w-10 text-right">{{ estado.porcentaje }}%</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card p-5 sm:p-6">
          <h3 class="font-bold text-white uppercase flex items-center gap-2 mb-4">
            <AppIcon name="activity" :size="18" class="text-orange-300" />
            Trámites registrados por mes
          </h3>
          <div v-if="!reporte.por_mes || !reporte.por_mes.length" class="text-center text-sm text-slate-400 py-6">
            Sin datos para el rango seleccionado
          </div>
          <div v-else class="space-y-3">
            <div v-for="mes in reporte.por_mes" :key="mes.clave" class="flex items-center gap-3">
              <span class="text-xs text-slate-400 w-16">{{ mes.label }}</span>
              <div class="flex-1 h-3 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-orange-400 to-orange-500" :style="{ width: (mes.porcentaje || 0) + '%' }"></div>
              </div>
              <span class="text-sm text-white font-semibold w-8 text-right">{{ mes.cantidad }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card p-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-white uppercase flex items-center gap-2">
            <AppIcon name="list" :size="18" class="text-orange-300" />
            Listado de trámites
          </h3>
          <span class="text-xs text-slate-400">{{ reporte.listado.length }} registros</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-white/10 text-left">
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">N° Trámite</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">Estudiante</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">CI</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">Modalidad</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">Estado</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">Tutor</th>
                <th class="py-2 px-3 text-xs uppercase tracking-wider text-slate-400">Fecha registro</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in reporte.listado" :key="t.id_tramite" class="border-b border-white/5 hover:bg-white/5 transition">
                <td class="py-2.5 px-3 text-white font-mono">#{{ t.id_tramite }}</td>
                <td class="py-2.5 px-3 text-white">{{ t.estudiante }}</td>
                <td class="py-2.5 px-3 text-slate-300">{{ t.ci }}</td>
                <td class="py-2.5 px-3 text-slate-300">{{ t.modalidad }}</td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded-full text-xs border" :class="badgeEstado(t.estado_actual)">
                    {{ formatoEstado(t.estado_actual) }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-slate-300">{{ t.tutor || 'Sin asignar' }}</td>
                <td class="py-2.5 px-3 text-slate-400">{{ fechaCorta(t.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Plantilla imprimible (fondo blanco) usada para generar el PDF -->
    <div v-if="reporte" class="reporte-pdf-oculto" aria-hidden="true">
      <div ref="contenedorPdf" class="reporte-pdf">
      <header class="pdf-header">
        <div class="pdf-header__institucion">
          <p class="pdf-universidad">Universidad Pública de El Alto</p>
          <p class="pdf-sistema">Sistema de Seguimiento de Trámites</p>
          <h1 class="pdf-titulo">{{ tituloReporte }}</h1>
          <p class="pdf-descripcion">{{ descripcionFiltros }}</p>
        </div>
        <div class="pdf-meta">
          <p><strong>Emitido:</strong> {{ fechaEmision }}</p>
          <p><strong>Usuario:</strong> {{ emisor }}</p>
        </div>
      </header>

      <section class="pdf-kpis">
        <div class="pdf-kpi">
          <span class="pdf-kpi__label">Total trámites</span>
          <span class="pdf-kpi__valor">{{ reporte.totales.total }}</span>
        </div>
        <div class="pdf-kpi">
          <span class="pdf-kpi__label">En proceso</span>
          <span class="pdf-kpi__valor">{{ reporte.totales.en_proceso }}</span>
        </div>
        <div class="pdf-kpi">
          <span class="pdf-kpi__label">Aprobados / Titulados</span>
          <span class="pdf-kpi__valor">{{ reporte.totales.finalizados }}</span>
        </div>
        <div class="pdf-kpi">
          <span class="pdf-kpi__label">Observados / Reprobados</span>
          <span class="pdf-kpi__valor">{{ reporte.totales.fallos }}</span>
        </div>
      </section>

      <section class="pdf-bloque">
        <h2 class="pdf-subtitulo">Avance por modalidad</h2>
        <table class="pdf-tabla">
          <thead>
            <tr>
              <th class="pdf-izq">Modalidad</th>
              <th>Total</th>
              <th>En proceso</th>
              <th>Finalizados</th>
              <th>Fallos</th>
              <th>% Finalizados</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in reporte.por_modalidad" :key="item.id_modalidad">
              <td class="pdf-izq pdf-negrita">{{ item.nombre }}</td>
              <td>{{ item.total }}</td>
              <td>{{ item.en_proceso }}</td>
              <td>{{ item.finalizados }}</td>
              <td>{{ item.fallos }}</td>
              <td>{{ item.porcentaje_finalizados }}%</td>
            </tr>
            <tr v-if="!reporte.por_modalidad.length">
              <td class="pdf-izq" colspan="6">Sin datos para los filtros seleccionados.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <div class="pdf-columnas">
        <section class="pdf-bloque">
          <h2 class="pdf-subtitulo">Distribución por estado actual</h2>
          <table class="pdf-tabla">
            <thead>
              <tr>
                <th class="pdf-izq">Estado</th>
                <th>Cantidad</th>
                <th>%</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="estado in reporte.por_estado" :key="estado.nombre_estado">
                <td class="pdf-izq">{{ formatoEstado(estado.nombre_estado) }}</td>
                <td>{{ estado.cantidad }}</td>
                <td>{{ estado.porcentaje }}%</td>
              </tr>
              <tr v-if="!reporte.por_estado.length">
                <td class="pdf-izq" colspan="3">Sin datos para los filtros seleccionados.</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="pdf-bloque">
          <h2 class="pdf-subtitulo">Trámites registrados por mes</h2>
          <table class="pdf-tabla">
            <thead>
              <tr>
                <th class="pdf-izq">Mes</th>
                <th>Cantidad</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="mes in reporte.por_mes" :key="mes.clave">
                <td class="pdf-izq">{{ mes.label }}</td>
                <td>{{ mes.cantidad }}</td>
              </tr>
              <tr v-if="!reporte.por_mes.length">
                <td class="pdf-izq" colspan="2">Sin datos para los filtros seleccionados.</td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>

      <section class="pdf-bloque">
        <h2 class="pdf-subtitulo">Listado de trámites</h2>
        <table class="pdf-tabla pdf-tabla--listado">
          <thead>
            <tr>
              <th>N°</th>
              <th class="pdf-izq">Estudiante</th>
              <th>CI</th>
              <th class="pdf-izq">Modalidad</th>
              <th class="pdf-izq">Estado</th>
              <th class="pdf-izq">Tutor</th>
              <th>Fecha registro</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in reporte.listado" :key="t.id_tramite" class="pdf-fila">
              <td>#{{ t.id_tramite }}</td>
              <td class="pdf-izq">{{ t.estudiante }}</td>
              <td>{{ t.ci }}</td>
              <td class="pdf-izq">{{ t.modalidad }}</td>
              <td class="pdf-izq">{{ formatoEstado(t.estado_actual) }}</td>
              <td class="pdf-izq">{{ t.tutor || 'Sin asignar' }}</td>
              <td>{{ fechaCorta(t.created_at) }}</td>
            </tr>
            <tr v-if="!reporte.listado.length">
              <td class="pdf-izq" colspan="7">Sin trámites para los filtros seleccionados.</td>
            </tr>
          </tbody>
        </table>
        <p v-if="reporte.listado.length >= 200" class="pdf-nota">
          El listado muestra los primeros 200 registros del reporte.
        </p>
      </section>

      <footer class="pdf-pie">
        Documento generado automáticamente por el Sistema de Seguimiento de Trámites.
      </footer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import html2pdf from 'html2pdf.js';
import { useKardexStore } from '@/modules/kardex';
import { formatoEstado } from '@/modules/tramites';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import StatCard from '@/ui/StatCard.vue';

const kardexStore = useKardexStore();
const authStore = useAuthStore();
const toastStore = useToastStore();

const filtros = ref({
  id_modalidad: '',
  anio: '',
  mes: ''
});

const cargando = ref(false);
const reporte = ref(null);
const contenedorPdf = ref(null);
const generandoPdf = ref(false);
const fechaEmision = ref('');

const modalidades = computed(() => kardexStore.modalidades || []);

const emisor = computed(() => {
  const u = authStore.user || {};
  return (
    [u.nombres, u.apellidos].filter(Boolean).join(' ') ||
    u.username ||
    'Kardex Académico'
  );
});

const hayFiltros = computed(() =>
  Boolean(filtros.value.id_modalidad || filtros.value.anio || filtros.value.mes)
);

const tituloReporte = computed(() =>
  hayFiltros.value ? 'Reporte de Modalidad' : 'Reporte General del Kardex'
);

const descripcionFiltros = computed(() => {
  const partes = [];

  if (filtros.value.id_modalidad) {
    const m = modalidades.value.find(
      (item) => String(item.id_modalidad) === String(filtros.value.id_modalidad)
    );
    partes.push(`Modalidad: ${m?.nombre || 'Seleccionada'}`);
  } else {
    partes.push('Modalidad: Todas');
  }

  if (filtros.value.anio) {
    partes.push(`Año: ${filtros.value.anio}`);
  } else {
    partes.push('Año: Todos');
  }

  if (filtros.value.mes) {
    const mes = meses.find((item) => String(item.value) === String(filtros.value.mes));
    partes.push(`Mes: ${mes?.label || filtros.value.mes}`);
  } else {
    partes.push('Mes: Todos');
  }

  return partes.join('  |  ');
});

const meses = [
  { value: 1, label: 'Enero' },
  { value: 2, label: 'Febrero' },
  { value: 3, label: 'Marzo' },
  { value: 4, label: 'Abril' },
  { value: 5, label: 'Mayo' },
  { value: 6, label: 'Junio' },
  { value: 7, label: 'Julio' },
  { value: 8, label: 'Agosto' },
  { value: 9, label: 'Septiembre' },
  { value: 10, label: 'Octubre' },
  { value: 11, label: 'Noviembre' },
  { value: 12, label: 'Diciembre' },
];

const aniosDisponibles = computed(() => {
  const actual = new Date().getFullYear();
  const lista = [];
  for (let i = 0; i < 6; i++) lista.push(actual - i);
  return lista;
});

onMounted(async () => {
  if (!kardexStore.modalidades || kardexStore.modalidades.length === 0) {
    await kardexStore.cargarModalidades();
  }
  await generarReporte();
});

const ahora = () =>
  new Date().toLocaleString('es-BO', { dateStyle: 'long', timeStyle: 'short' });

const generarReporte = async () => {
  cargando.value = true;
  try {
    const res = await kardexStore.obtenerReporte({ ...filtros.value });
    reporte.value = res;
    fechaEmision.value = ahora();
  } catch (e) {
    console.error(e);
  } finally {
    cargando.value = false;
  }
};

/* Márgenes de 1.5 cm iguales en los cuatro lados (mismo criterio que las
   actas del flujo de titulación). */
const OPCIONES_PDF = {
  margin: [15, 15, 15, 15],
  image: { type: 'jpeg', quality: 0.98 },
  html2canvas: { scale: 3, useCORS: true, allowTaint: false, logging: false, backgroundColor: '#ffffff' },
  jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
  pagebreak: { mode: ['css', 'legacy'] },
};

const paginar = (pdf) => {
  const total = pdf.internal.getNumberOfPages();
  const ancho = pdf.internal.pageSize.getWidth();
  const alto = pdf.internal.pageSize.getHeight();

  for (let i = 1; i <= total; i += 1) {
    pdf.setPage(i);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(8);
    pdf.setTextColor(120, 120, 120);
    pdf.text(`Página ${i} / ${total}`, ancho - 15, alto - 7, { align: 'right' });
  }
};

const producirPdf = async () => {
  if (!contenedorPdf.value) return null;
  await new Promise((r) => setTimeout(r, 150));
  const pdf = await html2pdf().set(OPCIONES_PDF).from(contenedorPdf.value).toPdf().get('pdf');
  paginar(pdf);
  return pdf;
};

const nombrePdf = () => {
  const sufijo = hayFiltros.value ? 'modalidad' : 'general';
  return `reporte_${sufijo}_${new Date().toISOString().slice(0, 10)}.pdf`;
};

const descargarPdf = async () => {
  if (!reporte.value) return;

  generandoPdf.value = true;
  fechaEmision.value = ahora();
  try {
    await nextTick();
    const pdf = await producirPdf();
    if (!pdf) return;
    pdf.save(nombrePdf());
  } catch (error) {
    console.error('[Reporte Kardex] No se pudo generar el PDF:', error);
    toastStore.error('No se pudo generar el PDF del reporte. Intente nuevamente.');
  } finally {
    generandoPdf.value = false;
  }
};

const limpiarFiltros = () => {
  filtros.value = { id_modalidad: '', anio: '', mes: '' };
  reporte.value = null;
};

const fechaCorta = (fecha) => {
  if (!fecha) return '—';
  try {
    return new Date(fecha).toLocaleDateString('es-BO');
  } catch {
    return fecha;
  }
};


const colorEstado = (estado) => {
  if (['titulado', 'aprobado'].includes(estado)) return 'bg-emerald-400';
  if (['rechazado', 'observado', 'archivado'].includes(estado)) return 'bg-rose-400';
  if (['en_proceso', 'perfil_presentado', 'investigacion_en_desarrollo', 'tribunal_asignado', 'defensa_programada', 'habilitado_examen'].includes(estado)) return 'bg-orange-400';
  return 'bg-slate-400';
};

const badgeEstado = (estado) => {
  if (['titulado', 'aprobado'].includes(estado)) return 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30';
  if (['rechazado', 'observado', 'archivado'].includes(estado)) return 'bg-rose-500/15 text-rose-300 border-rose-500/30';
  if (['en_proceso', 'perfil_presentado', 'investigacion_en_desarrollo', 'tribunal_asignado', 'defensa_programada', 'habilitado_examen'].includes(estado)) return 'bg-orange-500/15 text-orange-300 border-orange-500/30';
  return 'bg-white/5 text-slate-300 border-white/10';
};
</script>

<style scoped>
/* ============================================================
   Plantilla imprimible del reporte (fondo blanco, carta)
   ============================================================ */
/* El contenedor se saca de pantalla; el elemento capturado debe quedar con
   posicionamiento estático para que html2canvas lo renderice completo. */
.reporte-pdf-oculto {
  position: fixed;
  left: -12000px;
  top: 0;
  z-index: -9999;
  pointer-events: none;
}

.reporte-pdf {
  position: static;
  width: 186mm; /* área útil de una hoja carta con márgenes de 1.5 cm */
  box-sizing: border-box;
  padding: 0;
  background: #ffffff;
  color: #111827;
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, sans-serif;
  font-size: 9.5px;
  line-height: 1.4;
}

.pdf-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #111827;
}

.pdf-universidad {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.pdf-sistema {
  font-size: 8.5px;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.pdf-titulo {
  font-size: 16px;
  font-weight: 700;
  margin-top: 4px;
  text-transform: uppercase;
}

.pdf-descripcion {
  font-size: 8.5px;
  color: #374151;
  margin-top: 3px;
}

.pdf-meta {
  text-align: right;
  font-size: 8px;
  color: #4b5563;
  white-space: nowrap;
}

.pdf-meta p {
  margin: 1px 0;
}

.pdf-kpis {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

.pdf-kpi {
  flex: 1;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  padding: 6px 8px;
  background: #f9fafb;
}

.pdf-kpi__label {
  display: block;
  font-size: 7.5px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6b7280;
}

.pdf-kpi__valor {
  display: block;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.1;
  margin-top: 2px;
}

.pdf-bloque {
  margin-top: 14px;
}

.pdf-columnas {
  display: flex;
  gap: 12px;
}

.pdf-columnas > .pdf-bloque {
  flex: 1;
  min-width: 0;
}

.pdf-subtitulo {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 5px;
  padding-bottom: 3px;
  border-bottom: 1px solid #9ca3af;
}

.pdf-tabla {
  width: 100%;
  border-collapse: collapse;
  font-size: 8px;
}

.pdf-tabla th,
.pdf-tabla td {
  border: 1px solid #d1d5db;
  padding: 3px 5px;
  text-align: right;
  vertical-align: top;
}

.pdf-tabla thead th {
  background: #e5e7eb;
  font-size: 7.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #111827;
}

.pdf-tabla tbody tr:nth-child(even) td {
  background: #f9fafb;
}

.pdf-izq {
  text-align: left !important;
}

.pdf-negrita {
  font-weight: 600;
}

.pdf-fila {
  page-break-inside: avoid;
  break-inside: avoid;
}

.pdf-nota {
  margin-top: 4px;
  font-size: 7.5px;
  color: #6b7280;
  font-style: italic;
}

.pdf-pie {
  margin-top: 16px;
  padding-top: 6px;
  border-top: 1px solid #d1d5db;
  font-size: 7.5px;
  color: #6b7280;
  text-align: center;
}
</style>
