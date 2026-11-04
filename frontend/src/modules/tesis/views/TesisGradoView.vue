<template>
  <AppShell title="Solicitud de Tesis de Grado" subtitle="Flujo oficial del estudiante: solicitud, seguimiento y defensa">
    <div v-if="cargando" class="card flex items-center justify-center gap-2 py-16 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando tu tesis de grado...
    </div>

    <div v-else-if="!estudianteStore.perfilEstudiante?.id_estudiante"
         class="card overflow-hidden">
      <div class="h-2 bg-orange"></div>
      <div class="p-8 flex flex-col items-center text-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
          <AppIcon name="user" :size="24" />
        </div>
        <h2 class="text-lg font-extrabold text-white">Necesitas completar tu perfil académico</h2>
        <p class="text-sm text-slate-400 max-w-md">
          El módulo de Tesis de Grado requiere tu información académica registrada
          para iniciar la solicitud.
        </p>
        <router-link to="/estudiante" class="btn-primary px-6 py-2.5 mt-1">
          <AppIcon name="arrow-left" :size="15" />
          Ir a mi Portal de Estudiante
        </router-link>
      </div>
    </div>

    <template v-else>
      <!-- ============================================================== -->
       <!-- SIN TESIS ACTIVA: fase de solicitud (documentos PDF opcionales) -->
      <!-- ============================================================== -->
      <div v-if="!tesisActiva" class="space-y-6">
        <!-- Flujo que siguió el trámite en Kardex hasta su resultado -->
        <TesisActualizacionesKardex v-if="ultimaTesisTerminada" :tramite="ultimaTesisTerminada" />

        <!-- Tesis aprobada: panel terminal, ya no puede iniciar más trámites -->
        <div v-if="tesisAprobada" class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 p-6 sm:p-8 text-center">
          <div class="mx-auto w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-300 mb-4">
            <AppIcon name="award" :size="34" />
          </div>
          <h2 class="text-2xl font-extrabold text-emerald-300">{{ titulado ? 'TRAMITE CONCLUIDO' : '¡FELICIDADES! APROBADO' }}</h2>
          <p class="mt-2 text-sm font-medium text-emerald-400 max-w-md mx-auto">
            {{
              titulado
                ? 'Tu trámite de titulación concluyó con éxito,Ya no puedes realizar más solicitudes de trámite.'
                : 'Tu tesis de grado fue aprobada. Has culminado tu modalidad de titulación y ya no puedes realizar más solicitudes de trámite.'
            }}
          </p>
          <p v-if="ultimaTesisTerminada" class="mt-1 text-xs text-emerald-500">
            {{ titulado ? 'Titulación' : 'Sustentación' }}
            <template v-if="ultimaTesisTerminada.hitos?.fecha_defensa">
              aprobada el {{ formatoFechaLarga(ultimaTesisTerminada.hitos.fecha_defensa) }}.
            </template>
            <template v-else>
              aprobada.
            </template>
          </p>
        </div>

        <template v-else>
          <!-- Tesis reprobada: informa cuándo podrá re-optar por una modalidad -->
          <div v-if="tesisReprobada" class="rounded-xl bg-red-900/50 border border-red-500/30 p-4 flex items-start gap-3 text-red-400">
            <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu tesis de grado finalizó en estado
              <strong>{{ formatoEstado(ultimaTesisTerminada.estado_actual) }}</strong>.
              <template v-if="reoptar.puede">
                Ya puedes volver a presentar tu solicitud para optar por una modalidad.
              </template>
              <template v-else>
                Podrás volver a presentar tu solicitud a partir del
                <strong>{{ formatoFechaLarga(reoptar.fechaHabilitacion) }}</strong>
                (plazos de corrección de 90 días y máximo de 365 días).
              </template>
            </p>
          </div>

          <div v-else-if="ultimaTesisTerminada" class="rounded-xl bg-orange-500/10 border border-orange-500/30 p-4 flex items-start gap-3 text-orange-200">
            <AppIcon name="check-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu último trámite de tesis finalizó con estado
              <strong>{{ formatoEstado(ultimaTesisTerminada.estado_actual) }}</strong>.
              Puedes iniciar una nueva solicitud cuando lo necesites.
            </p>
          </div>

          <!-- Solicitud de nueva tesis (documentos PDF opcionales) -->
          <div v-if="puedeIniciarNueva" class="card overflow-hidden">
            <div class="h-2 bg-orange"></div>
            <div class="p-6 sm:p-8">
              <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                  <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
                    <AppIcon name="graduation" :size="28" />
                  </div>
                  <div>
                    <h2 class="text-lg font-extrabold text-white">Solicitud de Tesis de Grado</h2>
                    <p class="text-sm text-slate-400">Presenta tu solicitud; los 3 documentos en PDF son opcionales.</p>
                  </div>
                </div>
              </div>

              <!-- Resumen de fases del flujo -->
              <div class="mt-6 grid sm:grid-cols-7 gap-2">
                <div v-for="fase in FASES_TESIS" :key="fase.id"
                     class="rounded-xl px-3 py-3 ring-1 ring-white/10 bg-white/5 text-center">
                  <AppIcon :name="fase.icon" :size="18" class="mx-auto text-orange-300 mb-1" />
                  <p class="text-[11px] font-bold text-white uppercase tracking-wide">{{ fase.label }}</p>
                </div>
              </div>

              <form @submit.prevent="enviarSolicitud" class="mt-7 grid sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                  <label class="label" for="titulo-tesis">Título de tesis de grado · obligatorio</label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                      <AppIcon name="file-text" :size="18" />
                    </span>
                    <input
                      id="titulo-tesis"
                      v-model="tituloSolicitud"
                      type="text"
                      class="input pl-10"
                      placeholder="Ej: Sistemas de riego eficientes para cultivos de altura en el altiplano boliviano"
                      autocomplete="off"
                    />
                  </div>
                  <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-400">
                    <AppIcon name="info" :size="13" />
                    KARDEX verificará que el título no esté repetido en otro trabajo de grado
                    antes de aprobar tu perfil.
                  </p>
                </div>

                <div v-for="(ruta, tipo) in tesisStore.config.tipos_documento" :key="tipo">
                  <label class="label">{{ ruta }} (PDF) · opcional</label>
                  <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivos[tipo] ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']">
                    <span class="flex items-center gap-2 text-sm min-w-0" :class="archivos[tipo] ? 'text-orange-200' : 'text-slate-400'">
                      <AppIcon v-if="archivos[tipo]" name="check-circle" :size="18" class="text-emerald-400 shrink-0" />
                      <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
                      <span class="truncate">{{ archivos[tipo]?.name || 'Selecciona el archivo...' }}</span>
                    </span>
                    <input type="file" accept=".pdf" class="hidden" @change="handleArchivo($event, tipo)">
                    <span class="btn-ghost !py-2 pointer-events-none">
                      <AppIcon name="plus" :size="15" />
                      Subir
                    </span>
                  </label>
                </div>

                <div class="sm:col-span-2 flex items-center justify-between gap-3 pt-2">
                  <p v-if="faltantes.length" class="text-xs text-slate-400 inline-flex items-center gap-1.5">
                    <AppIcon name="info" :size="14" />
                    Documentos sin adjuntar (opcional): {{ faltantes.join(', ') }}
                  </p>
                  <p v-else-if="!tituloSolicitudValido" class="text-xs text-slate-400 inline-flex items-center gap-1.5">
                    <AppIcon name="info" :size="14" />
                    Ingrese el título de su tesis (mínimo 10 caracteres).
                  </p>
                  <p v-else class="text-xs font-semibold text-emerald-300 inline-flex items-center gap-1.5">
                    <AppIcon name="check" :size="14" />
                    Documentación lista para enviar.
                  </p>
                  <button type="submit" class="btn-primary px-6 py-2.5 shrink-0" :disabled="tesisStore.enviando || !tituloSolicitudValido">
                    <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
                    <AppIcon v-else name="send" :size="15" />
                    {{ tesisStore.enviando ? 'Enviando...' : 'Enviar Solicitud' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </template>
      </div>

      <!-- ============================================================== -->
      <!-- CON TESIS ACTIVA: seguimiento del flujo -->
      <!-- ============================================================== -->
      <div v-else class="space-y-6">
        <!-- Cabecera: estado y avance por fase -->
        <div class="card p-6">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-extrabold text-white">Seguimiento de tu Tesis de Grado</h2>
              <p class="text-sm text-slate-400 mt-1">
                Trámite N.º {{ tramite.id_tramite }} · Tutor:
                <template v-if="tramite.tutor">
                  <span class="font-semibold text-white">{{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}</span>
                  <span v-if="tramite.tutor.telefono" class="inline-flex items-center gap-1 ml-2 text-orange-300 font-semibold">
                    <AppIcon name="phone" :size="14" />
                    {{ tramite.tutor.telefono }}
                  </span>
                </template>
                <template v-else class="text-orange-400">por asignar</template>
              </p>
            </div>
            <EstadoBadge :estado="tramite.estado_actual" upper />
          </div>

          <p v-if="descripcionEstado" class="mt-3 text-sm text-slate-300 rounded-xl bg-white/5 border border-white/10 px-4 py-3">
            {{ descripcionEstado }}
          </p>

          <!-- Título de tesis vigente (el último presentado por el estudiante) -->
          <div v-if="tituloVigente" class="mt-3 rounded-xl bg-white/5 border border-white/10 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Título de tesis presentado</p>
            <p class="mt-0.5 text-sm text-white font-semibold">{{ tituloVigente }}</p>
          </div>

          <!-- Indicador de fase actual -->
          <div class="mt-5 grid sm:grid-cols-7 gap-2">
            <div v-for="fase in FASES_TESIS" :key="fase.id"
                 class="rounded-xl px-3 py-3 ring-1 transition text-center"
                 :class="estadoFase(fase).contenedor">
              <AppIcon :name="fase.icon" :size="18" class="mx-auto mb-1" :class="estadoFase(fase).icono" />
              <p class="text-[11px] font-bold uppercase tracking-wide" :class="estadoFase(fase).texto">{{ fase.label }}</p>
            </div>
          </div>
        </div>

        <!-- Perfil de tesis: título y PDFs opcionales (editable en verificación) -->
        <div v-if="perfilEditable" class="card overflow-hidden">
          <div class="h-2 bg-orange"></div>
          <div class="p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
                <AppIcon name="file-text" :size="26" />
              </div>
              <div>
                <h3 class="text-lg font-extrabold text-white">Tu perfil de tesis</h3>
                <p class="text-sm text-slate-400">Registra o corrige el título y adjunta los 3 documentos en PDF (opcionales) mientras tu solicitud está en verificación.</p>
              </div>
            </div>

            <form @submit.prevent="guardarPerfilEdicion" class="mt-6 grid sm:grid-cols-2 gap-5">
              <div class="sm:col-span-2">
                <label class="label" for="titulo-perfil-seguimiento">Título de tesis de grado · obligatorio</label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <AppIcon name="file-text" :size="18" />
                  </span>
                  <input
                    id="titulo-perfil-seguimiento"
                    v-model="tituloPerfilEdicion"
                    type="text"
                    class="input pl-10"
                    placeholder="Ej: Sistemas de riego eficientes para cultivos de altura en el altiplano boliviano"
                    autocomplete="off"
                  />
                </div>
                <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-400">
                  <AppIcon name="info" :size="13" />
                  KARDEX verificará que el título no esté repetido en otro trabajo de grado antes de aprobar tu perfil.
                </p>
              </div>

              <div v-for="(ruta, tipo) in tesisStore.config.tipos_documento" :key="'edicion-' + tipo">
                <label class="label">{{ ruta }} (PDF) · opcional</label>
                <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', documentosEdicion[tipo] ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']">
                  <span class="flex items-center gap-2 text-sm min-w-0" :class="documentosEdicion[tipo] ? 'text-orange-200' : 'text-slate-400'">
                    <AppIcon v-if="documentosEdicion[tipo]" name="check-circle" :size="18" class="text-emerald-400 shrink-0" />
                    <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
                    <span class="truncate">{{ documentosEdicion[tipo]?.name || documentoExistente(tipo)?.nombre_archivo || 'Selecciona el archivo...' }}</span>
                  </span>
                  <input type="file" accept=".pdf" class="hidden" @change="handleArchivoEdicion($event, tipo)">
                  <span class="btn-ghost !py-2 pointer-events-none">
                    <AppIcon name="plus" :size="15" />
                    Subir
                  </span>
                </label>
              </div>

              <div class="sm:col-span-2 flex items-center justify-between gap-3 pt-2">
                <p v-if="!tituloEdicionValido" class="text-xs text-slate-400 inline-flex items-center gap-1.5">
                  <AppIcon name="info" :size="14" />
                  El título debe tener al menos 10 caracteres.
                </p>
                <p v-else-if="!hayCambiosPerfil" class="text-xs text-slate-400 inline-flex items-center gap-1.5">
                  <AppIcon name="info" :size="14" />
                  Modifica el título o adjunta un PDF para guardar.
                </p>
                <p v-else class="text-xs font-semibold text-emerald-300 inline-flex items-center gap-1.5">
                  <AppIcon name="check" :size="14" />
                  Cambios listos para guardar.
                </p>
                <button type="submit" class="btn-primary px-6 py-2.5 shrink-0" :disabled="tesisStore.enviando || !tituloEdicionValido || !hayCambiosPerfil">
                  <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
                  <AppIcon v-else name="send" :size="15" />
                  {{ tesisStore.enviando ? 'Guardando...' : 'Guardar Perfil' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Flujo seguido por Kardex: todo lo que se registró en el trámite -->
        <TesisActualizacionesKardex :tramite="tramite" />

        <!-- Alerta de perfil rechazado + reenvío -->
        <div v-if="tramite.estado_actual === 'perfil_rechazado'"
             class="rounded-xl bg-red-900/50 border border-red-500/30 p-5 flex items-start gap-3 text-red-400">
          <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
          <div class="flex-1">
            <p class="font-bold text-sm">KARDEX rechazó tu perfil de tesis.</p>
            <p class="text-sm mt-1">
              No puedes avanzar a la siguiente etapa hasta que tu perfil sea aceptado.
              Corrige lo indicado y vuelve a presentarlo con un título de tesis diferente.
            </p>

            <div v-if="rechazoPerfil?.titulo" class="mt-3 rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
              <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Título rechazado</p>
              <p class="mt-0.5 text-sm text-slate-200 font-semibold">{{ rechazoPerfil.titulo }}</p>
            </div>

            <div v-if="rechazoPerfil?.observacion" class="mt-2 rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
              <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
                Observación de KARDEX
                <span v-if="rechazoPerfil.fecha">· {{ formatoFechaLarga(rechazoPerfil.fecha) }}</span>
              </p>
              <p class="mt-0.5 text-sm text-slate-200 italic">“{{ rechazoPerfil.observacion }}”</p>
            </div>

            <button class="mt-3 btn-warm px-5 py-2.5" @click="abrirReenvio">
              <AppIcon name="pencil" :size="15" />
              Presentar Perfil Corregido
            </button>
          </div>
        </div>

        <!-- Alerta de documento insuficiente + reenvío a la Comisión Revisora -->
        <div v-if="tramite.estado_actual === 'insuficiente'"
             class="rounded-xl bg-red-900/50 border border-red-500/30 p-5 flex items-start gap-3 text-red-400">
          <AppIcon name="alert-triangle" :size="20" class="mt-0.5 shrink-0" />
          <div class="flex-1">
            <p class="font-bold text-sm">La Comisión Revisora calificó tu documento como insuficiente.</p>
            <p class="text-sm mt-1">Debes revisar tu trabajo, corregir las observaciones señaladas y volver a presentarlo a la Comisión Revisora.</p>
            <button class="mt-3 btn-warm px-5 py-2.5" @click="mostrarReenvioDocumento = true">
              <AppIcon name="pencil" :size="15" />
              Enviar Documento Corregido
            </button>
          </div>
        </div>

        <!-- Veredicto de la Comisión Revisora: trabajo aprobado -->
        <div v-if="tramite.estado_actual === 'suficiente'"
             class="rounded-2xl bg-emerald-500/10 border border-emerald-500/30 p-5 flex items-start gap-3 text-emerald-300">
          <AppIcon name="check-circle" :size="22" class="mt-0.5 shrink-0" />
          <div class="flex-1">
            <h4 class="font-bold text-emerald-300">¡Trabajo Aprobado!</h4>
            <p class="text-sm mt-1">
              La Comisión Revisora calificó tu tesis como suficiente. Ya puedes solicitar
              la fecha de tu defensa; Kardex o Secretaría la programará.
            </p>
          </div>
        </div>

        <!-- Defensa no aprobada: plazo de 90 días para volver a solicitar -->
        <div v-if="tramite.estado_actual === 'correcciones_90_dias'"
             class="rounded-2xl bg-red-900/50 border border-red-500/30 p-5 flex items-start gap-3 text-red-400">
          <AppIcon name="alert-triangle" :size="22" class="mt-0.5 shrink-0" />
          <div class="flex-1">
            <h4 class="font-bold text-red-300">Tu defensa no fue aprobada</h4>
            <p class="text-sm mt-1">
              Tienes <strong>90 días</strong> para corregir tu trabajo y volver a solicitar una
              fecha de defensa. Si no lo haces dentro del plazo, tu tesis quedará reprobada.
            </p>
          </div>
        </div>

        <!-- Countdown del periodo de presentación / correcciones -->
        <CountdownTitulacion v-if="countdownCfg" v-bind="countdownCfg" />

        <!-- Solicitud de fecha de defensa (la solicita el estudiante a Kardex/Secretaría) -->
        <div v-if="puedeSolicitarFechaDefensa(tramite.estado_actual)"
             class="rounded-2xl ring-1 p-5"
             :class="necesitaSolicitar ? 'bg-orange-500/10 ring-orange-500/30' : 'bg-emerald-500/10 ring-emerald-500/30'">
          <div class="flex items-start gap-3">
            <span class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center ring-1"
                  :class="necesitaSolicitar ? 'bg-orange-500/15 text-orange-300 ring-orange-500/40' : 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/40'">
              <AppIcon name="calendar" :size="22" />
            </span>
            <div class="flex-1 min-w-0">
              <h4 class="font-bold text-white">Solicitud de fecha de defensa</h4>
              <template v-if="necesitaSolicitar">
                <template v-if="tramite.estado_actual === 'correcciones_90_dias'">
                  <p class="text-sm text-slate-400 mt-1">
                    Vuelve a solicitar una fecha para tu defensa después de corregir tu trabajo.
                    Kardex o Secretaría la programará.
                  </p>
                </template>
                <template v-else>
                  <p class="text-sm text-slate-400 mt-1">
                    Tu tesis fue calificada como suficiente por la Comisión Revisora.
                    Solicita una fecha para tu defensa; Kardex o Secretaría la programará.
                  </p>
                </template>
                <form @submit.prevent="enviarSolicitudFecha" class="mt-3 flex flex-wrap items-end gap-2">
                  <div class="w-full sm:w-auto">
                    <label class="label">Fecha sugerida (opcional)</label>
                    <input v-model="fechaSugerida" type="date" :min="hoyISO" class="input w-full sm:w-auto" />
                  </div>
                  <button type="submit" class="btn-primary px-5 py-2.5" :disabled="tesisStore.enviando">
                    <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
                    <AppIcon v-else name="send" :size="15" />
                    {{ tesisStore.enviando ? 'Solicitando...' : 'Solicitar Fecha de Defensa' }}
                  </button>
                </form>
              </template>
              <template v-else>
                <p class="text-sm text-slate-400 mt-1">
                  Tu solicitud fue enviada a Kardex o Secretaría,
                  <span class="font-semibold text-emerald-300">
                    {{ tramite.hitos?.fecha_defensa_sugerida ? 'con fecha sugerida: ' + formatoFechaLarga(tramite.hitos.fecha_defensa_sugerida) + '.' : 'quienes te programarán una fecha de defensa.' }}
                  </span>
                </p>
                <p class="text-xs text-slate-500 mt-1.5">Solicitada el {{ formatoFechaLarga(tramite.hitos.fecha_defensa_solicitada) }}. Aguarda la programación de tu defensa.</p>
              </template>
            </div>
          </div>
        </div>

        <!-- Fecha de defensa programada -->
        <div v-if="tramite.estado_actual === 'defensa_programada' && tramite.hitos?.fecha_defensa"
             class="rounded-2xl bg-orange-500/10 border border-orange-500/30 p-5">
          <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-xl bg-orange-500/15 text-orange-300 border border-orange-500/40 flex items-center justify-center">
              <AppIcon name="calendar" :size="22" />
            </span>
            <div>
              <h4 class="font-bold text-orange-200">Fecha de defensa programada</h4>
              <p class="text-sm text-orange-300 font-semibold">{{ formatoFechaLarga(tramite.hitos.fecha_defensa) }}</p>
            </div>
          </div>
        </div>

        <!-- Trabajo de investigación: subida tras defensa aprobada -->
        <div v-if="tramite.estado_actual === 'defensa_aprobada'" class="card overflow-hidden">
          <div class="h-2 bg-orange"></div>
          <div class="p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
                <AppIcon name="book" :size="26" />
              </div>
              <div>
                <h3 class="text-lg font-extrabold text-white">Trabajo de Investigación</h3>
                <p class="text-sm text-slate-400">Tu defensa fue aprobada. Sube el PDF de tu trabajo de investigación para que KARDEX lo valide y cierre tu flujo.</p>
              </div>
            </div>

            <p v-if="publicacionSubida" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-300">
              <AppIcon name="check-circle" :size="16" />
              Trabajo enviado el {{ formatoFechaLarga(tramite.hitos.modulos.publicacion.fecha_publicacion) }}. KARDEX lo está validando.
            </p>

            <form v-else @submit.prevent="enviarPublicacion" class="mt-5 flex flex-wrap items-center gap-3">
              <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition min-w-0 sm:max-w-md flex-1', archivoPublicacion ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']">
                <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoPublicacion ? 'text-orange-200' : 'text-slate-400'">
                  <AppIcon v-if="archivoPublicacion" name="check-circle" :size="18" class="text-emerald-400 shrink-0" />
                  <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
                  <span class="truncate">{{ archivoPublicacion?.name || 'Selecciona el archivo PDF...' }}</span>
                </span>
                <input type="file" accept=".pdf" class="hidden" @change="handlePublicacion">
                <span class="btn-ghost !py-2 pointer-events-none">
                  <AppIcon name="plus" :size="15" />
                  Subir
                </span>
              </label>
              <button type="submit" class="btn-primary px-6 py-2.5" :disabled="tesisStore.enviando || !archivoPublicacion">
                <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
                <AppIcon v-else name="send" :size="15" />
                {{ tesisStore.enviando ? 'Enviando...' : 'Enviar Trabajo' }}
              </button>
            </form>
          </div>
        </div>

        <!-- Documentos presentados -->
        <div class="card overflow-hidden">
          <div class="p-6">
            <h3 class="font-bold text-white mb-4 inline-flex items-center gap-2">
              <span class="w-8 h-8 rounded-lg bg-orange-500/10 border border-orange-500/30 flex items-center justify-center text-orange-300">
                <AppIcon name="file-text" :size="17" />
              </span>
              Documentos Presentados
            </h3>
            <div v-if="tramite.documentos.length" class="grid sm:grid-cols-2 gap-2.5">
              <a v-for="doc in tramite.documentos" :key="doc.id_documento"
                 :href="assetUrl(doc.ruta_archivo)" target="_blank"
                 class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-orange-300 bg-orange-500/10 border border-orange-500/30 hover:bg-orange-500/20 transition-all duration-200">
                <AppIcon name="link" :size="16" />
                {{ etiquetaDocumento(doc.tipo_documento) }}
                <span class="ml-auto text-xs text-slate-400">{{ new Date(doc.created_at).toLocaleDateString('es-BO') }}</span>
              </a>
            </div>
            <p v-else class="text-sm text-slate-400">Sin documentos registrados.</p>
          </div>
        </div>

        </div>
    </template>

    <!-- Modal de reenvío del perfil -->
    <UiModal v-model="mostrarReenvio" title="Presentar Perfil Corregido" max-width="520px">
      <form @submit.prevent="enviarReenvio" class="space-y-5">
        <p class="text-sm text-slate-400">
          KARDEX rechazó tu perfil. Presenta nuevamente el perfil con un
          <strong class="text-slate-200">título de tesis diferente</strong> al rechazado y
          adjunta la versión corregida en PDF.
        </p>
        <div v-if="rechazoPerfil?.titulo">
          <label class="label">Título rechazado por KARDEX</label>
          <p class="input !bg-white/5 !text-slate-400 cursor-not-allowed truncate" :title="rechazoPerfil.titulo">
            {{ rechazoPerfil.titulo }}
          </p>
        </div>
        <div>
          <label class="label" for="titulo-reenvio">Nuevo título de tesis · obligatorio</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
              <AppIcon name="file-text" :size="18" />
            </span>
            <input
              id="titulo-reenvio"
              v-model="tituloReenvio"
              type="text"
              class="input pl-10"
              placeholder="Ej: Modelo predictivo de demanda eléctrica en campuses universitarios"
              autocomplete="off"
            />
          </div>
          <p class="mt-2 text-xs text-slate-400">
            Mínimo 10 caracteres. No puede coincidir con el título rechazado ni con otro
            trabajo de grado ya registrado.
          </p>
        </div>
        <div>
          <label class="label">Perfil de Tesis corregido (PDF)</label>
          <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivoReenvio ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']">
            <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoReenvio ? 'text-orange-200' : 'text-slate-400'">
              <AppIcon v-if="archivoReenvio" name="check-circle" :size="18" class="text-emerald-400 shrink-0" />
              <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
              <span class="truncate">{{ archivoReenvio?.name || 'Selecciona el archivo...' }}</span>
            </span>
            <input type="file" accept=".pdf" class="hidden" @change="archivoReenvio = $event.target.files[0] || null">
            <span class="btn-ghost !py-2 pointer-events-none">
              <AppIcon name="plus" :size="15" />
              Subir
            </span>
          </label>
        </div>
        <div>
          <label class="label">Observaciones (opcional)</label>
          <textarea v-model="observacionesReenvio" rows="3" class="input resize-none" placeholder="Indica las correcciones aplicadas..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="mostrarReenvio = false">Cancelar</button>
          <button type="submit" class="btn-primary px-5 py-2.5" :disabled="tesisStore.enviando || !tituloReenvioValido">
            <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
            <AppIcon v-else name="send" :size="15" />
            {{ tesisStore.enviando ? 'Enviando...' : 'Enviar Perfil Corregido' }}
          </button>
        </div>
      </form>
    </UiModal>

    <!-- Modal de reenvío del documento final tras calificación insuficiente -->
    <UiModal v-model="mostrarReenvioDocumento" title="Enviar Documento Corregido" max-width="520px">
      <form @submit.prevent="enviarReenvioDocumento" class="space-y-5">
        <p class="text-sm text-slate-400">
          Adjunta la versión corregida de tu documento final (PDF obligatorio). La
          Comisión Revisora lo evaluará nuevamente.
        </p>
        <div>
          <label class="label">Documento final corregido (PDF)</label>
          <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivoReenvioDocumento ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']">
            <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoReenvioDocumento ? 'text-orange-200' : 'text-slate-400'">
              <AppIcon v-if="archivoReenvioDocumento" name="check-circle" :size="18" class="text-emerald-400 shrink-0" />
              <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
              <span class="truncate">{{ archivoReenvioDocumento?.name || 'Selecciona el archivo...' }}</span>
            </span>
            <input type="file" accept=".pdf" class="hidden" @change="archivoReenvioDocumento = $event.target.files[0] || null">
            <span class="btn-ghost !py-2 pointer-events-none">
              <AppIcon name="plus" :size="15" />
              Subir
            </span>
          </label>
        </div>
        <div>
          <label class="label">Observaciones (opcional)</label>
          <textarea v-model="observacionesReenvioDocumento" rows="3" class="input resize-none" placeholder="Indica las correcciones aplicadas..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="mostrarReenvioDocumento = false">Cancelar</button>
          <button type="submit" class="btn-primary px-5 py-2.5" :disabled="tesisStore.enviando || !archivoReenvioDocumento">
            <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
            <AppIcon v-else name="send" :size="15" />
            {{ tesisStore.enviando ? 'Enviando...' : 'Reenviar Documento' }}
          </button>
        </div>
      </form>
    </UiModal>
  </AppShell>
</template>

<script setup>
// Vista del Módulo de Tesis de Grado (lado del estudiante).
// Dos modos:
//   - Sin tesis activa: formulario de solicitud con el título y los 3
//     documentos opcionales en PDF (Nota de Solicitud, Certificado de Notas,
//     Perfil de Tesis).
//   - Con tesis activa: seguimiento del flujo oficial (estado, fase, hitos,
//     cuenta regresiva del plazo, fecha de defensa y documentos), el detalle de
//     cada actualización registrada por Kardex y la opción de reenviar el perfil
//     si el Consejo lo rechazó. El trámite se sondea periódicamente para que el
//     estudiante vea los cambios de estado que Kardex vaya registrando sin
//     tener que recargar la página.
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { EstadoBadge, useTramitesStore, formatoEstado, ESTADOS_TERMINALES, CountdownTitulacion } from '@/modules/tramites';
import { useEstudianteStore } from '@/modules/estudiantes';
import { useAuthStore } from '@/modules/auth';
import { useToastStore } from '@/core/stores/toast';
import { assetUrl } from '@/core/http/storage';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import UiModal from '@/ui/UiModal.vue';
import TesisActualizacionesKardex from '../components/TesisActualizacionesKardex.vue';
import { useTesisStore } from '../stores/tesis';
import { FASES_TESIS, DESCRIPCION_ESTADO, faseDe, countdownDe, puedeSolicitarFechaDefensa, reoptarInfo } from '../utils/flujo';
import { rechazoPerfilTesis, tituloPerfilTesis } from '../utils/flujoTitulacion';

// Cada cuánto se consulta el trámite para detectar avances de Kardex.
const INTERVALO_SEGUIMIENTO = 30000;

const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const estudianteStore = useEstudianteStore();
const tesisStore = useTesisStore();
const toastStore = useToastStore();

// Estado del formulario de solicitud.
const archivos = ref({ nota_solicitud: null, certificado_notas: null, perfil_tesis: null });
// Título de tesis de grado presentado por el estudiante (KARDEX verifica su unicidad).
const tituloSolicitud = ref('');
// Modal de reenvío del perfil rechazado.
const mostrarReenvio = ref(false);
const archivoReenvio = ref(null);
const observacionesReenvio = ref('');
const tituloReenvio = ref('');
// Modal de reenvío del documento final calificado como insuficiente.
const mostrarReenvioDocumento = ref(false);
const archivoReenvioDocumento = ref(null);
const observacionesReenvioDocumento = ref('');
// Solicitud de fecha de defensa.
const fechaSugerida = ref('');
const cargando = ref(true);

const tramite = computed(() => tramitesStore.tramiteActivo);
const esTesis = computed(() => tramite.value?.modalidad?.nombre === 'Tesis de Grado');
const tesisTerminada = computed(() => esTesis.value && ESTADOS_TERMINALES.includes(tramite.value.estado_actual));

// Si la tesis está terminal (o no es tesis), se muestra la solicitud de nuevo.
const tesisActiva = computed(() => esTesis.value && !tesisTerminada.value);

// Último trámite de tesis que terminó (para mostrar su resultado al iniciar otra).
const ultimaTesisTerminada = computed(() => (tesisTerminada.value ? tramite.value : null));

// Resultado terminal: aprobada no permite más trámites; reprobada aplica la
// regla de re-opción de modalidad (90 días de corrección / 365 días máximos).
const tesisAprobada = computed(() =>
  tesisTerminada.value && ['aprobado', 'titulado'].includes(tramite.value?.estado_actual)
);
const titulado = computed(() => tramite.value?.estado_actual === 'titulado');
const tesisReprobada = computed(() =>
  tesisTerminada.value && ['reprobado'].includes(tramite.value?.estado_actual)
);
const reoptar = computed(() => reoptarInfo(tramite.value, {
  diasCorreccion: tesisStore.config.dias_correccion,
  diasRemodalidad: tesisStore.config.dias_remodalidad,
}));
const puedeIniciarNueva = computed(() => !tesisAprobada.value && (!tesisReprobada.value || reoptar.value.puede));

const descripcionEstado = computed(() => DESCRIPCION_ESTADO[tramite.value?.estado_actual]);
const countdownCfg = computed(() => countdownDe(tramite.value));

// true cuando el estudiante ya envió su solicitud de fecha de defensa a Kardex.
const fechaDefensaSolicitada = computed(() => Boolean(tramite.value?.hitos?.fecha_defensa_solicitada));

// true cuando el estudiante debe mostrar el formulario de solicitud de fecha:
// aún no la envió o debe volver a solicitarla tras las correcciones de 90 días.
const necesitaSolicitar = computed(() => {
  const estado = tramite.value?.estado_actual;
  if (estado === 'suficiente' || estado === 'correcciones_90_dias') return true;
  if (estado === 'solicitud_fecha_defensa') return !fechaDefensaSolicitada.value;
  return false;
});

// Fecha mínima seleccionable para la fecha sugerida (hoy).
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

// Documentos opcionales que aún no se subieron en el formulario.
const faltantes = computed(() =>
  Object.entries(tesisStore.config.tipos_documento)
    .filter(([tipo]) => !archivos.value[tipo])
    .map(([, etiqueta]) => etiqueta),
);

// Un título es válido si tiene la extensión mínima exigida por el backend.
// Cada formulario valida su propio campo: el de solicitud y el de reenvío
// comparten pantalla y un campo no debe decidir la validez del otro.
const tituloSolicitudValido = computed(() => tituloSolicitud.value.trim().length >= 10);
const tituloReenvioValido = computed(() => tituloReenvio.value.trim().length >= 10);

// Título rechazado y observación del último rechazo de perfil de Kardex.
const rechazoPerfil = computed(() => rechazoPerfilTesis(tramite.value));

// Título vigente del trámite (el registrado con el perfil, sea el aceptado o el
// que está en revisión tras un reenvío).
const tituloVigente = computed(() => tituloPerfilTesis(tramite.value));

// true cuando el estudiante ya subió su trabajo de investigación (publicación).
const publicacionSubida = computed(
  () => Boolean(tramite.value?.hitos?.modulos?.publicacion?.archivo?.nombre)
);

// ---------------------------------------------------------------------------
// Edición del perfil de tesis (título + 3 PDFs opcionales) mientras la
// solicitud del estudiante sigue en verificación (estado `solicitud_presentada`).
// ---------------------------------------------------------------------------
const tituloPerfilEdicion = ref('');
const documentosEdicion = ref({ nota_solicitud: null, certificado_notas: null, perfil_tesis: null });

// Pre-carga el título vigente del trámite al entrar en seguimiento.
watch(tituloVigente, (valor) => {
  tituloPerfilEdicion.value = valor || '';
}, { immediate: true });

// true mientras la solicitud está en verificación y el estudiante puede corregir su perfil.
const perfilEditable = computed(() => tramite.value?.estado_actual === 'solicitud_presentada');

const tituloEdicionValido = computed(() => tituloPerfilEdicion.value.trim().length >= 10);

const hayCambiosPerfil = computed(() => {
  const tituloCambiado = tituloPerfilEdicion.value.trim() !== (tituloVigente.value || '').trim();
  return tituloCambiado || Object.values(documentosEdicion.value).some(Boolean);
});

/** Documento previo del trámite para un tipo de la fase de solicitud. */
function documentoExistente(tipo) {
  return tramite.value?.documentos?.find((d) => d.tipo_documento === tipo) || null;
}

/** Asocia un archivo seleccionado a su tipo durante la edición del perfil. */
function handleArchivoEdicion(event, tipo) {
  const file = event.target.files[0];
  documentosEdicion.value[tipo] = file || null;
  event.target.value = '';
}

/**
 * Detalle legible de un fallo HTTP para los toasts de error: la primera
 * validación que reportó el backend, su mensaje, o un aviso de conexión cuando
 * ni siquiera hubo respuesta (backend apagado o red caída).
 *
 * @param {Error} error    Error de axios.
 * @param {string} defecto Mensaje si la respuesta no trae detalles.
 * @returns {string} Texto para el toast.
 */
function detalleDeError(error, defecto) {
  if (!error.response) {
    return 'No hay conexión con el servidor. Verifique que el backend esté activo e intente de nuevo.';
  }
  const errores = error.response.data?.errors;
  const primero = errores && Object.values(errores).find((lista) => Array.isArray(lista) && lista.length);
  return primero?.[0] || error.response.data?.message || defecto;
}

/**
 * Guarda el título corregido y los PDFs adjuntados (opcionales) contra
 * POST /tesis/{id}/perfil. Al tener éxito se limpian los archivos elegidos y se
 * recarga el trámite para reflejar el título y los documentos nuevos.
 */
async function guardarPerfilEdicion() {
  const formData = new FormData();
  formData.append('titulo', tituloPerfilEdicion.value.trim());

  let indice = 0;
  for (const [tipo, archivo] of Object.entries(documentosEdicion.value)) {
    if (archivo) {
      formData.append(`documentos[${indice}][archivo]`, archivo);
      formData.append(`documentos[${indice}][tipo]`, tipo);
      indice += 1;
    }
  }

  try {
    await tesisStore.actualizarPerfil(tramite.value.id_tramite, formData);
    toastStore.success('Perfil de tesis actualizado: título y documentos guardados.');
    documentosEdicion.value = { nota_solicitud: null, certificado_notas: null, perfil_tesis: null };
    await tramitesStore.cargarTramiteActivo();
    ultimoEstadoVisto = tramite.value?.estado_actual || null;
  } catch (error) {
    toastStore.error('Error al guardar: ' + detalleDeError(error, 'Verifique los datos'));
  }
}

/** Abre el modal de reenvío con el título rechazado a la vista. */
function abrirReenvio() {
  tituloReenvio.value = '';
  archivoReenvio.value = null;
  observacionesReenvio.value = '';
  mostrarReenvio.value = true;
}

// Seguimiento en segundo plano: temporizador del sondeo y último estado visto.
let temporizadorSeguimiento = null;
let sincronizando = false;
let ultimoEstadoVisto = null;

onMounted(async () => {
  cargando.value = true;
  try {
    await Promise.all([
      estudianteStore.cargarPerfil(false),
      tesisStore.cargarConfig(),
      tramitesStore.cargarTramiteActivo(),
    ]);
  } finally {
    cargando.value = false;
  }

  ultimoEstadoVisto = tramite.value?.estado_actual || null;
  iniciarSeguimiento();
});

onUnmounted(() => {
  if (temporizadorSeguimiento) clearInterval(temporizadorSeguimiento);
  document.removeEventListener('visibilitychange', alCambiarVisibilidad);
});

/**
 * Arranca el sondeo periódico del trámite para que el estudiante vea los
 * avances que Kardex registre sin recargar la página. La escucha de
 * `visibilitychange` sincroniza de inmediato al volver a la pestaña.
 */
function iniciarSeguimiento() {
  if (temporizadorSeguimiento) return;
  temporizadorSeguimiento = setInterval(sincronizarTramite, INTERVALO_SEGUIMIENTO);
  document.addEventListener('visibilitychange', alCambiarVisibilidad);
}

/** Sincroniza al regresar a la pestaña solo si estaba oculta. */
function alCambiarVisibilidad() {
  if (document.visibilityState === 'visible') sincronizarTramite();
}

/**
 * Consulta el trámite activo y avisa cuando Kardex cambió su estado. La carga
 * es silenciosa: no mueve el indicador de carga ni descarta lo ya mostrado si
 * la petición falla, y no se solapa si la anterior sigue en curso.
 */
async function sincronizarTramite() {
  if (sincronizando || document.visibilityState === 'hidden') return;
  sincronizando = true;
  try {
    await tramitesStore.cargarTramiteActivo(true);
    const estado = tramite.value?.estado_actual || null;
    if (estado && ultimoEstadoVisto && estado !== ultimoEstadoVisto) {
      toastStore.info(`Kardex actualizó tu tesis: ${formatoEstado(estado)}.`);
    }
    ultimoEstadoVisto = estado;
  } finally {
    sincronizando = false;
  }
}

/** Asocia un archivo seleccionado a su tipo de documento (opcional). */
function handleArchivo(event, tipo) {
  const file = event.target.files[0];
  archivos.value[tipo] = file || null;
}

/**
 * Envía la solicitud de tesis con el título como FormData multipart y, si el
 * estudiante lo adjuntó, los documentos en PDF (son opcionales). El título
 * queda registrado en el perfil para que Kardex verifique su unicidad antes de
 * aprobarlo.
 */
async function enviarSolicitud() {
  if (tituloSolicitud.value.trim().length < 10) {
    toastStore.warning('Ingrese el título de su tesis (mínimo 10 caracteres).');
    return;
  }

  const formData = new FormData();
  const tipos = Object.keys(tesisStore.config.tipos_documento);

  formData.append('titulo', tituloSolicitud.value.trim());
  tipos.forEach((tipo, index) => {
    if (!archivos.value[tipo]) return;
    formData.append(`documentos[${index}][archivo]`, archivos.value[tipo]);
    formData.append(`documentos[${index}][tipo]`, tipo);
  });

  try {
    await tesisStore.crearSolicitud(formData);
    toastStore.success('Solicitud de tesis enviada. Queda pendiente del Consejo Universitario.');
    archivos.value = { nota_solicitud: null, certificado_notas: null, perfil_tesis: null };
    tituloSolicitud.value = '';
    await tramitesStore.cargarTramiteActivo();
    ultimoEstadoVisto = tramite.value?.estado_actual || null;
  } catch (error) {
    toastStore.error('Error al enviar: ' + detalleDeError(error, 'Verifique los datos'));
  }
}

/**
 * Reenvía el perfil corregido con un título diferente al rechazado. El trámite
 * vuelve a la cola de revisión de Kardex.
 */
async function enviarReenvio() {
  if (tituloReenvio.value.trim().length < 10) {
    toastStore.warning('Ingrese el nuevo título de tesis (mínimo 10 caracteres).');
    return;
  }

  const formData = new FormData();
  formData.append('titulo', tituloReenvio.value.trim());
  if (archivoReenvio.value) formData.append('perfil', archivoReenvio.value);
  formData.append('observaciones', observacionesReenvio.value || '');

  try {
    const { data } = await tesisStore.reenviarPerfil(tramite.value.id_tramite, formData);
    tramitesStore.tramiteActivo = data;
    ultimoEstadoVisto = tramite.value?.estado_actual || null;
    mostrarReenvio.value = false;
    archivoReenvio.value = null;
    observacionesReenvio.value = '';
    tituloReenvio.value = '';
    toastStore.success('Perfil reenviado. Queda pendiente del Consejo Universitario.');
  } catch (error) {
    toastStore.error('Error al reenviar: ' + detalleDeError(error, 'Verifique los datos'));
  }
}

/**
 * Reenvía el documento final corregido tras su calificación insuficiente por la
 * Comisión Revisora.
 */
async function enviarReenvioDocumento() {
  if (!archivoReenvioDocumento.value) {
    return toastStore.warning('Debe adjuntar el documento final corregido.');
  }

  const formData = new FormData();
  formData.append('documento_final', archivoReenvioDocumento.value);
  formData.append('observaciones', observacionesReenvioDocumento.value || '');

  try {
    const { data } = await tesisStore.reenviarDocumento(tramite.value.id_tramite, formData);
    tramitesStore.tramiteActivo = data;
    ultimoEstadoVisto = tramite.value?.estado_actual || null;
    mostrarReenvioDocumento.value = false;
    archivoReenvioDocumento.value = null;
    observacionesReenvioDocumento.value = '';
    toastStore.success('Documento corregido enviado. Queda en evaluación de la Comisión Revisora.');
  } catch (error) {
    toastStore.error('Error al enviar: ' + detalleDeError(error, 'Verifique los datos'));
  }
}

/**
 * Envía la solicitud de fecha de defensa a Kardex para su programación.
 */
async function enviarSolicitudFecha() {
  try {
    const { data } = await tesisStore.solicitarFechaDefensa(
      tramite.value.id_tramite,
      fechaSugerida.value || null
    );
    tramitesStore.tramiteActivo = data;
    ultimoEstadoVisto = tramite.value?.estado_actual || null;
    fechaSugerida.value = '';
    toastStore.success('Solicitud de fecha de defensa enviada. Kardex o Secretaría la programará.');
  } catch (error) {
    toastStore.error('Error al solicitar: ' + detalleDeError(error, 'Verifique los datos'));
  }
}

/**
 * Estado visual de una fase del flujo según el estado actual de la tesis.
 *
 * @returns {{contenedor:string, icono:string, texto:string}}
 */
function estadoFase(fase) {
  const actual = faseDe(tramite.value?.estado_actual);

  if (actual.id === fase.id) {
    return {
      contenedor: 'bg-orange ring-orange-500 shadow-lg shadow-orange-900/30',
      icono: 'text-white',
      texto: 'text-white',
    };
  }
  if (actual.index > FASES_TESIS.indexOf(fase)) {
    return {
      contenedor: 'bg-emerald-500/10 ring-emerald-500/30',
      icono: 'text-emerald-400',
      texto: 'text-emerald-300',
    };
  }
  return {
    contenedor: 'bg-white/5 ring-white/10',
    icono: 'text-slate-400',
    texto: 'text-slate-400',
  };
}

/** Etiqueta legible de un tipo de documento subido. */
const ETIQUETAS_DOCUMENTO_FLUJO = {
  resolucion_designacion_tutor: 'Resolución de designación de tutor',
  resolucion_designacion_tribunal: 'Resolución de designación de tribunal revisor',
  nota_suficiencia_tutor: 'Nota de Suficiencia del Tutor',
  nota_suficiencia_presidente: 'Nota de Suficiencia del Presidente',
  nota_suficiencia_vocal: 'Nota de Suficiencia del Vocal',
  nota_suficiencia_secretario: 'Nota de Suficiencia del Secretario',
};

function etiquetaDocumento(tipo) {
  return tesisStore.config.tipos_documento?.[tipo]
    || tesisStore.config.tipos_documento_publicacion?.[tipo]
    || ETIQUETAS_DOCUMENTO_FLUJO[tipo]
    || tipo;
}

/** Formatea una fecha (YYYY-MM-DD) en formato largo en español. */
function formatoFechaLarga(iso) {
  if (!iso) return '—';
  return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  });
}

const archivoPublicacion = ref(null);

function handlePublicacion(event) {
  archivoPublicacion.value = event.target.files[0] || null;
}

async function enviarPublicacion() {
  if (!archivoPublicacion.value) return;
  const formData = new FormData();
  formData.append('documento_publicacion', archivoPublicacion.value);
  try {
    const { data } = await tesisStore.subirPublicacion(tramite.value.id_tramite, formData);
    if (tramite.value) {
      tramite.value = data;
    } else {
      tramitesStore.tramiteActivo = data;
    }
    archivoPublicacion.value = null;
    toastStore.success('Trabajo de investigación enviado. Kardex validará la publicación.');
  } catch (error) {
    toastStore.error('Error al enviar: ' + (error.response?.data?.message || 'Verifique el archivo'));
  }
}
</script>
