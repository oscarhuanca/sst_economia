// ============================================================================
// Datos simulados del Dashboard de KARDEX.
//
// Se usan únicamente como respaldo de demostración: cuando la API de Laravel no
// está disponible (o devuelve un error de servidor), el store conmuta a "modo
// simulado" para poder probar el flujo completo (búsqueda por CI/RU, asignación
// de modalidad, generación de credenciales y avance de estados) sin backend.
// La API real es siempre la fuente de verdad cuando responde.
// ============================================================================

// Secuencia oficial de estados por modalidad (espejo del backend).
export const SECUENCIAS = {
  'Tesis de Grado': [
    'solicitud_presentada',
    'perfil_aprobado',
    'tema_aprobado',
    'tribunal_asignado',
    'defensa_aprobada',
    'publicado',
    'titulado',
  ],
  'Trabajo Dirigido': [
    'solicitud_presentada',
    'perfil_aprobado',
    'tutor_asignado',
    'investigacion_en_desarrollo',
    'documento_final_presentado',
    'comision_revisora',
    'suficiente',
    'defensa_programada',
    'aprobado',
  ],
  'Examen de Grado': [
    'solicitud_presentada',
    'intentos_verificados',
    'habilitado_examen',
    'examen_teorico_sorteado',
    'examen_teorico_aprobado',
    'examen_especialidad_sorteado',
    'notas_habilitadas',
    'aprobado',
    'reporte_examen_generado',
    'titulado',
  ],
  'Excelencia Académica': [
    'solicitud_presentada',
    'promedio_verificado',
    'resolucion_hcc_registrada',
    'titulado',
  ],
};

// Estados siguientes permitidos por estado (espejo del backend).
export const SIGUIENTES_POR_ESTADO = {
  solicitud_presentada: ['pendiente_concejo_universitario', 'perfil_aprobado', 'perfil_rechazado'],
  pendiente_concejo_universitario: ['perfil_aprobado', 'perfil_rechazado'],
  perfil_aprobado: ['tema_aprobado', 'tutor_asignado'],
  perfil_rechazado: ['perfil_aprobado', 'tutor_asignado'],
  tutor_asignado: ['tema_aprobado', 'investigacion_en_desarrollo'],
  tema_aprobado: ['tribunal_asignado', 'investigacion_en_desarrollo'],
  investigacion_en_desarrollo: ['documento_final_presentado', 'tribunal_asignado'],
  documento_final_presentado: ['comision_revisora', 'tribunal_asignado'],
  comision_revisora: ['suficiente', 'insuficiente', 'tribunal_asignado'],
  suficiente: ['solicitud_fecha_defensa', 'tribunal_asignado', 'defensa_aprobada', 'reprobado'],
  insuficiente: ['comision_revisora'],
  solicitud_fecha_defensa: ['defensa_programada', 'defensa_aprobada', 'reprobado'],
  defensa_programada: ['defensa_en_curso', 'defensa_aprobada', 'reprobado'],
  tribunal_asignado: ['defensa_aprobada', 'defensa_programada', 'defensa_en_curso', 'reprobado'],
  defensa_en_curso: ['defensa_aprobada', 'aprobado', 'reprobado'],
  defensa_aprobada: ['publicado', 'aprobado'],
  publicado: ['titulado', 'aprobado'],
  reporte_generado: ['titulado', 'aprobado'],
  correcciones_90_dias: ['solicitud_fecha_defensa', 'reprobado'],

  // --- Examen de Grado (espejo de TramiteStateService::TRANSITIONS) ---
  intentos_verificados: ['habilitado_examen', 'rechazado'],
  habilitado_examen: ['examen_teorico_sorteado'],
  examen_teorico_sorteado: ['examen_teorico_aprobado'],
  examen_teorico_aprobado: ['examen_especialidad_sorteado', 'notas_habilitadas', 'examen_teorico_reprobado'],
  examen_teorico_reprobado: ['habilitado_examen'],
  examen_especialidad_sorteado: ['notas_habilitadas'],
  notas_habilitadas: ['aprobado', 'examen_especialidad_reprobado', 'examen_teorico_reprobado', 'examen_ambos_reprobados'],
  examen_especialidad_reprobado: ['examen_teorico_aprobado'],
  examen_ambos_reprobados: ['intentos_verificados'],
  aprobado: ['reporte_examen_generado'],
  reporte_examen_generado: ['titulado'],

  // --- Excelencia Académica (espejo de TramiteStateService::TRANSITIONS) ---
  promedio_verificado: ['resolucion_hcc_registrada'],
  resolucion_hcc_registrada: ['titulado'],

  reprobado: [],
  rechazado: [],
  titulado: [],
};

// Catálogo de modalidades activas (misma forma que GET /api/modalidades).
export const MODALIDADES_MOCK = [
  { id_modalidad: 1, nombre: 'Examen de Grado', activo: true },
  { id_modalidad: 2, nombre: 'Tesis de Grado', activo: true },
  { id_modalidad: 3, nombre: 'Trabajo Dirigido', activo: true },
  { id_modalidad: 4, nombre: 'Excelencia Académica', activo: true },
];

// Docentes del registro académico (catálogo para asignar tutor, forma de
// GET /api/docentes/catalogo).
export const DOCENTES_MOCK = [
  { id_docente: 1, nombre: 'Julia', nombres: 'Julia', apellidos: 'Paredes Villca', email: 'julia.paredes@upea.edu.bo', materia: null, ci: '1234567' },
  { id_docente: 2, nombre: 'Roberto', nombres: 'Roberto', apellidos: 'Sánchez Calle', email: 'roberto.sanchez@upea.edu.bo', materia: null, ci: '2345678' },
  { id_docente: 3, nombre: 'Elena', nombres: 'Elena', apellidos: 'Aguilar Rojas', email: 'elena.aguilar@upea.edu.bo', materia: null, ci: '3456789' },
];

// Postulantes de prueba con sus trámites (los que tengan `tramite`).
export const POSTULANTES_MOCK = [
  {
    id_estudiante: 101,
    ci: '5445112',
    nombres: 'Ramiro Antonio',
    apellidos: 'Quispe Mamani',
    registro_universitario: 'RU-2020-0087',
    fecha_nacimiento: '2001-09-20',
    email: 'ramiro.q@upea.edu.bo',
    telefono: '71234567',
    promedio_global: 78.5,
    tiene_cuenta: false,
    username: null,
    tramite: null,
  },
  {
    id_estudiante: 102,
    ci: '2273515',
    nombres: 'Anabel R.',
    apellidos: 'Choque Flores',
    registro_universitario: 'RU-2019-0231',
    fecha_nacimiento: '2002-03-14',
    email: 'anabel.c@upea.edu.bo',
    telefono: '68899001',
    promedio_global: 82.1,
    tiene_cuenta: true,
    username: 'Anabel_2273515',
    tramite: null,
  },
  {
    id_estudiante: 103,
    ci: '6654321',
    nombres: 'Carlos Alberto',
    apellidos: 'Mamani Ticona',
    registro_universitario: 'RU-2018-0112',
    fecha_nacimiento: '2000-11-05',
    email: 'carlos.m@upea.edu.bo',
    telefono: '70011223',
    promedio_global: 74.9,
    tiene_cuenta: true,
    username: 'Carlos_6654321',
    tramite: {
      id_tramite: 301,
      id_estudiante: 103,
      id_modalidad: 2,
      id_tutor: 1,
      tutor: { id_docente: 1, nombre: 'Julia', nombres: 'Julia', apellidos: 'Paredes Villca', email: 'julia.paredes@upea.edu.bo', materia: null, ci: '1234567' },
      estado_actual: 'tema_aprobado',
      observaciones: null,
      hitos: {
        perfil_aprobado: '2026-06-10',
        tema_aprobado: '2026-06-24',
        limite_presentacion: '2027-06-10',
        modulos: {
          perfil_tesis: {
            titulo: 'Sistemas de riego eficientes para cultivos de altura en el altiplano boliviano',
            titulo_actualizado: '2026-05-11',
            documentos: {
              nota_solicitud: { marcado: true, archivo: { nombre: 'nota_solicitud_carlos.pdf' } },
              certificado_notas: { marcado: true, archivo: { nombre: 'certificado_notas_carlos.pdf' } },
              perfil_tesis: { marcado: true, archivo: { nombre: 'perfil_tesis_carlos.pdf' } },
            },
            observaciones: 'Documentación verificada y aprobada por Kardex.',
          },
          aprobacion_tema: {
            numero_resolucion: 'HCC-0245/2026',
            fecha_resolucion: '2026-06-24',
            tema_investigacion: 'Sistemas de riego eficientes para cultivos de altura en el altiplano boliviano.',
            tutor_id: 1,
          },
        },
      },
      created_at: '2026-05-11T08:00:00.000000Z',
      updated_at: '2026-09-01T12:30:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 103, ci: '6654321', nombres: 'Carlos Alberto', apellidos: 'Mamani Ticona', registro_universitario: 'RU-2018-0112' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-05-11T08:00:00.000000Z' },
        { nombre_estado: 'perfil_aprobado', descripcion: 'Perfil aprobado', created_at: '2026-06-10T10:00:00.000000Z' },
        { nombre_estado: 'tema_aprobado', descripcion: 'Tema aprobado', created_at: '2026-06-24T11:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['tema_aprobado'],
      secuencia: SECUENCIAS['Tesis de Grado'],
    },
  },
  {
    id_estudiante: 104,
    ci: '3344521',
    nombres: 'María Fernanda',
    apellidos: 'Lima Villanueva',
    registro_universitario: 'RU-2017-0344',
    fecha_nacimiento: '1999-07-22',
    email: 'maria.l@upea.edu.bo',
    telefono: '69887766',
    promedio_global: 86.3,
    tiene_cuenta: true,
    username: 'Maria_3344521',
    tramite: {
      id_tramite: 302,
      id_estudiante: 104,
      id_modalidad: 2,
      id_tutor: 3,
      tutor: { id_docente: 3, nombre: 'Elena', nombres: 'Elena', apellidos: 'Aguilar Rojas', email: 'elena.aguilar@upea.edu.bo', materia: null, ci: '3456789' },
      estado_actual: 'tribunal_asignado',
      observaciones: null,
      hitos: {
        perfil_aprobado: '2026-02-08',
        tema_aprobado: '2026-02-15',
        tribunal_asignado: '2026-08-20',
        modulos: {
          perfil_tesis: {
            titulo: 'Incidencia de la educación digital en el rendimiento académico universitario',
            titulo_actualizado: '2026-01-20',
            documentos: {
              nota_solicitud: { marcado: true, archivo: { nombre: 'nota_solicitud_maria.pdf' } },
              certificado_notas: { marcado: true, archivo: { nombre: 'certificado_notas_maria.pdf' } },
              perfil_tesis: { marcado: true, archivo: { nombre: 'perfil_tesis_maria.pdf' } },
            },
            observaciones: '',
          },
          aprobacion_tema: {
            numero_resolucion: 'HCC-0031/2026',
            fecha_resolucion: '2026-02-15',
            tema_investigacion: 'Incidencia de la educación digital en el rendimiento académico universitario.',
            tutor_id: 3,
          },
          tribunal_revisor: {
            numero_resolucion: 'HCC-0158/2026',
            fecha_resolucion: '2026-08-20',
            tribunal: [
              { rol: 'presidente', nombre: 'Dr. Roberto Sánchez Calle' },
              { rol: 'vocal', nombre: 'Mgr. Julia Paredes Villca' },
              { rol: 'secretario', nombre: 'Lic. Elena Aguilar Rojas' },
            ],
          },
        },
      },
      created_at: '2026-01-20T08:00:00.000000Z',
      updated_at: '2026-09-18T09:00:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 104, ci: '3344521', nombres: 'María Fernanda', apellidos: 'Lima Villanueva', registro_universitario: 'RU-2017-0344' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-01-20T08:00:00.000000Z' },
        { nombre_estado: 'perfil_aprobado', descripcion: 'Perfil aprobado', created_at: '2026-02-08T10:00:00.000000Z' },
        { nombre_estado: 'tema_aprobado', descripcion: 'Tema aprobado', created_at: '2026-02-15T11:00:00.000000Z' },
        { nombre_estado: 'tribunal_asignado', descripcion: 'Tribunal asignado', created_at: '2026-08-20T09:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['tribunal_asignado'],
      secuencia: SECUENCIAS['Tesis de Grado'],
    },
  },
  {
    // Perfil pendiente de revisión: KARDEX debe verificar el título y decidir.
    id_estudiante: 105,
    ci: '7788990',
    nombres: 'Andrea Noelia',
    apellidos: 'Salazar Cruz',
    registro_universitario: 'RU-2019-0257',
    fecha_nacimiento: '2000-03-11',
    email: 'andrea.s@upea.edu.bo',
    telefono: '70112233',
    promedio_global: 81.2,
    tiene_cuenta: true,
    username: 'Andrea_7788990',
    tramite: {
      id_tramite: 303,
      id_estudiante: 105,
      id_modalidad: 2,
      id_tutor: null,
      tutor: null,
      estado_actual: 'pendiente_concejo_universitario',
      observaciones: null,
      hitos: {
        solicitud_presentada: '2026-09-20',
        modulos: {
          perfil_tesis: {
            titulo: 'Uso de imágenes satelitales para el monitoreo desequías en el altiplano',
            titulo_actualizado: '2026-09-20',
          },
        },
      },
      documentos: [
        { id_documento: 1, tipo_documento: 'nota_solicitud', nombre_archivo: 'nota_solicitud_andrea.pdf', ruta_archivo: 'documentos/andrea/nota.pdf', tamanio_kb: 320 },
        { id_documento: 2, tipo_documento: 'certificado_notas', nombre_archivo: 'certificado_notas_andrea.pdf', ruta_archivo: 'documentos/andrea/notas.pdf', tamanio_kb: 180 },
        { id_documento: 3, tipo_documento: 'perfil_tesis', nombre_archivo: 'perfil_andrea.pdf', ruta_archivo: 'documentos/andrea/perfil.pdf', tamanio_kb: 540 },
      ],
      created_at: '2026-09-20T08:00:00.000000Z',
      updated_at: '2026-09-20T08:00:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 105, ci: '7788990', nombres: 'Andrea Noelia', apellidos: 'Salazar Cruz', registro_universitario: 'RU-2019-0257' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-09-20T08:00:00.000000Z' },
        { nombre_estado: 'pendiente_concejo_universitario', descripcion: 'Perfil en revisión por Kardex', created_at: '2026-09-20T08:05:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['pendiente_concejo_universitario'],
      secuencia: SECUENCIAS['Tesis de Grado'],
    },
  },
  {
    // Perfil ya rechazado: el módulo muestra la observación y espera el reenvío.
    id_estudiante: 106,
    ci: '4455667',
    nombres: 'Diego Iván',
    apellidos: 'Rojas Yupanqui',
    registro_universitario: 'RU-2018-0451',
    fecha_nacimiento: '2000-11-05',
    email: 'diego.r@upea.edu.bo',
    telefono: '70223344',
    promedio_global: 70.4,
    tiene_cuenta: true,
    username: 'Diego_4455667',
    tramite: {
      id_tramite: 304,
      id_estudiante: 106,
      id_modalidad: 2,
      id_tutor: null,
      tutor: null,
      estado_actual: 'perfil_rechazado',
      observaciones: 'El título presentado repite el de un trabajo de grado ya registrado.',
      hitos: {
        solicitud_presentada: '2026-09-05',
        perfil_rechazado: '2026-09-12',
        modulos: {
          perfil_tesis: {
            titulo: 'Sistemas de riego eficientes para cultivos de altura en el altiplano boliviano',
            titulo_actualizado: '2026-09-05',
          },
        },
      },
      documentos: [
        { id_documento: 4, tipo_documento: 'nota_solicitud', nombre_archivo: 'nota_solicitud_diego.pdf', ruta_archivo: 'documentos/diego/nota.pdf', tamanio_kb: 300 },
        { id_documento: 5, tipo_documento: 'certificado_notas', nombre_archivo: 'certificado_notas_diego.pdf', ruta_archivo: 'documentos/diego/notas.pdf', tamanio_kb: 190 },
        { id_documento: 6, tipo_documento: 'perfil_tesis', nombre_archivo: 'perfil_diego.pdf', ruta_archivo: 'documentos/diego/perfil.pdf', tamanio_kb: 500 },
      ],
      created_at: '2026-09-05T08:00:00.000000Z',
      updated_at: '2026-09-12T09:00:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 106, ci: '4455667', nombres: 'Diego Iván', apellidos: 'Rojas Yupanqui', registro_universitario: 'RU-2018-0451' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-09-05T08:00:00.000000Z' },
        { nombre_estado: 'pendiente_concejo_universitario', descripcion: 'Perfil en revisión por Kardex', created_at: '2026-09-05T08:05:00.000000Z' },
        { nombre_estado: 'perfil_rechazado', descripcion: 'Perfil rechazado por Kardex', observaciones: 'Existen trabajos de grado registrados con el mismo título de tesis. Debe presentar su perfil nuevamente con un título de tesis diferente.', created_at: '2026-09-12T09:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['perfil_rechazado'],
      secuencia: SECUENCIAS['Tesis de Grado'],
    },
  },
  {
    // Examen de Grado habilitado: los sorteos quedan desbloqueados.
    id_estudiante: 107,
    ci: '8899001',
    nombres: 'Paola Elena',
    apellidos: 'Villarroel Choque',
    registro_universitario: 'RU-2016-0288',
    fecha_nacimiento: '1999-04-22',
    email: 'paola.v@upea.edu.bo',
    telefono: '70334455',
    promedio_global: 76.2,
    tiene_cuenta: true,
    username: 'Paola_8899001',
    numero_intentos: 1,
    tramite: {
      id_tramite: 305,
      id_estudiante: 107,
      id_modalidad: 1,
      id_tutor: null,
      tutor: null,
      estado_actual: 'habilitado_examen',
      observaciones: null,
      hitos: {
        solicitud_presentada: '2026-08-12',
        intentos_verificados: '2026-08-14',
        habilitado_examen: '2026-08-20',
        modulos: {
          habilitacion_examen: {
            numero_resolucion: 'HCC-0315/2026',
            fecha_resolucion: '2026-08-20',
          },
        },
      },
      created_at: '2026-08-12T08:00:00.000000Z',
      updated_at: '2026-08-20T10:15:00.000000Z',
      modalidad: { id_modalidad: 1, nombre: 'Examen de Grado' },
      estudiante: { id_estudiante: 107, ci: '8899001', nombres: 'Paola Elena', apellidos: 'Villarroel Choque', registro_universitario: 'RU-2016-0288', numero_intentos: 1 },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-08-12T08:00:00.000000Z' },
        { nombre_estado: 'intentos_verificados', descripcion: 'Intentos verificados', created_at: '2026-08-14T09:30:00.000000Z' },
        { nombre_estado: 'habilitado_examen', descripcion: 'Estudiante habilitado', created_at: '2026-08-20T10:15:00.000000Z' },
      ],
      historial_examen: [
        { id_historial_examen: 1, nombre_estado: 'intentos_verificados', etapa: 'verificacion_intentos', resultado: 'verificado', numero_resolucion: null, fecha_resolucion: null, tema: null, fecha_examen: null, nota: null, numero_intentos: 1, observaciones: 'Intento 1 de 3.', created_at: '2026-08-14T09:30:00.000000Z' },
        { id_historial_examen: 2, nombre_estado: 'habilitado_examen', etapa: 'habilitacion_examen', resultado: 'habilitado', numero_resolucion: 'HCC-0315/2026', fecha_resolucion: '2026-08-20', tema: null, fecha_examen: null, nota: null, numero_intentos: 1, observaciones: 'Resolución N.º HCC-0315/2026 del HCC.', created_at: '2026-08-20T10:15:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO.habilitado_examen,
      secuencia: SECUENCIAS['Examen de Grado'],
    },
  },
  {
    // Examen de Grado con el TEÓRICO reprobado y la ESPECIALIDAD aprobada: la
    // nota de especialidad se conserva y la re-solicitud reabre solo el teórico.
    id_estudiante: 108,
    ci: '5566778',
    nombres: 'Marcelo Alejandro',
    apellidos: 'Zeballos Nina',
    registro_universitario: 'RU-2015-0190',
    fecha_nacimiento: '1998-07-30',
    email: 'marcelo.z@upea.edu.bo',
    telefono: '70445566',
    promedio_global: 71.8,
    tiene_cuenta: true,
    username: 'Marcelo_5566778',
    numero_intentos: 2,
    tramite: {
      id_tramite: 306,
      id_estudiante: 108,
      id_modalidad: 1,
      id_tutor: null,
      tutor: null,
      estado_actual: 'examen_teorico_reprobado',
      observaciones: 'Reprobó el examen teórico con 48 puntos.',
      hitos: {
        solicitud_presentada: '2026-07-01',
        intentos_verificados: '2026-07-03',
        habilitado_examen: '2026-07-08',
        examen_teorico_reprobado: '2026-08-05',
        modulos: {
          habilitacion_examen: {
            numero_resolucion: 'HCC-0288/2026',
            fecha_resolucion: '2026-07-08',
          },
          sorteo_teorico: {
            asistio: true,
            tema: 'Gestión administrativa del Estado en Bolivia',
            fecha_examen: '2026-08-05',
          },
          resolucion_resultado_teorico: {
            numero_resolucion: 'HCC-0291/2026',
            fecha_resolucion: '2026-08-06',
          },
          sorteo_especialidad: {
            asistio: true,
            tema: 'Ética profesional y normativa universitaria',
            fecha_examen: '2026-08-07',
          },
          resolucion_asignacion_notas: {
            numero_resolucion: 'HCC-0292/2026',
            fecha_resolucion: '2026-08-07',
          },
          asignacion_notas: {
            nota_teorico: '48',
            nota_especialidad: '78',
          },
        },
        // La especialidad quedó aprobada: se conserva para el siguiente intento.
        examen_aprobado: {
          examen_especialidad: {
            nota: 78,
            tema: 'Ética profesional y normativa universitaria',
            fecha_examen: '2026-08-07',
          },
        },
      },
      created_at: '2026-07-01T08:00:00.000000Z',
      updated_at: '2026-08-07T15:30:00.000000Z',
      modalidad: { id_modalidad: 1, nombre: 'Examen de Grado' },
      estudiante: { id_estudiante: 108, ci: '5566778', nombres: 'Marcelo Alejandro', apellidos: 'Zeballos Nina', registro_universitario: 'RU-2015-0190', numero_intentos: 2 },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-07-01T08:00:00.000000Z' },
        { nombre_estado: 'intentos_verificados', descripcion: 'Intentos verificados', created_at: '2026-07-03T09:00:00.000000Z' },
        { nombre_estado: 'habilitado_examen', descripcion: 'Estudiante habilitado', created_at: '2026-07-08T11:20:00.000000Z' },
        { nombre_estado: 'examen_teorico_sorteado', descripcion: 'Examen teórico sorteado', created_at: '2026-08-05T10:00:00.000000Z' },
        { nombre_estado: 'examen_teorico_aprobado', descripcion: 'Resultado del examen teórico aprobado', created_at: '2026-08-06T09:00:00.000000Z' },
        { nombre_estado: 'examen_especialidad_sorteado', descripcion: 'Examen de especialidad sorteado', created_at: '2026-08-07T09:10:00.000000Z' },
        { nombre_estado: 'notas_habilitadas', descripcion: 'Asignación de notas habilitada', created_at: '2026-08-07T10:00:00.000000Z' },
        { nombre_estado: 'examen_teorico_reprobado', descripcion: 'Examen teórico reprobado', observaciones: 'Reprobó el examen teórico con 48 puntos.', created_at: '2026-08-07T15:30:00.000000Z' },
      ],
      historial_examen: [
        { id_historial_examen: 3, nombre_estado: 'intentos_verificados', etapa: 'verificacion_intentos', resultado: 'verificado', numero_resolucion: null, fecha_resolucion: null, tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Intento 2 de 3.', created_at: '2026-07-03T09:00:00.000000Z' },
        { id_historial_examen: 4, nombre_estado: 'habilitado_examen', etapa: 'habilitacion_examen', resultado: 'habilitado', numero_resolucion: 'HCC-0288/2026', fecha_resolucion: '2026-07-08', tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Resolución N.º HCC-0288/2026 del HCC.', created_at: '2026-07-08T11:20:00.000000Z' },
        { id_historial_examen: 5, nombre_estado: 'examen_teorico_sorteado', etapa: 'sorteo_teorico', resultado: 'asistio', numero_resolucion: null, fecha_resolucion: null, tema: 'Gestión administrativa del Estado en Bolivia', fecha_examen: '2026-08-05', nota: null, numero_intentos: 2, observaciones: 'Asistió al sorteo de bolos.', created_at: '2026-08-05T10:00:00.000000Z' },
        { id_historial_examen: 13, nombre_estado: 'examen_teorico_aprobado', etapa: 'resolucion_resultado_teorico', resultado: 'aprobado', numero_resolucion: 'HCC-0291/2026', fecha_resolucion: '2026-08-06', tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Resultado del examen teórico aprobado. Resolución N.º HCC-0291/2026 del HCC.', created_at: '2026-08-06T09:00:00.000000Z' },
        { id_historial_examen: 6, nombre_estado: 'examen_especialidad_sorteado', etapa: 'sorteo_especialidad', resultado: 'asistio', numero_resolucion: null, fecha_resolucion: null, tema: 'Ética profesional y normativa universitaria', fecha_examen: '2026-08-07', nota: null, numero_intentos: 2, observaciones: 'Asistió al sorteo de bolos.', created_at: '2026-08-07T09:10:00.000000Z' },
        { id_historial_examen: 15, nombre_estado: 'notas_habilitadas', etapa: 'resolucion_asignacion_notas', resultado: 'resolucion_registrada', numero_resolucion: 'HCC-0292/2026', fecha_resolucion: '2026-08-07', tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Resolución de asignación de notas N.º HCC-0292/2026 del HCC.', created_at: '2026-08-07T10:00:00.000000Z' },
        { id_historial_examen: 7, nombre_estado: 'examen_teorico_reprobado', etapa: 'asignacion_notas', resultado: 'reprobado', numero_resolucion: null, fecha_resolucion: null, tema: null, fecha_examen: null, nota: '48.00', numero_intentos: 2, observaciones: 'Asignación de notas: teórico 48 (mínimo 51) y especialidad 78 (mínimo 51).', created_at: '2026-08-07T15:30:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO.examen_teorico_reprobado,
      secuencia: SECUENCIAS['Examen de Grado'],
    },
  },
  {
    // Examen de Grado con AMBOS exámenes reprobados: la re-solicitud exige una
    // NUEVA Resolución del HCC y rendir de nuevo los dos exámenes.
    id_estudiante: 109,
    ci: '1234887',
    nombres: 'Luis Fernando',
    apellidos: 'Apaza Condori',
    registro_universitario: 'RU-2014-0405',
    fecha_nacimiento: '1997-02-14',
    email: 'luis.a@upea.edu.bo',
    telefono: '70556677',
    promedio_global: 68.5,
    tiene_cuenta: true,
    username: 'Luis_1234887',
    numero_intentos: 2,
    tramite: {
      id_tramite: 307,
      id_estudiante: 109,
      id_modalidad: 1,
      id_tutor: null,
      tutor: null,
      estado_actual: 'examen_ambos_reprobados',
      observaciones: 'Reprobó ambos exámenes.',
      hitos: {
        solicitud_presentada: '2026-06-10',
        intentos_verificados: '2026-06-12',
        habilitado_examen: '2026-06-18',
        examen_ambos_reprobados: '2026-07-20',
        modulos: {
          habilitacion_examen: {
            numero_resolucion: 'HCC-0210/2026',
            fecha_resolucion: '2026-06-18',
          },
          sorteo_teorico: {
            asistio: true,
            tema: 'Derecho administrativo boliviano',
            fecha_examen: '2026-07-15',
          },
          resolucion_resultado_teorico: {
            numero_resolucion: 'HCC-0213/2026',
            fecha_resolucion: '2026-07-16',
          },
          sorteo_especialidad: {
            asistio: true,
            tema: 'Legislación universitaria y ética profesional',
            fecha_examen: '2026-07-17',
          },
          resolucion_asignacion_notas: {
            numero_resolucion: 'HCC-0214/2026',
            fecha_resolucion: '2026-07-17',
          },
          asignacion_notas: {
            nota_teorico: '45',
            nota_especialidad: '38',
          },
        },
      },
      created_at: '2026-06-10T08:00:00.000000Z',
      updated_at: '2026-07-20T16:00:00.000000Z',
      modalidad: { id_modalidad: 1, nombre: 'Examen de Grado' },
      estudiante: { id_estudiante: 109, ci: '1234887', nombres: 'Luis Fernando', apellidos: 'Apaza Condori', registro_universitario: 'RU-2014-0405', numero_intentos: 2 },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-06-10T08:00:00.000000Z' },
        { nombre_estado: 'intentos_verificados', descripcion: 'Intentos verificados', created_at: '2026-06-12T09:00:00.000000Z' },
        { nombre_estado: 'habilitado_examen', descripcion: 'Estudiante habilitado', created_at: '2026-06-18T10:00:00.000000Z' },
        { nombre_estado: 'examen_teorico_sorteado', descripcion: 'Examen teórico sorteado', created_at: '2026-07-15T10:00:00.000000Z' },
        { nombre_estado: 'examen_teorico_aprobado', descripcion: 'Resultado del examen teórico aprobado', created_at: '2026-07-16T09:00:00.000000Z' },
        { nombre_estado: 'examen_especialidad_sorteado', descripcion: 'Examen de especialidad sorteado', created_at: '2026-07-17T09:10:00.000000Z' },
        { nombre_estado: 'notas_habilitadas', descripcion: 'Asignación de notas habilitada', created_at: '2026-07-17T10:00:00.000000Z' },
        { nombre_estado: 'examen_ambos_reprobados', descripcion: 'Ambos exámenes reprobados', observaciones: 'Reprobó ambos exámenes.', created_at: '2026-07-20T16:00:00.000000Z' },
      ],
      historial_examen: [
        { id_historial_examen: 8, nombre_estado: 'intentos_verificados', etapa: 'verificacion_intentos', resultado: 'verificado', numero_resolucion: null, fecha_resolucion: null, tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Intento 2 de 3.', created_at: '2026-06-12T09:00:00.000000Z' },
        { id_historial_examen: 9, nombre_estado: 'habilitado_examen', etapa: 'habilitacion_examen', resultado: 'habilitado', numero_resolucion: 'HCC-0210/2026', fecha_resolucion: '2026-06-18', tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Resolución N.º HCC-0210/2026 del HCC.', created_at: '2026-06-18T10:00:00.000000Z' },
        { id_historial_examen: 10, nombre_estado: 'examen_teorico_sorteado', etapa: 'sorteo_teorico', resultado: 'asistio', numero_resolucion: null, fecha_resolucion: null, tema: 'Derecho administrativo boliviano', fecha_examen: '2026-07-15', nota: null, numero_intentos: 2, observaciones: 'Asistió al sorteo de bolos.', created_at: '2026-07-15T10:00:00.000000Z' },
        { id_historial_examen: 14, nombre_estado: 'examen_teorico_aprobado', etapa: 'resolucion_resultado_teorico', resultado: 'aprobado', numero_resolucion: 'HCC-0213/2026', fecha_resolucion: '2026-07-16', tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Resultado del examen teórico aprobado. Resolución N.º HCC-0213/2026 del HCC.', created_at: '2026-07-16T09:00:00.000000Z' },
        { id_historial_examen: 11, nombre_estado: 'examen_especialidad_sorteado', etapa: 'sorteo_especialidad', resultado: 'asistio', numero_resolucion: null, fecha_resolucion: null, tema: 'Legislación universitaria y ética profesional', fecha_examen: '2026-07-17', nota: null, numero_intentos: 2, observaciones: 'Asistió al sorteo de bolos.', created_at: '2026-07-17T09:10:00.000000Z' },
        { id_historial_examen: 16, nombre_estado: 'notas_habilitadas', etapa: 'resolucion_asignacion_notas', resultado: 'resolucion_registrada', numero_resolucion: 'HCC-0214/2026', fecha_resolucion: '2026-07-17', tema: null, fecha_examen: null, nota: null, numero_intentos: 2, observaciones: 'Resolución de asignación de notas N.º HCC-0214/2026 del HCC.', created_at: '2026-07-17T10:00:00.000000Z' },
        { id_historial_examen: 12, nombre_estado: 'examen_ambos_reprobados', etapa: 'asignacion_notas', resultado: 'reprobado', numero_resolucion: null, fecha_resolucion: null, tema: null, fecha_examen: null, nota: '45.00', numero_intentos: 2, observaciones: 'Asignación de notas: teórico 45 (mínimo 51) y especialidad 38 (mínimo 51).', created_at: '2026-07-20T16:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO.examen_ambos_reprobados,
      secuencia: SECUENCIAS['Examen de Grado'],
    },
  },
  {
    // Excelencia Académica: la solicitud está presentada y KARDEX aún no
    // verificó el promedio general del estudiante.
    id_estudiante: 110,
    ci: '9081726',
    nombres: 'Andrea Nataly',
    apellidos: 'Ccori Mamani',
    registro_universitario: 'RU-2017-0334',
    fecha_nacimiento: '2000-05-11',
    email: 'andrea.c@upea.edu.bo',
    telefono: '70667788',
    promedio_global: 88.4,
    tiene_cuenta: true,
    username: 'Andrea_9081726',
    tramite: {
      id_tramite: 308,
      id_estudiante: 110,
      id_modalidad: 4,
      id_tutor: null,
      tutor: null,
      estado_actual: 'solicitud_presentada',
      observaciones: null,
      hitos: {
        solicitud_presentada: '2026-09-18',
        modulos: {
          verificacion_promedio: {
            promedio_declarado: 88.4,
            nota_minima: 80,
          },
        },
      },
      created_at: '2026-09-18T08:00:00.000000Z',
      updated_at: '2026-09-18T08:00:00.000000Z',
      modalidad: { id_modalidad: 4, nombre: 'Excelencia Académica' },
      estudiante: { id_estudiante: 110, ci: '9081726', nombres: 'Andrea Nataly', apellidos: 'Ccori Mamani', registro_universitario: 'RU-2017-0334', promedio_global: 88.4 },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-09-18T08:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['solicitud_presentada'],
      secuencia: SECUENCIAS['Excelencia Académica'],
    },
  },
];

// Estrategia de búsqueda por CI o R.U. (mismo criterio que el backend).
export function encontrarPostulante(identificador) {
  const clave = String(identificador || '').trim().toLowerCase();
  return POSTULANTES_MOCK.find(
    (p) =>
      String(p.ci).toLowerCase() === clave ||
      String(p.registro_universitario).toLowerCase() === clave,
  ) || null;
}

function clonar(valor) {
  return JSON.parse(JSON.stringify(valor));
}

export function usernameDeMock(p) {
  const primerNombre = String(p.nombres || '').trim().split(' ')[0] || 'Estudiante';
  return `${primerNombre.charAt(0).toUpperCase()}${primerNombre.slice(1).toLowerCase()}_${p.ci}`;
}

export function passwordDeMock(p) {
  return String(p.fecha_nacimiento || '').split('-').reverse().join('-');
}

// Simula POST /kardex/postulantes/buscar.
export function buscarMock(identificador) {
  const p = encontrarPostulante(identificador);
  if (!p) return null;
  return { postulante: clonar(p) };
}

// Simula GET /kardex/postulantes/consultar.
export function consultarMock(identificador) {
  const p = encontrarPostulante(identificador);
  if (!p) return null;
  return { postulante: clonar(p), tramite: p.tramite ? clonar(p.tramite) : null };
}

// Simula POST /kardex/postulantes/asignar-modalidad.
export function asignarMock(identificador, idModalidad) {
  const p = encontrarPostulante(identificador);
  if (!p) return null;

  const modalidad = MODALIDADES_MOCK.find((m) => m.id_modalidad === Number(idModalidad));

  if (p.tramite) {
    throw { message: 'El postulante ya tiene un trámite en curso para esta modalidad.' };
  }

  const credenciales = p.tiene_cuenta
    ? { generadas: false, ya_existia: true, username: p.username, password: null }
    : { generadas: true, ya_existia: false, username: usernameDeMock(p), password: passwordDeMock(p) };

  const tramite = {
    id_tramite: 900 + p.id_estudiante,
    id_estudiante: p.id_estudiante,
    id_modalidad: Number(idModalidad),
    id_tutor: null,
    estado_actual: 'solicitud_presentada',
    observaciones: null,
    hitos: {},
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    modalidad: { id_modalidad: modalidad.id_modalidad, nombre: modalidad.nombre },
    estudiante: { id_estudiante: p.id_estudiante, ci: p.ci, nombres: p.nombres, apellidos: p.apellidos, registro_universitario: p.registro_universitario },
    estados: [
      { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: new Date().toISOString() },
    ],
    siguientes_estados: SIGUIENTES_POR_ESTADO['solicitud_presentada'],
    secuencia: SECUENCIAS[modalidad.nombre] || SECUENCIAS['Tesis de Grado'],
  };

  return {
    postulante: { ...clonar(p), tiene_cuenta: true, username: credenciales.username, tramite: clonar(tramite) },
    credenciales,
    tramite,
  };
}

// Simula POST /tramites/{id}/transicionar (avance de estados).
export function transicionarMock(tramite, nuevoEstado, observaciones) {
  const t = clonar(tramite);
  t.estado_actual = nuevoEstado;
  t.observaciones = observaciones || t.observaciones;
  t.updated_at = new Date().toISOString();
  t.estados = [...(t.estados || []), { nombre_estado: nuevoEstado, descripcion: nuevoEstado, created_at: new Date().toISOString() }];
  t.siguientes_estados = SIGUIENTES_POR_ESTADO[nuevoEstado] || [];
  if (['aprobado', 'reprobado', 'rechazado'].includes(nuevoEstado)) t.hitos = { ...(t.hitos || {}), [nuevoEstado]: new Date().toISOString().slice(0, 10) };
  if (nuevoEstado === 'tutor_asignado') t.hitos = { ...(t.hitos || {}), tutor_asignado: new Date().toISOString().slice(0, 10) };
  return t;
}

// Simula POST /tramites/{id}/asignar-tutor (asignación de tutor docente).
export function asignarTutorMock(tramite, idTutor) {
  const t = clonar(tramite);
  const tutor = DOCENTES_MOCK.find((d) => d.id_docente === Number(idTutor));
  if (!tutor) {
    throw { message: 'El tutor seleccionado no existe o no es un docente válido.' };
  }
  t.id_tutor = tutor.id_docente;
  t.tutor = { ...tutor };
  t.hitos = { ...(t.hitos || {}), tutor_asignado: new Date().toISOString().slice(0, 10) };
  t.updated_at = new Date().toISOString();
  return t;
}

// Estado objetivo de cada módulo del flujo de titulación (espejo del backend).
const ESTADOS_MODULOS = {
  perfil_tesis: 'perfil_aprobado',
  perfil_proyecto: 'perfil_aprobado',
  aprobacion_tema: 'tema_aprobado',
  tribunal_revisor: 'tribunal_asignado',
  defensa: 'defensa_aprobada',
  reporte: 'titulado',
  publicacion: 'publicado',
};

// Estado al que pasa el trámite cuando KARDEX rechaza el perfil (el estudiante
// debe presentar otro perfil con un título diferente).
const PERFIL_RECHAZADO = 'perfil_rechazado';

// Nota mínima para aprobar la defensa (espejo de `tesis.nota_minima_defensa`).
const NOTA_MINIMA_DEFENSA = 51;

// Simula POST /tramites/{id}/flujo/{modulo} (guardado de un módulo del flujo).
export function aplicarModuloMock(tramite, moduloId, datos) {
  const t = clonar(tramite);
  // El rechazo del perfil aplica a cualquier modalidad (perfil de tesis o
  // perfil de proyecto): el estudiante debe presentar otro perfil.
  const rechaza = moduloId.startsWith('perfil_') && datos.accion === 'rechazar';
  // La defensa con nota por debajo del mínimo deja el trámite REPROBADO
  // (estado terminal): el flujo termina aquí (espejo del TramiteService).
  const paso = Number(datos.paso ?? 2);
  const reprobada = moduloId === 'defensa'
    && paso !== 1
    && datos.nota_final !== ''
    && datos.nota_final !== null
    && datos.nota_final !== undefined
    && !Number.isNaN(Number(datos.nota_final))
    && Number(datos.nota_final) < NOTA_MINIMA_DEFENSA;
  const estado = rechaza
    ? PERFIL_RECHAZADO
    : (reprobada ? 'reprobado' : ESTADOS_MODULOS[moduloId]);
  if (!estado) {
    throw { message: 'El módulo del flujo no es reconocido.' };
  }
  const hoy = new Date().toISOString().slice(0, 10);
  // La defensa se registra en dos pasos. La clave `paso` no se persiste (espejo
  // del TramiteService): el paso 1 guarda los datos sin transicionar el estado.
  const { paso: _paso, accion: _accion, ...datosGuardar } = datos;
  t.updated_at = new Date().toISOString();
  t.hitos = { ...(t.hitos || {}) };

  // Al rechazar el perfil se conserva lo que el estudiante presentó: solo se
  // registra la decisión (espejo del TramiteService).
  if (!rechaza) {
    t.hitos.modulos = {
      ...(t.hitos.modulos || {}),
      [moduloId]: { ...(t.hitos.modulos?.[moduloId] || {}), ...datosGuardar },
    };
  }

  if (moduloId === 'defensa' && paso === 1) {
    return t;
  }
  t.estado_actual = estado;
  t.hitos[estado] = hoy;
  const observacion = reprobada
    ? `Defensa reprobada con nota ${datos.nota_final} (mínimo de aprobación: ${NOTA_MINIMA_DEFENSA}). `
      + 'El flujo de titulación finaliza; el estudiante deberá volver a solicitar la '
      + 'modalidad de graduación.'
    : null;
  if (rechaza) {
    t.observaciones = datos.observaciones || t.observaciones;
  } else if (moduloId === 'aprobacion_tema' && datos.tutor_id) {
    const tutor = DOCENTES_MOCK.find((d) => d.id_docente === Number(datos.tutor_id));
    if (tutor) {
      t.id_tutor = tutor.id_docente;
      t.tutor = { ...tutor };
    }
  }
  t.estados = [
    ...(t.estados || []),
    {
      nombre_estado: estado,
      descripcion: estado,
      observaciones: rechaza ? datos.observaciones || null : observacion,
      created_at: new Date().toISOString(),
    },
  ];
  t.siguientes_estados = SIGUIENTES_POR_ESTADO[estado] || [];
  return t;
}

// ============================================================================
// Examen de Grado
//
// Espejo de ExamenService: cada módulo deja una fila en `historial_examen`, que
// es la fuente de verdad del avance del panel. Los sorteos son de un solo paso
// (asistencia/tema/fecha); después se registran las Resoluciones de resultado
// teórico y de asignación de notas, y finalmente las notas se asignan en
// `asignacion_notas`, donde se decide si cada examen fue aprobado o reprobado.
// ============================================================================

const ESTADOS_MODULOS_EXAMEN = {
  verificacion_intentos: 'intentos_verificados',
  habilitacion_examen: 'habilitado_examen',
  resolucion_resultado_teorico: 'examen_teorico_aprobado',
  resolucion_asignacion_notas: 'notas_habilitadas',
  reporte: 'titulado',
};

const ESTADOS_REAPERIBLES = [
  'examen_teorico_reprobado',
  'examen_especialidad_reprobado',
  'examen_ambos_reprobados',
];

/** Estado de la reapertura según el examen reprobado (espejo del backend). */
const REAPERTURA_POR_ESTADO = {
  examen_teorico_reprobado: {
    estado: 'habilitado_examen',
    resultado: 're_solicitud_teorico',
    limpiar: ['sorteo_teorico', 'resolucion_resultado_teorico'],
  },
  examen_especialidad_reprobado: {
    estado: 'examen_teorico_aprobado',
    resultado: 're_solicitud_especialidad',
    limpiar: ['sorteo_especialidad'],
  },
  examen_ambos_reprobados: {
    estado: 'intentos_verificados',
    resultado: 're_solicitud',
    limpiar: ['sorteo_teorico', 'resolucion_resultado_teorico', 'sorteo_especialidad', 'habilitacion_examen'],
  },
};

const MAXIMO_INTENTOS_MOCK = 3;
const NOTA_MINIMA_MOCK = 51;
// Mínimo de promedio exigido por la modalidad de Excelencia Académica.
const NOTA_MINIMA_EXCELENCIA_MOCK = 80;

/** Resuelve el estado y el resultado de un sorteo a partir de sus datos (1 paso). */
function destinoSorteoMock(datos, prefijo) {
  const estado = `examen_${prefijo}_sorteado`;
  return {
    estado,
    resultado: datos.asistio ? 'asistio' : 'no_asistio',
  };
}

/**
 * Resultado de `asignacion_notas` (espejo del backend).
 *
 * Cada examen se resuelve con dos fuentes posibles: la nota conservada de un
 * intento anterior (`hitos.examen_aprobado`) o la del intento en curso. Si
 * ninguno llega al mínimo, el trámite pasa a `examen_ambos_reprobados`.
 */
function destinoNotasMock(tramite, datos) {
  const filas = tramite.historial_examen || [];
  let corte = -1;
  filas.forEach((f, i) => {
    if (f.etapa === 'reapertura') corte = i;
  });
  const delIntento = corte === -1 ? filas : filas.slice(corte + 1);
  const ultimoRegistro = (etapa) =>
    [...delIntento]
      .filter((f) => f.etapa === etapa)
      .sort((a, b) => (b.id_historial_examen || 0) - (a.id_historial_examen || 0))[0];

  if (!ultimoRegistro('resolucion_asignacion_notas')) {
    throw { message: 'Registre la Resolución de asignación de notas antes de asignar las notas.' };
  }

  const aprobados = tramite.hitos?.examen_aprobado || {};

  const etiquetaDe = (prefijo) => (prefijo === 'examen_teorico' ? 'el examen teórico' : 'el examen de especialidad');

  const resolver = (prefijo, etapa, campoNota) => {
    if (aprobados[prefijo]) {
      return { aprobado: true, nota: Number(aprobados[prefijo].nota), entrada: aprobados[prefijo] };
    }

    const sorteo = ultimoRegistro(etapa);
    if (!sorteo) {
      throw { message: `Registre primero el sorteo de ${etiquetaDe(prefijo)} antes de asignar la nota.` };
    }
    if (sorteo.resultado === 'no_asistio') {
      return { aprobado: false, nota: null, entrada: null };
    }

    const nota = Number(datos[campoNota]);
    if (!Number.isFinite(nota)) {
      throw { message: `Ingrese la nota de ${etiquetaDe(prefijo)}.` };
    }

    return {
      aprobado: nota >= NOTA_MINIMA_MOCK,
      nota,
      entrada: { nota, tema: sorteo.tema, fecha_examen: sorteo.fecha_examen },
    };
  };

  const teorico = resolver('examen_teorico', 'sorteo_teorico', 'nota_teorico');
  const especialidad = resolver('examen_especialidad', 'sorteo_especialidad', 'nota_especialidad');

  let estado;
  if (teorico.aprobado && especialidad.aprobado) {
    estado = 'aprobado';
  } else if (!teorico.aprobado && !especialidad.aprobado) {
    estado = 'examen_ambos_reprobados';
  } else if (!teorico.aprobado) {
    estado = 'examen_teorico_reprobado';
  } else {
    estado = 'examen_especialidad_reprobado';
  }

  // Solo los exámenes APROBADOS de la resolución en curso se conservan para el
  // intento siguiente; el reprobado se vuelve a rendir.
  const examenesAprobados = {};
  if (teorico.aprobado) examenesAprobados.examen_teorico = teorico.entrada;
  if (especialidad.aprobado) examenesAprobados.examen_especialidad = especialidad.entrada;

  return {
    estado,
    resultado: estado === 'aprobado' ? 'aprobado' : 'reprobado',
    notas: { examen_teorico: teorico.nota, examen_especialidad: especialidad.nota },
    examenesAprobados,
  };
}

/**
 * Simula POST /tramites/{id}/examen/{modulo}.
 *
 * @param {Object} tramite   Trámite de Examen de Grado.
 * @param {string} moduloId  Identificador del módulo.
 * @param {Object} datos     Datos del formulario.
 */
export function guardarModuloExamenMock(tramite, moduloId, datos) {
  const t = clonar(tramite);
  const hoy = new Date().toISOString();
  const idSiguiente = (t.historial_examen?.length || 0) + 1;
  const usados = Number(t.estudiante?.numero_intentos ?? 0);

  let destino;
  if (moduloId === 'verificacion_intentos') {
    if (datos.accion === 'rechazar') {
      if (usados < MAXIMO_INTENTOS_MOCK) {
        throw {
          message: `El estudiante lleva ${usados} de ${MAXIMO_INTENTOS_MOCK} intentos permitidos. `
            + 'Solo se rechaza la solicitud cuando los intentos están agotados.',
        };
      }
      destino = { estado: 'rechazado', resultado: 'rechazado' };
    } else {
      if (usados >= MAXIMO_INTENTOS_MOCK) {
        throw { message: `El estudiante agotó los ${MAXIMO_INTENTOS_MOCK} intentos permitidos.` };
      }
      destino = { estado: 'intentos_verificados', resultado: 'verificado' };
    }
  } else if (moduloId === 'habilitacion_examen') {
    destino = { estado: 'habilitado_examen', resultado: 'habilitado' };
  } else if (moduloId === 'sorteo_teorico') {
    destino = destinoSorteoMock(datos, 'teorico');
  } else if (moduloId === 'resolucion_resultado_teorico') {
    if (!String(datos.numero_resolucion || '').trim()) {
      throw { message: 'Ingrese el número de Resolución del HCC.' };
    }
    if (!datos.fecha_resolucion) {
      throw { message: 'Seleccione la fecha de la Resolución.' };
    }
    destino = { estado: 'examen_teorico_aprobado', resultado: 'aprobado' };
  } else if (moduloId === 'resolucion_asignacion_notas') {
    if (!String(datos.numero_resolucion || '').trim()) {
      throw { message: 'Ingrese el número de Resolución de asignación de notas.' };
    }
    if (!datos.fecha_resolucion) {
      throw { message: 'Seleccione la fecha de la Resolución de asignación de notas.' };
    }

    const filas = t.historial_examen || [];
    let corte = -1;
    filas.forEach((f, i) => {
      if (f.etapa === 'reapertura') corte = i;
    });
    const delIntento = corte === -1 ? filas : filas.slice(corte + 1);
    const aprobados = t.hitos?.examen_aprobado || {};
    const examenes = [
      ['examen_teorico', 'sorteo_teorico', 'el examen teórico'],
      ['examen_especialidad', 'sorteo_especialidad', 'el examen de especialidad'],
    ];

    examenes.forEach(([prefijo, etapa, etiqueta]) => {
      if (aprobados[prefijo]) return;
      if (!delIntento.some((f) => f.etapa === etapa)) {
        throw {
          message: `Registre primero el sorteo de ${etiqueta} antes de la Resolución de asignación de notas.`,
        };
      }
    });

    destino = { estado: 'notas_habilitadas', resultado: 'resolucion_registrada' };
  } else if (moduloId === 'sorteo_especialidad') {
    const filas = t.historial_examen || [];
    let corte = -1;
    filas.forEach((f, i) => {
      if (f.etapa === 'reapertura') corte = i;
    });
    const delIntento = corte === -1 ? filas : filas.slice(corte + 1);
    const teoricoAprobado = Boolean(t.hitos?.examen_aprobado?.examen_teorico)
      || delIntento.some(
        (f) => f.etapa === 'resolucion_resultado_teorico' && f.resultado === 'aprobado',
      );

    if (!teoricoAprobado) {
      throw {
        message: 'Registre la Resolución de aprobación del resultado del examen teórico '
          + 'antes de sortear el examen de especialidad.',
      };
    }
    destino = destinoSorteoMock(datos, 'especialidad');
  } else if (moduloId === 'asignacion_notas') {
    destino = destinoNotasMock(t, datos);
  } else if (moduloId === 'reporte') {
    destino = { estado: 'titulado', resultado: 'titulado' };
  } else {
    throw { message: 'El módulo del flujo de examen no es reconocido.' };
  }

  const esSorteo = moduloId === 'sorteo_teorico' || moduloId === 'sorteo_especialidad';
  const esAsignacionNotas = moduloId === 'asignacion_notas';
  const guardado = { ...(t.hitos?.modulos?.[moduloId] || {}), ...datos };

  // La nota de un examen conservado la resuelve el backend (la trae de
  // `hitos.examen_aprobado`); se integra a los datos para que la tarjeta del
  // módulo quede completa, igual que en ExamenService.
  if (esAsignacionNotas) {
    guardado.nota_teorico = destino.notas?.examen_teorico ?? '';
    guardado.nota_especialidad = destino.notas?.examen_especialidad ?? '';
  }

  t.updated_at = hoy;
  t.estado_actual = destino.estado;
  t.observaciones = datos.observaciones || t.observaciones;
  t.hitos = { ...(t.hitos || {}) };
  t.hitos.modulos = { ...(t.hitos.modulos || {}), [moduloId]: guardado };
  // Solo los exámenes aprobados de la resolución en curso se conservan.
  if (esAsignacionNotas && Object.keys(destino.examenesAprobados || {}).length) {
    t.hitos.examen_aprobado = destino.examenesAprobados;
  }
  t.estados = [
    ...(t.estados || []),
    { nombre_estado: destino.estado, descripcion: destino.estado, observaciones: null, created_at: hoy },
  ];
  t.historial_examen = [
    ...(t.historial_examen || []),
    {
      id_historial_examen: idSiguiente,
      nombre_estado: destino.estado,
      etapa: moduloId,
      resultado: destino.resultado,
      numero_resolucion: ['habilitacion_examen', 'resolucion_resultado_teorico', 'resolucion_asignacion_notas'].includes(moduloId)
        ? datos.numero_resolucion || null
        : null,
      fecha_resolucion: ['habilitacion_examen', 'resolucion_resultado_teorico', 'resolucion_asignacion_notas'].includes(moduloId)
        ? datos.fecha_resolucion || null
        : null,
      tema: esSorteo ? guardado.tema || null : null,
      fecha_examen: esSorteo ? guardado.fecha_examen || null : null,
      nota: esAsignacionNotas
        ? String(destino.notas?.examen_teorico ?? destino.notas?.examen_especialidad ?? '')
        : null,
      numero_intentos: usados,
      observaciones: datos.observaciones || null,
      created_at: hoy,
    },
  ];
  t.siguientes_estados = SIGUIENTES_POR_ESTADO[destino.estado] || [];
  t.secuencia = SECUENCIAS['Examen de Grado'];
  return t;
}

/**
 * Simula POST /tramites/{id}/excelencia/{modulo}.
 *
 * El flujo de Excelencia Académica es lineal: verificación del promedio y
 * Resolución de admisión (única bifurcación, que puede dejar la solicitud
 * RECHAZADA), registro de la Resolución del HCC con la lectura del acta y
 * reporte final, que titula al estudiante.
 *
 * @param {Object} tramite   Trámite de Excelencia Académica.
 * @param {string} moduloId  Identificador del módulo.
 * @param {Object} datos     Datos del formulario (incluye metadatos de PDFs).
 */
export function guardarModuloExcelenciaMock(tramite, moduloId, datos) {
  const t = clonar(tramite);
  const hoy = new Date().toISOString();
  const guardado = { ...(t.hitos?.modulos?.[moduloId] || {}), ...datos };

  let destino;
  if (moduloId === 'verificacion_promedio') {
    const nota = Number(datos.promedio);
    const resultado = datos.resultado;

    if (!Number.isFinite(nota) || nota < 0 || nota > 100) {
      throw { message: 'Ingrese un promedio verificado entre 0 y 100.' };
    }
    if (resultado !== 'aceptado' && resultado !== 'rechazado') {
      throw { message: 'Seleccione si la solicitud fue ACEPTADA o RECHAZADA.' };
    }
    if (!String(datos.numero_resolucion || '').trim() || !datos.fecha_resolucion) {
      throw { message: 'Registre el número y la fecha de la Resolución del HCC.' };
    }
    if (datos.confirmado !== true) {
      throw { message: 'Marque la verificación del promedio y de la Resolución del HCC.' };
    }
    if (resultado === 'aceptado' && nota < NOTA_MINIMA_EXCELENCIA_MOCK) {
      throw {
        message: `No es posible ACEPTAR la solicitud: el promedio ${nota} es inferior al mínimo de ${NOTA_MINIMA_EXCELENCIA_MOCK}.`,
      };
    }

    Object.assign(guardado, {
      promedio: nota,
      nota_minima: NOTA_MINIMA_EXCELENCIA_MOCK,
      aprobado: resultado === 'aceptado',
      verificado: true,
      fecha_verificacion: hoy.slice(0, 10),
    });

    destino = resultado === 'aceptado'
      ? { estado: 'promedio_verificado', resultado: 'aceptado' }
      : { estado: 'rechazado', resultado: 'rechazado' };
  } else if (moduloId === 'resolucion_hcc') {
    if (
      !String(datos.numero_resolucion || '').trim()
      || !datos.fecha_resolucion
      || !datos.fecha_lectura_acta
      || datos.confirmado !== true
    ) {
      throw { message: 'Registre la Resolución, la fecha de lectura del acta y marque la verificación.' };
    }

    Object.assign(guardado, { verificado: true, fecha_verificacion: hoy.slice(0, 10) });
    destino = { estado: 'resolucion_hcc_registrada', resultado: 'resolucion_registrada' };
  } else if (moduloId === 'reporte') {
    const verificacion = t.hitos?.modulos?.verificacion_promedio || {};
    const resolucion = t.hitos?.modulos?.resolucion_hcc || {};

    if (verificacion.promedio === undefined) {
      throw { message: 'Registre primero la verificación del promedio del estudiante.' };
    }
    if (!resolucion.fecha_lectura_acta) {
      throw { message: 'Registre la fecha de lectura del acta antes de emitir el título.' };
    }

    destino = { estado: 'titulado', resultado: 'titulado' };
  } else {
    throw { message: 'El módulo del flujo de Excelencia Académica no es reconocido.' };
  }

  t.updated_at = hoy;
  t.estado_actual = destino.estado;
  t.observaciones = datos.observaciones || t.observaciones;
  t.hitos = { ...(t.hitos || {}) };
  t.hitos.modulos = { ...(t.hitos.modulos || {}), [moduloId]: guardado };
  // El hito del estado alcanzado queda fechado para el seguimiento.
  t.hitos[destino.estado] = hoy.slice(0, 10);
  t.estados = [
    ...(t.estados || []),
    { nombre_estado: destino.estado, descripcion: destino.estado, observaciones: datos.observaciones || null, created_at: hoy },
  ];
  // El promedio verificado actualiza el promedio global del estudiante.
  if (moduloId === 'verificacion_promedio' && t.estudiante) {
    t.estudiante.promedio_global = Number(datos.promedio);
  }
  t.siguientes_estados = SIGUIENTES_POR_ESTADO[destino.estado] || [];
  t.secuencia = SECUENCIAS['Excelencia Académica'];
  return t;
}

/** Simula POST /tramites/{id}/examen/reabrir. */
export function reabrirExamenMock(tramite, observaciones = null) {
  const t = clonar(tramite);

  if (!ESTADOS_REAPERIBLES.includes(t.estado_actual)) {
    throw { message: 'El estudiante solo puede re-solicitar el examen si reprobó un examen o no asistió a un sorteo.' };
  }

  const usados = Number(t.estudiante?.numero_intentos ?? 0);
  if (usados >= MAXIMO_INTENTOS_MOCK) {
    throw { message: 'El estudiante agotó los intentos permitidos para el Examen de Grado.' };
  }

  const config = REAPERTURA_POR_ESTADO[t.estado_actual];
  const hoy = new Date().toISOString();
  const motivo = String(observaciones || '').trim()
    || (config.estado === 'habilitado_examen'
      ? 'El estudiante solicitó volver a rendir únicamente el examen teórico; la especialidad aprobada se conserva.'
      : config.estado === 'examen_teorico_aprobado'
        ? 'El estudiante solicitó volver a rendir únicamente el examen de especialidad; el teórico aprobado se conserva.'
        : 'El estudiante solicitó volver a rendir ambos exámenes con una nueva Resolución del HCC.');

  t.updated_at = hoy;
  t.estado_actual = config.estado;
  t.estudiante = { ...(t.estudiante || {}), numero_intentos: usados + 1 };
  t.hitos = { ...(t.hitos || {}) };
  t.hitos.modulos = { ...(t.hitos.modulos || {}) };
  // Se reinicia el sorteo del examen reprobado y las notas; el examen aprobado
  // y la Resolución del HCC en curso se conservan salvo cuando se reprueban
  // AMBOS, que exigen una nueva resolución.
  [...config.limpiar, 'resolucion_asignacion_notas', 'asignacion_notas'].forEach((m) => {
    delete t.hitos.modulos[m];
  });
  t.estados = [
    ...(t.estados || []),
    { nombre_estado: config.estado, descripcion: 'Examen reabierto', observaciones: motivo, created_at: hoy },
  ];
  t.historial_examen = [
    ...(t.historial_examen || []),
    {
      id_historial_examen: (t.historial_examen?.length || 0) + 1,
      nombre_estado: config.estado,
      etapa: 'reapertura',
      resultado: config.resultado,
      numero_resolucion: null,
      fecha_resolucion: null,
      tema: null,
      fecha_examen: null,
      nota: null,
      numero_intentos: usados + 1,
      observaciones: motivo,
      created_at: hoy,
    },
  ];
  t.siguientes_estados = SIGUIENTES_POR_ESTADO[config.estado] || [];
  t.secuencia = SECUENCIAS['Examen de Grado'];
  return t;
}

// Simula GET /kardex/titulos-tesis (verificación de unicidad del título).
// Espejo del VerificacionTituloService del backend: normaliza el texto y puntúa
// la similitud combinando coincidencia de cadena y solapamiento de palabras.
export function verificarTituloMock(titulo, excluirTramite = null, excluirEstudiante = null) {
  const consulta = String(titulo || '').trim();
  const normalizado = normalizarTitulo(consulta);

  const titulos = POSTULANTES_MOCK
    .map((p) => ({
      id_tramite: p.tramite?.id_tramite,
      id_estudiante: p.id_estudiante,
      titulo: p.tramite?.hitos?.modulos?.perfil_tesis?.titulo
        || p.tramite?.hitos?.modulos?.perfil_proyecto?.titulo
        || p.tramite?.hitos?.modulos?.aprobacion_tema?.tema_investigacion,
      estado_actual: p.tramite?.estado_actual,
      modalidad: p.tramite?.modalidad?.nombre,
      estudiante: {
        nombres: p.nombres,
        apellidos: p.apellidos,
        ci: p.ci,
        registro_universitario: p.registro_universitario,
      },
      created_at: (p.tramite?.created_at || '').slice(0, 10),
    }))
    .filter((t) => t.titulo
      && t.id_tramite !== excluirTramite
      && t.id_estudiante !== excluirEstudiante);

  if (!normalizado) {
    return { consulta, unico: true, total: 0, coincidencias: [] };
  }

  const UMBRAL_SIMILAR = 55;
  const coincidencias = titulos
    .map((t) => {
      const candidato = normalizarTitulo(t.titulo);
      const exacto = candidato === normalizado;
      return { ...t, exacto, similitud: exacto ? 100 : similitudTitulos(normalizado, candidato) };
    })
    .filter((t) => t.exacto || t.similitud >= UMBRAL_SIMILAR)
    .sort((a, b) => b.similitud - a.similitud)
    .slice(0, 20)
    .map((t) => ({ ...t, nivel: t.exacto ? 'exacto' : 'similar' }));

  return {
    consulta,
    unico: !coincidencias.some((t) => t.exacto),
    total: coincidencias.length,
    coincidencias,
  };
}

function normalizarTitulo(texto) {
  return String(texto || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function similitudTitulos(consulta, candidato) {
  if (!consulta || !candidato) return 0;
  const palabrasConsulta = consulta.split(' ');
  const palabrasCandidato = candidato.split(' ');
  const comunes = palabrasConsulta.filter((p) => palabrasCandidato.includes(p)).length;
  const dice = (2 * comunes) / (palabrasConsulta.length + palabrasCandidato.length);
  return Math.round(dice * 100);
}

// Estados terminales por modalidad (espejo de TERMINALES_POR_MODALIDAD del
// backend). En Examen de Grado aprobar no cierra el flujo: todavía falta el
// reporte, así que el único logro terminal es `titulado`.
const TERMINALES_POR_MODALIDAD = {
  'Examen de Grado': ['rechazado', 'titulado'],
};
const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'titulado'];
const ESTADOS_FALLO = ['reprobado', 'rechazado'];

// Terminales de una modalidad (mismo fallback que el backend).
const terminalesDe = (modalidad) => TERMINALES_POR_MODALIDAD[modalidad] || ESTADOS_TERMINALES;

// Terminales de ÉXITO de una modalidad: los que no son rechazo ni reprobación.
const exitososDe = (modalidad) =>
  terminalesDe(modalidad).filter((estado) => !ESTADOS_FALLO.includes(estado));

// En curso mientras no haya alcanzado un estado terminal de su modalidad.
const esEnCurso = (estado, modalidad) => !terminalesDe(modalidad).includes(estado);

// Simula GET /kardex/resumen (resumen estadístico del dashboard).
export function resumenMock() {
  const tramites = POSTULANTES_MOCK
    .map((p) => p.tramite)
    .filter(Boolean);

  const totalPorModalidad = (idModalidad) =>
    tramites.filter((t) => t.id_modalidad === idModalidad);

  const modalidades = MODALIDADES_MOCK.map((m) => {
    const trámites = totalPorModalidad(m.id_modalidad);
    const exitosos = exitososDe(m.nombre);
    return {
      id_modalidad: m.id_modalidad,
      nombre: m.nombre,
      total: trámites.length,
      aprobados: trámites.filter((t) => exitosos.includes(t.estado_actual)).length,
      en_curso: trámites.filter((t) => esEnCurso(t.estado_actual, m.nombre)).length,
    };
  });

  return {
    modalidades,
    estudiantes: {
      total: POSTULANTES_MOCK.length,
      con_cuenta: POSTULANTES_MOCK.filter((p) => p.tiene_cuenta).length,
      con_tramite: tramites.length,
    },
    tutores: {
      total: DOCENTES_MOCK.length,
      activos: new Set(tramites.map((t) => t.id_tutor).filter(Boolean)).size,
    },
    totales: {
      tramites: tramites.length,
      aprobados: modalidades.reduce((suma, m) => suma + m.aprobados, 0),
      en_curso: modalidades.reduce((suma, m) => suma + m.en_curso, 0),
    },
  };
}