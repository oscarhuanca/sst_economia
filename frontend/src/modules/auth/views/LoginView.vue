<template>
  <div class="min-h-screen relative flex items-center justify-center px-4 py-10 overflow-hidden bg-gradient-to-br from-primary via-primary to-primary">
    <div class="absolute top-[-20%] right-[-10%] w-[500px] h-[500px] bg-orange-600 rounded-full blur-[120px] opacity-30 pointer-events-none"></div>
    <div class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] bg-red-600 rounded-full blur-[120px] opacity-20 pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.06] pointer-events-none" style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 26px 26px;"></div>

    <div class="relative z-10 w-full max-w-md">
      <div class="flex flex-col items-center text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-orange flex items-center justify-center text-white shadow-lg shadow-orange-900/40 ring-1 ring-white/20">
          <AppIcon name="graduation" :size="28" />
        </div>
        <h1 class="mt-4 text-2xl font-extrabold text-white tracking-tight">SSMTECO</h1>
        <p class="mt-1 text-sm text-slate-400">Seguimiento de Modalidades de Graduación</p>
      </div>

      <div class="relative card p-7 sm:p-8 overflow-hidden">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-orange to-transparent"></div>

        <Transition name="page" mode="out-in">
          <div :key="modo">
            <div class="text-center mb-7">
              <div class="mx-auto w-12 h-12 rounded-2xl bg-orange-500/15 border border-orange-500/30 text-orange-300 flex items-center justify-center">
                <AppIcon :name="modo === 'login' ? 'shield' : 'user-plus'" :size="22" />
              </div>
              <h2 class="mt-3 text-lg font-extrabold text-white">{{ titulo }}</h2>
              <p class="mt-1 text-sm text-slate-400 leading-relaxed">{{ subtitulo }}</p>
            </div>

            <form v-if="modo === 'login'" @submit.prevent="handleLogin" class="space-y-4">
              <div>
                <label class="label" for="username">Usuario</label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 pointer-events-none">
                    <AppIcon name="user" :size="18" />
                  </span>
                  <input id="username" ref="usernameInput" v-model="username" type="text" class="input pl-11" placeholder="Nombre_CI" autocomplete="username" required>
                </div>
              </div>

              <div>
                <label class="label" for="password">Contraseña</label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 pointer-events-none">
                    <AppIcon name="shield" :size="18" />
                  </span>
                  <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" class="input pl-11 pr-11" placeholder="••••••••" required>
                  <button type="button" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-orange-400 transition-colors" @click="showPassword = !showPassword">
                    <AppIcon :name="showPassword ? 'eye-off' : 'eye'" :size="19" />
                  </button>
                </div>
              </div>

              <button type="submit" :disabled="ingresando" class="btn-primary w-full py-3.5 mt-2">
                <AppIcon v-if="ingresando" name="loader" :size="17" class="animate-spin" />
                <AppIcon v-else name="send" :size="16" />
                {{ ingresando ? 'Ingresando...' : 'Ingresar' }}
              </button>
            </form>

            <form v-else @submit.prevent="registrarEstudiante" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="label">Nombres</label>
                  <input v-model="reg.nombres" class="input" placeholder="" required>
                </div>
                <div>
                  <label class="label">Apellidos</label>
                  <input v-model="reg.apellidos" class="input" placeholder="" required>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="label">CI</label>
                  <input v-model="reg.ci" class="input" placeholder="" required>
                </div>
                <div>
                  <label class="label">Registro Universitario</label>
                  <input v-model="reg.registro_universitario" class="input" placeholder="" required>
                </div>
              </div>

              <div>
                <label class="label">Fecha de Nacimiento</label>
                <p class="text-xs text-slate-500 -mt-1 mb-1">Se usa para generar la contraseña inicial (dd-mm-aaaa).</p>
                <input v-model="reg.fecha_nacimiento" type="date" class="input" required :max="hoy">
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="label">Email</label>
                  <input v-model="reg.email" type="email" class="input" placeholder="">
                </div>
                <div>
                  <label class="label">Teléfono</label>
                  <input v-model="reg.telefono" class="input" placeholder="">
                </div>
              </div>

              <button type="submit" :disabled="registrando" class="btn-primary w-full py-3.5 mt-2">
                <AppIcon v-if="registrando" name="loader" :size="17" class="animate-spin" />
                <AppIcon v-else name="user-plus" :size="16" />
                {{ registrando ? 'Registrando...' : 'Registrarme' }}
              </button>
            </form>

            <div class="mt-7 pt-6 border-t border-white/10 text-center">
              <p class="text-sm text-slate-400">
                {{ modo === 'login' ? '¿Aún no tienes cuenta de estudiante?' : '¿Ya tienes una cuenta registrada?' }}
              </p>
              <button
                type="button"
                class="mt-2 inline-flex items-center gap-1.5 text-sm font-bold text-orange-400 hover:text-orange-300 transition-colors"
                @click="cambiarModo(modo === 'login' ? 'registro' : 'login')"
              >
                {{ modo === 'login' ? 'Regístrate aquí' : 'Ingresa a tu cuenta' }}
                <AppIcon name="send" :size="15" />
              </button>
            </div>
          </div>
        </Transition>
      </div>

      <p class="mt-6 text-center text-xs text-slate-500">
        <p class="text-xs text-slate-500">
        © {{ new Date().getFullYear() }} Instituto de Investigaciones Economicas. Todos los derechos reservados.
      </p>
      </p>
    </div>

    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showErrorModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          @keydown.escape="closeErrorModal"
          tabindex="0"
          ref="modalOverlay"
        >
          <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="closeErrorModal"></div>

          <div class="relative w-full max-w-sm card p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-500/20 flex items-center justify-center mb-5">
              <AppIcon name="alert-triangle" :size="28" class="text-red-400" />
            </div>

            <h3 class="text-lg font-bold text-white">Error</h3>
            <p class="mt-2 text-sm text-slate-400 leading-relaxed">{{ errorMessage }}</p>

            <button
              @click="closeErrorModal"
              class="mt-6 w-full py-2.5 rounded-xl bg-orange text-white text-sm font-semibold shadow-lg shadow-orange-900/30 hover:opacity-90 active:scale-[0.98] transition-all duration-200"
            >
              Intentar de nuevo
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
// Vista de inicio de sesión / auto-registro, centrada en la pantalla.
// Muestra dos modos: "Ingresar" (usuario/contraseña) y "Registrarse" (el
// estudiante crea su perfil aislado). Tras el auto-registro el estudiante
// recibe usuario y contraseña generados por la instancia académica (CREAR
// USUARIO) y ya no edita su perfil: solo consulta sus datos y las modalidades,
// por eso la vista salta sola al formulario de Ingreso para que pueda entrar.
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '@/core/stores/toast';
import { homeForRol } from '../guards';
import { estudianteService } from '@/modules/estudiantes/services/estudiantes';
import AppIcon from '@/ui/AppIcon.vue';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

// Modo activo del panel: 'login' o 'registro'.
const modo = ref('login');

// Encabezado del card según el modo activo.
const titulo = computed(() => (modo.value === 'login' ? 'Bienvenido' : 'Crea tu cuenta de estudiante'));
const subtitulo = computed(() => (modo.value === 'login'
  ? 'Ingresa con tus credenciales para continuar.'
  : 'Regístrate y la Dirección de Carrera generará tu usuario y contraseña.'));

// Campos del formulario y controles de UI (login).
const username = ref('');
const password = ref('');
const showPassword = ref(false);   // Alterna visibilidad de la contraseña.
const ingresando = ref(false);     // true mientras se procesa el login.
const showErrorModal = ref(false); // Controla el modal de error.
const errorMessage = ref('');      // Mensaje de error a mostrar.
const usernameInput = ref(null);   // Input de usuario (enfocado tras el registro).

// Formulario de auto-registro del estudiante.
const reg = ref({ ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '', email: '', telefono: '' });
const registrando = ref(false);
const hoy = new Date().toISOString().slice(0, 10);

/** Cierra el modal de error. */
const closeErrorModal = () => {
  showErrorModal.value = false;
};

// Cierra el modal con la tecla Escape.
const onKeydown = (event) => {
  if (event.key === 'Escape' && showErrorModal.value) {
    closeErrorModal();
  }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

/** Abre el modal de error con el mensaje indicado. */
const openErrorModal = (message) => {
  errorMessage.value = message;
  showErrorModal.value = true;
};

/** Cambia entre los modos 'login' y 'registro'. */
const cambiarModo = (nuevo) => {
  modo.value = nuevo;
};

/**
 * Autentica con POST /api/login. Si el login es exitoso redirige según el rol;
 * en caso de error muestra un modal con el mensaje devuelto por la API.
 */
const handleLogin = async () => {
  ingresando.value = true;
  try {
    const result = await authStore.login(username.value, password.value);
    if (result === true) {
      router.push(homeForRol(authStore.user?.rol));
    } else {
      openErrorModal(result || 'Credenciales incorrectas.');
    }
  } catch (err) {
    // Error de red / servidor no disponible.
    openErrorModal('No se pudo conectar con el servidor. Inténtalo de nuevo.');
  } finally {
    ingresando.value = false;
  }
};

/**
 * Registra al estudiante vía POST /api/estudiantes/registro. Al completarse
 * limpia el formulario, avisa con un toast y salta automáticamente al modo
 * 'login' para que ingrese con las credenciales generadas por la instancia
 * académica, enfocando el campo de usuario.
 */
const registrarEstudiante = async () => {
  registrando.value = true;
  try {
    await estudianteService.registro(reg.value);
    reg.value = { ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '', email: '', telefono: '' };
    toastStore.success('¡Registro exitoso! Tu perfil fue creado. Ingresa con las credenciales generadas por la Dirección de Carrera.');
    modo.value = 'login';
    await nextTick();
    usernameInput.value?.focus();
  } catch (err) {
    const errData = err.response?.data;
    let mensaje = errData?.message;
    if (!mensaje && errData?.errors) {
      const primerError = Object.values(errData.errors).flat()[0];
      mensaje = primerError || null;
    }
    openErrorModal(mensaje || 'No se pudo completar el registro. Verifica tus datos.');
  } finally {
    registrando.value = false;
  }
};
</script>
