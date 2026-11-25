<template>
  <div class="space-y-6">
    <!-- Encabezado del módulo -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-orange text-white flex items-center justify-center shadow-lg shadow-orange-900/30">
        <AppIcon name="user-plus" :size="24" />
      </div>
      <div>
        <h2 class="text-2xl font-bold text-white uppercase">Registro de Postulante</h2>
        <p class="text-sm text-slate-400 mt-0.5">
          Etapa 1: generación de credenciales. Etapa 2: verificación documental y remisión al HCC.
        </p>
      </div>
    </div>

    <!-- Etapas del registro -->
    <div class="card p-4 sm:p-5">
      <div class="grid sm:grid-cols-2 gap-3">
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border-2 p-4 text-left transition"
          :class="etapa === 1 ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/50'"
          @click="irAEtapa(1)"
        >
          <span
            class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-sm font-bold"
            :class="etapa === 1 ? 'bg-orange-500 text-white' : 'bg-white/10 text-slate-300'"
          >1</span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-white">Generación de credenciales</span>
            <span class="block text-[11px] text-slate-400 mt-0.5">Crear el usuario y la contraseña del postulante.</span>
          </span>
          <AppIcon v-if="credencialesListas" name="check-circle" :size="18" class="shrink-0 text-emerald-400" />
        </button>
        <button
          type="button"
          :disabled="!credencialesListas"
          class="flex items-center gap-3 rounded-xl border-2 p-4 text-left transition disabled:opacity-40 disabled:cursor-not-allowed"
          :class="etapa === 2 ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/50'"
          @click="irAEtapa(2)"
        >
          <span
            class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-sm font-bold"
            :class="etapa === 2 ? 'bg-orange-500 text-white' : 'bg-white/10 text-slate-300'"
          >2</span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-white">Verificación documental y remisión al HCC</span>
            <span class="block text-[11px] text-slate-400 mt-0.5">Verificar documentos físicos/digitales y remitir al HCC.</span>
          </span>
          <AppIcon v-if="documentacionRemitida" name="check-circle" :size="18" class="shrink-0 text-emerald-400" />
        </button>
      </div>
    </div>

    <!-- Búsqueda por CI/RU -->
    <form class="card p-4 sm:p-5" @submit.prevent="buscar()">
      <label class="label" for="identificador">Filtrar por CI/RU</label>
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
            <AppIcon name="search" :size="18" />
          </span>
          <input
            id="identificador"
            v-model="identificador"
            type="text"
            class="input pl-10"
            placeholder="CI o registro universitario"
            autocomplete="off"
          />
        </div>
        <button
          type="submit"
          class="btn-primary shrink-0 justify-center"
          :disabled="!identificador.trim() || kardexStore.cargando"
        >
          <AppIcon v-if="kardexStore.cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="search" :size="18" />
          <span class="uppercase tracking-wide">Buscar</span>
        </button>
      </div>
    </form>

    <!-- Error de búsqueda -->
    <div
      v-if="kardexStore.errorMessage && !kardexStore.postulante"
      class="flex items-start gap-3 rounded-xl bg-red-900/50 border border-red-500/30 px-4 py-3 text-sm text-red-400"
    >
      <AppIcon name="alert-triangle" :size="18" class="shrink-0 mt-0.5" />
      {{ kardexStore.errorMessage }}
    </div>

    <!-- Estado vacío inicial -->
    <EmptyState
      v-if="!kardexStore.postulante && !kardexStore.errorMessage"
      icon="search"
      title="Buscar postulante"
      message="Ingrese el CI o el registro universitario del postulante para generar sus credenciales y asignarle una modalidad."
    />

    <!-- Postulante encontrado -->
    <template v-if="kardexStore.postulante">
      <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="w-14 h-14 shrink-0 rounded-2xl bg-orange text-white font-bold text-lg flex items-center justify-center shadow-lg">
            {{ iniciales(kardexStore.postulante) }}
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-white truncate">
              {{ kardexStore.postulante.nombres }} {{ kardexStore.postulante.apellidos }}
            </h3>
            <div class="flex flex-wrap gap-2 mt-1.5">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/5 border border-white/10 text-slate-300 rounded-full px-2.5 py-1">
                <AppIcon name="user" :size="12" /> CI: {{ kardexStore.postulante.ci }}
              </span>
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/5 border border-white/10 text-slate-300 rounded-full px-2.5 py-1">
                <AppIcon name="file-text" :size="12" /> R.U.: {{ kardexStore.postulante.registro_universitario }}
              </span>
              <span
                class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-2.5 py-1 border"
                :class="kardexStore.postulante.tiene_cuenta ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-300' : 'bg-orange-500/15 border-orange-500/30 text-orange-300'"
              >
                <AppIcon :name="kardexStore.postulante.tiene_cuenta ? 'check-circle' : 'clock'" :size="12" />
                {{ kardexStore.postulante.tiene_cuenta ? 'Con cuenta de acceso' : 'Sin cuenta de acceso' }}
              </span>
            </div>
          </div>
          <button class="btn-ghost shrink-0" @click="reiniciar">
            <AppIcon name="x" :size="16" />
            Limpiar
          </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 pt-4 border-t border-white/10 text-sm">
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de nacimiento</p>
            <p class="text-slate-200 font-medium mt-0.5">{{ fechaLegible(kardexStore.postulante.fecha_nacimiento) }}</p>
          </div>
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Email</p>
            <p class="text-slate-200 font-medium mt-0.5 truncate">{{ kardexStore.postulante.email || '—' }}</p>
          </div>
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Teléfono</p>
            <p class="text-slate-200 font-medium mt-0.5">{{ kardexStore.postulante.telefono || '—' }}</p>
          </div>
          <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Promedio</p>
            <p class="text-slate-200 font-medium mt-0.5">{{ kardexStore.postulante.promedio_global ?? '—' }}</p>
          </div>
        </div>
      </div>

      <!-- Etapa 1: generación de credenciales -->
      <section v-if="etapa === 1" class="card p-5">
        <h3 class="font-bold text-white uppercase flex items-center gap-2">
          <AppIcon name="zap" :size="18" class="text-orange-300" />
          1. Generación de credenciales
        </h3>
        <p class="text-sm text-slate-400 mt-1">
          Se crea el usuario y la contraseña temporal del postulante. Esta etapa no inicia ningún trámite.
        </p>
        <button
          class="btn-primary mt-5 w-full sm:w-auto justify-center"
          :disabled="kardexStore.cargando"
          @click="generar"
        >
          <AppIcon v-if="kardexStore.cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="zap" :size="18" />
          <span class="uppercase tracking-wide">
            {{ kardexStore.postulante.tiene_cuenta ? 'Verificar credenciales existentes' : 'Generar credenciales' }}
          </span>
        </button>
      </section>

      <!-- Credenciales generadas en la etapa 1 -->
      <div v-if="etapa === 1 && kardexStore.credenciales" class="card p-5 border-emerald-500/30">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <h3 class="font-bold text-white uppercase flex items-center gap-2">
            <AppIcon :name="kardexStore.credenciales.generadas ? 'check-circle' : 'info'" :size="18" :class="kardexStore.credenciales.generadas ? 'text-emerald-400' : 'text-orange-400'" />
            {{ kardexStore.credenciales.generadas ? 'Credenciales generadas' : 'Credenciales existentes' }}
          </h3>
          <span
            class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-2.5 py-1 border"
            :class="kardexStore.credenciales.generadas ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-300' : 'bg-orange-500/15 border-orange-500/30 text-orange-300'"
          >
            <AppIcon :name="kardexStore.credenciales.generadas ? 'zap' : 'user'" :size="12" />
            {{ kardexStore.credenciales.generadas ? 'Nueva cuenta' : 'Cuenta ya creada' }}
          </span>
        </div>

        <p v-if="kardexStore.credenciales.generadas" class="text-sm text-slate-400 mt-1">
          Entregue estas credenciales al postulante para que acceda al sistema.
        </p>
        <p v-else class="text-sm text-slate-400 mt-1">
          El postulante ya posee acceso. Use el usuario mostrado para informe; la contraseña no se vuelve a generar.
        </p>

        <div class="grid sm:grid-cols-2 gap-3 mt-4">
          <div class="rounded-xl bg-primary border border-white/10 text-white p-4">
            <p class="text-[11px] uppercase tracking-wider text-orange-300 font-semibold">Usuario</p>
            <p class="font-mono text-lg font-bold mt-1 break-all">{{ kardexStore.credenciales.username }}</p>
            <button
              class="inline-flex items-center gap-1.5 mt-2 text-xs font-semibold text-orange-300 hover:text-orange-200 transition"
              @click="copiar(kardexStore.credenciales.username)"
            >
              <AppIcon name="clipboard" :size="14" /> Copiar
            </button>
          </div>
          <div class="rounded-xl bg-primary border border-white/10 text-white p-4">
            <p class="text-[11px] uppercase tracking-wider text-orange-300 font-semibold">Contraseña</p>
            <p v-if="kardexStore.credenciales.password" class="font-mono text-lg font-bold mt-1 break-all">
              {{ kardexStore.credenciales.password }}
            </p>
            <p v-else class="text-slate-400 mt-1 text-sm">—</p>
            <button v-if="kardexStore.credenciales.password" class="inline-flex items-center gap-1.5 mt-2 text-xs font-semibold text-orange-300 hover:text-orange-200 transition" @click="copiar(kardexStore.credenciales.password)">
              <AppIcon name="clipboard" :size="14" /> Copiar
            </button>
          </div>
        </div>

        <button class="btn-primary mt-5 w-full sm:w-auto justify-center" @click="irAEtapa(2)">
          <span class="uppercase tracking-wide">Continuar a verificación y asignación</span>
          <AppIcon name="chevron-right" :size="18" />
        </button>
      </div>

      <!-- Etapa 2: verificación documental y remisión al HCC -->
      <template v-if="etapa === 2 && credencialesListas">
        <div class="card p-5">
          <h3 class="font-bold text-white uppercase flex items-center gap-2">
            <AppIcon name="shield" :size="18" class="text-orange-300" />
            2. Verificación documental y remisión al HCC
          </h3>
          <p class="text-sm text-slate-400 mt-1">
            Verifique la documentación digital con la documentación física recepcionada y remita el trámite al HCC. En este paso no se aprueba el perfil.
          </p>

          <div class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 mt-4">
            <AppIcon name="check-circle" :size="18" class="text-emerald-400 shrink-0" />
            <span class="text-sm text-slate-300">
              Cuenta verificada:
              <strong class="text-white">{{ cuentaVerificada }}</strong>
            </span>
            <span class="text-sm text-slate-300">
              Estado:
              <strong class="text-white">{{ kardexStore.tramite ? formatoEstado(kardexStore.tramite.estado_actual) : 'Sin trámite asignado' }}</strong>
            </span>
            <button class="text-xs font-semibold text-orange-300 hover:text-orange-200 transition" @click="verificar">
              Verificar nuevamente
            </button>
          </div>

          <template v-if="kardexStore.tramite">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-orange-500/10 border border-orange-500/30 px-4 py-3 mt-4">
              <div class="flex items-center gap-3">
                <AppIcon name="graduation" :size="20" class="text-orange-300 shrink-0" />
                <div>
                  <p class="text-[11px] uppercase tracking-wider text-orange-200/80 font-semibold">Modalidad elegida por el estudiante</p>
                  <p class="text-sm font-bold text-white">{{ kardexStore.tramite.modalidad?.nombre || '—' }}</p>
                </div>
              </div>
              <EstadoBadge :estado="kardexStore.tramite.estado_actual" />
            </div>

            <div v-if="tituloTramite" class="rounded-xl bg-white/5 border border-white/10 px-4 py-3 mt-4">
              <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Título presentado</p>
              <p class="mt-1 text-sm font-semibold text-white">{{ tituloTramite }}</p>
            </div>

            <div class="mt-5">
              <h4 class="text-sm font-bold text-white uppercase">Documentación digital recepcionada</h4>
              <p class="text-xs text-slate-400 mt-1">Verifique cada documento digital con su respaldo físico recepcionado.</p>

              <div v-if="documentosTramite.length" class="space-y-3 mt-3">
                <label
                  v-for="doc in documentosTramite"
                  :key="doc.id_documento || doc.tipo_documento"
                  class="flex items-start gap-3 rounded-xl bg-white/5 border border-white/10 p-3.5 cursor-pointer select-none"
                >
                  <input
                    v-if="pendienteRemision"
                    v-model="verificados[claveDoc(doc)]"
                    type="checkbox"
                    class="mt-0.5 w-4 h-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
                  />
                  <AppIcon v-else name="check-circle" :size="18" class="mt-0.5 text-emerald-400 shrink-0" />
                  <span class="flex-1 min-w-0">
                    <span class="block text-sm font-semibold text-white">{{ etiquetaDocumento(doc.tipo_documento) }}</span>
                    <span class="block text-xs text-slate-400 truncate mt-0.5">{{ doc.nombre_archivo }}</span>
                  </span>
                  <a
                    v-if="doc.ruta_archivo"
                    :href="assetUrl(doc.ruta_archivo)"
                    target="_blank"
                    rel="noopener"
                    class="btn-ghost !py-1.5 text-xs shrink-0"
                    @click.stop
                  >
                    <AppIcon name="link" :size="13" />
                    Ver PDF
                  </a>
                </label>
              </div>
              <div v-else class="flex items-start gap-3 rounded-xl bg-white/5 border border-white/10 px-4 py-3 mt-3 text-sm text-slate-400">
                <AppIcon name="info" :size="18" class="shrink-0 mt-0.5" />
                El estudiante aún no adjuntó documentación digital.
              </div>
            </div>

            <div v-if="pendienteRemision" class="mt-5">
              <label class="label" for="obs-recepcion">Observaciones (opcional)</label>
              <textarea
                id="obs-recepcion"
                v-model="observacionesRecepcion"
                rows="3"
                class="input resize-none"
                placeholder="Observaciones de la verificación de la documentación recepcionada..."
              ></textarea>
              <button
                class="btn-primary mt-4 w-full sm:w-auto justify-center"
                :disabled="remitiendo || !puedeRemitir"
                @click="remitirAlHcc"
              >
                <AppIcon v-if="remitiendo" name="loader" :size="18" class="animate-spin" />
                <AppIcon v-else name="send" :size="18" />
                <span class="uppercase tracking-wide">
                  {{ esExamen
                    ? 'Verificado: continuar a Examen de Grado'
                    : esExcelencia
                      ? 'Verificado: continuar a Excelencia Académica'
                      : 'Acusar recibido y remitir al HCC' }}
                </span>
              </button>
            </div>

            <div v-else class="flex items-start gap-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 mt-5 text-sm text-emerald-200">
              <AppIcon name="check-circle" :size="18" class="shrink-0 mt-0.5" />
              <span>
                Documentación recepcionada{{ requiereRecepcionDocumentos ? ' y verificada' : ' y remitida al HCC' }}.
                Estado actual: <strong>{{ formatoEstado(kardexStore.tramite.estado_actual) }}</strong>.
              </span>
            </div>
          </template>

          <template v-else>
            <h4 class="text-sm font-bold text-white uppercase mt-5">Modalidad de titulación</h4>
            <p class="text-xs text-slate-400 mt-1">Si el estudiante aún no inició su trámite, puede asignarle la modalidad.</p>
            <div class="grid sm:grid-cols-3 gap-3 mt-3">
              <button
                v-for="m in asignables"
                :key="m.id_modalidad"
                type="button"
                class="relative rounded-xl border-2 p-4 text-left transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                :class="seleccion === m.id_modalidad
                  ? 'border-orange-500 bg-orange-500/10'
                  : 'border-white/10 bg-white/5 hover:border-orange-500/50'"
                @click="seleccion = m.id_modalidad"
              >
                <span
                  class="absolute top-3 right-3 w-4 h-4 rounded-full border-2"
                  :class="seleccion === m.id_modalidad ? 'bg-orange-500 border-orange-500' : 'border-white/20'"
                >
                  <span v-if="seleccion === m.id_modalidad" class="absolute inset-0.5 rounded-full bg-primary" />
                </span>
                <span class="text-sm font-bold text-white">{{ m.nombre }}</span>
                <p class="text-[11px] text-slate-400 mt-1">Modalidad de titulación</p>
              </button>
            </div>

            <button
              class="btn-primary mt-5 w-full sm:w-auto justify-center"
              :disabled="!seleccion || kardexStore.cargando"
              @click="asignar"
            >
              <AppIcon v-if="kardexStore.cargando" name="loader" :size="18" class="animate-spin" />
              <AppIcon v-else name="graduation" :size="18" />
              <span class="uppercase tracking-wide">Asignar modalidad</span>
            </button>
          </template>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useKardexStore, MODALIDADES_ASIGNABLES } from '@/modules/kardex';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { tramitesService } from '@/modules/tramites/services/tramites';
import { configDeTramite, tituloPerfil } from '@/modules/tramites/utils/flujoTitulacion';
import { formatoEstado, etiquetaDocumento as etiquetaDocumentoEstado } from '@/modules/tramites/utils/estados';
import { TIPOS_DOCUMENTO_EXAMEN } from '@/modules/examen/utils/flujoExamen';
import { TIPOS_DOCUMENTO_EXCELENCIA } from '@/modules/excelencia/utils/flujoExcelencia';
import { assetUrl } from '@/core/http/storage';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import EmptyState from '@/ui/EmptyState.vue';

const kardexStore = useKardexStore();
const toastStore = useToastStore();
const route = useRoute();
const router = useRouter();

const identificador = ref('');
const seleccion = ref(null);
const etapa = ref(1);
const remitiendo = ref(false);
const verificados = reactive({});
const observacionesRecepcion = ref('');

const asignables = computed(() =>
  kardexStore.modalidades.filter((m) => MODALIDADES_ASIGNABLES.includes(m.nombre)),
);

const credencialesListas = computed(() =>
  Boolean(kardexStore.credenciales) || Boolean(kardexStore.postulante?.tiene_cuenta),
);

const cuentaVerificada = computed(() =>
  kardexStore.credenciales?.username || kardexStore.postulante?.username || '—',
);

const configTramite = computed(() => configDeTramite(kardexStore.tramite));
const documentosTramite = computed(() => kardexStore.tramite?.documentos || []);
const tituloTramite = computed(() => tituloPerfil(kardexStore.tramite));
const esExamen = computed(() => kardexStore.tramite?.modalidad?.nombre === 'Examen de Grado');
const esExcelencia = computed(() => kardexStore.tramite?.modalidad?.nombre === 'Excelencia Académica');
const requiereRecepcionDocumentos = computed(() => esExamen.value || esExcelencia.value);
const documentosVerificadosPorModalidad = computed(
  () => kardexStore.tramite?.hitos?.recepcion_documentos?.verificado === true,
);
const pendienteRemision = computed(() =>
  kardexStore.tramite?.estado_actual === 'solicitud_presentada'
  && !(requiereRecepcionDocumentos.value && documentosVerificadosPorModalidad.value),
);
const documentacionRemitida = computed(() => requiereRecepcionDocumentos.value
  ? documentosVerificadosPorModalidad.value
  : kardexStore.tramite?.estado_actual === 'pendiente_concejo_universitario',
);
const puedeRemitir = computed(() => {
  if (!pendienteRemision.value || !documentosTramite.value.length) return false;

  if (!requiereRecepcionDocumentos.value) {
    return documentosTramite.value.every((doc) => verificados[claveDoc(doc)]);
  }

  const tipos = esExamen.value ? TIPOS_DOCUMENTO_EXAMEN : TIPOS_DOCUMENTO_EXCELENCIA;

  return tipos.every(({ tipo }) => {
    const doc = documentosTramite.value.find((item) => item.tipo_documento === tipo);
    return doc && verificados[claveDoc(doc)];
  });
});

const esEtapaVerificacion = (valor) => ['modalidad', 'verificacion'].includes(String(valor || ''));

onMounted(async () => {
  await kardexStore.cargarModalidades();
  if (asignables.value.length) seleccion.value = asignables.value[0].id_modalidad;

  const identificadorInicial = String(route.query.identificador || '').trim();
  if (identificadorInicial) {
    identificador.value = identificadorInicial;
    await buscar(false);
  }
  if (esEtapaVerificacion(route.query.etapa)) {
    etapa.value = 2;
    if (identificadorInicial) await verificar();
  }
});

watch(() => route.query.identificador, async (valor) => {
  const nuevo = String(valor || '').trim();
  if (!nuevo || nuevo === identificador.value.trim()) return;
  identificador.value = nuevo;
  await buscar(false);
  etapa.value = esEtapaVerificacion(route.query.etapa) ? 2 : 1;
  if (etapa.value === 2) await verificar();
});

/** Ejecuta la búsqueda por CI o R.U. */
const buscar = async (reiniciarEtapa = true) => {
  if (!identificador.value.trim() || kardexStore.cargando) return;
  const postulante = await kardexStore.buscarPostulante(identificador.value.trim());
  if (postulante) {
    if (asignables.value.length) seleccion.value = asignables.value[0].id_modalidad;
    if (reiniciarEtapa) etapa.value = 1;
  }
};

/** Etapa 1: genera las credenciales del postulante. */
const generar = async () => {
  const resultado = await kardexStore.generarCredenciales(identificador.value.trim());
  if (resultado) {
    toastStore.success(
      resultado.credenciales.generadas
        ? 'Credenciales generadas correctamente.'
        : 'El postulante ya tenía credenciales; se verificó su cuenta.',
    );
  } else if (!kardexStore.simulado) {
    toastStore.error(kardexStore.errorMessage || 'No se pudieron generar las credenciales.');
  }
};

function claveDoc(doc) {
  return String(doc?.id_documento || doc?.tipo_documento || '');
}

function etiquetaDocumento(tipo) {
  const encontrada = configTramite.value?.documentosPerfil?.find((d) => d.tipo === tipo);
  if (encontrada) return encontrada.etiqueta;
  return etiquetaDocumentoEstado(tipo);
}

function resetRecepcion() {
  Object.keys(verificados).forEach((k) => delete verificados[k]);
  observacionesRecepcion.value = '';
}

function sincronizarVerificacion() {
  resetRecepcion();
  const recepcion = kardexStore.tramite?.hitos?.recepcion_documentos;
  if (!recepcion?.verificado) return;

  documentosTramite.value.forEach((doc) => {
    if (recepcion.documentos?.[doc.tipo_documento]?.marcado) {
      verificados[claveDoc(doc)] = true;
    }
  });
}

/** Verifica en el backend la cuenta y el trámite del postulante. */
const verificar = async () => {
  const datos = await kardexStore.refrescar(identificador.value.trim());
  if (datos?.tramite?.estado_actual !== 'solicitud_presentada') resetRecepcion();
  else sincronizarVerificacion();
  if (!datos) toastStore.error('No se pudo verificar la información del postulante.');
};

/** Cambia de etapa; la etapa 2 exige credenciales previas. */
const irAEtapa = async (destino) => {
  if (destino === 2 && !credencialesListas.value) return;
  etapa.value = destino;
  if (destino === 2) await verificar();
};

/** Etapa 2: asigna la modalidad elegida (sin generar credenciales). */
const asignar = async () => {
  if (!seleccion.value || kardexStore.cargando) return;
  const resultado = await kardexStore.asignarModalidad(identificador.value.trim(), seleccion.value);
  if (resultado) {
    toastStore.success('Modalidad asignada correctamente.');
  } else if (!kardexStore.simulado) {
    toastStore.error(kardexStore.errorMessage || 'No se pudo asignar la modalidad.');
  }
};

/** Acusa recibo de la documentación física/digital y continúa según la modalidad. */
const remitirAlHcc = async () => {
  if (!kardexStore.tramite || !puedeRemitir.value || remitiendo.value) return;
  remitiendo.value = true;
  const idTramite = kardexStore.tramite.id_tramite;
  const identificadorPostulante = identificador.value.trim();
  const observaciones = observacionesRecepcion.value.trim();
  try {
    if (requiereRecepcionDocumentos.value) {
      const documentos = {};
      documentosTramite.value.forEach((doc) => {
        documentos[doc.tipo_documento] = Boolean(verificados[claveDoc(doc)]);
      });

      const verificado = esExamen.value
        ? await kardexStore.verificarDocumentosExamen(documentos, observaciones)
        : await kardexStore.verificarDocumentosExcelencia(documentos, observaciones);

      if (!verificado) {
        toastStore.error(
          esExamen.value
            ? kardexStore.errorMessage || 'No se pudo verificar la documentación del examen.'
            : kardexStore.errorMessage || 'No se pudo verificar la documentación de Excelencia Académica.',
        );
        return;
      }

      toastStore.success(
        esExamen.value
          ? 'Documentación verificada. Continúe con el flujo de Examen de Grado.'
          : 'Documentación verificada. Continúe con el flujo de Excelencia Académica.',
      );
      resetRecepcion();
      await router.push({
        path: esExamen.value ? '/kardex/examen' : '/kardex/excelencia-academica',
        query: { identificador: identificadorPostulante, tramite: String(idTramite) },
      });
      return;
    }

    await tramitesService.revisar(
      idTramite,
      'aprobar',
      observaciones || 'Documentación digital verificada con la documentación física recepcionada. Remitido al HCC.',
    );
    toastStore.success('Documentación acusada como recibida y remitida al HCC.');
    resetRecepcion();
    await verificar();
  } catch (error) {
    toastStore.error(error?.response?.data?.message || 'No se pudo remitir la documentación al HCC.');
  } finally {
    remitiendo.value = false;
  }
};

/** Copia un valor al portapapeles. */
const copiar = async (valor) => {
  try {
    await navigator.clipboard.writeText(valor);
    toastStore.success('Copiado al portapapeles.');
  } catch {
    toastStore.error('No se pudo copiar.');
  }
};

/** Limpia la búsqueda para empezar de nuevo. */
const reiniciar = () => {
  kardexStore.limpiar();
  identificador.value = '';
  etapa.value = 1;
  resetRecepcion();
  seleccion.value = asignables.value[0]?.id_modalidad || null;
};
</script>
