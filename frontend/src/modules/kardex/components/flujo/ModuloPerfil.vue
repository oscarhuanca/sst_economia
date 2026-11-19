<template>
  <ModuloTarjeta
    numero="1"
    :titulo="etiquetaPerfil"
    :descripcion="descripcionModulo"
    icono="file-text"
    :completado="completado && !editando"
    :en-progreso="enProgreso || editando"
    :bloqueado="bloqueado && !editando"
  >
    <!-- Perfil aprobado -->
    <div v-if="completado && datos && !editando">
      <div v-if="tituloAprobado" class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-3">
        <p class="text-[11px] uppercase tracking-wider text-emerald-300/80 font-semibold flex items-center gap-1.5">
          <AppIcon name="file-text" :size="13" />
          {{ tituloLabel }} aprobado
        </p>
        <p class="mt-1 text-sm font-semibold text-emerald-100">{{ tituloAprobado }}</p>
      </div>

      <div class="space-y-2 mt-3">
        <div
          v-for="d in documentosPerfil"
          :key="d.tipo"
          class="flex items-center justify-between gap-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5"
        >
          <span class="flex items-center gap-2 text-sm font-semibold text-emerald-200">
            <AppIcon name="check-circle" :size="16" class="text-emerald-400" />
            {{ d.etiqueta }}
          </span>
          <span class="text-xs text-emerald-400 truncate max-w-[40%]">
            {{ datos.documentos?.[d.tipo]?.archivo?.nombre || 'Verificado' }}
          </span>
        </div>
      </div>
      <p v-if="datos.observaciones" class="mt-3 text-sm text-slate-300 italic">
        “{{ datos.observaciones }}”
      </p>
    </div>

    <!-- Estado heredado: el flujo espera la corrección del estudiante -->
    <div v-else-if="rechazado && !editando" class="space-y-3">
      <div class="rounded-xl bg-red-900/50 border border-red-500/30 px-4 py-3.5">
        <p class="flex items-center gap-2 text-sm font-bold text-red-300">
          <AppIcon name="x-circle" :size="17" />
          {{ etiquetaPerfil }} rechazado — esperando corrección
        </p>
        <p class="mt-1.5 text-sm text-red-200/90">
          El estudiante ya fue notificado y debe presentar nuevamente su perfil
          con un {{ tituloGenerico }} diferente.
        </p>
      </div>

      <div v-if="tituloRechazado" class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-3">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Título propuesto y rechazado</p>
        <p class="mt-1 text-sm text-white font-semibold">{{ tituloRechazado }}</p>
      </div>

      <div v-if="rechazo?.observacion" class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-3">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
          Observación de KARDEX
          <span v-if="rechazo.fecha">· {{ fechaLegible(rechazo.fecha) }}</span>
        </p>
        <p class="mt-1 text-sm text-slate-200 italic">“{{ rechazo.observacion }}”</p>
      </div>

      <p class="text-xs text-slate-400 flex items-start gap-1.5">
        <AppIcon name="info" :size="13" class="mt-0.5 shrink-0" />
        Cuando el estudiante envíe el perfil corregido volverá a este módulo para
        verificar el nuevo título antes de remitirlo al HCC.
      </p>
    </div>

    <!-- Remitido al HCC: tema y tutor se registran con la Resolución del HCC -->
    <div v-else-if="esperandoHcc && !editando" class="space-y-3">
      <div class="flex items-start gap-3 rounded-xl bg-orange-500/10 border border-orange-500/30 px-4 py-3.5 text-sm text-orange-100">
        <AppIcon name="info" :size="18" class="shrink-0 mt-0.5 text-orange-300" />
        <div>
          <p class="font-semibold text-orange-200">Documentación verificada y remitida al HCC</p>
          <p class="mt-1 text-orange-100/90">
            La aprobación del tema y la designación del tutor se registrarán en el
            siguiente paso con la <strong>Resolución del HCC</strong>.
          </p>
        </div>
      </div>
      <div v-if="tituloPresentado" class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-3">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">{{ tituloLabel }} presentado</p>
        <p class="mt-1 text-sm font-semibold text-white">{{ tituloPresentado }}</p>
      </div>
    </div>

    <!-- Paso bloqueado -->
    <div v-else-if="bloqueado && !editando">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete los pasos anteriores para habilitar la revisión del perfil.
      </div>
    </div>

    <!-- Revisión del perfil -->
    <form v-else @submit.prevent="remitir">
      <p v-if="editando" class="mb-4 text-xs font-semibold text-orange-300 flex items-center gap-1.5">
        <AppIcon name="pencil" :size="14" />
        Editando información y documentos ya registrados.
      </p>
      <!-- Título presentado por el estudiante: editable para corregir errores
           ortográficos; la corrección se persiste al remitir al HCC. -->
      <div class="rounded-xl bg-white/5 border border-white/10 p-3.5">
        <label class="label" for="perfil-titulo">{{ tituloLabel }} presentado por el estudiante</label>
        <textarea
          id="perfil-titulo"
          v-model="form.titulo"
          rows="2"
          maxlength="255"
          class="input resize-none"
          :placeholder="`El estudiante aún no registró un titulo de ${tituloGenerico}.`"
        ></textarea>
        <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2.5">
          <p class="flex items-center gap-1.5 text-xs text-slate-400">
            <AppIcon name="info" :size="13" class="shrink-0" />
            Puede corregir errores ortográficos; verifique el título y el resultado se guardará al remitir al HCC.
          </p>
          <button
            type="button"
            class="btn-primary shrink-0 justify-center"
            :disabled="form.titulo.trim().length < 10 || kardexStore.verificandoTitulo"
            @click="verificarTitulo"
          >
            <AppIcon v-if="kardexStore.verificandoTitulo" name="loader" :size="16" class="animate-spin" />
            <AppIcon v-else name="shield" :size="16" />
            <span class="uppercase tracking-wide">Verificar</span>
          </button>
        </div>
        <p v-if="tituloSinVerificar" class="mt-2 flex items-center gap-1.5 text-xs text-amber-300">
          <AppIcon name="alert-triangle" :size="13" class="shrink-0" />
          El título fue modificado: verifíquelo nuevamente antes de remitir al HCC.
        </p>

        <!-- Resultado de la verificación -->
        <div v-if="kardexStore.verificacionTitulo" class="mt-3 rounded-xl border p-3.5" :class="tonoVerificacion.borde">
          <div class="flex items-start gap-2.5">
            <AppIcon :name="tonoVerificacion.icono" :size="18" :class="tonoVerificacion.texto" class="shrink-0 mt-0.5" />
            <p class="text-sm min-w-0" :class="tonoVerificacion.texto">
              <template v-if="tituloDuplicado">
                Ya existe un trabajo registrado con <strong>exactamente este título</strong>.
                <template v-if="duplicadoTitulo"> Corresponde a {{ duplicadoTitulo.estudiante?.nombres }} {{ duplicadoTitulo.estudiante?.apellidos }}.</template>
                No se puede remitir al HCC con este título.
              </template>
              <template v-else-if="tituloHaySimilares">
                No hay títulos idénticos, pero se encontraron
                <strong>{{ kardexStore.verificacionTitulo.total }} {{ etiquetaSimilares }}</strong>.
                Revise si existe algún riesgo de duplicidad.
              </template>
              <template v-else>
                El título está <strong>disponible</strong>: no se encontraron trabajos iguales ni similares.
              </template>
            </p>
          </div>

          <ul v-if="kardexStore.verificacionTitulo.coincidencias.length" class="mt-3 space-y-2">
            <li
              v-for="c in kardexStore.verificacionTitulo.coincidencias"
              :key="c.id_tramite"
              class="rounded-xl bg-white/5 border border-white/10 p-3"
            >
              <div class="flex flex-wrap items-start justify-between gap-2.5">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span
                      class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider rounded-full px-2.5 py-1"
                      :class="c.exacto ? 'bg-red-500/20 text-red-300 border border-red-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'"
                    >
                      <AppIcon :name="c.exacto ? 'x-circle' : 'alert-triangle'" :size="12" />
                      {{ c.exacto ? 'Duplicado' : `${c.similitud}% similar` }}
                    </span>
                    <EstadoBadge :estado="c.estado_actual" :upper="true" />
                  </div>
                  <p class="mt-1.5 text-sm font-semibold text-white">{{ c.titulo }}</p>
                  <p class="mt-1 text-xs text-slate-400">
                    {{ c.estudiante.nombres }} {{ c.estudiante.apellidos }} · CI: {{ c.estudiante.ci }}
                    <span v-if="c.modalidad"> · {{ c.modalidad }}</span>
                  </p>
                </div>
                <button type="button" class="btn-ghost shrink-0" @click="emitir('consultar-coincidencia', c)">
                  <AppIcon name="search" :size="15" />
                  Ver trámite
                </button>
              </div>
            </li>
          </ul>

          <div class="mt-3 flex justify-end">
            <button type="button" class="btn-ghost" @click="kardexStore.limpiarVerificacionTitulo()">
              <AppIcon name="x" :size="15" />
              Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Documentos presentados por el estudiante: solo se verifican -->
      <div class="space-y-3 mt-3.5">
        <div
          v-for="d in documentosPerfil"
          :key="d.tipo"
          class="rounded-xl bg-white/5 border border-white/10 p-3.5"
        >
          <label class="flex items-center gap-3 cursor-pointer select-none">
            <input
              :id="`perfil-${d.tipo}-check`"
              v-model="form.documentos[d.tipo].marcado"
              type="checkbox"
              class="w-4 h-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
            />
            <span class="text-sm font-semibold text-white flex-1">
              {{ d.etiqueta }}
              <span class="text-xs font-normal text-slate-400">(verificado)</span>
            </span>
          </label>

          <div class="mt-3 flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl border border-white/10 bg-slate-900/40">
            <span
              class="flex items-center gap-2 text-sm min-w-0"
              :class="form.documentos[d.tipo].archivo ? 'text-slate-200' : 'text-slate-500'"
            >
              <AppIcon
                :name="form.documentos[d.tipo].archivo ? 'file-text' : 'alert-triangle'"
                :size="16"
                class="shrink-0"
                :class="form.documentos[d.tipo].archivo ? 'text-orange-300' : 'text-amber-400'"
              />
              <span class="truncate">{{ nombreArchivo(form.documentos[d.tipo].archivo) || 'El estudiante no adjuntó este PDF.' }}</span>
            </span>
            <a
              v-if="form.documentos[d.tipo].archivo?.ruta"
              :href="assetUrl(form.documentos[d.tipo].archivo.ruta)"
              target="_blank"
              rel="noopener"
              class="btn-ghost !py-1.5 text-xs shrink-0"
            >
              <AppIcon name="link" :size="13" />
              Ver PDF
            </a>
          </div>
        </div>
      </div>

      <!-- Observación -->
      <div class="mt-4">
        <label class="label" for="perfil-observaciones">Observaciones (opcional)</label>
        <textarea
          id="perfil-observaciones"
          v-model="form.observaciones"
          rows="3"
          class="input resize-none"
          placeholder="Comentarios sobre la verificación de la documentación..."
        ></textarea>
      </div>

      <ul v-if="errores.length" class="mt-4 space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <p v-if="tituloDuplicado" class="mt-4 flex items-start gap-2 rounded-xl bg-red-900/50 border border-red-500/30 px-3.5 py-3 text-sm text-red-300">
        <AppIcon name="alert-triangle" :size="16" class="shrink-0 mt-0.5" />
        No se puede remitir al HCC: hay un trabajo con el mismo título. Solicite al
        estudiante un título diferente antes de continuar.
      </p>

      <!-- Remisión al HCC -->
      <div class="mt-4 flex justify-end">
        <button
          type="submit"
          class="btn-warm justify-center"
          :disabled="cargando || tituloDuplicado || tituloSinVerificar"
        >
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="send" :size="18" />
          <span class="uppercase tracking-wide">Remitir al HCC</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
// Paso 1 del flujo de titulaci\u00f3n: revisi\u00f3n del PERFIL por parte de KARDEX.
//
// Es genérico: lo usan las modalidades del flujo de titulación por módulos
// (Tesis de Grado y Trabajo Dirigido). Lo que distingue a una de otra —el
// nombre del módulo de perfil, el nombre del trabajo y el catálogo de
// documentos que el estudiante adjunta— llega en la prop `config` (el registro
// `MODALIDADES_TITULACION` del núcleo compartido).
//
// Integra la verificación de unicidad del título presentado por el estudiante
// (que KARDEX puede corregir —p. ej. errores ortográficos— antes de remitir;
// la corrección se persiste con la remisión) y la verificación documental.
// Una vez validada la documentación, KARDEX remite el trámite al HCC
// (`pendiente_concejo_universitario`), donde se aprueba el tema y se designa al
// tutor en el módulo siguiente. El estado heredado `perfil_rechazado` solo se
// muestra cuando ya fue registrado: desde este módulo ya no se generan rechazos.
import { computed, reactive, ref, watch } from 'vue';
import { tituloPerfil, validarModulo } from '@/modules/tramites/utils/flujoTitulacion';
import { useKardexStore } from '../../stores/kardex';
import { fechaLegible } from '../../utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  editando: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
  tramite: { type: Object, default: null },
  rechazo: { type: Object, default: null },
  config: { type: Object, default: null },
});

const emit = defineEmits(['guardar', 'consultar-coincidencia']);

const kardexStore = useKardexStore();

const errores = ref([]);

// Último título del estudiante copiado al formulario (para no pisar las
// correcciones de KARDEX cuando el estudiante no cambia el suyo).
const ultimoTituloEstudiante = ref(null);

// Título con el que se verificó la unicidad por última vez; si el formulario
// se desvía de él, la verificación debe repetirse antes de remitir.
const tituloVerificado = ref('');

const form = reactive({
  titulo: '',
  documentos: {},
  observaciones: '',
});

/* ==========================================================================
   Textos y catálogos de la modalidad (todo viene de `config`)
   ========================================================================== */

/** Nombre del módulo de perfil ("Perfil de Tesis" / "Perfil de Proyecto"). */
const etiquetaPerfil = computed(() => props.config?.etiquetaPerfil || 'Perfil');

/** Etiqueta del título del trabajo ("Título de tesis" / "Título del trabajo dirigido"). */
const tituloLabel = computed(() => props.config?.tituloLabelCorto || 'Título');

/** Nombre genérico del trabajo en minúscula ("tesis" / "trabajo dirigido"). */
const tituloGenerico = computed(() => props.config?.etiquetaTitulo || 'tesis');

/** Documentos que el estudiante adjunta en la fase de solicitud. */
const documentosPerfil = computed(() => props.config?.documentosPerfil || []);

/** Etiqueta de los títulos parecidos hallados por la verificación. */
const etiquetaSimilares = computed(() => {
  const similares = props.config?.similares;
  if (!similares) return 'tesis similares';
  return kardexStore.verificacionTitulo?.total === 1 ? similares.singular : similares.plural;
});

const descripcionModulo = computed(
  () => `Revise el título y los ${documentosPerfil.value.length} documentos presentados por el postulante, `
    + 'verifíquelos y remítalos al HCC para la aprobación del tema y la designación del tutor.',
);

// Garantiza que el formulario tenga una entrada por cada documento de la
// modalidad (la configuración puede llegar después del montaje).
watch(documentosPerfil, (docs) => {
  docs.forEach((d) => {
    if (!form.documentos[d.tipo]) {
      form.documentos[d.tipo] = { marcado: false, archivo: null };
    }
  });
}, { immediate: true });

// Estado heredado `perfil_rechazado`: el módulo solo informa al estudiante y
// espera a que vuelva a presentar su perfil.
const rechazado = computed(() => props.tramite?.estado_actual === 'perfil_rechazado');
const esperandoHcc = computed(() => props.tramite?.estado_actual === 'pendiente_concejo_universitario');

// Título que el estudiante presentó con el perfil (o el confirmado en el tema).
const tituloPresentado = computed(() => tituloPerfil(props.tramite));

const tituloAprobado = computed(() => props.datos?.titulo || tituloPresentado.value);
const tituloRechazado = computed(() => tituloPresentado.value);

// Identificador del módulo de perfil de la modalidad (define el estado al que
// avanza el trámite cuando KARDEX remite la documentación al HCC).
const moduloPerfilId = computed(() => props.config?.moduloPerfil || 'perfil_tesis');

// true cuando el título en pantalla (posiblemente corregido por KARDEX) aún no
// fue verificado tras la última modificación.
const tituloSinVerificar = computed(() => {
  const titulo = form.titulo.trim();
  return titulo.length >= 10 && titulo !== tituloVerificado.value;
});

// true cuando la verificación encontró otro trabajo con el mismo título.
const tituloDuplicado = computed(
  () => Boolean(kardexStore.verificacionTitulo && !kardexStore.verificacionTitulo.unico),
);

// true cuando solo hay títulos parecidos (informativo: no bloquea la aprobación).
const tituloHaySimilares = computed(
  () => Boolean(kardexStore.verificacionTitulo && kardexStore.verificacionTitulo.unico
    && kardexStore.verificacionTitulo.total > 0),
);

// Trabajo que registra exactamente el mismo título (para nombrar al autor).
const duplicadoTitulo = computed(
  () => kardexStore.verificacionTitulo?.coincidencias?.find((c) => c.exacto) || null,
);

const tonoVerificacion = computed(() => {
  if (tituloDuplicado.value) {
    return { icono: 'x-circle', texto: 'text-red-300', borde: 'bg-red-900/40 border-red-500/30' };
  }
  if (tituloHaySimilares.value) {
    return { icono: 'alert-triangle', texto: 'text-amber-200', borde: 'bg-amber-500/10 border border-amber-500/30' };
  }
  return { icono: 'check-circle', texto: 'text-emerald-200', borde: 'bg-emerald-500/10 border border-emerald-500/30' };
});

// Documentos que el estudiante adjuntó en su solicitud, por tipo.
const documentosDelTramite = computed(() => {
  const lista = props.tramite?.documentos || [];
  return Object.fromEntries(
    lista
      .filter((d) => documentosPerfil.value.some((x) => x.tipo === d.tipo_documento))
      .map((d) => [
        d.tipo_documento,
        { nombre: d.nombre_archivo, tamanio: d.tamanio_kb, ruta: d.ruta_archivo },
      ]),
  );
});


function hidratarFormulario() {
  const titulo = props.datos?.titulo || tituloPresentado.value || '';
  if (titulo) {
    ultimoTituloEstudiante.value = tituloPresentado.value || titulo;
    form.titulo = titulo;
  }
  form.observaciones = props.datos?.observaciones || '';

  const documentos = documentosDelTramite.value;
  for (const d of documentosPerfil.value) {
    if (!form.documentos[d.tipo]) {
      form.documentos[d.tipo] = { marcado: false, archivo: null };
    }
    const guardado = props.datos?.documentos?.[d.tipo];
    form.documentos[d.tipo].marcado = Boolean(guardado?.marcado);
    form.documentos[d.tipo].archivo = guardado?.archivo || documentos[d.tipo] || null;
  }
}

/** Carga el titulo presentado y los documentos adjuntos en el formulario. */
watch(
  [tituloPresentado, documentosDelTramite, () => props.datos],
  () => {
    hidratarFormulario();
  },
  { immediate: true, deep: true },
);

watch(
  () => props.editando,
  (editando) => {
    if (editando) {
      errores.value = [];
      hidratarFormulario();
      verificarTitulo();
    }
  },
);

// Al entrar en revisión —o cuando el estudiante cambia el título que está en
// pantalla, gracias al auto-refresco de la vista— se verifica la unicidad:
// KARDEX debe ver el resultado vigente antes de remitir.
watch(
  [() => props.enProgreso, tituloPresentado],
  ([enProgreso]) => {
    if (enProgreso) verificarTitulo();
  },
  { immediate: true },
);

// Al corregir el título, la verificación previa deja de ser válida: se limpia
// el resultado para que KARDEX verifique la versión vigente antes de remitir.
watch(
  () => form.titulo,
  (titulo) => {
    if (titulo.trim() !== tituloVerificado.value) {
      kardexStore.limpiarVerificacionTitulo();
    }
  },
);

/**
 * Nombre del archivo en pantalla: el que el estudiante adjuntó en su solicitud
 * (`nombre`).
 */
function nombreArchivo(archivo) {
  return archivo?.name || archivo?.nombre || '';
}

/** Verifica la unicidad del título en pantalla (incluidas correcciones de KARDEX). */
async function verificarTitulo() {
  const titulo = form.titulo.trim();
  if (titulo.length < 10 || kardexStore.verificandoTitulo) return null;
  tituloVerificado.value = titulo;
  return kardexStore.verificarTitulo(titulo, props.tramite?.id_tramite || null);
}

function obtenerDatos(accion) {
  return {
    accion,
    titulo: form.titulo.trim(),
    documentos: Object.fromEntries(
      Object.entries(form.documentos).map(([tipo, item]) => [
        tipo,
        {
          marcado: Boolean(item.marcado),
          archivo: item.archivo
            ? {
              nombre: nombreArchivo(item.archivo),
              tamanio: item.archivo.size ?? item.archivo.tamanio ?? null,
            }
            : null,
        },
      ]),
    ),
    observaciones: form.observaciones.trim(),
  };
}

/** Remite la documentación verificada al HCC para aprobar tema y designar tutor. */
function remitir() {
  const datos = obtenerDatos('remitir');
  const res = validarModulo(moduloPerfilId.value, datos, props.config?.nombre);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  emit('guardar', datos);
}

// Permite que la vista dispare la verificación desde el resumen del postulante.
defineExpose({ verificarTitulo });
</script>
