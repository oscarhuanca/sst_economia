<template>
  <ModuloTarjeta
    numero="3"
    titulo="Reporte Final"
    descripcion="Emite el acta oficial de Excelencia Académica; titula al estudiante cuando su promedio cumple el mínimo reglamentario y la lectura del acta ya fue registrada."
    icono="file-text"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Estudiante ya titulado -->
    <div v-if="completado" class="space-y-4">
      <div
        class="flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3"
      >
        <AppIcon name="check-circle" :size="18" class="shrink-0 text-emerald-300" />
        <p class="text-sm font-semibold text-emerald-200">
          Excelencia Académica concluida. El estudiante quedó titulado.
        </p>
      </div>

      <dl class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio verificado</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ verificado !== null ? `${verificado} / 100` : '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Resolución del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ resolucion.numero_resolucion || '—' }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Lectura del acta</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(resolucion.fecha_lectura_acta || '') || '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Título emitido</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(tramite?.hitos?.titulado || '') || '—' }}
          </dd>
        </div>
      </dl>

      <div class="flex flex-col sm:flex-row gap-2">
        <button type="button" class="btn-warm justify-center" :disabled="generando" @click="descargar">
          <AppIcon v-if="generando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="download" :size="18" />
          <span class="uppercase tracking-wide">Descargar acta</span>
        </button>
        <button type="button" class="btn-ghost justify-center" :disabled="generando" @click="visualizar">
          <AppIcon name="eye" :size="18" />
          <span class="uppercase tracking-wide">Visualizar</span>
        </button>
      </div>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      Registre la Resolución del HCC y la fecha de lectura del acta antes de emitir el acta de titulación.
    </div>

    <!-- Emisión del acta: genera el PDF y, sólo si sale bien, titula -->
    <div v-else class="space-y-5">
      <dl class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">N.º de Resolución del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ resolucion.numero_resolucion || '—' }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la resolución</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(resolucion.fecha_resolucion || '') || '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Lectura del acta</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(resolucion.fecha_lectura_acta || '') || '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio declarado</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ declarado !== null ? `${declarado} / 100` : '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio verificado</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ verificado !== null ? `${verificado} / 100` : '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Mínimo exigido</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ notaMinima }} / 100</dd>
        </div>
      </dl>

      <p class="aviso-bloqueo !border-amber-500/40 !bg-amber-500/10 !text-amber-200">
        <AppIcon name="alert-triangle" :size="15" class="shrink-0" />
        Generar el acta titula al estudiante y cierra el trámite: no admite más cambios.
      </p>

      <ul v-if="errores.length" class="space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <div class="flex justify-end">
        <button type="button" class="btn-warm px-6" :disabled="cargando || generando" @click="generar">
          <AppIcon v-if="generando || cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="file-text" :size="18" />
          <span class="uppercase tracking-wide">Generar acta y titular</span>
        </button>
      </div>
    </div>
  </ModuloTarjeta>

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

      <h1 class="doc-titulo">Acta de Excelencia Académica</h1>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">1. Verificación del promedio general</h2>
        <table class="doc-tabla">
          <tbody>
            <tr>
              <td class="doc-tabla__desc">Promedio declarado por el estudiante</td>
              <td class="doc-tabla__detalle">{{ declarado !== null ? declarado : '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Promedio verificado por el HCC</td>
              <td class="doc-tabla__detalle">{{ verificado !== null ? verificado : '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Promedio mínimo exigido por la modalidad</td>
              <td class="doc-tabla__detalle">{{ notaMinima }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Resultado de la verificación</td>
              <td class="doc-tabla__detalle">{{ verificado !== null && verificado >= notaMinima ? 'HABILITADO' : 'NO HABILITADO' }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">2. Resolución del Consejo de la Universidad</h2>
        <table class="doc-tabla">
          <tbody>
            <tr>
              <td class="doc-tabla__desc">N.º de Resolución</td>
              <td class="doc-tabla__detalle">{{ resolucion.numero_resolucion || '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Fecha de la resolución</td>
              <td class="doc-tabla__detalle">{{ fechaLegible(resolucion.fecha_resolucion || '') || '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__desc">Fecha de lectura del acta</td>
              <td class="doc-tabla__detalle">{{ fechaLegible(resolucion.fecha_lectura_acta || '') || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-seccion__titulo">3. Dictamen</h2>
        <p class="doc-parrafo">
          En virtud del promedio general sobresaliente verificado por el Consejo de la
          Universidad Pública de El Alto y al cumplimiento del plan de estudios de la
          carrera de {{ carrera }}, el Consejo RESUELVE <strong>TITULAR</strong> al estudiante
          <strong>{{ estudiante.nombre }}</strong>, con C.I. {{ estudiante.ci }} y R.U.
          {{ estudiante.ru }}, en la modalidad de
          <strong>{{ tramite?.modalidad?.nombre || 'Excelencia Académica' }}</strong>.
        </p>
        <p class="doc-parrafo">
          Promedio final verificado: <strong>{{ verificado !== null ? verificado : '—' }}</strong>.
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
// Módulo 3 del flujo de Excelencia Académica: emite el acta oficial y titula
// al estudiante. El PDF se arma en el frontend con html2pdf a partir de los
// datos que KARDEX registró (`hitos.modulos`), que son la fuente de verdad.
//
// Emitir el acta y titular son la misma acción: el botón genera el PDF y sólo
// si sale bien guarda el módulo, que deja el trámite en `titulado`. Si el PDF
// falla, el módulo NO se guarda y el estudiante no queda titular sin constancia.
import { computed, ref } from 'vue';
import html2pdf from 'html2pdf.js';
import {
    NOTA_MINIMA,
    promedioDeclarado,
    promedioVerificado,
    resolucionHcc,
    validarModulo,
} from '@/modules/excelencia/utils/flujoExcelencia';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
    completado: { type: Boolean, default: false },
    enProgreso: { type: Boolean, default: false },
    bloqueado: { type: Boolean, default: false },
    cargando: { type: Boolean, default: false },
    tramite: { type: Object, default: null },
    // Mínimo reglamentario: lo define el backend (`config/excelencia.php`).
    notaMinima: { type: Number, default: NOTA_MINIMA },
});

const emit = defineEmits(['guardar']);

const toastStore = useToastStore();
const authStore = useAuthStore();

const contenedorReporte = ref(null);
const generando = ref(false);
const errores = ref([]);

const declarado = computed(() => promedioDeclarado(props.tramite));
const verificado = computed(() => promedioVerificado(props.tramite));
const resolucion = computed(() => resolucionHcc(props.tramite));

// Mínimo reglamentario vigente (lo define el backend).
const notaMinima = computed(() => Number(props.notaMinima) || NOTA_MINIMA);

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
    return `acta_excelencia_academica${sufijo}.pdf`;
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
    toastStore.error('No se pudo generar el acta PDF. Intente nuevamente.');
}

/** Genera el PDF y, sólo si sale bien, titula al estudiante. */
async function generar() {
    const res = validarModulo('reporte', {});

    if (!res.ok) {
        errores.value = res.errores;
        return;
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

    emit('guardar', {});
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
