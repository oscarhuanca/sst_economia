<template>
  <ModuloTarjeta
    numero="3"
    titulo="Tribunal Revisor"
    descripcion="Verifique la Nota de Suficiencia del tutor, la Resolución de designación del tribunal revisor y registre los datos de la Resolución del HCC."
    icono="users"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="space-y-3">
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

      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
              Nota de Suficiencia del Tutor
            </p>
            <p class="text-sm font-semibold text-white mt-0.5">{{ nombreTutor }}</p>
            <p
              class="text-xs mt-0.5 truncate"
              :class="datos.nota_suficiencia_tutor?.nombre ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ datos.nota_suficiencia_tutor?.nombre || 'Sin PDF adjunto' }}
            </p>
          </div>
          <div class="flex flex-col items-end gap-1.5 shrink-0">
            <span
              v-if="datos.nota_suficiencia_tutor_entregada"
              class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-emerald-300"
            >
              <AppIcon name="check-circle" :size="13" />
              Entregada
            </span>
            <a
              v-if="datos.nota_suficiencia_tutor?.ruta"
              :href="assetUrl(datos.nota_suficiencia_tutor.ruta)"
              target="_blank"
              rel="noopener"
              class="btn-ghost !py-1.5 text-xs"
            >
              <AppIcon name="link" :size="13" />
              Ver PDF
            </a>
          </div>
        </div>
      </div>

      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
              Resolución de Designación de Tribunal Revisor
            </p>
            <p
              class="text-sm font-semibold mt-0.5 truncate"
              :class="datos.resolucion_tribunal?.nombre ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ datos.resolucion_tribunal?.nombre || 'Sin PDF adjunto' }}
            </p>
          </div>
          <div class="flex flex-col items-end gap-1.5 shrink-0">
            <span
              v-if="datos.resolucion_tribunal_entregada"
              class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-emerald-300"
            >
              <AppIcon name="check-circle" :size="13" />
              Confirmada
            </span>
            <a
              v-if="datos.resolucion_tribunal?.ruta"
              :href="assetUrl(datos.resolucion_tribunal.ruta)"
              target="_blank"
              rel="noopener"
              class="btn-ghost !py-1.5 text-xs"
            >
              <AppIcon name="link" :size="13" />
              Ver PDF
            </a>
          </div>
        </div>
      </div>

      <div class="space-y-2">
        <div
          v-for="miembro in datos.tribunal"
          :key="miembro.rol"
          class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 space-y-2"
        >
          <div class="flex items-center justify-between gap-3">
            <span class="text-[11px] uppercase tracking-wide text-emerald-300 font-bold">{{ miembro.rol }}</span>
            <span class="text-sm font-semibold text-emerald-100">{{ miembro.nombre }}</span>
          </div>
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span
              v-if="miembro.nota_suficiencia_entregada"
              class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-emerald-300"
            >
              <AppIcon name="check-circle" :size="13" />
              Nota de Suficiencia entregada
            </span>
            <span v-else class="text-[10px] uppercase tracking-wide text-slate-400">Nota sin confirmar</span>
            <a
              v-if="miembro.nota_suficiencia?.ruta"
              :href="assetUrl(miembro.nota_suficiencia.ruta)"
              target="_blank"
              rel="noopener"
              class="btn-ghost !py-1.5 text-xs"
            >
              <AppIcon name="link" :size="13" />
              Ver PDF
            </a>
            <span v-else-if="miembro.nota_suficiencia?.nombre" class="text-xs text-slate-300 truncate">
              {{ miembro.nota_suficiencia.nombre }}
            </span>
            <span v-else class="text-xs text-slate-500">Sin PDF adjunto</span>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para designar el tribunal revisor.
      </div>
    </div>

    <form v-else @submit.prevent="enviar" class="space-y-5">
      <div class="grid sm:grid-cols-3 gap-3">
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso1"
          @click="irPaso(1)"
        >
          <span
            class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold"
            :class="dotPaso1"
          >
            <AppIcon v-if="tutorEntregada" name="check" :size="14" />
            <template v-else>1</template>
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Nota de Suficiencia del Tutor</span>
            <span class="block text-xs" :class="tutorEntregada ? 'text-emerald-300/80' : 'text-slate-400'">
              {{ tutorEntregada ? 'Entrega confirmada' : 'Verifique la entrega y adjunte el PDF opcional' }}
            </span>
          </span>
        </button>

        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso2"
          :disabled="!tutorEntregada"
          @click="irPaso(2)"
        >
          <span
            class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold"
            :class="dotPaso2"
          >
            <AppIcon v-if="resolucionTribunalEntregada" name="check" :size="14" />
            <template v-else>2</template>
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Resolución de Designación de Tribunal Revisor</span>
            <span
              class="block text-xs"
              :class="resolucionTribunalEntregada ? 'text-emerald-300/80' : 'text-slate-400'"
            >
              {{ resolucionTribunalEntregada ? 'Resolución confirmada' : 'Confirme la resolución y adjunte el PDF opcional' }}
            </span>
          </span>
        </button>

        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso3"
          :disabled="!puedePaso3"
          @click="irPaso(3)"
        >
          <span
            class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold"
            :class="dotPaso3"
          >
            3
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Resolución y Tribunal Revisor</span>
            <span class="block text-xs text-slate-400">
              {{ puedePaso3 ? 'Registre la resolución del HCC y valide las notas' : 'Se habilita al confirmar el paso 2' }}
            </span>
          </span>
        </button>
      </div>

      <ul v-if="errores.length" class="space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <div v-if="paso === '1'" class="space-y-4">
        <div class="rounded-xl bg-white/5 border border-white/10 p-3.5 space-y-3">
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Tutor asignado</p>
            <p class="text-sm font-semibold text-white mt-0.5">{{ nombreTutor }}</p>
          </div>

          <div>
            <label class="label" for="tribunal-nota-tutor">
              Nota de Suficiencia del tutor
              <span class="text-slate-500 font-normal normal-case">(PDF opcional)</span>
            </label>
            <label
              :class="[
                'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
                archivoTutor ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
              ]"
            >
              <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoTutor ? 'text-orange-200' : 'text-slate-400'">
                <AppIcon
                  :name="archivoTutor ? 'check-circle' : 'file-text'"
                  :size="18"
                  :class="archivoTutor ? 'text-emerald-400 shrink-0' : 'shrink-0'"
                />
                <span class="truncate">{{ archivoTutor?.name || 'Seleccione el PDF de la Nota de Suficiencia...' }}</span>
              </span>
              <input
                id="tribunal-nota-tutor"
                type="file"
                accept=".pdf,application/pdf"
                class="hidden"
                @change="handleArchivoTutor"
              />
              <span class="btn-ghost !py-2 pointer-events-none">
                <AppIcon name="plus" :size="15" />
                Subir
              </span>
            </label>
          </div>

          <label class="flex items-start gap-2.5 rounded-xl border border-orange-500/30 bg-orange-500/10 px-3.5 py-3 cursor-pointer">
            <input
              v-model="tutorEntregada"
              type="checkbox"
              class="mt-0.5 h-4 w-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
            />
            <span class="text-xs text-orange-100 leading-relaxed">
              Confirmo que el tutor entregó la <strong>Nota de Suficiencia</strong> para continuar con la designación del tribunal.
            </span>
          </label>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="btn-warm px-6">
            <AppIcon name="check" :size="18" />
            <span class="uppercase tracking-wide">Continuar a designación del tribunal</span>
          </button>
        </div>
      </div>

      <div v-else-if="paso === '2'" class="space-y-4">
        <div class="rounded-xl bg-white/5 border border-white/10 p-3.5 space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Tutor asignado</p>
              <p class="text-sm font-semibold text-white mt-0.5">{{ nombreTutor }}</p>
            </div>
            <span
              v-if="datos?.resolucion_tribunal?.nombre"
              class="text-xs text-slate-400 truncate max-w-[220px]"
            >
              PDF previo: {{ datos.resolucion_tribunal.nombre }}
            </span>
          </div>

          <div>
            <label class="label" for="tribunal-resolucion-designacion">
              Resolución de designación del tribunal revisor
              <span class="text-slate-500 font-normal normal-case">(PDF opcional)</span>
            </label>
            <label
              :class="[
                'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition',
                archivoResolucionTribunal ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40',
              ]"
            >
              <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoResolucionTribunal ? 'text-orange-200' : 'text-slate-400'">
                <AppIcon
                  :name="archivoResolucionTribunal ? 'check-circle' : 'file-text'"
                  :size="18"
                  :class="archivoResolucionTribunal ? 'text-emerald-400 shrink-0' : 'shrink-0'"
                />
                <span class="truncate">{{ archivoResolucionTribunal?.name || 'Seleccione el PDF de la Resolución...' }}</span>
              </span>
              <input
                id="tribunal-resolucion-designacion"
                type="file"
                accept=".pdf,application/pdf"
                class="hidden"
                @change="handleArchivoResolucionTribunal"
              />
              <span class="btn-ghost !py-2 pointer-events-none">
                <AppIcon name="plus" :size="15" />
                Subir
              </span>
            </label>
            <p class="text-[11px] text-slate-500 mt-1.5">
              Adjunte la resolución del HCC que designa al tribunal revisor, si ya cuenta con ella.
            </p>
          </div>

          <label class="flex items-start gap-2.5 rounded-xl border border-orange-500/30 bg-orange-500/10 px-3.5 py-3 cursor-pointer">
            <input
              v-model="resolucionTribunalEntregada"
              type="checkbox"
              class="mt-0.5 h-4 w-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
            />
            <span class="text-xs text-orange-100 leading-relaxed">
              Confirmo que se cuenta con la <strong>Resolución de designación del tribunal revisor</strong> para continuar con la resolución del HCC.
            </span>
          </label>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="btn-warm px-6">
            <AppIcon name="check" :size="18" />
            <span class="uppercase tracking-wide">Continuar a resolución y tribunal</span>
          </button>
        </div>
      </div>

      <div v-else class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="tribunal-resolucion">Número de Resolución del HCC</label>
          <input
            id="tribunal-resolucion"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: HCC-0158/2026"
          />
        </div>
        <div>
          <label class="label" for="tribunal-fecha">Fecha de Resolución</label>
          <input id="tribunal-fecha" v-model="form.fecha_resolucion" type="date" class="input" :max="hoyISO" />
        </div>

        <div class="sm:col-span-2">
          <label class="label">Nombres del Tribunal Revisor</label>
          <div class="space-y-2.5">
            <div
              v-for="miembro in form.tribunal"
              :key="miembro.rol"
              class="rounded-xl bg-white/5 border border-white/10 p-3.5 space-y-2.5"
            >
              <div class="flex items-center gap-3">
                <span class="w-24 shrink-0 text-[11px] uppercase tracking-wider font-bold text-orange-300">
                  {{ miembro.rol }}
                </span>
                <input
                  v-model="miembro.nombre"
                  type="text"
                  class="input flex-1"
                  :placeholder="`Nombre del ${miembro.rol} del tribunal...`"
                />
              </div>

              <div class="flex flex-wrap items-center gap-2 sm:pl-[108px]">
                <label
                  :class="[
                    'flex items-center justify-between gap-2 flex-1 min-w-0 px-3 py-2 rounded-xl border-2 border-dashed cursor-pointer transition',
                    archivoMiembro[miembro.rol]
                      ? 'border-orange-500/50 bg-orange-500/10'
                      : 'border-white/10 bg-white/5 hover:border-orange-500/40',
                  ]"
                >
                  <span
                    class="flex items-center gap-2 text-xs min-w-0"
                    :class="archivoMiembro[miembro.rol] ? 'text-orange-200' : 'text-slate-400'"
                  >
                    <AppIcon
                      :name="archivoMiembro[miembro.rol] ? 'check-circle' : 'file-text'"
                      :size="15"
                      :class="archivoMiembro[miembro.rol] ? 'text-emerald-400 shrink-0' : 'shrink-0'"
                    />
                    <span class="truncate">
                      {{ archivoMiembro[miembro.rol]?.name || 'Nota de Suficiencia (PDF opcional)' }}
                    </span>
                  </span>
                  <input
                    :id="`tribunal-nota-${miembro.rol}`"
                    type="file"
                    accept=".pdf,application/pdf"
                    class="hidden"
                    @change="(event) => handleArchivoMiembro(miembro.rol, event)"
                  />
                  <span class="btn-ghost !py-1.5 text-xs pointer-events-none shrink-0">
                    <AppIcon name="plus" :size="13" />
                    Subir
                  </span>
                </label>

                <label
                  class="flex items-center gap-2 shrink-0 rounded-xl border px-3 py-2 cursor-pointer select-none transition"
                  :class="miembro.nota_suficiencia_entregada
                    ? 'border-emerald-500/40 bg-emerald-500/10'
                    : 'border-white/10 bg-white/5'"
                >
                  <input
                    v-model="miembro.nota_suficiencia_entregada"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
                  />
                  <span
                    class="text-xs font-semibold"
                    :class="miembro.nota_suficiencia_entregada ? 'text-emerald-200' : 'text-slate-400'"
                  >
                    Entregó su Nota
                  </span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="sm:col-span-2 flex justify-end">
          <button type="submit" class="btn-warm px-6" :disabled="cargando">
            <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="check" :size="18" />
            <span class="uppercase tracking-wide">Guardar y actualizar flujo</span>
          </button>
        </div>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { ROLES_TRIBUNAL, validarModulo } from '@/modules/tramites/utils/flujoTitulacion';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { assetUrl } from '@/core/http/storage';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  tutor: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
});

const emit = defineEmits(['guardar']);

function confirmado(valor) {
  return valor === true || valor === 1 || valor === '1';
}

const form = reactive({
  numero_resolucion: props.datos?.numero_resolucion || '',
  fecha_resolucion: props.datos?.fecha_resolucion || '',
  tribunal: ROLES_TRIBUNAL.map((rol) => {
    const previo = props.datos?.tribunal?.find((t) => t.rol === rol);
    return {
      rol,
      nombre: previo?.nombre || '',
      nota_suficiencia: previo?.nota_suficiencia || null,
      nota_suficiencia_entregada: confirmado(previo?.nota_suficiencia_entregada),
    };
  }),
});

const paso = ref('1');
const errores = ref([]);
const tutorEntregada = ref(confirmado(props.datos?.nota_suficiencia_tutor_entregada));
const resolucionTribunalEntregada = ref(confirmado(props.datos?.resolucion_tribunal_entregada));
const archivoTutor = ref(null);
const archivoResolucionTribunal = ref(null);
const archivoMiembro = reactive({
  presidente: null,
  vocal: null,
  secretario: null,
});

const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

const nombreTutor = computed(() => {
  if (!props.tutor) return 'Tutor no asignado';
  return [props.tutor.nombres || props.tutor.nombre, props.tutor.apellidos]
    .filter(Boolean)
    .join(' ');
});

const DOT_ACTIVO = 'bg-orange text-white';
const DOT_PENDIENTE = 'bg-white/10 text-slate-400';

const chipPaso1 = computed(() =>
  tutorEntregada.value
    ? 'border-emerald-500/30 bg-emerald-500/10'
    : paso.value === '1'
      ? 'border-orange-500 bg-orange-500/10'
      : 'border-white/10 bg-white/5',
);

const dotPaso1 = computed(() => {
  if (tutorEntregada.value) return 'bg-emerald-500 text-white';
  return paso.value === '1' ? DOT_ACTIVO : DOT_PENDIENTE;
});

const puedePaso3 = computed(() => tutorEntregada.value && resolucionTribunalEntregada.value);

const chipPaso2 = computed(() => {
  if (!tutorEntregada.value) return 'border-white/10 bg-white/5 opacity-50';
  if (resolucionTribunalEntregada.value) return 'border-emerald-500/30 bg-emerald-500/10';
  return paso.value === '2' ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5';
});

const dotPaso2 = computed(() => {
  if (resolucionTribunalEntregada.value) return 'bg-emerald-500 text-white';
  return paso.value === '2' ? DOT_ACTIVO : DOT_PENDIENTE;
});

const chipPaso3 = computed(() => {
  if (!puedePaso3.value) return 'border-white/10 bg-white/5 opacity-50';
  return paso.value === '3' ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5';
});

const dotPaso3 = computed(() => (paso.value === '3' ? DOT_ACTIVO : DOT_PENDIENTE));

function irPaso(n) {
  if (n >= 2 && !tutorEntregada.value) {
    errores.value = ['Confirme que el tutor entregó la Nota de Suficiencia para continuar.'];
    return;
  }
  if (n >= 3 && !resolucionTribunalEntregada.value) {
    errores.value = ['Confirme que se cuenta con la Resolución de designación del tribunal revisor para continuar.'];
    return;
  }
  paso.value = String(n);
  errores.value = [];
}

watch(tutorEntregada, (entregada) => {
  if (!entregada && paso.value !== '1') {
    paso.value = '1';
  }
});

watch(resolucionTribunalEntregada, (confirmada) => {
  if (!confirmada && paso.value === '3') {
    paso.value = '2';
  }
});

function esPdf(file) {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

function handleArchivoTutor(event) {
  const file = event.target.files[0] || null;
  if (file && !esPdf(file)) {
    errores.value = ['La Nota de Suficiencia del tutor debe ser un archivo PDF.'];
    archivoTutor.value = null;
    event.target.value = '';
    return;
  }
  archivoTutor.value = file;
  errores.value = [];
  event.target.value = '';
}

function handleArchivoResolucionTribunal(event) {
  const file = event.target.files[0] || null;
  if (file && !esPdf(file)) {
    errores.value = ['La Resolución de designación del tribunal revisor debe ser un archivo PDF.'];
    archivoResolucionTribunal.value = null;
    event.target.value = '';
    return;
  }
  archivoResolucionTribunal.value = file;
  errores.value = [];
  event.target.value = '';
}

function handleArchivoMiembro(rol, event) {
  const file = event.target.files[0] || null;
  if (file && !esPdf(file)) {
    errores.value = [`La Nota de Suficiencia del ${rol} debe ser un archivo PDF.`];
    archivoMiembro[rol] = null;
    event.target.value = '';
    return;
  }
  archivoMiembro[rol] = file;
  errores.value = [];
  event.target.value = '';
}

function obtenerDatos() {
  const datos = {
    numero_resolucion: form.numero_resolucion.trim(),
    fecha_resolucion: form.fecha_resolucion,
    nota_suficiencia_tutor_entregada: tutorEntregada.value,
    resolucion_tribunal_entregada: resolucionTribunalEntregada.value,
    tribunal: form.tribunal.map((t) => ({
      rol: t.rol,
      nombre: t.nombre.trim(),
      nota_suficiencia_entregada: Boolean(t.nota_suficiencia_entregada),
    })),
  };

  if (props.datos?.nota_suficiencia_tutor) {
    datos.nota_suficiencia_tutor = props.datos.nota_suficiencia_tutor;
  }

  if (props.datos?.resolucion_tribunal) {
    datos.resolucion_tribunal = props.datos.resolucion_tribunal;
  }

  form.tribunal.forEach((t, i) => {
    if (t.nota_suficiencia) {
      datos.tribunal[i].nota_suficiencia = t.nota_suficiencia;
    }
  });

  return datos;
}

function obtenerArchivos() {
  const archivos = {};
  if (archivoTutor.value) {
    archivos.nota_suficiencia_tutor = archivoTutor.value;
  }
  if (archivoResolucionTribunal.value) {
    archivos.resolucion_tribunal = archivoResolucionTribunal.value;
  }
  ROLES_TRIBUNAL.forEach((rol) => {
    if (archivoMiembro[rol]) {
      archivos[`nota_suficiencia_${rol}`] = archivoMiembro[rol];
    }
  });
  return archivos;
}

function enviar() {
  if (paso.value === '1') {
    errores.value = [];
    if (!tutorEntregada.value) {
      errores.value = ['Confirme que el tutor entregó la Nota de Suficiencia para continuar.'];
      return;
    }
    paso.value = '2';
    return;
  }

  if (paso.value === '2') {
    errores.value = [];
    if (!resolucionTribunalEntregada.value) {
      errores.value = ['Confirme que se cuenta con la Resolución de designación del tribunal revisor para continuar.'];
      return;
    }
    paso.value = '3';
    return;
  }

  const datos = obtenerDatos();
  const res = validarModulo('tribunal_revisor', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }

  errores.value = [];
  emit('guardar', datos, obtenerArchivos());
}
</script>
