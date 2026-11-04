<template>
  <AppShell title="Registro de Docentes" subtitle="Docentes de la carrera y materias que imparten">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard label="Total Docentes" :value="docentes.length" icon="user-check" tone="amber" />
      <StatCard label="Con Materia Asignada" :value="conMateria" icon="book" tone="rose" />
      <StatCard label="Primer Docente" :value="docentes[0] ? docentes[0].nombre : '—'" icon="clock" tone="orange" />
      <StatCard label="Registrados" :value="docentes.length" icon="users" tone="amber" />
    </div>

    <div class="card overflow-hidden">
      <div class="flex flex-wrap items-center gap-3 p-5 border-b border-white/10">
        <div>
          <h2 class="text-lg font-bold text-white">Docentes Registrados</h2>
          <p class="text-sm text-slate-400">Registro académico independiente de las cuentas de acceso.</p>
        </div>
        <div class="ml-auto w-full md:w-auto flex flex-wrap gap-3 items-center">
          <div class="relative flex-1 md:flex-none md:min-w-52">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
              <AppIcon name="search" :size="17" />
            </span>
            <input v-model="buscar" class="input pl-9 md:w-52" placeholder="Buscar docente..." />
          </div>
          <button class="btn-primary" @click="abrirCrear">
            <AppIcon name="plus" :size="16" />
            Nuevo Docente
          </button>
        </div>
      </div>

      <div v-if="cargando" class="flex items-center justify-center gap-2 py-16 text-slate-400">
        <AppIcon name="loader" :size="20" class="animate-spin" />
        Cargando docentes...
      </div>

      <template v-else>
        <div v-if="docentesFiltrados.length === 0" class="p-6">
          <EmptyState icon="user-check" title="Sin resultados" message="No se encontraron docentes con los criterios actuales.">
            <button class="btn-ghost" @click="buscar = ''">Limpiar filtros</button>
          </EmptyState>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[640px] text-left">
            <thead class="bg-white/5 border-b border-white/10">
              <tr>
                <th class="th">Docente</th>
                <th class="th">CI</th>
                <th class="th">Contacto</th>
                <th class="th">Materia</th>
                <th class="th text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
              <tr v-for="doc in docentesFiltrados" :key="doc.id_docente" class="hover:bg-orange-500/5 transition">
                <td class="td">
                  <div class="flex items-center gap-3">
                    <Avatar :nombres="doc.nombre || ''" :apellidos="doc.apellidos || ''" :size="10" />
                    <p class="font-semibold text-white">{{ doc.nombre }} {{ doc.apellidos }}</p>
                  </div>
                </td>
                <td class="td text-slate-300">{{ doc.ci }}</td>
                <td class="td">
                  <span class="inline-flex items-center gap-1.5 text-slate-300">
                    <AppIcon name="mail" :size="14" class="text-slate-400" />
                    {{ doc.email || '—' }}
                  </span>
                  <span v-if="doc.telefono" class="text-xs text-slate-400 block mt-0.5">{{ doc.telefono }}</span>
                </td>
                <td class="td">
                  <span v-if="doc.materia" class="inline-flex px-2.5 py-1 rounded-full bg-orange-500/15 border border-orange-500/30 text-orange-300 text-xs font-semibold">
                    {{ doc.materia }}
                  </span>
                  <span v-else class="text-slate-500">—</span>
                </td>
                <td class="td text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-300 hover:text-orange-200 transition px-2 py-1 rounded-lg hover:bg-orange-500/10"
                      @click="abrirEditar(doc)"
                    >
                      <AppIcon name="pencil" :size="14" />
                      Editar
                    </button>
                    <button class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-400 hover:text-red-300 transition px-2 py-1 rounded-lg hover:bg-red-900/50" @click="eliminar(doc)">
                      <AppIcon name="trash" :size="15" />
                      Eliminar
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <UiModal v-model="showModal" :title="editando ? 'Editar Docente' : 'Nuevo Docente'" max-width="520px">
      <form @submit.prevent="guardar" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombre</label>
            <input v-model="form.nombre" class="input" placeholder="Nombre(s)" required>
          </div>
          <div>
            <label class="label">Apellidos</label>
            <input v-model="form.apellidos" class="input" placeholder="Apellido(s)" required>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">CI</label>
            <input v-model="form.ci" class="input" placeholder="1234567" required>
          </div>
          <div>
            <label class="label">Materia <span class="text-slate-400 font-normal">(opcional)</span></label>
            <input v-model="form.materia" class="input" placeholder="Ej: Matemática III">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Email <span class="text-slate-400 font-normal">(opcional)</span></label>
            <input v-model="form.email" type="email" class="input" placeholder="correo@upea.bo">
          </div>
          <div>
            <label class="label">Teléfono <span class="text-slate-400 font-normal">(opcional)</span></label>
            <input v-model="form.telefono" class="input" placeholder="59170000000">
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="showModal = false">Cancelar</button>
          <button type="submit" class="btn-primary px-4 py-2.5" :disabled="guardando">
            <AppIcon v-if="guardando" name="loader" :size="16" class="animate-spin" />
            <AppIcon v-else name="check" :size="16" />
            {{ editando ? 'Actualizar' : 'Crear Docente' }}
          </button>
        </div>
      </form>
    </UiModal>
  </AppShell>
</template>

<script setup>
// Vista de administración del Registro de Docentes.
// CRUD sobre la tabla `docentes` (nombre, apellidos, CI, teléfono, email y
// materia opcional). Es un registro académico independiente de las cuentas de
// acceso (`users`).
import { ref, computed, onMounted } from 'vue';
import { docentesService } from '../services/docentes';
import { useToastStore } from '@/core/stores/toast';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';
import StatCard from '@/ui/StatCard.vue';
import UiModal from '@/ui/UiModal.vue';
import EmptyState from '@/ui/EmptyState.vue';

const toastStore = useToastStore();

const docentes = ref([]);
const cargando = ref(false);
const buscar = ref('');
const showModal = ref(false);
const editando = ref(false);
const guardando = ref(false);

const form = ref({ id_docente: null, nombre: '', apellidos: '', ci: '', telefono: '', email: '', materia: '' });
const formLimpiar = () => {
  form.value = { id_docente: null, nombre: '', apellidos: '', ci: '', telefono: '', email: '', materia: '' };
};

onMounted(fetchDocentes);

const docentesFiltrados = computed(() => {
  const q = buscar.value.toLowerCase().trim();
  if (!q) return docentes.value;
  return docentes.value.filter((d) =>
    `${d.nombre} ${d.apellidos}`.toLowerCase().includes(q)
    || `${d.ci}`.includes(q)
    || (d.materia || '').toLowerCase().includes(q)
  );
});

const conMateria = computed(() => docentes.value.filter((d) => d.materia).length);

async function fetchDocentes() {
  cargando.value = true;
  try {
    const { data } = await docentesService.index({ per_page: 100 });
    docentes.value = data.data;
  } catch (error) {
    toastStore.error('No se pudieron cargar los docentes.');
  } finally {
    cargando.value = false;
  }
}

const abrirCrear = () => {
  editando.value = false;
  formLimpiar();
  showModal.value = true;
};

const abrirEditar = (doc) => {
  editando.value = true;
  form.value = {
    id_docente: doc.id_docente,
    nombre: doc.nombre,
    apellidos: doc.apellidos,
    ci: doc.ci,
    telefono: doc.telefono || '',
    email: doc.email || '',
    materia: doc.materia || '',
  };
  showModal.value = true;
};

async function guardar() {
  guardando.value = true;
  try {
    const payload = {
      nombre: form.value.nombre,
      apellidos: form.value.apellidos,
      ci: form.value.ci,
      telefono: form.value.telefono || null,
      email: form.value.email || null,
      materia: form.value.materia || null,
    };
    if (editando.value) {
      await docentesService.actualizar(form.value.id_docente, payload);
      toastStore.success(`Docente ${form.value.nombre} ${form.value.apellidos} actualizado.`);
    } else {
      await docentesService.crear(payload);
      toastStore.success(`Docente ${form.value.nombre} ${form.value.apellidos} registrado.`);
    }
    showModal.value = false;
    await fetchDocentes();
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'Verifica los datos'));
  } finally {
    guardando.value = false;
  }
}

async function eliminar(doc) {
  if (!confirm(`¿Eliminar al docente ${doc.nombre} ${doc.apellidos}?`)) return;
  try {
    await docentesService.eliminar(doc.id_docente);
    toastStore.success('Docente eliminado del registro.');
    await fetchDocentes();
  } catch (error) {
    toastStore.error('No se pudo eliminar el docente.');
  }
}
</script>