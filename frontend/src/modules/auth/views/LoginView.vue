<template>
  <div class="min-h-screen relative flex overflow-hidden bg-[#152036]">
    
    <!-- ========================================== -->
    <!-- LADO IZQUIERDO: IMAGEN DE ECONOMÍA         -->
    <!-- ========================================== -->
    <div class="relative hidden w-1/2 lg:block">
      <!-- 👇 CAMBIA ESTA RUTA POR LA RUTA REAL DE TU IMAGEN 👇 -->
      <img 
        src="@/assets/images/IMG-FONDO.svg" 
        alt="Carrera de Economía" 
        class="absolute inset-0 h-full w-full object-cover" 
      />
      
      <!-- EL TRUCO: Overlay oscuro para integrar la imagen con el formulario -->
      <div class="absolute inset-0 bg-gradient-to-r from-[#152036]/60 via-[#152036]/70 to-[#152036]"></div>
      
      <!-- Texto opcional sobre la imagen -->
      <div class="absolute bottom-12 left-12 z-10 max-w-md text-white">
        <h2 class="text-3xl font-bold mb-3 tracking-tight">Carrera de Economía</h2>
        <p class="text-slate-300 text-sm leading-relaxed">
          Sistema de Seguimiento de modalidades de titulacion de la Carrera de Economía         </p>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- LADO DERECHO: FORMULARIO (TU CÓDIGO)       -->
    <!-- ========================================== -->
    <div class="relative flex flex-col justify-center w-full lg:w-1/2 px-4 py-10 z-10">
      
      <!-- Fondos decorativos (Luces naranjas/rojas) ahora aplicadas al lado derecho -->
      <div class="absolute top-[-10%] right-[-10%] w-[400px] h-[400px] bg-orange-600 rounded-full blur-[120px] opacity-20 pointer-events-none"></div>
      <div class="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] bg-red-600 rounded-full blur-[120px] opacity-10 pointer-events-none"></div>
      <div class="absolute inset-0 opacity-[0.04] pointer-events-none" style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 26px 26px;"></div>

      <div class="relative w-full max-w-md mx-auto">
        <!-- ENCABEZADO (Logo y Título) -->
        <div class="flex flex-col items-center text-center mb-8">
          <img src="@/assets/images/LOGO-ECONOMIA.svg" class="w-20 h-20 rounded-2xl object-cover shadow-lg" />
          
          <h1 class="mt-4 text-4xl font-extrabold tracking-tight select-none">
            <span class="text-orange-500 inline-block transition-transform duration-300 hover:-translate-y-1">SSMT</span><span class="text-white inline-block transition-transform duration-300 hover:-translate-y-1">ECO</span>
          </h1>
          
          <p class="mt-1 text-sm text-slate-400 min-h-[20px]">
            {{ textoSubtitulo }}<span class="animate-pulse">|</span>
          </p>
        </div>

        <!-- TARJETA DEL FORMULARIO -->
        <!-- Añadimos bg-[#253041] por si tu clase .card no tiene fondo, y backdrop-blur -->
        <div class="relative card bg-[#1e293b]/90 backdrop-blur-xl border border-slate-700/50 shadow-2xl p-7 sm:p-8 overflow-hidden rounded-2xl">
          <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-orange-500 to-transparent"></div>

          <Transition name="page" mode="out-in">
            <div :key="modo">
              <div class="text-center mb-7">
                <div class="mx-auto w-16 h-12 rounded-2xl bg-slate-900/50 border border-orange-500/100 overflow-hidden flex items-center justify-center shadow-md">
                  <img src="@/assets/images/IconoTitulacion.svg" alt="Icono Titulación" class="w-full h-full object-contain">
                </div>
                <h2 class="mt-3 text-lg font-extrabold text-white">{{ titulo }}</h2>
                <p class="mt-1 text-sm text-slate-400 leading-relaxed">{{ subtitulo }}</p>
              </div>

              <!-- FORMULARIO LOGIN -->
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

              <!-- FORMULARIO REGISTRO -->
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

              <!-- CAMBIO DE MODO -->
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

        <!-- FOOTER -->
        <div class="mt-6 text-center text-xs text-slate-500">
          <p>
            © {{ new Date().getFullYear() }} Instituto de Investigaciones Economicas. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </div>

    <!-- MODAL DE ERROR -->
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

          <div class="relative w-full max-w-sm card bg-[#1e293b] border border-slate-700/50 shadow-2xl p-8 text-center rounded-2xl">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-500/20 flex items-center justify-center mb-5">
              <AppIcon name="alert-triangle" :size="28" class="text-red-400" />
            </div>

            <h3 class="text-lg font-bold text-white">Error</h3>
            <p class="mt-2 text-sm text-slate-400 leading-relaxed">{{ errorMessage }}</p>

            <button
              @click="closeErrorModal"
              class="mt-6 w-full py-2.5 rounded-xl bg-orange-500 text-white text-sm font-semibold shadow-lg shadow-orange-900/30 hover:bg-orange-600 active:scale-[0.98] transition-all duration-200"
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

const modo = ref('login');

const titulo = computed(() => (modo.value === 'login' ? 'Bienvenido' : 'Crea tu cuenta de estudiante'));
const subtitulo = computed(() => (modo.value === 'login'
  ? 'Ingresa con tus credenciales para continuar.'
  : 'Regístrate y la Dirección de Carrera generará tu usuario y contraseña.'));

const username = ref('');
const password = ref('');
const showPassword = ref(false);
const ingresando = ref(false);
const showErrorModal = ref(false);
const errorMessage = ref('');
const usernameInput = ref(null);

const reg = ref({ ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '', email: '', telefono: '' });
const registrando = ref(false);
const hoy = new Date().toISOString().slice(0, 10);

const closeErrorModal = () => {
  showErrorModal.value = false;
};

const onKeydown = (event) => {
  if (event.key === 'Escape' && showErrorModal.value) {
    closeErrorModal();
  }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

const openErrorModal = (message) => {
  errorMessage.value = message;
  showErrorModal.value = true;
};

const cambiarModo = (nuevo) => {
  modo.value = nuevo;
};

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
    openErrorModal('No se pudo conectar con el servidor. Inténtalo de nuevo.');
  } finally {
    ingresando.value = false;
  }
};

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

const textoSubtitulo = ref('');
const textoCompleto = 'Seguimiento de Modalidades de Graduación';

onMounted(() => {
  let i = 0;
  const intervalo = setInterval(() => {
    if (i < textoCompleto.length) {
      textoSubtitulo.value += textoCompleto.charAt(i);
      i++;
    } else {
      clearInterval(intervalo);
    }
  }, 40);
});
</script>