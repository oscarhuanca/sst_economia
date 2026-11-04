<template>
  <AppShell title="Panel de Administración" subtitle="Gestión de usuarios y roles del sistema">
    <div id="seccion-estadisticas" class="scroll-mt-28">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <StatCard label="Total de Usuarios" :value="totales.total" icon="users" tone="amber" />
        <StatCard label="Estudiantes" :value="totales.estudiantes" icon="graduation" tone="rose" />
        <StatCard label="Docentes" :value="totales.docentes" icon="user-check" tone="orange" />
        <StatCard label="Personal Académico" :value="totales.personal_academico" icon="shield" tone="amber" />
      </div>

      <EstadisticasModalidades />

      <div class="mt-6 grid sm:grid-cols-2 gap-4">
        <button class="card group flex items-center gap-4 p-5 text-left hover:ring-2 hover:ring-orange-500/40 transition-all duration-200" @click="abrirCrearEstudiante">
          <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-orange-300 shrink-0 group-hover:bg-orange-500/20 transition-colors">
            <AppIcon name="graduation" :size="24" />
          </div>
          <div class="flex-1">
            <p class="font-extrabold text-white group-hover:text-orange-300 transition-colors">CREAR ESTUDIANTE</p>
            <p class="text-xs text-slate-400 mt-0.5">Registra el perfil aislado del estudiante (CI, registro universitario y nacimiento).</p>
          </div>
          <AppIcon name="chevron-right" :size="18" class="text-slate-500 group-hover:text-orange-400 transition-colors" />
        </button>

        <button class="card group flex items-center gap-4 p-5 text-left hover:ring-2 hover:ring-orange-400/40 transition-all duration-200" @click="abrirAltaUsuario">
          <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-orange-300 shrink-0 group-hover:bg-orange-500/20 transition-colors">
            <AppIcon name="user-plus" :size="24" />
          </div>
          <div class="flex-1">
            <p class="font-extrabold text-white group-hover:text-orange-300 transition-colors">CREAR USUARIO</p>
            <p class="text-xs text-slate-400 mt-0.5">Genera la cuenta de acceso de un estudiante ya registrado (usuario y contraseña automáticos).</p>
          </div>
          <AppIcon name="chevron-right" :size="18" class="text-slate-500 group-hover:text-orange-400 transition-colors" />
        </button>
      </div>
    </div>

    <div id="seccion-tabla" class="scroll-mt-28 card overflow-hidden mt-6">
      <div class="flex flex-wrap items-center gap-3 p-5 border-b border-white/10">
        <div>
          <h2 class="text-lg font-bold text-white">Matriz de Actores</h2>
          <p class="text-sm text-slate-400">Usuarios registrados y habilitados en la plataforma.</p>
        </div>
        <div class="ml-auto w-full md:w-auto flex flex-wrap gap-3 items-center">
          <div class="relative flex-1 md:flex-none md:min-w-52">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
              <AppIcon name="search" :size="17" />
            </span>
            <input v-model="buscar" class="input pl-9 md:w-52" placeholder="Buscar usuario..." />
          </div>
          <select v-model="filtroRol" class="input md:w-44">
            <option value="">Todos los roles</option>
            <option v-for="rol in roles" :key="rol" :value="rol">{{ rolLabel(rol) }}</option>
          </select>
          <button class="btn-ghost px-4" title="Alta manual para roles de gestión (admin, docente, etc.)" @click="showModalCrear = true">
            <AppIcon name="plus" :size="16" />
            Alta Manual
          </button>
        </div>
      </div>

      <div v-if="cargando" class="flex items-center justify-center gap-2 py-16 text-slate-400">
        <AppIcon name="loader" :size="20" class="animate-spin" />
        Cargando usuarios...
      </div>

      <template v-else>
        <div v-if="usuariosFiltrados.length === 0" class="p-6">
          <EmptyState icon="users" title="Sin resultados" message="No se encontraron usuarios con los criterios actuales.">
            <button class="btn-ghost" @click="buscar = ''; filtroRol = ''">Limpiar filtros</button>
          </EmptyState>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[640px] text-left">
            <thead class="bg-white/5 border-b border-white/10">
              <tr>
                <th class="th">Usuario</th>
                <th class="th">Contacto</th>
                <th class="th">Rol</th>
                <th class="th text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr
                v-for="user in usuariosFiltrados"
                :key="user.id_usuario"
                class="hover:bg-white/5 transition cursor-pointer"
                :class="usuarioSeleccionado?.id_usuario === user.id_usuario ? 'bg-orange-500/10 ring-1 ring-orange-500/30' : ''"
                @click="seleccionarUsuario(user)"
              >
                <td class="td">
                  <div class="flex items-center gap-3">
                    <Avatar :nombres="user.nombres || ''" :apellidos="user.apellidos || ''" :size="10" />
                    <div>
                      <p class="font-semibold text-white">{{ user.nombres }} {{ user.apellidos }}</p>
                      <p class="text-xs text-slate-400">CI: {{ user.ci }} · @{{ user.username }}</p>
                    </div>
                  </div>
                </td>
                <td class="td">
                  <span class="inline-flex items-center gap-1.5 text-slate-300">
                    <AppIcon name="mail" :size="14" class="text-slate-400" />
                    {{ user.email || '—' }}
                  </span>
                  <span v-if="user.telefono" class="text-xs text-slate-400 block mt-0.5">{{ user.telefono }}</span>
                </td>
                <td class="td">
                  <RoleBadge :rol="user.rol" />
                </td>
                <td class="td text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-300 hover:text-orange-200 transition px-2 py-1 rounded-lg hover:bg-orange-500/10"
                      @click.stop="editarUsuario(user)"
                    >
                      <AppIcon name="pencil" :size="14" />
                      Editar
                    </button>
                    <button class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-400 hover:text-red-300 transition px-2 py-1 rounded-lg hover:bg-red-500/10" @click.stop="eliminarUsuario(user)">
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

    <UiModal v-model="showModalCrearEstudiante" title="CREAR ESTUDIANTE" max-width="520px">
      <form @submit.prevent="crearEstudiante" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">CI</label>
            <input v-model="estudianteForm.ci" class="input" placeholder="1234567" required>
          </div>
          <div>
            <label class="label">Registro Universitario</label>
            <input v-model="estudianteForm.registro_universitario" class="input" placeholder="2020-0001" required>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombres</label>
            <input v-model="estudianteForm.nombres" class="input" placeholder="Nombre(s)" required>
          </div>
          <div>
            <label class="label">Apellidos</label>
            <input v-model="estudianteForm.apellidos" class="input" placeholder="Apellido(s)" required>
          </div>
        </div>
        <div>
          <label class="label">Fecha de Nacimiento</label>
          <p class="text-xs text-slate-400 -mt-1 mb-1">Se usa para generar la contraseña inicial (dd-mm-aa).</p>
          <input v-model="estudianteForm.fecha_nacimiento" type="date" class="input" required :max="hoy">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Email</label>
            <input v-model="estudianteForm.email" type="email" class="input" placeholder="correo@upea.bo">
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input v-model="estudianteForm.telefono" class="input" placeholder="59170000000">
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="showModalCrearEstudiante = false">Cancelar</button>
          <button type="submit" class="btn-primary px-4 py-2.5" :disabled="creando">
            <AppIcon v-if="creando" name="loader" :size="16" class="animate-spin" />
            <AppIcon v-else name="plus" :size="16" />
            Crear Estudiante
          </button>
        </div>
      </form>
    </UiModal>

    <UiModal v-model="showModalAltaUsuario" title="CREAR USUARIO" max-width="560px">
      <template v-if="alta.resultado">
        <div class="text-center py-2">
          <div class="mx-auto w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
            <AppIcon name="check-circle" :size="28" />
          </div>
          <h3 class="mt-4 text-lg font-extrabold text-white">Cuenta creada correctamente</h3>
          <p class="mt-1 text-sm text-slate-400">Entrega estas credenciales al estudiante. Solo se muestran esta vez.</p>

          <div class="mt-6 space-y-3 text-left">
            <div class="rounded-xl bg-white/5 border border-white/10 p-4 flex items-center justify-between gap-3">
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Usuario</p>
                <p class="font-mono font-bold text-white">{{ alta.resultado.username }}</p>
              </div>
              <button class="btn-ghost px-3 py-1.5 text-xs" @click="copiar(alta.resultado.username)">
                <AppIcon name="clipboard" :size="14" /> Copiar
              </button>
            </div>
            <div class="rounded-xl bg-white/5 border border-white/10 p-4 flex items-center justify-between gap-3">
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Contraseña temporal</p>
                <p class="font-mono font-bold text-white">{{ alta.resultado.password }}</p>
              </div>
              <button class="btn-ghost px-3 py-1.5 text-xs" @click="copiar(alta.resultado.password)">
                <AppIcon name="clipboard" :size="14" /> Copiar
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button class="btn-primary px-6 py-2.5" @click="cerrarAlta">
            <AppIcon name="check" :size="16" />
            Confirmar
          </button>
        </div>
      </template>

      <template v-else>
        <p class="text-sm text-slate-400 mb-4">
          Busca al estudiante por CI, nombre o registro universitario. Solo aparecen
          estudiantes <strong class="text-white">sin cuenta de acceso</strong>.
        </p>

        <form class="flex gap-2" @submit.prevent="buscarCandidatos">
          <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
              <AppIcon name="search" :size="17" />
            </span>
            <input v-model="alta.q" class="input pl-9" placeholder="CI, nombre o registro universitario..." />
          </div>
          <button type="submit" class="btn-ghost px-4" :disabled="alta.cargando">
            <AppIcon v-if="alta.cargando" name="loader" :size="16" class="animate-spin" />
            <AppIcon v-else name="search" :size="16" />
            Buscar
          </button>
        </form>

        <div class="mt-4 max-h-64 overflow-y-auto space-y-2 pr-1">
          <div v-if="alta.cargando" class="text-center py-8 text-slate-400">
            <AppIcon name="loader" :size="20" class="animate-spin inline" />
          </div>

          <div v-else-if="alta.candidatos.length === 0" class="text-center py-8 text-slate-400 text-sm">
            {{ alta.buscado ? 'Sin estudiantes pendientes de cuenta.' : 'Ingresa un criterio de búsqueda.' }}
          </div>

          <button
            v-for="c in alta.candidatos"
            :key="c.id_estudiante"
            type="button"
            class="w-full text-left rounded-xl p-3 border transition-all duration-200"
            :class="alta.seleccionado?.id_estudiante === c.id_estudiante ? 'border-orange-500/40 bg-orange-500/10' : 'border-white/10 hover:border-orange-500/30 hover:bg-white/5'"
            @click="alta.seleccionado = c"
          >
            <p class="font-semibold text-white text-sm">{{ c.nombres }} {{ c.apellidos }}</p>
            <p class="text-xs text-slate-400 mt-0.5">CI: {{ c.ci }} · R.U.: {{ c.registro_universitario }}</p>
          </button>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="showModalAltaUsuario = false">Cancelar</button>
          <button type="button" class="btn-warm px-4 py-2.5" :disabled="!alta.seleccionado || creandoCredenciales" @click="generarCredenciales">
            <AppIcon v-if="creandoCredenciales" name="loader" :size="16" class="animate-spin" />
            <AppIcon v-else name="user-plus" :size="16" />
            Validar y Crear Cuenta
          </button>
        </div>
      </template>
    </UiModal>

    <UiModal v-model="showModalCrear" title="Alta Manual de Usuario" max-width="520px">
      <form @submit.prevent="crearUsuario" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">CI</label>
            <input v-model="nuevoUsuario.ci" class="input" placeholder="1234567" required>
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input v-model="nuevoUsuario.telefono" class="input" placeholder="59170000000">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombres</label>
            <input v-model="nuevoUsuario.nombres" class="input" placeholder="Nombre(s)" required>
          </div>
          <div>
            <label class="label">Apellidos</label>
            <input v-model="nuevoUsuario.apellidos" class="input" placeholder="Apellido(s)" required>
          </div>
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="nuevoUsuario.email" type="email" class="input" placeholder="correo@upea.bo">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Username</label>
            <input v-model="nuevoUsuario.username" class="input" placeholder="p. ej. juan_1234567" required>
          </div>
          <div>
            <label class="label">Rol</label>
            <select v-model="nuevoUsuario.rol" class="input" required>
              <option v-for="rol in roles" :key="rol" :value="rol">{{ rolLabel(rol) }}</option>
            </select>
          </div>
        </div>
        <div>
          <label class="label">Contraseña</label>
          <input v-model="nuevoUsuario.password" type="password" class="input" placeholder="Mín. 6 caracteres" required>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="showModalCrear = false">Cancelar</button>
          <button type="submit" class="btn-primary px-4 py-2.5">
            <AppIcon name="plus" :size="16" />
            Dar de Alta
          </button>
        </div>
      </form>
    </UiModal>

    <UiModal v-model="showModalActualizar" title="Actualizar Usuario" max-width="560px">
      <form @submit.prevent="actualizarUsuario" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">CI</label>
            <input v-model="editarForm.ci" class="input" placeholder="1234567" required>
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input v-model="editarForm.telefono" class="input" placeholder="59170000000">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombres</label>
            <input v-model="editarForm.nombres" class="input" placeholder="Nombre(s)" required>
          </div>
          <div>
            <label class="label">Apellidos</label>
            <input v-model="editarForm.apellidos" class="input" placeholder="Apellido(s)" required>
          </div>
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="editarForm.email" type="email" class="input" placeholder="correo@upea.bo">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Username</label>
            <input v-model="editarForm.username" class="input" placeholder="p. ej. juan_1234567" required>
          </div>
          <div>
            <label class="label">Contraseña</label>
            <input v-model="editarForm.password" type="password" class="input" placeholder="Nueva contraseña" autocomplete="new-password">
          </div>
          <p class="sm:col-span-2 text-xs text-slate-400">Déjala vacía para mantener la contraseña actual o escríbela para reestablecerla.</p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="showModalActualizar = false">Cancelar</button>
          <button type="submit" class="btn-warm px-4 py-2.5">
            <AppIcon name="check" :size="16" />
            Actualizar
          </button>
        </div>
      </form>
    </UiModal>
  </AppShell>
</template>

<script setup>
// Vista de administración de usuarios.
// Expone los dos flujos del rediseño: "CREAR ESTUDIANTE" (perfil aislado) y
// "CREAR USUARIO" (alta automática de credenciales para un estudiante ya
// registrado). La matriz de actores permite buscar, editar (username, rol,
// datos de contacto) y dar de baja usuarios. La "Alta Manual" cubre roles de
// gestión que no son estudiantes.
import { ref, computed, onMounted } from 'vue';
import { usuariosService } from '../services/usuarios';
import { estudianteService } from '@/modules/estudiantes/services/estudiantes';
import { rolLabel, ROLE_LABELS } from '@/core/roles';
import { useToastStore } from '@/core/stores/toast';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';
import RoleBadge from '@/ui/RoleBadge.vue';
import StatCard from '@/ui/StatCard.vue';
import { EstadisticasModalidades } from '@/modules/tramites';
import UiModal from '@/ui/UiModal.vue';
import EmptyState from '@/ui/EmptyState.vue';

const toastStore = useToastStore();

// Estado de la matriz de usuarios y de la UI.
const users = ref([]);
const cargando = ref(false);
const totales = ref({ total: 0, estudiantes: 0, docentes: 0, personal_academico: 0 });
const showModalCrearEstudiante = ref(false);
const showModalAltaUsuario = ref(false);
const showModalCrear = ref(false);
const showModalActualizar = ref(false);
const buscar = ref('');
const filtroRol = ref('');
const roles = Object.keys(ROLE_LABELS);
const usuarioSeleccionado = ref(null);

// Botón CREAR ESTUDIANTE: perfil aislado del estudiante.
const estudianteForm = ref({
  ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '',
  email: '', telefono: '',
});
const creando = ref(false);
const hoy = new Date().toISOString().slice(0, 10);

// Botón CREAR USUARIO: alta automática de credenciales.
const alta = ref({
  q: '', buscado: false, cargando: false,
  candidatos: [], seleccionado: null, resultado: null,
});
const creandoCredenciales = ref(false);

// Alta manual para roles de gestión.
const nuevoUsuario = ref({ ci: '', nombres: '', apellidos: '', email: '', telefono: '', username: '', rol: 'docente', password: '' });

// Edición de un usuario existente (sin bloque de perfil académico: el
// estudiante auto-gestiona sus campos y su perfil se edita desde CREAR ESTUDIANTE).
const editarForm = ref({
  id_usuario: null, ci: '', nombres: '', apellidos: '', email: '', telefono: '',
  username: '', rol: 'estudiante', password: '',
});

const editarFormLimpiar = () => {
  editarForm.value = {
    id_usuario: null, ci: '', nombres: '', apellidos: '', email: '', telefono: '',
    username: '', rol: 'estudiante', password: '',
  };
};

onMounted(() => {
  fetchUsers();
});

const usuariosFiltrados = computed(() => {
  const q = buscar.value.toLowerCase().trim();
  return users.value.filter((u) => {
    const matchRol = !filtroRol.value || u.rol === filtroRol.value;
    const matchQ = !q
      || `${u.nombres} ${u.apellidos}`.toLowerCase().includes(q)
      || (u.email || '').toLowerCase().includes(q)
      || (u.username || '').toLowerCase().includes(q)
      || `${u.ci}`.includes(q);
    return matchRol && matchQ;
  });
});

const fetchEstadisticas = async () => {
  try {
    const { data } = await usuariosService.estadisticas();
    totales.value = {
      total: data.total ?? 0,
      estudiantes: data.estudiantes ?? 0,
      docentes: data.docentes ?? 0,
      personal_academico: data.personal_academico ?? 0,
    };
  } catch (error) {
    console.error('Error al cargar las estadísticas de usuarios:', error);
  }
};

const fetchUsers = async () => {
  cargando.value = true;
  try {
    const { data } = await usuariosService.index();
    users.value = data.data;
  } catch (error) {
    console.error('Error al cargar la matriz de usuarios:', error);
  } finally {
    cargando.value = false;
  }
  await fetchEstadisticas();
};

const seleccionarUsuario = (user) => {
  usuarioSeleccionado.value = user;
};

// ---------- CREAR ESTUDIANTE ----------
const abrirCrearEstudiante = () => {
  estudianteForm.value = { ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '', email: '', telefono: '' };
  showModalCrearEstudiante.value = true;
};

const crearEstudiante = async () => {
  creando.value = true;
  try {
    const { data } = await estudianteService.crear(estudianteForm.value);
    showModalCrearEstudiante.value = false;
    toastStore.success(`Estudiante ${data.nombres} ${data.apellidos} registrado (R.U. ${data.registro_universitario}).`);
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'Verifica los datos'));
  } finally {
    creando.value = false;
  }
};

// ---------- CREAR USUARIO (alta automática de credenciales) ----------
const abrirAltaUsuario = () => {
  alta.value = {
    q: '', buscado: false, cargando: false,
    candidatos: [], seleccionado: null, resultado: null,
  };
  showModalAltaUsuario.value = true;
  buscarCandidatos();
};

const buscarCandidatos = async () => {
  alta.value.cargando = true;
  alta.value.seleccionado = null;
  try {
    const { data } = await estudianteService.sinUsuario({
      per_page: 20,
      q: alta.value.q || undefined,
    });
    alta.value.candidatos = data.data;
    alta.value.buscado = true;
  } catch (error) {
    toastStore.error('No se pudieron cargar los estudiantes.');
  } finally {
    alta.value.cargando = false;
  }
};

const generarCredenciales = async () => {
  if (!alta.value.seleccionado) return;
  creandoCredenciales.value = true;
  try {
    const { data } = await usuariosService.altaEstudiante(alta.value.seleccionado.ci);
    alta.value.resultado = data;
    await fetchUsers();
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo crear la cuenta'));
  } finally {
    creandoCredenciales.value = false;
  }
};

const cerrarAlta = () => {
  showModalAltaUsuario.value = false;
  alta.value.resultado = null;
};

const copiar = async (texto) => {
  try {
    await navigator.clipboard.writeText(texto);
    toastStore.success('Copiado al portapapeles.');
  } catch {
    toastStore.error('No se pudo copiar.');
  }
};

// ---------- Alta manual (roles de gestión) ----------
const crearUsuario = async () => {
  try {
    await usuariosService.crear(nuevoUsuario.value);
    showModalCrear.value = false;
    toastStore.success(`Usuario ${nuevoUsuario.value.nombres} ${nuevoUsuario.value.apellidos} dado de alta correctamente.`);
    nuevoUsuario.value = { ci: '', nombres: '', apellidos: '', email: '', telefono: '', username: '', rol: 'docente', password: '' };
    await fetchUsers();
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'Verifica los datos'));
  }
};

// ---------- Edición ----------
const editarUsuario = (user) => {
  usuarioSeleccionado.value = user;
  editarFormLimpiar();
  editarForm.value = {
    id_usuario: user.id_usuario,
    ci: user.ci,
    nombres: user.nombres,
    apellidos: user.apellidos,
    email: user.email || '',
    telefono: user.telefono || '',
    username: user.username || '',
    rol: user.rol,
    password: '',
  };
  showModalActualizar.value = true;
};

const actualizarUsuario = async () => {
  try {
    const payload = {
      ci: editarForm.value.ci,
      nombres: editarForm.value.nombres,
      apellidos: editarForm.value.apellidos,
      email: editarForm.value.email || null,
      telefono: editarForm.value.telefono,
      username: editarForm.value.username,
      rol: editarForm.value.rol,
    };
    if (editarForm.value.password) payload.password = editarForm.value.password;

    await usuariosService.actualizar(editarForm.value.id_usuario, payload);
    showModalActualizar.value = false;
    toastStore.success(`Datos de ${editarForm.value.nombres} ${editarForm.value.apellidos} actualizados correctamente.`);
    await fetchUsers();
    usuarioSeleccionado.value = null;
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo actualizar'));
  }
};

const eliminarUsuario = async (user) => {
  if (!confirm(`¿Dar de baja a ${user.nombres} ${user.apellidos}? Esta acción deshabilita su acceso.`)) return;
  try {
    await usuariosService.eliminar(user.id_usuario);
    await fetchUsers();
  } catch (error) {
    toastStore.error('No se pudo dar de baja al usuario.');
  }
};
</script>