<template>
  <ModuloTarjeta
    numero="8"
    titulo="Reporte"
    descripcion="Emite el acta oficial del Examen de Grado; titula al estudiante cuando aprobó ambos exámenes."
    icono="file-text"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado" class="space-y-4">
      <div
        class="flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3"
      >
        <AppIcon name="check-circle" :size="18" class="shrink-0 text-emerald-300" />
        <p class="text-sm font-semibold text-emerald-200">
          Examen de Grado aprobado. El estudiante quedó titulado.
        </p>
      </div>

      <dl class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Examen teórico</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ notaDe('sorteo_teorico') }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Examen de especialidad</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ notaDe('sorteo_especialidad') }}</dd>
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
          <span class="uppercase tracking-wide">Descargar reporte</span>
        </button>
        <button type="button" class="btn-ghost justify-center" :disabled="generando" @click="visualizar">
          <AppIcon name="eye" :size="18" />
          <span class="uppercase tracking-wide">Visualizar</span>
        </button>
      </div>
    </div>

    <!-- Estado reprobado: se emite el acta de resultados, sin titular. -->
    <div v-else-if="reprobadoMode" class="space-y-4">
      <div
        class="flex items-start gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3"
      >
        <AppIcon name="alert-triangle" :size="18" class="shrink-0 mt-0.5 text-amber-300" />
        <p class="text-sm font-semibold text-amber-200 leading-relaxed">
          El estudiante reprobó {{ reprobadosLista }}. El acta documenta los resultados bajo la
          Resolución del HCC vigente y NO titula al estudiante; para re-solicitar el examen
          use «Reabrir examen» en el panel superior, pero desontara el núemero de intentos.
        </p>
      </div>

      <dl class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 sm:col-span-3">
          <dt class="dato-label">N.º de Resolución del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ habilitacion?.numero_resolucion || '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Examen teórico</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ notaDe('sorteo_teorico') }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Examen de especialidad</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ notaDe('sorteo_especialidad') }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos utilizados</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ numeroIntentos }} de {{ MAXIMO_INTENTOS }}
          </dd>
        </div>
      </dl>

      <div class="flex flex-col sm:flex-row justify-end gap-2">
        <button type="button" class="btn-warm justify-center" :disabled="generando" @click="descargar">
          <AppIcon v-if="generando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="download" :size="18" />
          <span class="uppercase tracking-wide">Generar acta de resultados</span>
        </button>
        <button type="button" class="btn-ghost justify-center" :disabled="generando" @click="visualizar">
          <AppIcon name="eye" :size="18" />
          <span class="uppercase tracking-wide">Visualizar</span>
        </button>
      </div>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      El estudiante debe aprobar el examen teórico y el de especialidad (51 o más) para emitir el reporte.
    </div>

    <div v-else class="space-y-5">
      <dl class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 sm:col-span-3">
          <dt class="dato-label">N.º de Resolución del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ habilitacion?.numero_resolucion || '—' }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Examen teórico</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ notaDe('sorteo_teorico') }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Examen de especialidad</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ notaDe('sorteo_especialidad') }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos utilizados</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ numeroIntentos }} de {{ MAXIMO_INTENTOS }}
          </dd>
        </div>
      </dl>

      <p class="aviso-bloqueo !border-amber-500/40 !bg-amber-500/10 !text-amber-200">
        <AppIcon name="alert-triangle" :size="15" class="shrink-0" />
        Generar el reporte titula al estudiante y cierra el trámite: no admite más cambios.
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
          <span class="uppercase tracking-wide">Generar reporte y titular</span>
        </button>
      </div>
    </div>

    <!-- Historial de resoluciones del HCC: cuáles quedaron aprobadas y bajo cuál. -->
    <section v-if="!bloqueado && resoluciones.length" class="space-y-2.5 mt-6">
      <h5 class="dato-label uppercase tracking-wide">Historial de resoluciones del HCC</h5>
      <div class="overflow-x-auto rounded-xl border border-white/10">
        <table class="w-full text-sm">
          <thead class="bg-white/5 text-left text-[10px] uppercase tracking-wide text-slate-400">
            <tr>
              <th class="px-3 py-2">Resolución</th>
              <th class="px-3 py-2">Fecha</th>
              <th class="px-3 py-2">Examen teórico</th>
              <th class="px-3 py-2">Examen de especialidad</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="r in resoluciones" :key="r.id_historial_examen" class="align-top">
              <td class="px-3 py-2 font-semibold text-white">
                {{ r.numero }}
                <span
                  v-if="r.vigente"
                  class="ml-1 inline-block rounded-full bg-emerald-500/20 text-emerald-300 px-2 py-0.5 text-[9px] font-bold uppercase"
                >
                  Vigente
                </span>
              </td>
              <td class="px-3 py-2 text-slate-300">{{ fechaLegible(r.fecha) || '—' }}</td>
              <td class="px-3 py-2" :class="estadoClase(r.teorico.aprobado)">
                {{ textoExamen(r.teorico) }}
              </td>
              <td class="px-3 py-2" :class="estadoClase(r.especialidad.aprobado)">
                {{ textoExamen(r.especialidad) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
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
          Promedio final del examen de grado: <strong>{{ promedioFinal }}</strong>.
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
// Módulo 7 del flujo de Examen de Grado: genera el acta oficial y titula al
// estudiante. El PDF se arma en el frontend con html2pdf a partir de
// `historial_examen`, que es la fuente de verdad del examen (tema, fecha, nota y
// resolución), y no del estado actual, que tras una reapertura vuelve atrás.
//
// Emitir el reporte y titular son la misma acción CUANDO EL ESTUDIANTE APROBÓ:
// el botón guarda el módulo, que deja el trámite en `titulado`. Si el PDF
// falla, el módulo NO se guarda y el estudiante no queda titular sin constancia.
//
// Cuando el estudiante reprobó cualquiera de los dos exámenes también se puede
// generar el DOCUMENTO (acta de resultados) desde la tarjeta, pero sin titular:
// ahí no se guarda nada en el backend y la reapertura sigue disponible.
//
// El acta incluye el historial de Resoluciones del HCC con qué examen se aprobó
// bajo cada una (los aprobados viven en `hitos.examen_aprobado`).
import { computed, ref } from 'vue';
import html2pdf from 'html2pdf.js';
import {
  ESTADOS_REAPERIBLES,
  MAXIMO_INTENTOS,
  NOTA_MINIMA,
  historialExamen,
  resolucionesHistorial,
  validarModulo,
} from '@/modules/examen/utils/flujoExamen';
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
});

const emit = defineEmits(['guardar']);

const toastStore = useToastStore();
const authStore = useAuthStore();

const contenedorReporte = ref(null);
const generando = ref(false);
const errores = ref([]);

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

/** El reporte titula solo cuando el estudiante aprobó ambos exámenes. */
const aprobadoMode = computed(() => ['aprobado', 'titulado'].includes(props.tramite?.estado_actual));

/** Estados donde quedó un examen sin aprobar: se emite el acta sin titular. */
const reprobadoMode = computed(() => ESTADOS_REAPERIBLES.includes(props.tramite?.estado_actual));

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
  const notas = exams.value.map((e) => Number(String(e.nota).split('/')[0])).filter((n) => !Number.isNaN(n));
  if (!notas.length) return '—';
  return `${(notas.reduce((a, b) => a + b, 0) / notas.length).toFixed(2)} / 100`;
});

const emitter = computed(() => authStore.user?.nombres || 'Kardex Académico');
const fechaEmision = computed(() => fechaLegible(new Date().toISOString()));

function notaDe(etapa) {
  const campoNota = ETIQUETAS.find((e) => e.clave === etapa)?.campoNota;
  if (!asistioA(etapa)) return 'No asistió';
  const nota = notasAsignadas.value[campoNota];
  if (nota === null || nota === undefined || nota === '') return '—';
  return `${nota} / 100 · ${Number(nota) >= NOTA_MINIMA ? 'Aprobado' : 'Reprobado'}`;
}

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
