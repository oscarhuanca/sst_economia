<template>
  <ModuloTarjeta
    v-if="esActa"
    :numero="numero"
    :titulo="titulo"
    :descripcion="descripcion"
    icono="file-text"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado" class="space-y-4">
      <div
        class="flex items-center gap-3 rounded-xl border px-4 py-3"
        :class="esActa ? 'border-orange-500/30 bg-orange-500/10' : 'border-emerald-500/30 bg-emerald-500/10'"
      >
        <AppIcon
          name="check-circle"
          :size="18"
          class="shrink-0"
          :class="esActa ? 'text-orange-300' : 'text-emerald-300'"
        />
        <p class="text-sm font-semibold" :class="esActa ? 'text-orange-200' : 'text-emerald-200'">
          {{ esActa
            ? `Acta generada y lectura registrada el ${fechaLegible(acta.fecha_lectura) || '—'}.`
            : 'Excelencia Académica concluida. El estudiante quedó titulado.' }}
        </p>
      </div>

      <dl class="grid sm:grid-cols-3 gap-2.5">
        <template v-if="!esActa">
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Resolución de designación</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ designacion.numero_resolucion || '—' }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Tribunal 1 / Tribunal 2</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">
              {{ nombreMiembro(informe.tribunal_1) }} / {{ nombreMiembro(informe.tribunal_2) }}
            </dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="dato-label">Calificación final</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">
              {{ formatoCalificacion(resolucion.calificacion_final) }}
            </dd>
          </div>
        </template>
        <div v-if="esActa" class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de lectura</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(acta.fecha_lectura) || '—' }}
          </dd>
        </div>
      </dl>

      <div class="flex flex-col sm:flex-row gap-2">
        <button type="button" class="btn-warm justify-center" :disabled="generando" @click="descargar">
          <AppIcon v-if="generando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="download" :size="18" />
          <span class="uppercase tracking-wide">{{ esActa ? 'Descargar acta' : 'Descargar reporte' }}</span>
        </button>
        <button type="button" class="btn-ghost justify-center" :disabled="generando" @click="visualizar">
          <AppIcon name="eye" :size="18" />
          <span class="uppercase tracking-wide">Visualizar</span>
        </button>
      </div>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      {{ textoBloqueo }}
    </div>

    <div v-else class="space-y-5">
      <dl v-if="!esActa" class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Resolución de designación</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ designacion.numero_resolucion || '—' }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de designación</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ designacion.fecha_resolucion ? fechaLegible(designacion.fecha_resolucion) : '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Tribunal 1 / Tribunal 2</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ nombreMiembro(informe.tribunal_1) }} / {{ nombreMiembro(informe.tribunal_2) }}
          </dd>
        </div>
        <div
          v-for="area in areas"
          :key="area.campo"
          class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5"
        >
          <dt class="dato-label">{{ area.etiqueta }} ({{ area.porcentaje }}%)</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ formatoCalificacion(resolucion[area.campo]) }}
          </dd>
        </div>
        <div class="rounded-xl bg-orange-500/10 border border-orange-500/30 px-3.5 py-2.5">
          <dt class="dato-label text-orange-200">Calificación final</dt>
          <dd class="text-sm font-semibold text-orange-200 mt-0.5">
            {{ formatoCalificacion(resolucion.calificacion_final) }}
          </dd>
        </div>
      </dl>

      <form v-if="esActa" class="space-y-4" @submit.prevent="generar">
        <section class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-3">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <p class="dato-label text-orange-200">5.1 Generación de Acta</p>
              <p class="dato-ayuda">Genere el acta y use la opción de visualización.</p>
            </div>
            <div class="flex gap-2">
              <button type="button" class="btn-ghost" :disabled="generando" @click="visualizar">
                <AppIcon name="eye" :size="15" />
                <span>Visualizar</span>
              </button>
            </div>
          </div>
        </section>

        <section class="space-y-3 rounded-xl border border-white/10 bg-white/5 px-3.5 py-3">
          <p class="dato-label text-orange-200">5.2 Lectura del acta</p>
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="label" for="excelencia-fecha-lectura">Fecha de lectura del acta</label>
              <input
                id="excelencia-fecha-lectura"
                v-model="form.fecha_lectura"
                type="date"
                class="input"
              />
            </div>
            <label class="flex items-start gap-2.5 cursor-pointer sm:mt-6">
              <input
                v-model="form.confirmado"
                type="checkbox"
                class="mt-0.5 h-4 w-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
              />
              <span class="text-xs leading-relaxed text-slate-300">
                Confirmo y valido que el acta fue leída en la fecha indicada.
              </span>
            </label>
          </div>
        </section>

        <ul v-if="errores.length" class="space-y-1">
          <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
            <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
            {{ e }}
          </li>
        </ul>

        <div class="flex justify-end">
          <button type="submit" class="btn-warm px-6" :disabled="cargando || generando">
            <AppIcon v-if="generando || cargando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="file-text" :size="18" />
            <span class="uppercase tracking-wide">Guardar</span>
          </button>
        </div>
      </form>
    </div>
  </ModuloTarjeta>

  <div v-else class="card inline-flex p-3" aria-label="Reporte">
    <button type="button" class="btn-warm px-6" :disabled="generando" @click="generar">
      <AppIcon v-if="generando" name="loader" :size="18" class="animate-spin" />
      <AppIcon v-else name="file-text" :size="18" />
      <span class="uppercase tracking-wide">Generar reporte</span>
    </button>
  </div>


  <!-- Área imprimible (fuera de pantalla): el PDF se arma en el frontend. -->
  <div class="reporte-oculto" aria-hidden="true">
    <div ref="contenedorReporte" class="reporte-pagina">
      <header class="doc-header">
        <div class="doc-header__brand">
          <img src="/images/logo.png" alt="Logo institucional" class="doc-logo" crossorigin="anonymous" />
          <div class="doc-brand-text">
            <p class="doc-brand-text__title">Universidad Pública de El Alto</p>
            <p class="doc-brand-text__line">Sistema de Seguimiento de Titulación — Carrera de Economía</p>
            <p class="doc-brand-text__line">El Alto — Bolivia</p>
          </div>
        </div>
      </header>

      <section class="doc-info">
        <div class="doc-info__estudiante">
          <h3>Datos del Estudiante</h3>
          <p><strong>{{ estudiante.nombre }}</strong></p>
          <p>C.I.: {{ estudiante.ci }} · R.U.: {{ estudiante.ru }}</p>
          <p v-if="estudiante.promedio">Promedio general: {{ estudiante.promedio }}</p>
        </div>
        <div class="doc-info__meta">
          <h3>Datos del Trámite</h3>
          <p><strong>N.º:</strong> #{{ tramite?.id_tramite || '—' }}</p>
          <p><strong>Modalidad:</strong> {{ tramite?.modalidad?.nombre || 'Excelencia Académica' }}</p>
          <p><strong>Emitido por:</strong> {{ emitter }}</p>
          <p><strong>Fecha:</strong> {{ fechaEmision }}</p>
        </div>
      </section>

      <h1 class="doc-titulo">{{ docTitulo }}</h1>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">1. Designación del Tribunal de Evaluación</h2>
        <table class="doc-tabla">
          <tbody>
            <tr>
              <td class="doc-tabla__desc">Resolución del HCC de designación</td>
              <td class="doc-tabla__detalle">{{ designacion.numero_resolucion || '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Fecha de la resolución</td>
              <td class="doc-tabla__detalle">{{ designacion.fecha_resolucion ? fechaLegible(designacion.fecha_resolucion) : '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Fecha de lectura del acta</td>
              <td class="doc-tabla__detalle">{{ fechaLecturaPdf ? fechaLegible(fechaLecturaPdf) : '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Tribunal 1</td>
              <td class="doc-tabla__detalle">{{ nombreMiembro(informe.tribunal_1) }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Tribunal 2</td>
              <td class="doc-tabla__detalle">{{ nombreMiembro(informe.tribunal_2) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">2. Calificación</h2>
        <table class="doc-tabla">
          <tbody>
            <tr v-for="area in areas" :key="area.campo">
              <td class="doc-tabla__desc">{{ area.etiqueta }} ({{ area.porcentaje }}%)</td>
              <td class="doc-tabla__detalle">{{ formatoCalificacion(resolucion[area.campo]) }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc"><strong>Calificación final</strong></td>
              <td class="doc-tabla__detalle"><strong>{{ formatoCalificacion(resolucion.calificacion_final) }}</strong></td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">3. Dictamen</h2>
        <p class="doc-parrafo">
          En virtud de la designación del Tribunal de Evaluación, del Informe de Evaluación
          presentado, de la calificación obtenida y del cumplimiento del plan de estudios de la carrera de {{ carrera }},
          el Consejo RESUELVE <strong>TITULAR</strong> al estudiante
          <strong>{{ estudiante.nombre }}</strong>, con C.I. {{ estudiante.ci }} y R.U.
          {{ estudiante.ru }}, en la modalidad de
          <strong>{{ tramite?.modalidad?.nombre || 'Excelencia Académica' }}</strong>.
        </p>
      </section>

      <footer class="doc-footer">
        <div class="doc-firma">
          <span class="doc-firma__linea"></span>
          <p class="doc-firma__cargo">Presidenta del HCC</p>
        </div>
        <div class="doc-firma">
          <span class="doc-firma__linea"></span>
          <p class="doc-firma__cargo">Secretaría General</p>
        </div>
        <div class="doc-firma">
          <span class="doc-firma__linea"></span>
          <p class="doc-firma__cargo">Kardex</p>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
// Comparte la generación del PDF entre el punto 5 (acta y fecha de lectura) y
// el punto 6 (reporte final). El PDF se arma en el frontend con html2pdf a
// partir de los datos que KARDEX registró (`hitos.modulos`), fuente de verdad.
//
// En ambos casos el PDF se genera antes de guardar el módulo: si falla, el
// trámite no avanza y no queda una constancia emitida sin registro.
import { computed, reactive, ref } from 'vue';
import html2pdf from 'html2pdf.js';
import {
    AREAS_CALIFICACION,
    MODULO_ACTA,
    MODULO_REPORTE,
    actaLectura,
    designacionTribunal,
    formatoCalificacion,
    informeTribunal,
    nombreDocente,
    resolucionHcc,
    validarModulo,
} from '@/modules/excelencia/utils/flujoExcelencia';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
    numero: { type: [Number, String], required: true },
    modo: { type: String, default: 'reporte' },
    completado: { type: Boolean, default: false },
    enProgreso: { type: Boolean, default: false },
    bloqueado: { type: Boolean, default: false },
    cargando: { type: Boolean, default: false },
    tramite: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const toastStore = useToastStore();
const authStore = useAuthStore();

const contenedorReporte = ref(null);
const generando = ref(false);
const errores = ref([]);
const form = reactive({ fecha_lectura: '', confirmado: false });

const areas = AREAS_CALIFICACION;
const esActa = computed(() => props.modo === 'acta');
const moduloId = computed(() => (esActa.value ? MODULO_ACTA : MODULO_REPORTE));

const titulo = computed(() => (esActa.value ? 'Generación y lectura del acta' : 'Reporte'));
const descripcion = computed(() => (esActa.value
    ? 'Genera el acta oficial de Excelencia Académica y registra la fecha en que fue leída.'
    : 'Genera el reporte final de Excelencia Académica y permite visualizarlo e imprimirlo.'));
const textoBloqueo = computed(() => (esActa.value
    ? 'Registre la calificación por áreas antes de generar el acta.'
    : ''));
const docTitulo = computed(() => (esActa.value
    ? 'Acta de Excelencia Académica'
    : 'Reporte Final de Excelencia Académica'));

const designacion = computed(() => designacionTribunal(props.tramite));
const informe = computed(() => informeTribunal(props.tramite));
const resolucion = computed(() => resolucionHcc(props.tramite));
const acta = computed(() => actaLectura(props.tramite));
const fechaLecturaPdf = computed(() => (esActa.value
    ? form.fecha_lectura || acta.value.fecha_lectura
    : acta.value.fecha_lectura));

function nombreMiembro(miembro) {
    return nombreDocente(miembro) || '—';
}

const carrera = 'Economía';

const estudiante = computed(() => ({
    nombre: [props.tramite?.estudiante?.nombres, props.tramite?.estudiante?.apellidos]
        .filter(Boolean)
        .join(' ') || '—',
    ci: props.tramite?.estudiante?.ci || '—',
    ru: props.tramite?.estudiante?.registro_universitario || '—',
    promedio: props.tramite?.estudiante?.promedio_global ?? null,
}));

const emitter = computed(() => authStore.user?.nombres || 'Kardex Académico');
const fechaEmision = computed(() => fechaLegible(new Date().toISOString()));

/* Márgenes de 1.5 cm iguales en los cuatro lados (mismo criterio que el acta
   de Examen de Grado): el HTML debe medir exactamente el ancho útil. */
const OPCIONES_PDF = {
    margin: [15, 15, 15, 15],
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 3, useCORS: true, allowTaint: false, logging: false, backgroundColor: '#ffffff' },
    jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
    pagebreak: { mode: ['css', 'legacy'] },
};

function nombreReporte() {
    const sufijo = props.tramite?.id_tramite ? `_${props.tramite.id_tramite}` : '';
    const base = esActa.value ? 'acta_excelencia_academica' : 'reporte_final_excelencia_academica';
    return `${base}${sufijo}.pdf`;
}

async function esperarImagenes(contenedor) {
    await Promise.all(
        Array.from(contenedor.querySelectorAll('img')).map((img) =>
            img.complete
                ? Promise.resolve()
                : new Promise((res) => {
                    img.addEventListener('load', res, { once: true });
                    img.addEventListener('error', res, { once: true });
                }),
        ),
    );
}

function paginar(pdf) {
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
}

async function producirPdf() {
    if (!contenedorReporte.value) return null;
    await esperarImagenes(contenedorReporte.value);
    await new Promise((r) => setTimeout(r, 100));
    const pdf = await html2pdf().set(OPCIONES_PDF).from(contenedorReporte.value).toPdf().get('pdf');
    paginar(pdf);
    return pdf;
}

function registrarErrorPdf(error) {
    console.error('[Reporte Excelencia] No se pudo generar el PDF:', error);
    toastStore.error(`No se pudo generar el ${esActa.value ? 'acta' : 'reporte'} PDF. Intente nuevamente.`);
}

async function generar() {
    const datos = esActa.value
        ? { fecha_lectura: form.fecha_lectura, confirmado: form.confirmado }
        : null;

    if (esActa.value) {
        const res = validarModulo(moduloId.value, datos);

        if (!res.ok) {
            errores.value = res.errores;
            return;
        }
    }

    errores.value = [];
    generando.value = true;

    try {
        const pdf = await producirPdf();
        if (!pdf) return;
        pdf.save(nombreReporte());
    } catch (error) {
        registrarErrorPdf(error);
        return;
    } finally {
        generando.value = false;
    }

    if (esActa.value) emit('guardar', datos);
}

async function descargar() {
    generando.value = true;
    try {
        const pdf = await producirPdf();
        if (!pdf) return;
        pdf.save(nombreReporte());
    } catch (error) {
        registrarErrorPdf(error);
    } finally {
        generando.value = false;
    }
}

async function visualizar() {
    generando.value = true;
    try {
        const pdf = await producirPdf();
        if (!pdf) return;
        const url = URL.createObjectURL(pdf.output('blob'));
        window.open(url, '_blank');
        setTimeout(() => URL.revokeObjectURL(url), 60_000);
    } catch (error) {
        registrarErrorPdf(error);
    } finally {
        generando.value = false;
    }
}
</script>

<style scoped>
/* ============================================================
   Área imprimible — Acta de Excelencia Académica (blanco y negro)
   ============================================================ */

.reporte-oculto {
    position: fixed;
    left: -12000px;
    top: 0;
    z-index: -9999;
    pointer-events: none;
}

.reporte-pagina {
    width: 100%;               /* igual al área de captura de html2pdf */
    min-height: 941px;         /* ≈ 249 mm: altura útil de la carta */
    box-sizing: border-box;
    padding: 0;                /* el margen de 1.5 cm lo pone jsPDF */
    background: #ffffff;
    color: #000000;
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
        "Helvetica Neue", Arial, sans-serif;
    font-size: 10px;
    line-height: 1.4;
    overflow: hidden;
    overflow-wrap: anywhere;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}

.doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #000;
    width: 100%;
}

.doc-header__brand {
    display: flex;
    align-items: center;
    gap: 10px;
}

.doc-logo {
    width: 52px;
    height: 52px;
    object-fit: contain;
}

.doc-brand-text__title {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.doc-brand-text__line {
    font-size: 8.5px;
    color: #333;
}

.doc-info {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
    gap: 12px;
    margin-top: 10px;
    width: 100%;
    box-sizing: border-box;
}

.doc-info__estudiante,
.doc-info__meta {
    border: 1px solid #000;
    padding: 7px 9px;
    min-width: 0;
    overflow-wrap: anywhere;
}

.doc-info h3 {
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    border-bottom: 1px solid #999;
    padding-bottom: 3px;
    margin-bottom: 4px;
}

.doc-titulo {
    text-align: center;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin: 14px 0 10px;
}

.doc-seccion {
    margin: 0 0 10px 0;
    width: 100%;
    box-sizing: border-box;
    page-break-inside: avoid;
}

.doc-seccion__titulo {
    font-size: 10px;
    font-weight: 800;
    margin-bottom: 4px;
    border-left: 3px solid #000;
    padding-left: 6px;
}

.doc-parrafo {
    text-align: justify;
    margin-bottom: 5px;
}

.doc-tabla {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    table-layout: fixed;
    box-sizing: border-box;
}

.doc-tabla th,
.doc-tabla td {
    border: 1px solid #000;
    padding: 4px 6px;
    text-align: left;
    vertical-align: top;
    box-sizing: border-box;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

.doc-tabla tr {
    page-break-inside: avoid;
}

.doc-tabla__desc {
    width: 55%;
}

.doc-tabla__detalle {
    width: 45%;
}

.doc-footer {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-top: auto;
    padding-top: 18px;
    text-align: center;
    page-break-inside: avoid;
}

.doc-firma__linea {
    display: block;
    height: 1px;
    background: #000;
    margin-bottom: 3px;
}

.doc-firma__cargo {
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>
