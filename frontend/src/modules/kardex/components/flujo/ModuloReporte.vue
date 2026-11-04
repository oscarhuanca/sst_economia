<template>
  <ModuloTarjeta
    numero="6"
    titulo="Reporte"
    descripcion="Genere el reporte consolidado del trámite de titulación."
    icono="bar-chart"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3">
      <p class="flex items-center gap-2 text-sm font-semibold text-emerald-200">
        <AppIcon name="check-circle" :size="16" class="text-emerald-400" />
        Reporte generado el {{ fechaLegible(datos.generado) }}
      </p>
      <p v-if="datos.formato" class="text-xs text-emerald-400 mt-0.5">Formato: {{ datos.formato.toUpperCase() }}</p>

      <div class="mt-3 flex flex-wrap gap-2">
        <button class="btn-ghost !py-2 px-4" :disabled="cargando || generando" @click="visualizar">
          <AppIcon name="eye" :size="15" />
          Ver reporte
        </button>
        <button class="btn-warm px-4 !py-2" :disabled="cargando || generando" @click="descargarActual">
          <AppIcon name="download" :size="15" />
          Descargar Reporte
        </button>
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para generar el reporte.
      </div>
    </div>

    <div v-else>
      <div class="rounded-xl border border-white/10 bg-slate-900/40 overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-white/10">
          <h5 class="text-xs font-bold uppercase tracking-wider text-orange-300 inline-flex items-center gap-2">
            <AppIcon name="eye" :size="14" />
            Vista previa del reporte
          </h5>
          <span class="text-[11px] text-slate-400">{{ filas.length }} registros</span>
        </div>
        <div class="max-h-56 overflow-auto">
          <table class="w-full text-sm">
            <tbody>
              <tr v-for="fila in filas" :key="fila[0]" class="border-b border-white/5 last:border-0">
                <td class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">
                  {{ fila[0] }}
                </td>
                <td class="px-4 py-2 text-slate-200">{{ fila[1] }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
        <p class="text-xs text-slate-400 inline-flex items-center gap-1.5">
          <AppIcon name="zap" :size="14" />
          Este módulo se genera automáticamente. El PDF se puede descargar cuando se necesite.
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <button class="btn-ghost !py-2.5 px-5" :disabled="cargando || generando" @click="visualizar">
            <AppIcon name="eye" :size="16" />
            Vista previa
          </button>
          <button class="btn-warm px-6" :disabled="cargando || generando" @click="exportarReporte">
            <AppIcon v-if="cargando || generando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="download" :size="18" />
            <span class="uppercase tracking-wide">Generar Reporte PDF</span>
          </button>
        </div>
      </div>
    </div>
  </ModuloTarjeta>

  <!-- Área imprimible (fuera de pantalla): el PDF se genera en el frontend -->
  <div class="reporte-oculto" aria-hidden="true">
    <div ref="contenedorReporte" class="reporte-pagina">

      <!-- ===== ENCABEZADO: logo + datos institucionales al lado Drecho ===== -->
      <header class="doc-header">
        <div class="doc-header__brand">
          <img src="/images/logo.png" alt="Logo institucional" class="doc-logo" crossorigin="anonymous" />
          <div class="doc-brand-text">
            <p class="doc-brand-text__title">Universidad Pública de El Alto</p>
            <p class="doc-brand-text__line">Sistema de Seguimiento de Titulación Carrera de Economía</p>
            <p class="doc-brand-text__line">El Alto — Bolivia</p>
            <p class="doc-brand-text__line">+591 12345678 · upea@edu.bo</p>
          </div>
        </div>
        <div class="doc-header__right">
          <!--<p class="doc-tagline">REPORTE OFICIAL</p>-->
          <!--<p class="doc-tagline2">Emitido: {{ fechaEmision }}</p>-->
        </div>
      </header>

      <!-- ===== DATOS DEL ESTUDIANTE + METADATOS ===== -->
      <section class="doc-info">
        <div class="doc-info__estudiante">
          <h3>Datos del Estudiante</h3>
          <p><strong>{{ datosEstudiante.nombre }}</strong></p>
          <p>C.I.: {{ datosEstudiante.ci }} · R.U.: {{ datosEstudiante.matricula }}</p>
          <p>{{ datosEstudiante.carrera }}</p>
          <p>{{ datosEstudiante.correo }}</p>
        </div>
        <div class="doc-info__meta">
          <h3>Datos del Trámite</h3>
          <p><strong>N.º:</strong> #{{ tramite?.id_tramite || 'S00000' }}</p>
          <p><strong>Modalidad:</strong> {{ tramite?.modalidad?.nombre || '—' }}</p>
          <p><strong>Estado:</strong> <span :class="badgeClass(estadoGeneral)">{{ estadoGeneral }}</span></p>
          <p><strong>Avance:</strong> {{ avance }}%</p>
          <p><strong>Emitido por:</strong> {{ usuarioEmisor }}</p>
        </div>
      </section>

      <!-- ===== TÍTULO ===== -->
      <h1 class="doc-titulo">Detalle del Proceso de Titulación</h1>

      <!-- ===== TABLA DE DETALLE DEL PROCESO ===== -->
      <section class="doc-seccion">
        <table class="doc-tabla">
          <thead>
            <tr>
              <th class="doc-tabla__nro">N.º</th>
              <th class="doc-tabla__desc">Etapa / Descripción</th>
              <th class="doc-tabla__estado">Estado</th>
              <th class="doc-tabla__fecha">Fecha</th>
              <th class="doc-tabla__detalle">Detalle</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(etapa, i) in etapasProceso" :key="i">
              <td class="doc-tabla__nro">{{ String(i + 1).padStart(2, '0') }}</td>
              <td class="doc-tabla__desc">
                <strong>{{ etapa.etapa }}</strong>
                <span v-if="etapa.subetapa" class="doc-tabla__sub">{{ etapa.subetapa }}</span>
              </td>
              <td class="doc-tabla__estado">
                <span :class="badgeClass(etapa.estado)">{{ etapa.estado }}</span>
              </td>
              <td class="doc-tabla__fecha">{{ etapa.fecha || '—' }}</td>
              <td class="doc-tabla__detalle">{{ etapa.detalle || '—' }}</td>
            </tr>
          </tbody>
        </table>

        <div class="doc-totales">
          <div class="doc-totales__row">
            <span>Etapas completadas</span>
            <strong>{{ etapasCompletadas }} / {{ etapasProceso.length }}</strong>
          </div>
          <div class="doc-totales__row doc-totales__row--total">
            <span>Estado general del trámite</span>
            <strong>{{ estadoGeneral }} · {{ avance }}%</strong>
          </div>
        </div>
      </section>

      <!-- ===== PIE DE FIRMA (una sola área: Kardex o Secretaría) ===== -->
      <section class="doc-firmas">
        <div class="doc-firma">
          <div class="doc-firma__img-wrap">
            <img
              v-if="firmante.firmaImg"
              :src="firmante.firmaImg"
              :alt="`Firma ${firmante.rol}`"
              class="doc-firma__img"
              crossorigin="anonymous"
            />
          </div>
          <div class="doc-firma__linea"></div>
          <p class="doc-firma__nombre">{{ firmante.nombre }}</p>
          <p class="doc-firma__cargo">{{ firmante.cargo }}</p>
          <p class="doc-firma__rol">Área: {{ firmante.rol }}</p>
          <p class="doc-firma__fecha">Fecha: {{ firmante.fecha }}</p>
          <p v-if="firmante.codigo" class="doc-firma__codigo">Código: {{ firmante.codigo }}</p>
        </div>
      </section>

      <!-- ===== NOTA LEGAL ===== -->
      <section class="doc-notas">
        <p class="doc-notas__legal">
          Documento generado automáticamente por el Sistema de Seguimiento de Titulación.
          La información contenida es de carácter confidencial y de uso exclusivo del proceso de titulación.
          Vigencia: 30 días desde la emisión.
        </p>
      </section>

    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import html2pdf from 'html2pdf.js';
import { formatoEstado } from '@/modules/tramites';
import {
  configDeTramite,
  datosDeModulo,
  pctTitulacion,
} from '@/modules/tramites/utils/flujoTitulacion';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  tramite: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
  cargando: { type: Boolean, default: false },
  simulado: { type: Boolean, default: false },
  areaFirmante: { type: String, default: null },
});

const emit = defineEmits(['guardar']);

const authStore = useAuthStore();
const toastStore = useToastStore();

const contenedorReporte = ref(null);
const generando = ref(false);

/* ============================================================
   DATOS DEL ESTUDIANTE
   ============================================================ */
const postulante = computed(() => props.tramite?.estudiante || {});

const datosEstudiante = computed(() => {
  const e = postulante.value;
  const nombre = `${e.nombres || ''} ${e.apellidos || ''}`.trim();
  return {
    nombre: nombre || '—',
    carrera: e.carrera || e.programa || props.tramite?.modalidad?.nombre || '—',
    matricula: e.registro_universitario || '—',
    ci: e.ci || '—',
    correo: e.correo || e.email || '—',
  };
});

/* ============================================================
   DATOS DE CADA MÓDULO
   ============================================================ */

/**
 * Configuración de la modalidad del trámite (Tesis de Grado, Trabajo Dirigido):
 * define el módulo de perfil, sus documentos y cómo se llama el trabajo en el
 * reporte.
 */
const config = computed(() => configDeTramite(props.tramite));

/** Documentos que el estudiante adjuntó en la fase de solicitud. */
const documentosPerfil = computed(() => config.value?.documentosPerfil || []);

/** Nombre del trabajo en minúscula ("tesis" / "trabajo dirigido"). */
const tituloGenerico = computed(() => config.value?.etiquetaTitulo || 'tesis');

/** Prefijo del archivo PDF del reporte. */
const prefijoReporte = computed(() =>
  (config.value?.nombre || 'Tesis de Grado')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, ''),
);

const perfilDatos = computed(() => datosDeModulo(props.tramite, config.value?.moduloPerfil) || {});
const aprobacionTema = computed(() => datosDeModulo(props.tramite, 'aprobacion_tema') || {});
const tribunalDatos = computed(() => datosDeModulo(props.tramite, 'tribunal_revisor') || {});
const defensa = computed(() => datosDeModulo(props.tramite, 'defensa') || {});

const nombreTutor = computed(() => {
  const t = aprobacionTema.value;
  const porDocentes = props.docentes?.find((d) => d.id_docente === Number(t.tutor_id));
  if (porDocentes) return `${porDocentes.nombres} ${porDocentes.apellidos}`;
  const tr = props.tramite?.tutor;
  if (tr) return `${tr.nombres} ${tr.apellidos}`;
  return t.tutor_id ? `Docente #${t.tutor_id}` : '—';
});

const resultadoDefensa = computed(() => {
  const nota = defensa.value.nota_final ?? defensa.value.resultado;
  if (nota === null || nota === undefined || nota === '') return '—';
  const n = Number(nota);
  return Number.isNaN(n) ? String(nota) : `${n} / 100`;
});

/* ============================================================
   ETAPAS DEL PROCESO
   ============================================================ */
const etapasProceso = computed(() => {
  const etapas = [];

  const solicitud = props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at;
  etapas.push({
    etapa: 'Solicitud de titulación',
    subetapa: 'Presentación inicial del trámite',
    estado: solicitud ? 'COMPLETADO' : 'PENDIENTE',
    fecha: fechaLegible(solicitud) || '—',
    detalle: `Modalidad: ${props.tramite?.modalidad?.nombre || '—'}`,
  });

  documentosPerfil.value.forEach((doc) => {
    const item = perfilDatos.value.documentos?.[doc.tipo] || {};
    etapas.push({
      etapa: doc.etiqueta,
      subetapa: `Módulo 1 — ${config.value?.etiquetaPerfil || 'Perfil'}`,
      estado: item.marcado ? 'COMPLETADO' : 'PENDIENTE',
      fecha: item.fecha ? fechaLegible(item.fecha) : '—',
      detalle: item.archivo?.nombre || '—',
    });
  });

  etapas.push({
    etapa: `Aprobación del tema de ${tituloGenerico.value}`,
    subetapa: 'Módulo 2 — Aprobación del Tema',
    estado: aprobacionTema.value.completado ? 'COMPLETADO' : 'PENDIENTE',
    fecha: fechaLegible(aprobacionTema.value.fecha_resolucion) || '—',
    detalle: aprobacionTema.value.numero_resolucion
      ? `Resolución N.º ${aprobacionTema.value.numero_resolucion}`
      : '—',
  });

  etapas.push({
    etapa: 'Designación de docente tutor',
    subetapa: 'Módulo 2 — Aprobación del Tema',
    estado: aprobacionTema.value.tutor_id ? 'COMPLETADO' : 'PENDIENTE',
    fecha: fechaLegible(aprobacionTema.value.fecha_resolucion) || '—',
    detalle: nombreTutor.value,
  });

  const tribunal = tribunalDatos.value.tribunal || [];
  etapas.push({
    etapa: 'Conformación del tribunal revisor',
    subetapa: 'Módulo 3 — Tribunal Revisor',
    estado: tribunal.length ? 'COMPLETADO' : 'PENDIENTE',
    fecha: fechaLegible(tribunalDatos.value.fecha_asignacion) || '—',
    detalle: tribunal.length ? tribunal.map((m) => `${m.rol}: ${m.nombre}`).join(' · ') : '—',
  });

  etapas.push({
    etapa: `Defensa de ${tituloGenerico.value}`,
    subetapa: 'Módulo 4 — Defensa',
    estado: defensa.value.completado ? 'COMPLETADO' : 'PENDIENTE',
    fecha: fechaLegible(defensa.value.fecha_defensa) || '—',
    detalle:
      [
        defensa.value.hora,
        defensa.value.lugar,
        defensa.value.numero_resolucion ? `Res. ${defensa.value.numero_resolucion}` : null,
      ]
        .filter(Boolean)
        .join(' · ') || '—',
  });

  etapas.push({
    etapa: 'Resultado de la defensa',
    subetapa: 'Módulo 4 — Defensa',
    estado: defensa.value.nota_final != null ? 'COMPLETADO' : 'PENDIENTE',
    fecha: fechaLegible(defensa.value.fecha_defensa) || '—',
    detalle: resultadoDefensa.value,
  });

  etapas.push({
    etapa: 'Generación del reporte final',
    subetapa: 'Módulo 5 — Reporte',
    estado: avance.value >= 100 ? 'COMPLETADO' : 'EN PROCESO',
    fecha: fechaEmision.value,
    detalle: 'Documento consolidado del trámite de titulación',
  });

  return etapas;
});

const etapasCompletadas = computed(
  () => etapasProceso.value.filter((e) => e.estado === 'COMPLETADO').length,
);

/* ============================================================
   KPIs
   ============================================================ */
const avance = computed(() => pctTitulacion(props.tramite));

const estadoGeneral = computed(() => {
  if (avance.value >= 100) return 'COMPLETADO';
  if (avance.value > 0) return 'EN PROCESO';
  return 'PENDIENTE';
});

const fechaEmision = computed(() =>
  new Date().toLocaleDateString('es-PE', { day: '2-digit', month: 'long', year: 'numeric' }),
);

const usuarioEmisor = computed(() => {
  const u = authStore.user;
  if (!u) return '—';
  return `${u.nombres || ''} ${u.apellidos || ''}`.trim() || u.username || '—';
});

/* ============================================================
   PIE DE FIRMA — una sola área (Kardex o Secretaría)
   ============================================================ */
const areaFirmanteResuelta = computed(() => {
  if (props.areaFirmante) return props.areaFirmante;

  if (props.tramite?.firmas?.kardex) return 'kardex';
  if (props.tramite?.firmas?.secretaria) return 'secretaria';

  const rol = authStore.user?.rol || authStore.user?.role || '';
  if (String(rol).toLowerCase().includes('secretar')) return 'secretaria';
  if (String(rol).toLowerCase().includes('kardex')) return 'kardex';

  return 'kardex';
});

const firmante = computed(() => {
  const area = areaFirmanteResuelta.value;
  const datosArea = props.tramite?.firmas?.[area] || {};

  const defaultsPorArea = {
    kardex: {
      rolLabel: 'Kardex',
      nombre: 'Responsable de Kardex',
      cargo: 'Jefatura de Registro Académico',
    },
    secretaria: {
      rolLabel: 'Secretaría Académica',
      nombre: 'Secretaría Académica',
      cargo: 'Facultad / Programa',
    },
  };

  const def = defaultsPorArea[area] || defaultsPorArea.kardex;

  return {
    rol: def.rolLabel,
    nombre: datosArea.nombre || def.nombre,
    cargo: datosArea.cargo || def.cargo,
    fecha: datosArea.fecha ? fechaLegible(datosArea.fecha) : fechaEmision.value,
    firmaImg: datosArea.imagen || null,
    codigo: datosArea.codigo || null,
  };
});

/* ============================================================
   VISTA PREVIA
   ============================================================ */
const historial = computed(() =>
  [...(props.tramite?.estados || [])].sort((a, b) => new Date(a.created_at) - new Date(b.created_at)),
);

const filas = computed(() => {
  const t = props.tramite;
  if (!t) return [];
  return [
    ['Postulante', `${postulante.value.nombres || ''} ${postulante.value.apellidos || ''}`.trim()],
    ['CI', postulante.value.ci || '—'],
    ['Registro Universitario', postulante.value.registro_universitario || '—'],
    ['Modalidad', t.modalidad?.nombre || '—'],
    ['Trámite N.º', t.id_tramite],
    ['Estado actual', formatoEstado(t.estado_actual)],
    ['Tutor asignado', t.tutor ? `${t.tutor.nombres} ${t.tutor.apellidos}` : nombreTutor.value],
    ...historial.value.map((e) => [
      `Hito: ${formatoEstado(e.nombre_estado)}`,
      `${fechaLegible(e.created_at)} ${e.observaciones ? `— ${e.observaciones}` : ''}`,
    ]),
  ];
});

/* ============================================================
   PDF — CARTA, márgenes 1 cm en los 4 lados
   ============================================================ */
const OPCIONES_PDF = {
  margin: [10, 10, 10, 10],
  image: { type: 'jpeg', quality: 0.98 },
  html2canvas: {
    scale: 3,
    useCORS: true,
    allowTaint: false,
    logging: false,
    backgroundColor: '#ffffff',
  },
  jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
  pagebreak: { mode: ['css', 'legacy'] },
};

function datosReporte() {
  return {
    generado: new Date().toISOString().slice(0, 10),
    formato: 'pdf',
    contenido: etapasProceso.value
      .map((e) => `${e.etapa} | ${e.estado} | ${e.fecha} | ${e.detalle}`)
      .join('\n'),
  };
}

let autogenerado = false;

watch(
  () => props.enProgreso && !props.completado,
  (disponible) => {
    if (!disponible || autogenerado) return;
    autogenerado = true;
    emit('guardar', datosReporte());
  },
  { immediate: true, flush: 'post' },
);

function nombreReporte() {
  return `reporte_ejecutivo_${prefijoReporte.value}${props.tramite?.id_tramite ? `_${props.tramite.id_tramite}` : ''}.pdf`;
}

async function esperarImagenes(contenedor) {
  const imgs = contenedor.querySelectorAll('img');
  await Promise.all(
    Array.from(imgs).map((img) =>
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
    pdf.setTextColor(150, 150, 150);
    pdf.text(`Página ${i} / ${total}`, ancho - 10, alto - 5, { align: 'right' });
  }
}

async function producirPdf() {
  if (!contenedorReporte.value) return null;
  await esperarImagenes(contenedorReporte.value);
  await new Promise((r) => setTimeout(r, 100));
  const worker = html2pdf().set(OPCIONES_PDF).from(contenedorReporte.value).toPdf();
  const pdf = await worker.get('pdf');
  paginar(pdf);
  return pdf;
}

function registrarErrorPdf(error) {
  console.error('[Reporte] No se pudo generar el PDF:', error);
  toastStore.error('No se pudo generar el reporte PDF. Intente nuevamente.');
}

async function exportarReporte() {
  generando.value = true;
  try {
    const pdf = await producirPdf();
    if (!pdf) return;
    pdf.save(nombreReporte());
    autogenerado = true;
    emit('guardar', datosReporte());
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
    abrirBlob(pdf.output('blob'));
  } catch (error) {
    registrarErrorPdf(error);
  } finally {
    generando.value = false;
  }
}

async function descargarActual() {
  generando.value = true;
  try {
    const pdf = await producirPdf();
    if (!pdf) return;
    descargarBlob(pdf.output('blob'), nombreReporte());
  } catch (error) {
    registrarErrorPdf(error);
  } finally {
    generando.value = false;
  }
}

function descargarBlob(blob, nombre) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  a.click();
  URL.revokeObjectURL(url);
}

function abrirBlob(blob) {
  const url = URL.createObjectURL(blob);
  window.open(url, '_blank');
  setTimeout(() => URL.revokeObjectURL(url), 60_000);
}

function badgeClass(estado) {
  const mapa = {
    COMPLETADO: 'reporte-badge reporte-badge--verde',
    'EN PROCESO': 'reporte-badge reporte-badge--azul',
    PENDIENTE: 'reporte-badge reporte-badge--gris',
    ASIGNADO: 'reporte-badge reporte-badge--azul',
  };
  return mapa[estado] || mapa.PENDIENTE;
}
</script>

<style scoped>
/* ============================================================
   Área imprimible — Reporte Ejecutivo de Titulación (Blanco y Negro)
   ============================================================ */

.reporte-oculto {
  position: fixed;
  left: -12000px;
  top: 0;
  z-index: -9999;
  pointer-events: none;
}

.reporte-pagina {
  width: 816px;
  height: 981px;
  box-sizing: border-box;
  padding: 20px 30px 20px 20px;
  background: #ffffff;
  color: #000000;
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, sans-serif;
  font-size: 10px;
  line-height: 1.35;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* ---------- Encabezado ---------- */
.doc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  padding-bottom: 8px;
  border-bottom: 1.5px solid #000000;
  margin-bottom: 8px;
  flex-shrink: 0;
  width: 100%;
}

.doc-header__brand {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1 1 auto;
  min-width: 0;
}

.doc-logo {
  display: block;
  height: 48px;
  max-width: 130px;
  object-fit: contain;
  flex-shrink: 0;
}

.doc-brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.3;
  min-width: 0;
}

.doc-brand-text__title {
  margin: 0;
  font-size: 13px;
  font-weight: 800;
  color: #000000;
  white-space: nowrap;
}

.doc-brand-text__line {
  margin: 0;
  font-size: 9px;
  color: #333333;
  white-space: nowrap;
}

.doc-header__right {
  text-align: right;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  min-width: 150px;
}

.doc-tagline {
  margin: 0;
  font-size: 10px;
  font-weight: 700;
  color: #000000;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  white-space: nowrap;
}

.doc-tagline2 {
  margin: 2px 0 0;
  font-size: 8px;
  color: #333333;
  white-space: nowrap;
}

/* ---------- Bloque info ---------- */
.doc-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 8px;
  flex-shrink: 0;
}

.doc-info h3 {
  margin: 0 0 3px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #000000;
  border-bottom: 1px solid #000000;
  padding-bottom: 2px;
}

.doc-info p {
  margin: 0;
  font-size: 9.5px;
  color: #222222;
  line-height: 1.3;
}

.doc-info strong {
  color: #000000;
  font-weight: 600;
}

/* ---------- Título ---------- */
.doc-titulo {
  margin: 2px 0 6px;
  font-size: 13px;
  font-weight: 700;
  color: #000000;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-left: 4px solid #000000;
  padding-left: 8px;
  flex-shrink: 0;
}

/* ---------- Tabla ---------- */
.doc-seccion {
  flex: 0 0 auto;
  width: 100%;
  overflow: hidden;
}

.doc-tabla {
  width: 100%;
  border-collapse: collapse;
  font-size: 8.5px;
  table-layout: fixed;
}

.doc-tabla thead th {
  text-align: left;
  font-size: 8px;
  font-weight: 800;
  color: #101010;
  background: #d3d0d0;
  padding: 3.5px 4px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  overflow: hidden;
  word-break: break-word;
}

.doc-tabla tbody td {
  padding: 3px 4px;
  color: #222222;
  border-bottom: 1px solid #cccccc;
  vertical-align: top;
  line-height: 1.2;
  overflow: hidden;
  word-break: break-word;
}

.doc-tabla tbody tr:nth-child(even) {
  background: #f5f5f5;
}

.doc-tabla__nro { width: 5%; text-align: center; color: #333333; font-weight: 700; }
.doc-tabla__desc { width: 30%; }
.doc-tabla__desc strong { display: block; color: #000000; font-weight: 600; font-size: 8.5px; }
.doc-tabla__sub { display: block; font-size: 7.5px; color: #555555; margin-top: 0.5px; }
.doc-tabla__estado { width: 15%; }
.doc-tabla__fecha { width: 15%; white-space: nowrap; }
.doc-tabla__detalle { width: 35%; font-size: 8.2px; color: #333333; }

/* ---------- Totales ---------- */
.doc-totales {
  margin: 6px auto 0;
  width: 60%;
  max-width: 400px;
  font-size: 9px;
  flex-shrink: 0;
  box-sizing: border-box;
}

.doc-totales__row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 10px;
  padding: 2px 4px;
  color: #222222;
}

.doc-totales__row span {
  flex: 0 1 auto;
  white-space: nowrap;
}

.doc-totales__row strong {
  flex: 0 0 auto;
  text-align: right;
  white-space: nowrap;
}

.doc-totales__row--total {
  border-top: 1.2px solid #000000;
  margin-top: 2px;
  padding-top: 4px;
  font-weight: 800;
  color: #000000;
  font-size: 9.5px;
}

/* ---------- Badges (sin bordes ni bordes redondeados) ---------- */
.reporte-badge {
  display: inline-block;
  padding: 0;
  font-size: 7.5px;
  font-weight: 700;
  letter-spacing: 0.3px;
  white-space: nowrap;
  max-width: 100%;
  box-sizing: border-box;
  border: none;
  border-radius: 0;
  background: transparent;
  text-transform: uppercase;
}

.reporte-badge--verde {
  color: #000000;
  font-weight: 800;
}

.reporte-badge--azul {
  color: #000000;
  font-weight: 700;
}

.reporte-badge--gris {
  color: #555555;
  font-weight: 600;
  font-style: italic;
}

/* ---------- Pie de firma ---------- */
.doc-firmas {
  margin-top: auto;
  padding-top: 12px;
  display: flex;
  justify-content: center;
  flex-shrink: 0;
}

.doc-firma {
  width: 300px;
  text-align: center;
}

.doc-firma__img-wrap {
  height: 30px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  margin-bottom: 2px;
}

.doc-firma__img {
  max-height: 28px;
  max-width: 140px;
  object-fit: contain;
}

.doc-firma__linea {
  height: 1px;
  background: #000000;
  margin-bottom: 4px;
}

.doc-firma__nombre {
  margin: 0;
  font-size: 9.5px;
  font-weight: 700;
  color: #000000;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.doc-firma__cargo {
  margin: 1px 0 0;
  font-size: 8.5px;
  color: #333333;
}

.doc-firma__rol {
  margin: 1px 0 0;
  font-size: 8px;
  color: #000000;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.doc-firma__fecha {
  margin: 1px 0 0;
  font-size: 8px;
  color: #333333;
}

.doc-firma__codigo {
  margin: 1px 0 0;
  font-size: 7px;
  color: #555555;
  font-family: "SFMono-Regular", Consolas, Menlo, monospace;
}

/* ---------- Nota legal ---------- */
.doc-notas {
  margin-top: 6px;
  padding-top: 4px;
  border-top: 1px solid #cccccc;
  flex-shrink: 0;
}

.doc-notas__legal {
  margin: 0;
  font-size: 7.5px;
  color: #333333;
  line-height: 1.35;
  text-align: justify;
}
</style>