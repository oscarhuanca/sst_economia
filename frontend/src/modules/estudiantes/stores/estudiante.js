import { defineStore } from 'pinia';
import { estudianteService } from '../services/estudiantes';

/**
 * Store del perfil del estudiante autenticado.
 *
 * Vive en el módulo Estudiantes y pertenece al dominio estudiante (perfil),
 * desacoplado del store de trámites. Mantiene el perfil sincronizado con
 * localStorage y aplica caché temporal (`_ts`) para evitar peticiones repetidas.
 * El perfil es SOLO LECTURA para el estudiante: los datos los aporta el
 * auto-registro o la administración y el estudiante no puede modificarlos.
 */
export const useEstudianteStore = defineStore('estudiante', {
    state: () => ({
        perfilEstudiante: JSON.parse(localStorage.getItem('perfilEstudiante')) || null,
        _ts: {},                                // Marca de tiempo de la última carga.
    }),
    actions: {
        /**
         * Comprueba si la caché sigue vigente según un TTL en milisegundos.
         *
         * @param {number} ttl Duración máxima de vigencia en ms.
         * @returns {boolean} true si la caché aún es válida.
         */
        _fresco(ttl) {
            return this._ts.perfil && Date.now() - this._ts.perfil < ttl;
        },
        /**
         * Carga el perfil de estudiante del usuario autenticado desde
         * GET /api/estudiante/perfil y lo persiste en localStorage.
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<Object|null>} Perfil de estudiante o null si no existe.
         */
        async cargarPerfil(force = false) {
            if (!force && this._fresco(30000)) return this.perfilEstudiante;
            try {
                const { data } = await estudianteService.perfil();
                this.perfilEstudiante = data?.id_estudiante ? data : null;
                if (this.perfilEstudiante) {
                    localStorage.setItem('perfilEstudiante', JSON.stringify(this.perfilEstudiante));
                } else {
                    localStorage.removeItem('perfilEstudiante');
                }
                this._ts.perfil = Date.now();
            } catch (error) {
                this.perfilEstudiante = null;
                localStorage.removeItem('perfilEstudiante');
            }
            return this.perfilEstudiante;
        },
    },
});