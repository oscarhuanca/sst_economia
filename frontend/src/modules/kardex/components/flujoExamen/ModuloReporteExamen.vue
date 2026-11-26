<template>
  <div class="card inline-flex p-3" aria-label="Reporte">
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
          <p><strong>Modalidad:</strong> {{ tramite?.modalidad?.nombre || 'Examen de Grado' }}</p>
          <p><strong>Intentos:</strong> {{ numeroIntentos }} de {{ MAXIMO_INTENTOS }}</p>
          <p><strong>Emitido por:</strong> {{ emitter }}</p>
        </div>
      </section>

      <h1 class="doc-titulo">Acta de Examen de Grado</h1>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">1. Habilitación</h2>
        <table class="doc-tabla">
          <tbody>
            <tr>
              <td class="doc-tabla__desc">Resolución del Consejo Universitario</td>
              <td class="doc-tabla__detalle">{{ habilitacion?.numero_resolucion || '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Fecha de la resolución</td>
              <td class="doc-tabla__detalle">{{ fechaLegible(habilitacion?.fecha_resolucion || '') || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">2. Resultados de los exámenes</h2>
        <table class="doc-tabla">
          <thead>
            <tr>
              <th class="doc-tabla__desc">Examen</th>
              <th class="doc-tabla__tema">Tema sorteado</th>
              <th class="doc-tabla__fecha">Fecha</th>
              <th class="doc-tabla__nota">Nota</th>
              <th class="doc-tabla__estado">Resultado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ex in exams" :key="ex.clave">
              <td class="doc-tabla__desc">{{ ex.etiqueta }}</td>
              <td class="doc-tabla__tema">{{ ex.tema }}</td>
              <td class="doc-tabla__fecha">{{ ex.fecha }}</td>
              <td class="doc-tabla__nota">{{ ex.nota }}</td>
              <td class="doc-tabla__estado">{{ ex.resultado }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">3. Historial de resoluciones del HCC</h2>
        <table class="doc-tabla">
          <thead>
            <tr>
              <th class="doc-tabla__resolucion">Resolución</th>
              <th class="doc-tabla__fecha">Fecha</th>
              <th class="doc-tabla__desc">Examen teórico</th>
              <th class="doc-tabla__desc">Examen de especialidad</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in resoluciones" :key="r.id_historial_examen">
              <td class="doc-tabla__resolucion">
                {{ r.numero }}<template v-if="r.vigente"> (Vigente)</template>
              </td>
              <td class="doc-tabla__fecha">{{ fechaLegible(r.fecha) || '—' }}</td>
              <td class="doc-tabla__desc">{{ textoExamen(r.teorico) }}</td>
              <td class="doc-tabla__desc">{{ textoExamen(r.especialidad) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">4. Dictamen</h2>
        <p v-if="aprobadoMode" class="doc-parrafo">
          En virtud a los resultados obtenidos y al cumplimiento del plan de estudios de la
          carrera de {{ carrera }}, el Consejo de la Universidad Pública de El Alto RESUELVE
          <strong>TITULAR</strong> al estudiante
          <strong>{{ estudiante.nombre }}</strong>, con C.I. {{ estudiante.ci }} y R.U.
          {{ estudiante.ru }}, en la modalidad de
          <strong>{{ tramite?.modalidad?.nombre || 'Examen de Grado' }}</strong>.
        </p>
        <p v-else class="doc-parrafo">
          En virtud a los resultados obtenidos durante el examen de grado, el Consejo de la
          Universidad Pública de El Alto hace constar que el estudiante
          <strong>{{ estudiante.nombre }}</strong>, con C.I. {{ estudiante.ci }} y R.U.
          {{ estudiante.ru }}, <strong>NO CORRESPONDE TITULAR</strong> al haber reprobado
          {{ reprobadosLista }}. Deberá volver a solicitar el examen de grado según el reglamento
          y tramitar una nueva habilitación conforme a la Resolución del HCC vigente.
        </p>
        <p v-if="aprobadoMode" class="doc-parrafo">
          Nota final del examen de grado (promedio): <strong>{{ promedioFinal }}</strong>.
        </p>
      </section>

      <footer class="doc-footer">
        
        <div class="doc-firma">
          <span class=""></span>
          <p class="doc-firma__cargo"></p>
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
import { computed, ref } from 'vue';
import html2pdf from 'html2pdf.js';
import {
  MAXIMO_INTENTOS,
  NOTA_MINIMA,
  historialExamen,
  resolucionesHistorial,
  notaFinalExamen,
} from '@/modules/examen/utils/flujoExamen';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
  tramite: { type: Object, default: null },
});

const toastStore = useToastStore();
const authStore = useAuthStore();

const contenedorReporte = ref(null);
const generando = ref(false);

/** Último registro del historial correspondiente a una etapa del flujo. */
function registroDe(etapa) {
  const filas = historialExamen(props.tramite).filter((f) => f.etapa === etapa);
  return filas.length ? filas[filas.length - 1] : null;
}

const habilitacion = computed(() => registroDe('habilitacion_examen'));
const numeroIntentos = computed(() => Number(props.tramite?.estudiante?.numero_intentos ?? 0));

const ETIQUETAS = [
  { clave: 'sorteo_teorico', etiqueta: 'Examen teórico', campoNota: 'nota_teorico' },
  { clave: 'sorteo_especialidad', etiqueta: 'Examen de especialidad', campoNota: 'nota_especialidad' },
];

const carrera = 'Economía';

const aprobadoMode = computed(() => ['aprobado', 'titulado'].includes(props.tramite?.estado_actual));

/** Notas asignadas en el módulo 7 (fuente de verdad de las notas). */
const notasAsignadas = computed(() => props.tramite?.hitos?.modulos?.asignacion_notas || {});

/**
 * Resoluciones del HCC con la suerte de cada examen en cada una.
 *
 * `hitos.examen_aprobado` conserva los exámenes aprobados de la resolución
 * vigente (los reprobados se leen de `hitos.modulos.asignacion_notas`); las
 * resoluciones anteriores quedaron sin aprobados, porque solo se cambia la
 * resolución cuando AMBOS exámenes salieron reprobados.
 */
const resoluciones = computed(() => {
  const conservados = props.tramite?.hitos?.examen_aprobado || {};
  const notas = notasAsignadas.value;

  return resolucionesHistorial(props.tramite).map((h) => {
    if (!h.vigente) {
      return {
        id_historial_examen: h.id_historial_examen,
        numero: h.numero_resolucion || '—',
        fecha: h.fecha_resolucion || '',
        vigente: false,
        teorico: { aprobado: false, nota: null },
        especialidad: { aprobado: false, nota: null },
      };
    }

    const teorico = conservados.examen_teorico;
    const especialidad = conservados.examen_especialidad;

    return {
      id_historial_examen: h.id_historial_examen,
      numero: h.numero_resolucion || '—',
      fecha: h.fecha_resolucion || '',
      vigente: true,
      teorico: teorico
        ? { aprobado: true, nota: teorico.nota }
        : { aprobado: false, nota: notas.nota_teorico ?? null },
      especialidad: especialidad
        ? { aprobado: true, nota: especialidad.nota }
        : { aprobado: false, nota: notas.nota_especialidad ?? null },
    };
  });
});

/** Exámenes del intento en curso que quedaron reprobados, como lista. */
const reprobadosLista = computed(() => {
  const fallidos = exams.value.filter(
    (e) => e.resultado === 'REPROBADO' || e.resultado === 'NO ASISTIÓ',
  );

  if (!fallidos.length) return 'los exámenes';

  return fallidos.map((e) => `el ${e.etiqueta.toLowerCase()}`).join(' y ');
});

function tieneNota(nota) {
  return nota !== null && nota !== undefined && nota !== '';
}

/** Resultado legible de un examen en el historial de resoluciones. */
function textoExamen(ex) {
  if (ex.aprobado) return `APROBADO — Nota ${ex.nota}`;
  return tieneNota(ex.nota) ? `Reprobado — Nota ${ex.nota}` : 'Reprobado';
}

function estadoClase(aprobado) {
  return aprobado ? 'font-semibold text-emerald-300' : 'font-semibold text-red-300';
}

/** ¿El estudiante asistió al sorteo de esa etapa? */
function asistioA(etapa) {
  return ['asistio', 'aprobado', 'reprobado'].includes(registroDe(etapa)?.resultado);
}

/** Datos de cada examen tal como figuran en el reporte. */
const exams = computed(() =>
  ETIQUETAS.map(({ clave, etiqueta, campoNota }) => {
    const r = registroDe(clave);
    const asistio = asistioA(clave);
    const nota = notasAsignadas.value[campoNota] ?? null;
    const tieneNota = nota !== null && nota !== undefined && nota !== '';

    return {
      clave,
      etiqueta,
      tema: r?.tema || '—',
      fecha: fechaLegible(r?.fecha_examen || '') || '—',
      nota: asistio && tieneNota ? `${String(nota)} / 100` : '—',
      resultado: !asistio
        ? 'NO ASISTIÓ'
        : !tieneNota
          ? '—'
          : Number(nota) >= NOTA_MINIMA ? 'APROBADO' : 'REPROBADO',
    };
  }),
);

const estudiante = computed(() => ({
  nombre: [props.tramite?.estudiante?.nombres, props.tramite?.estudiante?.apellidos]
    .filter(Boolean)
    .join(' ') || '—',
  ci: props.tramite?.estudiante?.ci || '—',
  ru: props.tramite?.estudiante?.registro_universitario || '—',
  promedio: props.tramite?.estudiante?.promedio_global ?? null,
}));

const promedioFinal = computed(() => {
  const notas = notasAsignadas.value || {};
  const valor = notas.nota_final ?? notaFinalExamen(notas);

  if (valor === null || valor === undefined || valor === '') return '—';
  return `${Number(valor).toFixed(2)} / 100`;
});

const emitter = computed(() => authStore.user?.nombres || 'Kardex Académico');
const fechaEmision = computed(() => fechaLegible(new Date().toISOString()));

/* Márgenes de 1.5 cm iguales en los cuatro lados. html2pdf captura el HTML
   dentro de esa área (página − márgenes), por lo que `.reporte-pagina` debe
   medir exactamente el ancho útil y NO llevar padding propio: si el HTML es
   más ancho que el área de captura se recorta por los costados. */
const OPCIONES_PDF = {
  margin: [15, 15, 15, 15],
  image: { type: 'jpeg', quality: 0.98 },
  html2canvas: { scale: 3, useCORS: true, allowTaint: false, logging: false, backgroundColor: '#ffffff' },
  jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
  pagebreak: { mode: ['css', 'legacy'] },
};

function nombreReporte() {
  const sufijo = props.tramite?.id_tramite ? `_${props.tramite.id_tramite}` : '';
  return `reporte_examen_grado${sufijo}.pdf`;
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
  console.error('[Reporte Examen] No se pudo generar el PDF:', error);
  toastStore.error('No se pudo generar el reporte PDF. Intente nuevamente.');
}

async function generar() {
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
</script>

<style scoped>
/* ============================================================
   Área imprimible — Acta de Examen de Grado (blanco y negro)
   ============================================================ */

.reporte-oculto {
  position: fixed;
  left: -12000px;
  top: 0;
  z-index: -9999;
  pointer-events: none;
}

.reporte-pagina {
  width: 100%;               /* igual al área de captura de html2pdf: no sobra
                                ancho, así nada se recorta a izquierda/derecha */
  min-height: 941px;         /* ≈ 249 mm: altura útil de la carta con margen de
                                1.5 cm (249.4 mm), 3 px menos para que html2pdf
                                no genere una segunda página vacía */
  box-sizing: border-box;
  padding: 0;                /* el margen de 1.5 cm lo pone jsPDF, no el HTML */
  background: #ffffff;
  color: #000000;
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, sans-serif;
  font-size: 10px;
  line-height: 1.4;
  overflow: hidden;
  overflow-wrap: anywhere;   /* ninguna palabra larga desborda el contenido */
  display: flex;
  flex-direction: column;
  align-items: stretch;      /* fuerza a los hijos a ocupar el ancho útil */
}

/* --- Encabezado --- */
.doc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #000;
  width: 100%;               /* ocupa todo el ancho útil */
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

.doc-header__right {
  text-align: right;
  flex-shrink: 0;
}

.doc-tagline {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.12em;
  border: 1.5px solid #000;
  padding: 2px 6px;
  display: inline-block;
}

.doc-tagline2 {
  font-size: 8.5px;
  color: #333;
  margin-top: 4px;
}

/* --- Datos del estudiante y del trámite --- */
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
  min-width: 0;              /* la columna no crece más allá de su ancho */
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

/* --- Título --- */
.doc-titulo {
  text-align: center;
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  margin: 14px 0 10px;
}

/* --- Secciones ---
   Centradas y sin desbordar. `box-sizing: border-box` evita que el
   padding/border sumen al ancho del contenedor padre. */
.doc-seccion {
  margin: 0 0 10px 0;
  width: 100%;
  box-sizing: border-box;
  page-break-inside: avoid;  /* la sección (y su tabla) no se parte entre páginas */
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

/* --- Tablas ---
   `table-layout: fixed` obliga a respetar los anchos de columna;
   `width: 100%` + `margin: 0 auto` centra la tabla dentro de la sección. */
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
  word-wrap: break-word;        /* rompe palabras largas */
  overflow-wrap: break-word;
  white-space: normal;          /* permite bajar de línea */
}

.doc-tabla th {
  background: #e5e5e5;
  font-size: 8.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* Anchos de columna: deben sumar 100% en cada tabla para que ninguna
   columna (sobre todo la primera, pegada al margen izquierdo) se recorte */
.doc-tabla__desc {
  width: 30%;
}

.doc-tabla__detalle {
  width: 70%;
}

.doc-tabla__tema {
  width: 30%;
}

.doc-tabla__resolucion {
  width: 28%;
}

.doc-tabla__fecha {
  width: 12%;
}

.doc-tabla__nota {
  width: 10%;
}

.doc-tabla__estado {
  width: 18%;
}

.doc-tabla__estado,
.doc-tabla__nota {
  font-weight: 700;
  text-align: center;
}

/* Evita que una fila se parta entre páginas al imprimir */
.doc-tabla tr {
  page-break-inside: avoid;
}

/* --- Firmas --- */
.doc-footer {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
  margin-top: auto;
  padding-top: 18px;
  text-align: center;
  page-break-inside: avoid;  /* las firmas no se parten entre páginas */
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
