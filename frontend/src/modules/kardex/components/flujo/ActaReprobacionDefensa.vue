<template>
  <!-- Área imprimible (fuera de pantalla): el PDF se genera en el frontend -->
  <div class="acta-oculta" aria-hidden="true">
    <div ref="contenedorActa" class="acta-pagina">
      <header class="doc-header">
        <div class="doc-header__brand">
          <img src="/images/logo.png" alt="Logo institucional" class="doc-logo" crossorigin="anonymous" />
          <div class="doc-brand-text">
            <p class="doc-brand-text__title">Universidad Pública de El Alto</p>
            <p class="doc-brand-text__line">Sistema de Seguimiento de Titulación Carrera de Economía</p>
            <p class="doc-brand-text__line">El Alto — Bolivia</p>
          </div>
        </div>
        <div class="doc-header__right">
          <p class="doc-tagline">Acta de Reprobación</p>
          <p class="doc-tagline2">Emitido: {{ fechaEmision }}</p>
        </div>
      </header>

      <h1 class="doc-titulo">Acta de Reprobación de Defensa de {{ etiquetaTitulo }}</h1>

      <section class="doc-info">
        <div class="doc-info__bloque">
          <h3>Datos del Estudiante</h3>
          <p><strong>{{ datosEstudiante.nombre }}</strong></p>
          <p>C.I.: {{ datosEstudiante.ci }} · R.U.: {{ datosEstudiante.matricula }}</p>
          <p>{{ datosEstudiante.carrera }}</p>
          <p>{{ datosEstudiante.correo }}</p>
        </div>
        <div class="doc-info__bloque">
          <h3>Datos del Trámite</h3>
          <p><strong>N.º:</strong> #{{ tramite?.id_tramite || '—' }}</p>
          <p><strong>Modalidad:</strong> {{ tramite?.modalidad?.nombre || '—' }}</p>
          <p><strong>Estado actual:</strong> REPROBADO</p>
          <p><strong>Tutor asignado:</strong> {{ nombreTutor }}</p>
        </div>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-subtitulo">Resultado de la defensa</h2>
        <table class="doc-tabla">
          <tbody>
            <tr>
              <td class="doc-tabla__label">N.º de Resolución de Aprobación Final</td>
              <td>{{ defensa.numero_resolucion || '—' }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__label">Fecha de la defensa</td>
              <td>{{ fechaDefensa }}</td>
            </tr>
            <tr>
              <td class="doc-tabla__label">Nota final obtenida</td>
              <td><strong>{{ notaFinal }} / 100</strong></td>
            </tr>
            <tr>
              <td class="doc-tabla__label">Nota mínima de aprobación</td>
              <td>{{ notaMinima }} / 100</td>
            </tr>
            <tr>
              <td class="doc-tabla__label">Resultado</td>
              <td><strong>REPROBADO</strong></td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-subtitulo">Declaración</h2>
        <p class="doc-declaracion">
          Se deja constancia de que el estudiante <strong>{{ datosEstudiante.nombre }}</strong>
          (C.I. {{ datosEstudiante.ci }}), inscrito en la modalidad
          <strong>{{ tramite?.modalidad?.nombre || '—' }}</strong> con trámite N.º
          #{{ tramite?.id_tramite || '—' }} realizó su defensa de {{ tituloGenerico }} el
          {{ fechaDefensa }} obteniendo una nota final de
          <strong>{{ notaFinal }} puntos</strong>, por debajo de la nota mínima de
          aprobación de {{ notaMinima }} puntos.
        </p>
        <p class="doc-declaracion">
          En consecuencia, el trámite queda en estado <strong>REPROBADO</strong>, el flujo de
          titulación finaliza en este acto y el estudiante deberá volver a solicitar la
          modalidad de graduación, respetando los plazos reglamentarios que le correspondan.
        </p>
      </section>

      <section class="doc-seccion">
        <h2 class="doc-subtitulo">Historial del trámite</h2>
        <table class="doc-tabla doc-tabla--historial">
          <thead>
            <tr>
              <th class="doc-tabla__fecha">Fecha</th>
              <th class="doc-tabla__estado">Estado</th>
              <th class="doc-tabla__detalle">Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(e, i) in historial" :key="i">
              <td class="doc-tabla__fecha">{{ fechaCorta(e.created_at) }}</td>
              <td class="doc-tabla__estado">{{ formatoEstado(e.nombre_estado) }}</td>
              <td class="doc-tabla__detalle">{{ e.observaciones || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="doc-firmas">
        <div class="doc-firma">
          <div class="doc-firma__img-wrap">
            <img
              v-if="firmaImagen"
              :src="firmaImagen"
              alt="Firma"
              class="doc-firma__img"
              crossorigin="anonymous"
            />
          </div>
          <div class="doc-firma__linea"></div>
          <p class="doc-firma__nombre">{{ usuarioEmisor }}</p>
          <p class="doc-firma__cargo">{{ usuarioCargo }}</p>
          <p class="doc-firma__fecha">Fecha: {{ fechaEmision }}</p>
        </div>
      </section>

      <section class="doc-notas">
        <p class="doc-notas__legal">
          Documento generado automáticamente por el Sistema de Seguimiento de Titulación.
          La información contenida es de carácter confidencial y de uso exclusivo del proceso
          de titulación. Vigencia: 30 días desde la emisión.
        </p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import html2pdf from 'html2pdf.js';
import { formatoEstado } from '@/modules/tramites';
import { NOTA_MINIMA_DEFENSA, configDeTramite, datosDeModulo } from '@/modules/tramites/utils/flujoTitulacion';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { useAuthStore } from '@/modules/auth/stores/auth';

/**
 * Acta de Reprobación de Defensa (PDF en el navegador, patrón html2pdf).
 *
 * Se genera cuando la nota final de la defensa queda por debajo del mínimo:
 * documenta la reprobación y el cierre del flujo de titulación para que el
 * estudiante deba volver a solicitar la modalidad de graduación.
 */
const props = defineProps({
  tramite: { type: Object, default: null },
  defensa: { type: Object, default: null },
});

const authStore = useAuthStore();

const contenedorActa = ref(null);
const generando = ref(false);

const notaMinima = NOTA_MINIMA_DEFENSA;

/**
 * Configuración de la modalidad del trámite: define cómo se llama el trabajo
 * en el acta ("Defensa de Tesis" / "Defensa de Trabajo Dirigido").
 */
const config = computed(() => configDeTramite(props.tramite));

/** Nombre del trabajo con mayúscula inicial ("Tesis" / "Trabajo Dirigido"). */
const etiquetaTitulo = computed(() => config.value?.etiquetaTituloCorto || 'Tesis');

/** Nombre del trabajo en minúscula ("tesis" / "trabajo dirigido"). */
const tituloGenerico = computed(() => config.value?.etiquetaTitulo || 'tesis');

const defensa = computed(() => props.defensa || datosDeModulo(props.tramite, 'defensa') || {});

const notaFinal = computed(() => {
  const n = Number(defensa.value.nota_final);
  return Number.isNaN(n) ? '—' : n;
});

const fechaDefensa = computed(() => fechaLegible(defensa.value.fecha_defensa) || '—');

const datosEstudiante = computed(() => {
  const e = props.tramite?.estudiante || {};
  const nombre = `${e.nombres || ''} ${e.apellidos || ''}`.trim();
  return {
    nombre: nombre || '—',
    carrera: e.carrera || e.programa || props.tramite?.modalidad?.nombre || '—',
    matricula: e.registro_universitario || '—',
    ci: e.ci || '—',
    correo: e.correo || e.email || '—',
  };
});

const nombreTutor = computed(() => {
  const t = props.tramite?.tutor;
  if (t) return `${t.nombres} ${t.apellidos}`;
  const id = datosDeModulo(props.tramite, 'aprobacion_tema')?.tutor_id;
  return id ? `Docente #${id}` : '—';
});

const historial = computed(() =>
  [...(props.tramite?.estados || [])].sort((a, b) => new Date(a.created_at) - new Date(b.created_at)),
);

const fechaEmision = computed(() =>
  new Date().toLocaleDateString('es-BO', { day: '2-digit', month: 'long', year: 'numeric' }),
);

const usuarioEmisor = computed(() => {
  const u = authStore.user;
  if (!u) return '—';
  return `${u.nombres || ''} ${u.apellidos || ''}`.trim() || u.username || '—';
});

const usuarioCargo = computed(() => {
  const rol = String(authStore.user?.rol || authStore.user?.role || '').toUpperCase();
  return rol || 'Responsable de Kardex';
});

const firmaImagen = computed(() => props.tramite?.firmas?.kardex?.imagen || null);

function fechaCorta(iso) {
  if (!iso) return '—';
  return String(iso).slice(0, 10);
}

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

function nombreActa() {
  return `acta_reprobacion_defensa${props.tramite?.id_tramite ? `_${props.tramite.id_tramite}` : ''}.pdf`;
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

/** Genera el acta en PDF y la descarga en el navegador. */
async function generar() {
  if (!contenedorActa.value || generando.value) return false;
  generando.value = true;
  try {
    await esperarImagenes(contenedorActa.value);
    await new Promise((r) => setTimeout(r, 100));
    const worker = html2pdf().set(OPCIONES_PDF).from(contenedorActa.value).toPdf();
    const pdf = await worker.get('pdf');
    paginar(pdf);
    pdf.save(nombreActa());
    return true;
  } catch (error) {
    console.error('[Acta Reprobación] No se pudo generar el PDF:', error);
    return false;
  } finally {
    generando.value = false;
  }
}

defineExpose({ generar });
</script>

<style scoped>
.acta-oculta {
  position: fixed;
  left: -12000px;
  top: 0;
  z-index: -9999;
  pointer-events: none;
}

.acta-pagina {
  width: 816px;
  box-sizing: border-box;
  padding: 20px;
  background: #ffffff;
  color: #000000;
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, sans-serif;
  font-size: 10px;
  line-height: 1.35;
}

.doc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  padding-bottom: 8px;
  border-bottom: 1.5px solid #000000;
  margin-bottom: 8px;
}

.doc-header__brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.doc-logo {
  display: block;
  height: 48px;
  max-width: 130px;
  object-fit: contain;
  flex-shrink: 0;
}

.doc-brand-text__title {
  margin: 0;
  font-size: 13px;
  font-weight: 800;
}

.doc-brand-text__line {
  margin: 0;
  font-size: 9px;
  color: #333333;
  white-space: nowrap;
}

.doc-header__right {
  text-align: right;
}

.doc-tagline {
  margin: 0;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.doc-tagline2 {
  margin: 2px 0 0;
  font-size: 8px;
  color: #333333;
}

.doc-titulo {
  margin: 2px 0 6px;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-left: 4px solid #000000;
  padding-left: 8px;
}

.doc-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 8px;
}

.doc-info h3,
.doc-subtitulo {
  margin: 0 0 3px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #000000;
  padding-bottom: 2px;
}

.doc-info p {
  margin: 0;
  font-size: 9.5px;
  color: #222222;
  line-height: 1.3;
}

.doc-seccion {
  margin-bottom: 8px;
}

.doc-tabla {
  width: 100%;
  border-collapse: collapse;
  font-size: 9px;
  table-layout: fixed;
}

.doc-tabla thead th {
  text-align: left;
  font-size: 8px;
  font-weight: 800;
  background: #d3d0d0;
  padding: 3.5px 4px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.doc-tabla tbody td {
  padding: 3px 4px;
  color: #222222;
  border-bottom: 1px solid #cccccc;
  vertical-align: top;
  word-break: break-word;
}

.doc-tabla tbody tr:nth-child(even) {
  background: #f5f5f5;
}

.doc-tabla__label {
  width: 45%;
  font-weight: 700;
  color: #000000;
}

.doc-tabla--historial .doc-tabla__fecha { width: 15%; white-space: nowrap; }
.doc-tabla--historial .doc-tabla__estado { width: 25%; }
.doc-tabla--historial .doc-tabla__detalle { width: 60%; color: #333333; }

.doc-declaracion {
  margin: 0 0 5px;
  font-size: 9.5px;
  color: #222222;
  line-height: 1.45;
  text-align: justify;
}

.doc-firmas {
  margin-top: 18px;
  padding-top: 12px;
  display: flex;
  justify-content: center;
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
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.doc-firma__cargo {
  margin: 1px 0 0;
  font-size: 8.5px;
  color: #333333;
}

.doc-firma__fecha {
  margin: 1px 0 0;
  font-size: 8px;
  color: #333333;
}

.doc-notas {
  margin-top: 8px;
  padding-top: 4px;
  border-top: 1px solid #cccccc;
}

.doc-notas__legal {
  margin: 0;
  font-size: 7.5px;
  color: #333333;
  line-height: 1.35;
  text-align: justify;
}
</style>
