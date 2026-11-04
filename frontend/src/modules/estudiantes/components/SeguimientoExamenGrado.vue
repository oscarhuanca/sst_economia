<template>
  <div class="space-y-4">
    <!-- Resumen del trámite: estado global e intentos del examen -->
    <div class="card overflow-hidden">
      <div class="h-2 bg-orange"></div>
      <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-extrabold text-white">Mi Examen de Grado</h2>
            <p class="text-xs text-slate-400">
              Estado:
              <span class="font-bold text-orange-300">{{ formatoEstado(estadoActual) }}</span>
            </p>
          </div>
          <div class="flex items-center gap-2 text-right">
            <div>
              <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Intentos utilizados</p>
              <p class="text-lg font-extrabold text-white">
                {{ usados }} <span class="text-xs font-semibold text-slate-400">de {{ maximo }}</span>
              </p>
            </div>
            <span
              class="px-3 py-1.5 rounded-full text-xs font-bold border inline-flex items-center gap-1.5"
              :class="tone.tone"
            >
              <AppIcon :name="iconoEstado" :size="13" />
              {{ tone.label }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Intentos agotados: debe volver a solicitar otra modalidad -->
    <div
      v-if="intentosAgotados"
      class="rounded-2xl border border-red-500/40 bg-red-900/40 px-4 py-3 flex items-center gap-3"
      role="alert"
    >
      <AppIcon name="x-circle" :size="20" class="shrink-0 text-red-400" />
      <p class="text-sm font-semibold text-red-200">
        <template v-if="intentoActualEnCurso">
          Último intento ({{ usados }} de {{ maximo }}): si no apruebas, deberás solicitar
          <span class="text-white">otra modalidad de titulación</span>.
        </template>
        <template v-else>
          Agotaste tus {{ maximo }} intentos. Debes solicitar
          <span class="text-white">otra modalidad de titulación</span>.
        </template>
      </p>
    </div>

    <!-- Reprobó: puede volver a postular por lo que reprobó -->
    <div
      v-if="puedeReSolicitar"
      class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 flex items-start gap-3"
      role="alert"
    >
      <AppIcon name="alert-triangle" :size="22" class="mt-0.5 shrink-0 text-amber-300" />
      <div class="min-w-0">
        <p class="text-sm font-extrabold text-white">{{ resumenReprobacion.titulo }}</p>
        <p class="mt-1 text-sm text-amber-100/90 leading-relaxed">
          {{ resumenReprobacion.texto }}
        </p>
      </div>
    </div>

    <!-- Avance del flujo, paso a paso en tarjetas -->
    <div class="card overflow-hidden">
      <div class="h-2 bg-gradient-to-r from-slate-500 to-slate-400"></div>
      <div class="p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="trending-up" :size="18" class="text-orange-300" />
          Mi avance
        </h3>

        <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="m in modulos"
            :key="m.id"
            class="rounded-xl bg-white/5 p-3.5 ring-1"
            :class="m.ring"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="inline-flex items-center gap-2 min-w-0">
                <AppIcon :name="m.icon" :size="15" class="shrink-0" :class="m.icono" />
                <span class="text-sm font-bold text-white truncate">{{ m.label }}</span>
              </span>
              <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide ring-1" :class="m.badge.cls">
                {{ m.badge.label }}
              </span>
            </div>
            <p v-if="m.datos" class="mt-2 text-xs text-slate-300 leading-snug">
              {{ m.datos }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Intentos realizados, en tarjetas compactas -->
    <div class="card overflow-hidden">
      <div class="h-2 bg-gradient-to-r from-slate-500 to-slate-400"></div>
      <div class="p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="clipboard" :size="18" class="text-orange-300" />
          Mis intentos
        </h3>

        <ol v-if="intentos.length" class="mt-4 space-y-3">
          <li
            v-for="it in intentos"
            :key="it.numero"
            class="rounded-xl bg-white/5 ring-1 ring-white/10 p-3.5"
            :class="{ 'ring-amber-500/40': it.enCurso }"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h4 class="font-bold text-white">Intento {{ it.numero }}</h4>
              <div class="flex items-center gap-2">
                <span
                  v-if="it.enCurso"
                  class="bg-orange-500/20 text-orange-200 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide ring-1 ring-orange-500/30"
                >
                  En curso
                </span>
                <span
                  v-else
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide ring-1"
                  :class="it.resultadoFinal ? 'bg-emerald-500/20 text-emerald-300 ring-emerald-500/30' : 'bg-red-900/50 text-red-400 ring-red-500/30'"
                >
                  {{ it.resultadoFinal ? 'Aprobado' : 'Reprobado' }}
                </span>
                <span v-if="it.fecha" class="text-[11px] text-slate-400">
                  {{ fechaHora(it.fecha) }}
                </span>
              </div>
            </div>

            <p v-if="it.resolucion" class="mt-1.5 text-xs text-slate-400">
              Resolución HCC:
              <span class="font-semibold text-slate-200">{{ it.resolucion }}</span>
            </p>

            <dl class="mt-2.5 grid sm:grid-cols-2 gap-2 text-xs">
              <div class="rounded-lg bg-white/5 px-2.5 py-2">
                <dt class="dato-label">Examen teórico</dt>
                <dd class="mt-0.5 font-semibold text-white leading-snug">
                  {{ it.teorico.tema || '—' }}
                </dd>
                <dd class="text-[10px] text-slate-400 mt-0.5">
                  <template v-if="it.teorico.fecha">Fecha: {{ fechaCorta(it.teorico.fecha) }} · </template>
                  <span :class="notaClase(it.teorico)">
                    {{ textoNota(it.teorico) }}
                  </span>
                </dd>
              </div>
              <div class="rounded-lg bg-white/5 px-2.5 py-2">
                <dt class="dato-label">Examen de especialidad</dt>
                <dd class="mt-0.5 font-semibold text-white leading-snug">
                  {{ it.especialidad.tema || '—' }}
                </dd>
                <dd class="text-[10px] text-slate-400 mt-0.5">
                  <template v-if="it.especialidad.fecha">Fecha: {{ fechaCorta(it.especialidad.fecha) }} · </template>
                  <span :class="notaClase(it.especialidad)">
                    {{ textoNota(it.especialidad) }}
                  </span>
                </dd>
              </div>
            </dl>
          </li>
        </ol>

        <div v-else class="mt-4 rounded-xl border border-dashed border-white/20 bg-white/5 px-3 py-4 text-center text-sm text-slate-400">
          Aún no hay intentos registrados.
        </div>
      </div>
    </div>

    <!-- Última actualización del trámite, en una sola tarjeta -->
    <div class="card overflow-hidden">
      <div class="p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="clock" :size="18" class="text-orange-300" />
          Última actualización
        </h3>

        <div v-if="ultimaActualizacion" class="mt-3 rounded-xl bg-white/5 ring-1 ring-white/10 p-3.5 flex items-start gap-3">
          <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0" :class="ultimaActualizacion.tone.dot"></span>
          <div class="min-w-0">
            <p class="text-sm font-bold text-white">{{ formatoEstado(ultimaActualizacion.nombre_estado) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">
              <span v-if="ultimaActualizacion.created_at">{{ fechaHora(ultimaActualizacion.created_at) }}</span>
              <template v-if="ultimaActualizacion.responsable">
                <span v-if="ultimaActualizacion.created_at"> · </span>
                {{ ultimaActualizacion.responsable.nombres }} {{ ultimaActualizacion.responsable.apellidos }}
                ({{ rolLabel(ultimaActualizacion.responsable.rol) }})
              </template>
            </p>
          </div>
        </div>

        <div
          v-else
          class="mt-3 rounded-xl border border-dashed border-white/20 bg-white/5 px-3 py-4 text-center text-sm text-slate-400"
        >
          Aún no hay actualizaciones registradas.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Seguimiento del estudiante para la modalidad de Examen de Grado.
//
// Muestra la información ESENCIAL en tarjetas, sin el historial completo:
//   (1) resumen de estado e intentos consumidos,
//   (2) el avance del flujo paso a paso (una tarjeta por módulo),
//   (3) cada intento rendido con sus temas, fechas y notas,
//   (4) la última actualización registrada por Kardex.
//
// Si reprobó alguno de los exámenes y aún le quedan intentos, aparece un aviso
// indicándole qué reprobó y que puede volver a postular (la reapertura la
// registra Kardex). Si agotó los intentos y no aprobó, se le indica que debe
// volver a solicitar otra modalidad de titulación.
import { computed } from 'vue';
import { formatoEstado, statusGlobal, toneEstado } from '@/modules/tramites';
import { rolLabel } from '@/core/roles';
import {
  MAXIMO_INTENTOS,
  historialExamen,
  intentosUsados,
  intentosRestantes,
  esReabrible,
  modulosExamen,
  datosDeModulo,
} from '@/modules/examen/utils/flujoExamen';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
  tramite: { type: Object, required: true },
});

const maximo = MAXIMO_INTENTOS;
const estadoActual = computed(() => props.tramite?.estado_actual);
const usados = computed(() => intentosUsados(props.tramite));

// `aprobado` (ambos exámenes rendidos) y `titulado` (reporte emitido) son los
// cierres de éxito del Examen de Grado: con ellos no corresponde el aviso.
const exito = computed(() => ['aprobado', 'titulado'].includes(estadoActual.value));
const intentosAgotados = computed(() => usados.value >= maximo && !exito.value);
const intentoActualEnCurso = computed(() => {
  const ultimo = intentos.value[intentos.value.length - 1];
  return Boolean(ultimo?.enCurso);
});

// Reprobó alguno de los exámenes y aún le quedan intentos: puede volver a
// postular por lo que reprobó (la reapertura la registra Kardex).
const restantes = computed(() => intentosRestantes(props.tramite));
const puedeReSolicitar = computed(() => esReabrible(props.tramite) && restantes.value > 0);

/** Mensaje del aviso de re-solicitud: indica qué se reprobó y cómo volver a rendirlo. */
const resumenReprobacion = computed(() => {
  const estado = estadoActual.value;
  const alcance = {
    examen_teorico_reprobado: {
      titulo: 'Reprobaste el examen teórico',
      detalle: 'el examen teórico',
      rendir: 'volver a rendir el examen teórico',
    },
    examen_especialidad_reprobado: {
      titulo: 'Reprobaste el examen de especialidad',
      detalle: 'el examen de especialidad',
      rendir: 'volver a rendir el examen de especialidad',
    },
    examen_ambos_reprobados: {
      titulo: 'Reprobaste ambos exámenes',
      detalle: 'los dos exámenes',
      rendir: 'rendir nuevamente el examen teórico y el de especialidad',
    },
  }[estado];

  if (!alcance) {
    return { titulo: '', texto: '' };
  }

  return {
    titulo: alcance.titulo,
    texto:
      `Puedes volver a rendir ${alcance.rendir}: acude a Kardex para que registre la reapertura de ${alcance.detalle}. `
      + `Se descontará un intento (te quedan ${restantes.value} de ${maximo}).`,
  };
});

const tone = computed(() => statusGlobal(estadoActual.value));

const iconoEstado = computed(() => {
  if (exito.value) return 'award';
  if (['examen_teorico_reprobado', 'examen_especialidad_reprobado', 'examen_ambos_reprobados'].includes(estadoActual.value)) {
    return 'x-circle';
  }
  return 'clock';
});

// Última actualización del trámite (la más reciente de `tramite.estados`).
const ultimaActualizacion = computed(() => {
  const lista = [...(props.tramite?.estados || [])]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  const ultima = lista[0];
  return ultima ? { ...ultima, tone: toneEstado(ultima.nombre_estado) } : null;
});

/**
 * Módulos del flujo con su estado visual (badge, anillo, icono) y el dato
 * esencial que se muestra debajo del título (resolución, tema/fecha, resultado).
 */
const modulos = computed(() =>
  modulosExamen(props.tramite).map((m) => {
    const badge = estadoModulo(m);
    return {
      ...m,
      badge,
      datos: datosModulo(m),
      ring: badge.ring,
      icono: m.completado ? 'text-emerald-300' : m.enProgreso ? 'text-orange-300' : 'text-slate-400',
    };
  }),
);

/** Badge de estado de un módulo: Listo, En curso, Reprobado, Reabierto o Pendiente. */
function estadoModulo(m) {
  if (m.completado) {
    if (m.registro?.resultado === 'no_asistio') {
      return { label: 'Reprobado', cls: 'bg-red-900/50 text-red-400 ring-red-500/30', ring: 'ring-red-500/30' };
    }
    return { label: 'Listo', cls: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30', ring: 'ring-emerald-500/30' };
  }
  if (m.enProgreso) {
    return { label: 'En curso', cls: 'bg-orange-500/15 text-orange-200 ring-orange-500/30', ring: 'ring-orange-500/40' };
  }
  if (m.reabierto) {
    return { label: 'Reabierto', cls: 'bg-amber-500/15 text-amber-300 ring-amber-500/30', ring: 'ring-amber-500/30' };
  }
  return { label: 'Pendiente', cls: 'bg-white/5 text-slate-400 ring-white/10', ring: 'ring-white/10' };
}

/** Dato esencial de cada módulo para la tarjeta (sin párrafos largos). */
function datosModulo(m) {
  const r = m.registro;
  switch (m.id) {
    case 'verificacion_intentos':
      return m.completado ? `${usados.value} de ${maximo} intentos` : '';
    case 'habilitacion_examen': {
      if (!r?.numero_resolucion) return '';
      return r.fecha_resolucion
        ? `Resolución HCC N.º ${r.numero_resolucion} — ${fechaCorta(r.fecha_resolucion)}`
        : `Resolución HCC N.º ${r.numero_resolucion}`;
    }
    case 'resolucion_resultado_teorico': {
      if (!r?.numero_resolucion) return '';
      return r.fecha_resolucion
        ? `Resolución de aprobación N.º ${r.numero_resolucion} - ${fechaCorta(r.fecha_resolucion)}`
        : `Resolución de aprobación N.º ${r.numero_resolucion}`;
    }
    case 'resolucion_asignacion_notas': {
      if (!r?.numero_resolucion) return '';
      return r.fecha_resolucion
        ? `Resolución de asignación de notas N.º ${r.numero_resolucion} - ${fechaCorta(r.fecha_resolucion)}`
        : `Resolución de asignación de notas N.º ${r.numero_resolucion}`;
    }
    case 'sorteo_teorico':
    case 'sorteo_especialidad': {
      if (!r) return '';
      if (r.conservado) return `Aprobado en intento anterior · Nota ${r.nota}`;
      if (r.resultado === 'no_asistio') return 'No asistió al sorteo';
      return [r.tema, r.fecha_examen ? fechaCorta(r.fecha_examen) : ''].filter(Boolean).join(' · ');
    }
    case 'asignacion_notas': {
      if (!r) return '';
      // Las notas que registró Kardex viven en `hitos.modulos.asignacion_notas`.
      const notas = datosDeModulo(props.tramite, 'asignacion_notas') || {};
      const partes = [];
      if (notas.nota_teorico !== null && notas.nota_teorico !== undefined && notas.nota_teorico !== '') {
        partes.push(`Teórico ${notas.nota_teorico}`);
      }
      if (notas.nota_especialidad !== null && notas.nota_especialidad !== undefined && notas.nota_especialidad !== '') {
        partes.push(`Especialidad ${notas.nota_especialidad}`);
      }
      if (partes.length) return partes.join(' · ');
      return r.resultado === 'aprobado' ? 'Ambos exámenes aprobados' : 'Examen reprobado';
    }
    case 'reporte':
      return m.completado ? 'Examen de Grado concluido' : '';
    default:
      return '';
  }
}

/**
 * Agrupa el historial por intento (`numero_intentos` de cada fila) y resume cada
 * ronda con su resolución vigente, temas, fechas, notas y resultado.
 */
const intentos = computed(() => {
  const filas = historialExamen(props.tramite);
  const grupos = new Map();

  filas.forEach((f) => {
    const n = Number(f.numero_intentos ?? 1);
    if (!grupos.has(n)) grupos.set(n, []);
    grupos.get(n).push(f);
  });

  let resolucionCargada = '';
  const resultado = [];

  [...grupos.keys()]
    .sort((a, b) => a - b)
    .forEach((n) => {
      const grupo = grupos.get(n);
      const habilitacion = grupo.find((f) => f.etapa === 'habilitacion_examen');
      if (habilitacion?.numero_resolucion) resolucionCargada = habilitacion.numero_resolucion;

      const sorteoTeorico = grupo.find((f) => f.etapa === 'sorteo_teorico');
      const sorteoEspecialidad = grupo.find((f) => f.etapa === 'sorteo_especialidad');
      const asignacion = [...grupo].reverse().find((f) => f.etapa === 'asignacion_notas');
      const ultima = grupo[grupo.length - 1];

      const teorico = resumenExamen(sorteoTeorico, asignacion, 'examen_teorico');
      const especialidad = resumenExamen(sorteoEspecialidad, asignacion, 'examen_especialidad');

      resultado.push({
        numero: n,
        fecha: ultima?.created_at || '',
        resolucion: resolucionCargada,
        enCurso: !asignacion,
        resultadoFinal: Boolean(asignacion && asignacion.resultado === 'aprobado'),
        teorico,
        especialidad,
      });
    });

  return resultado;
});

/**
 * Resume un examen dentro de un intento: tema, fecha, nota y resultado.
 *
 * `conservado`: un examen ya aprobado en esta resolución no vuelve a sortearse
 * en los intentos siguientes, así que su tema/nota se leen de
 * `hitos.examen_aprobado` (fuente de verdad de los aprobados).
 */
function resumenExamen(sorteo, asignacion, clave) {
  const conservado = props.tramite?.hitos?.examen_aprobado?.[clave];
  const enCurso = !asignacion;

  if (sorteo && sorteo.resultado === 'no_asistio') {
    return { tema: sorteo.tema, fecha: sorteo.fecha_examen || '', nota: null, resultado: 'reprobado', sinRendir: true };
  }

  if (!sorteo && conservado) {
    return {
      tema: conservado.tema,
      fecha: conservado.fecha_examen || '',
      nota: conservado.nota,
      resultado: enCurso ? 'conservado' : 'aprobado',
      sinRendir: false,
    };
  }

  const nota = asignacion ? extraerNota(asignacion, clave === 'examen_teorico') : null;

  return {
    tema: sorteo?.tema || null,
    fecha: sorteo?.fecha_examen || '',
    nota,
    resultado: enCurso ? 'pendiente' : resultadoExamen(asignacion, clave),
    sinRendir: false,
  };
}

/** Aprobación de un examen según el estado con el que cerró la asignación. */
function resultadoExamen(asignacion, clave) {
  const estado = asignacion?.nombre_estado || '';
  if (estado === 'aprobado') return 'aprobado';
  if (clave === 'examen_teorico') return estado === 'examen_especialidad_reprobado' ? 'aprobado' : 'reprobado';
  return estado === 'examen_teorico_reprobado' ? 'aprobado' : 'reprobado';
}

/**
 * Nota del examen desde el registro de asignación de notas.
 *
 * La nota del teórico queda en la columna `nota` del registro; la del examen de
 * especialidad solo vive en la observación oficial que genera el backend
 * ("examen de especialidad: 78 puntos" / "no asistió").
 */
function extraerNota(asignacion, esTeorico) {
  if (esTeorico) {
    const nota = asignacion.nota;
    return nota !== null && nota !== undefined && nota !== '' ? String(nota) : null;
  }

  const texto = String(asignacion.observaciones || '');
  const coincidencia = texto.match(/examen de especialidad:\s*(?:([\d.]+)\s*puntos|no asistió)/i);
  return coincidencia?.[1] || null;
}

function textoNota(examen) {
  if (examen.sinRendir) return 'No asistió';
  if (examen.resultado === 'pendiente') return 'Nota pendiente';
  if (examen.resultado === 'conservado') return `Aprobado en intento anterior (Nota ${examen.nota})`;
  if (examen.nota === null) return examen.resultado === 'aprobado' ? 'Aprobado' : 'Reprobado';
  return `${examen.resultado === 'aprobado' ? 'Aprobado' : 'Reprobado'} — Nota ${examen.nota}`;
}

function notaClase(examen) {
  if (examen.resultado === 'aprobado' || examen.resultado === 'conservado') return 'text-emerald-300';
  if (examen.resultado === 'pendiente') return 'text-orange-300';
  return 'text-red-400';
}

/** Formatea una fecha ISO en formato corto local (dd mmm yyyy, hh:mm). */
function fechaHora(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString('es-BO', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

/** Formatea una fecha de examen (YYYY-MM-DD) en formato corto. */
function fechaCorta(iso) {
  if (!iso) return '—';
  return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', {
    day: '2-digit', month: 'short', year: 'numeric',
  });
}
</script>
