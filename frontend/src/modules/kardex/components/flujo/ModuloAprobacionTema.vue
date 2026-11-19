<template>
  <ModuloTarjeta
    numero="2"
    titulo="Aprobación del tema y designación del tutor"
    descripcion="Registre la Resolución del HCC, valide la aprobación del perfil, apruebe el tema y designe al tutor."
    icono="book"
    :completado="completado && !editando"
    :en-progreso="enProgreso || editando"
    :bloqueado="bloqueado && !editando"
  >
    <div v-if="completado && datos && !editando" class="space-y-4">
      <section class="space-y-3">
        <h5 class="text-xs font-bold uppercase tracking-wider text-orange-300 flex items-center gap-1.5">
          <AppIcon name="book" :size="15" />
          2.1 Aprobación del tema
        </h5>
        <dl class="grid sm:grid-cols-2 gap-2.5">
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">N.º Resolución HCC</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.numero_resolucion }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de Resolución</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_resolucion) }}</dd>
          </div>
        </dl>
        <div
          v-if="datos.resolucion_tema?.nombre"
          class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 flex items-center justify-between gap-3"
        >
          <span class="flex items-center gap-2 text-sm text-slate-200 min-w-0">
            <AppIcon name="file-text" :size="15" class="text-orange-300 shrink-0" />
            <span class="truncate">Resolución de aprobación del tema: {{ datos.resolucion_tema.nombre }}</span>
          </span>
          <a
            v-if="datos.resolucion_tema.ruta"
            :href="assetUrl(datos.resolucion_tema.ruta)"
            target="_blank"
            rel="noopener"
            class="btn-ghost !py-1.5 text-xs shrink-0"
          >
            <AppIcon name="link" :size="13" />
            Ver PDF
          </a>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Tema de Investigación</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.tema_investigacion }}</dd>
        </div>
      </section>

      <section class="space-y-3 pt-4 border-t border-white/10">
        <h5 class="text-xs font-bold uppercase tracking-wider text-orange-300 flex items-center gap-1.5">
          <AppIcon name="user-check" :size="15" />
          2.2 Designación del tutor
        </h5>
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 flex items-center gap-2">
          <AppIcon name="user-check" :size="16" class="text-emerald-400" />
          <span class="text-sm font-semibold text-emerald-200">{{ nombreTutor }}</span>
        </div>
        <div
          v-if="datos.resolucion_tutor?.nombre"
          class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 flex items-center justify-between gap-3"
        >
          <span class="flex items-center gap-2 text-sm text-slate-200 min-w-0">
            <AppIcon name="file-text" :size="15" class="text-orange-300 shrink-0" />
            <span class="truncate">{{ datos.resolucion_tutor.nombre }}</span>
          </span>
          <a
            v-if="datos.resolucion_tutor.ruta"
            :href="assetUrl(datos.resolucion_tutor.ruta)"
            target="_blank"
            rel="noopener"
            class="btn-ghost !py-1.5 text-xs shrink-0"
          >
            <AppIcon name="link" :size="13" />
            Ver PDF
          </a>
        </div>
      </section>
    </div>

    <div v-else-if="bloqueado && !editando">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el paso anterior para registrar la aprobación del tema.
      </div>
    </div>

    <form v-else @submit.prevent="guardar" class="grid sm:grid-cols-2 gap-4">
      <p v-if="editando" class="sm:col-span-2 text-xs font-semibold text-orange-300 flex items-center gap-1.5">
        <AppIcon name="pencil" :size="14" />
        Editando información y documentos ya registrados.
      </p>

      <div class="sm:col-span-2 flex items-center gap-2 text-orange-300">
        <AppIcon name="book" :size="16" />
        <h5 class="text-sm font-bold uppercase text-white">2.1 Aprobación del tema</h5>
      </div>

      <div v-if="debeAprobarPerfil" class="sm:col-span-2 rounded-xl bg-orange-500/10 border border-orange-500/30 p-4">
        <p class="text-sm font-semibold text-orange-200 flex items-center gap-2">
          <AppIcon name="check-circle" :size="16" />
          Validación de aprobación del perfil en base a la Resolución del HCC
        </p>
        <p class="mt-1 text-xs text-orange-100/90">
          Con la Resolución ingresada, verifique el título y la documentación del perfil antes de guardar.
        </p>

        <div v-if="form.titulo_perfil" class="mt-3 rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Título de perfil presentado</p>
          <p class="mt-0.5 text-sm font-semibold text-white">{{ form.titulo_perfil }}</p>
        </div>

        <label class="mt-3 flex items-start gap-3 cursor-pointer select-none text-sm text-slate-200">
          <input v-model="form.perfil_aprobado" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0" />
          <span>Confirmo que el perfil de tesis queda <strong class="text-white">aprobado</strong> según la Resolución del HCC.</span>
        </label>
      </div>

      <div>
        <label class="label" for="tema-resolucion">Número de Resolución del HCC</label>
        <input
          id="tema-resolucion"
          v-model="form.numero_resolucion"
          type="text"
          class="input"
          placeholder="Ej: HCC-0102/2026"
        />
      </div>
      <div>
        <label class="label" for="tema-fecha">Fecha de Resolución</label>
        <input id="tema-fecha" v-model="form.fecha_resolucion" type="date" class="input" :max="hoyISO" />
      </div>

      <div class="sm:col-span-2">
        <label class="label" for="tema-resolucion-tema">
          Resolución de aprobación del tema (HCC)
          <span class="text-slate-500 font-normal normal-case">(PDF, opcional)</span>
        </label>
        <label
          :class="[
            'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
            archivoResolucionTema ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
          ]"
        >
          <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoResolucionTema ? 'text-orange-200' : 'text-slate-400'">
            <AppIcon
              :name="archivoResolucionTema ? 'check-circle' : 'file-text'"
              :size="18"
              :class="archivoResolucionTema ? 'text-emerald-400 shrink-0' : 'shrink-0'"
            />
            <span class="truncate">{{ archivoResolucionTema?.name || datos?.resolucion_tema?.nombre || 'Seleccione el PDF de la Resolución de aprobación del tema...' }}</span>
          </span>
          <input
            id="tema-resolucion-tema"
            type="file"
            accept=".pdf,application/pdf"
            class="hidden"
            @change="handleResolucionTema"
          />
          <span class="btn-ghost !py-2 pointer-events-none">
            <AppIcon name="plus" :size="15" />
            Adjuntar
          </span>
        </label>
        <p class="mt-1.5 text-xs text-slate-500">
          Adjunte la Resolución del HCC que aprueba el tema y, en este paso, valida la aprobación del perfil.
        </p>
      </div>

      <div class="sm:col-span-2">
        <label class="label" for="tema-investigacion">Tema de Investigación</label>
        <textarea
          id="tema-investigacion"
          v-model="form.tema_investigacion"
          rows="3"
          class="input resize-none"
          placeholder="Tema de investigación aprobado por el Consejo..."
        ></textarea>
      </div>

      <div class="sm:col-span-2 flex items-center gap-2 pt-2 border-t border-white/10 text-orange-300">
        <AppIcon name="user-check" :size="16" />
        <h5 class="text-sm font-bold uppercase text-white">2.2 Designación del tutor</h5>
      </div>

      <div class="sm:col-span-2">
        <label class="label" for="tema-resolucion-tutor">
          Resolución de designación como tutor
          <span class="text-slate-500 font-normal normal-case">(opcional)</span>
        </label>
        <label
          :class="[
            'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
            archivoResolucion ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
          ]"
        >
          <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoResolucion ? 'text-orange-200' : 'text-slate-400'">
            <AppIcon
              :name="archivoResolucion ? 'check-circle' : 'file-text'"
              :size="18"
              :class="archivoResolucion ? 'text-emerald-400 shrink-0' : 'shrink-0'"
            />
            <span class="truncate">{{ archivoResolucion?.name || 'Seleccione el PDF de la resolución...' }}</span>
          </span>
          <input
            id="tema-resolucion-tutor"
            type="file"
            accept=".pdf,application/pdf"
            class="hidden"
            @change="handleResolucion"
          />
          <span class="btn-ghost !py-2 pointer-events-none">
            <AppIcon name="plus" :size="15" />
            Subir
          </span>
        </label>
        <p class="mt-1.5 text-xs text-slate-500">
          PDF de la Resolución que designa al docente como tutor. Es opcional.
        </p>
      </div>

      <div class="sm:col-span-2">
        <label class="label">Asignación de Tutor</label>
        <div class="relative">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
            <AppIcon name="search" :size="16" />
          </span>
          <input
            v-model="busqueda"
            type="text"
            class="input pl-10"
            placeholder="Buscar docente por nombre..."
            @focus="abrirLista"
            @blur="cerrarLista"
          />
          <ul
            v-if="abierta"
            class="absolute z-20 mt-1 w-full rounded-xl border border-white/10 bg-slate-800 max-h-56 overflow-auto shadow-xl"
          >
            <li v-if="!docentesFiltrados.length" class="px-4 py-3 text-sm text-slate-400">
              Sin coincidencias.
            </li>
            <li
              v-for="doc in docentesFiltrados"
              :key="doc.id_docente"
              class="px-4 py-2.5 text-sm text-slate-200 cursor-pointer hover:bg-orange-500/10 hover:text-orange-200 flex items-center gap-2"
              @mousedown.prevent
              @click="seleccionar(doc)"
            >
              <AppIcon name="user" :size="14" class="text-slate-500" />
              {{ doc.nombres }} {{ doc.apellidos }}
            </li>
          </ul>
        </div>
        <p v-if="tutorElegido" class="mt-2 text-sm font-semibold text-emerald-300 flex items-center gap-1.5">
          <AppIcon name="user-check" :size="15" />
          Tutor: {{ tutorElegido.nombres }} {{ tutorElegido.apellidos }}
        </p>
      </div>

      <ul v-if="errores.length" class="sm:col-span-2 space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <div class="sm:col-span-2 flex justify-end">
        <button type="submit" class="btn-warm px-6" :disabled="cargando">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="check" :size="18" />
          <span class="uppercase tracking-wide">Guardar y actualizar flujo</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { validarModulo } from '@/modules/tramites/utils/flujoTitulacion';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  editando: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
  cargando: { type: Boolean, default: false },
  tituloPerfil: { type: String, default: '' },
  tramite: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const form = reactive({
  numero_resolucion: props.datos?.numero_resolucion || '',
  fecha_resolucion: props.datos?.fecha_resolucion || '',
  tema_investigacion: props.datos?.tema_investigacion || '',
  tutor_id: props.datos?.tutor_id || '',
  perfil_aprobado: Boolean(props.datos?.perfil_aprobado),
  titulo_perfil: '',
});

const busqueda = ref('');
const abierta = ref(false);
const errores = ref([]);
const archivoResolucion = ref(null);
const archivoResolucionTema = ref(null);

const hoyISO = computed(() => new Date().toISOString().slice(0, 10));
const tutorElegido = computed(() => props.docentes.find((d) => d.id_docente === Number(form.tutor_id)));
const debeAprobarPerfil = computed(
  () => props.tramite?.estado_actual === 'pendiente_concejo_universitario' && !props.completado,
);

const docentesFiltrados = computed(() => {
  const q = busqueda.value.trim().toLowerCase();
  if (!q) return props.docentes;
  return props.docentes.filter((d) => `${d.nombres} ${d.apellidos}`.toLowerCase().includes(q));
});

const nombreTutor = computed(() => {
  if (!props.datos?.tutor_id) return 'No registrado';
  const doc = props.docentes.find((d) => d.id_docente === Number(props.datos.tutor_id));
  return doc ? `${doc.nombres} ${doc.apellidos}` : `Docente #${props.datos.tutor_id}`;
});

function abrirLista() {
  if (tutorElegido.value) busqueda.value = '';
  abierta.value = true;
}

function cerrarLista() {
  abierta.value = false;
}

function seleccionar(doc) {
  form.tutor_id = doc.id_docente;
  busqueda.value = `${doc.nombres} ${doc.apellidos}`;
  abierta.value = false;
}

function handleResolucion(event) {
  const file = event.target.files[0] || null;
  if (file && file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    errores.value = ['La Resolución de designación como tutor debe ser un archivo PDF.'];
    archivoResolucion.value = null;
    event.target.value = '';
    return;
  }
  archivoResolucion.value = file;
  errores.value = [];
  event.target.value = '';
}

function handleResolucionTema(event) {
  const file = event.target.files[0] || null;
  if (file && file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    errores.value = ['La Resolución de aprobación del tema debe ser un archivo PDF.'];
    archivoResolucionTema.value = null;
    event.target.value = '';
    return;
  }
  archivoResolucionTema.value = file;
  errores.value = [];
  event.target.value = '';
}

function obtenerDatos() {
  const datos = {
    numero_resolucion: form.numero_resolucion.trim(),
    fecha_resolucion: form.fecha_resolucion,
    tema_investigacion: form.tema_investigacion.trim(),
    tutor_id: form.tutor_id,
  };
  if (debeAprobarPerfil.value) {
    datos.requiere_aprobacion_perfil = true;
    datos.perfil_aprobado = Boolean(form.perfil_aprobado);
    datos.titulo_perfil = String(form.titulo_perfil || props.tituloPerfil || '').trim();
  }
  return datos;
}

function guardar() {
  const datos = obtenerDatos();
  if (debeAprobarPerfil.value && !datos.perfil_aprobado) {
    errores.value = ['Debe confirmar la aprobación del perfil según la Resolución del HCC.'];
    return;
  }
  if (debeAprobarPerfil.value && !String(datos.titulo_perfil || '').trim()) {
    errores.value = ['No se encontró el título del perfil a aprobar.'];
    return;
  }
  const res = validarModulo('aprobacion_tema', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  const archivos = {};
  if (archivoResolucionTema.value) archivos.resolucion_tema = archivoResolucionTema.value;
  if (archivoResolucion.value) archivos.resolucion_tutor = archivoResolucion.value;
  emit('guardar', datos, archivos);
}

watch(
  () => props.tituloPerfil,
  (titulo) => {
    const limpio = String(titulo || '').trim();
    form.titulo_perfil = limpio;
    if (limpio && !form.tema_investigacion.trim()) {
      form.tema_investigacion = limpio;
    }
  },
  { immediate: true },
);

watch(
  () => [props.editando, props.datos, props.tramite?.estado_actual],
  () => {
    if (props.datos) {
      form.numero_resolucion = props.datos.numero_resolucion || '';
      form.fecha_resolucion = props.datos.fecha_resolucion || '';
      form.tema_investigacion = props.datos.tema_investigacion || '';
      form.tutor_id = props.datos.tutor_id || '';
      form.perfil_aprobado = Boolean(props.datos.perfil_aprobado);
      archivoResolucionTema.value = null;
      archivoResolucion.value = null;
    } else if (debeAprobarPerfil.value) {
      form.perfil_aprobado = false;
    }
  },
  { immediate: true },
);
</script>