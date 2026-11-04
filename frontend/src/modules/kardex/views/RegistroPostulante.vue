<template>
  <div class="space-y-6">
    <!-- Encabezado del módulo -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-orange text-white flex items-center justify-center shadow-lg shadow-orange-900/30">
        <AppIcon name="user-plus" :size="24" />
      </div>
      <div>
        <h2 class="text-2xl font-bold text-white uppercase">Registro de Postulante</h2>
        <p class="text-sm text-slate-400 mt-0.5">Busque por CI/RU y asigne la modalidad de titulación.</p>
      </div>
    </div>

    <!-- Búsqueda por CI/RU -->
    <form class="card p-4 sm:p-5" @submit.prevent="buscar">
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
            placeholder=""
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
      message="Ingrese el CI o el registro universitario del postulante para ver su perfil y asignarle una modalidad."
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
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold"></p>
            <p class="text-slate-200 font-medium mt-0.5">{{ kardexStore.postulante.promedio_global ?? '—' }}</p>
          </div>
        </div>
      </div>

      <!-- Asignación de modalidad -->
      <div class="card p-5">
        <h3 class="font-bold text-white uppercase flex items-center gap-2">
          <AppIcon name="graduation" :size="18" class="text-orange-300" />
          Asignar modalidad de titulación
        </h3>
        <p class="text-sm text-slate-400 mt-1">
          Al asignar la modalidad se generarán automáticamente las credenciales de acceso del postulante.
        </p>

        <div class="grid sm:grid-cols-3 gap-3 mt-4">
          <button
            v-for="m in asignables"
            :key="m.id_modalidad"
            type="button"
            class="relative rounded-xl border-2 p-4 text-left transition cursor-pointer"
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
          <AppIcon v-else name="zap" :size="18" />
          <span class="uppercase tracking-wide">Asignar modalidad y generar credenciales</span>
        </button>
      </div>

      <!-- Credenciales generadas -->
      <div v-if="kardexStore.credenciales" class="card p-5 border-emerald-500/30">
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

        <div v-if="kardexStore.tramite" class="mt-4 pt-4 border-t border-white/10 text-sm flex flex-wrap items-center gap-x-4 gap-y-2">
          <span class="text-slate-400">Trámite iniciado:</span>
          <EstadoBadge :estado="kardexStore.tramite.estado_actual" />
          <span class="text-slate-400">
            Modalidad: <strong class="text-white">{{ kardexStore.tramite.modalidad?.nombre }}</strong>
          </span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
// Módulo A del Dashboard de KARDEX: Registro de Postulante.
//
// Busca al postulante por CI o registro universitario, muestra su perfil,
// permite asignarle una modalidad (Tesis de Grado, Trabajo Dirigido o Examen
// de Grado) y muestra las credenciales de acceso generadas automáticamente
// (usuario [PrimerNombre]_[CI] y contraseña [DD-MM-AAAA]).
import { ref, computed, onMounted } from 'vue';
import { useKardexStore, MODALIDADES_ASIGNABLES } from '@/modules/kardex';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import EmptyState from '@/ui/EmptyState.vue';

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const identificador = ref('');
const seleccion = ref(null);

// Modalidades que el KARDEX puede asignar (Tesis, Trabajo Dirigido, Examen).
const asignables = computed(() =>
  kardexStore.modalidades.filter((m) => MODALIDADES_ASIGNABLES.includes(m.nombre)),
);

onMounted(() => {
  kardexStore.cargarModalidades().then(() => {
    if (asignables.value.length) seleccion.value = asignables.value[0].id_modalidad;
  });
});

/** Ejecuta la búsqueda por CI o R.U. */
const buscar = async () => {
  if (!identificador.value.trim() || kardexStore.cargando) return;
  const postulante = await kardexStore.buscarPostulante(identificador.value.trim());
  if (postulante && asignables.value.length) {
    seleccion.value = asignables.value[0].id_modalidad;
  }
};

/** Asigna la modalidad elegida y genera las credenciales. */
const asignar = async () => {
  if (!seleccion.value || kardexStore.cargando) return;
  const resultado = await kardexStore.asignarModalidad(identificador.value.trim(), seleccion.value);
  if (resultado) {
    toastStore.success(
      resultado.credenciales.generadas
        ? 'Modalidad asignada y credenciales generadas correctamente.'
        : 'Modalidad asignada. El postulante ya tenía cuenta de acceso.',
    );
  } else if (!kardexStore.simulado) {
    toastStore.error(kardexStore.errorMessage || 'No se pudo asignar la modalidad.');
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
  seleccion.value = asignables.value[0]?.id_modalidad || null;
};
</script>