/**
 * Fachada pública del Módulo de Examen de Grado.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/examen`). Expone rutas, store y utilidades del flujo, espejo
 * frontend de `App\Modules\Tramites\Services\ExamenService`.
 */
import routes from './router/examen.routes';

export { useExamenStore } from './stores/examen';
export {
    MAXIMO_INTENTOS,
    NOTA_MINIMA,
    esFlujoExamen,
    intentosRestantes,
    intentosUsados,
    modulosExamen,
} from './utils/flujoExamen';

export default {
    nombre: 'examen',
    rutas: routes,
};
