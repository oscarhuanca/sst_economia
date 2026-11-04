/**
 * Registry de módulos de dominio.
 *
 * Único lugar donde se enumeran los módulos de la aplicación. Cada módulo
 * exporta por defecto un descriptor `{ nombre, rutas }`. El router y cualquier
 * otro cliente de la plataforma consumen AQUÍ, nunca a rutas internas.
 *
 * Para incorporar un módulo nuevo basta con:
 *   1. crear `src/modules/<dominio>/index.js` con su descriptor y fachada,
 *   2. importarlo y añadirlo al arreglo `modules` de este archivo.
 * Nada más cambia: el resto de módulos y el router siguen funcionando.
 */
import authModule from './auth';
import layoutModule from './layout';
import estudiantesModule from './estudiantes';
import docentesModule from './docentes';
import notificacionesModule from './notificaciones';
import tesisModule from './tesis';
import tramitesModule from './tramites';
import trabajodirigidoModule from './trabajodirigido';
import usuariosModule from './usuarios';
import kardexModule from './kardex';
import excelenciaModule from './excelencia';

export const modules = [
    authModule,
    layoutModule,
    estudiantesModule,
    docentesModule,
    notificacionesModule,
    tesisModule,
    tramitesModule,
    trabajodirigidoModule,
    usuariosModule,
    kardexModule,
    excelenciaModule,
];

/** Concatena las rutas declaradas por todos los módulos registrados. */
export const moduleRoutes = modules.flatMap((modulo) =>
    Array.isArray(modulo.rutas) ? modulo.rutas : [],
);