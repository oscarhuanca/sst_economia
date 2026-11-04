/**
 * Configuración centralizada de variables de entorno (frontend).
 *
 * Único punto donde se leen las variables `VITE_*` expuestas por Vite. Los
 * módulos NO deben leer `import.meta.env` directamente ni hardcodear URLs;
 * deben importar estos valores desde aquí.
 *
 * Valores por defecto SOLO para desarrollo local; en cualquier otro entorno
 * se deben definir en los archivos `.env.*` correspondientes.
 */
export const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const STORAGE_URL = import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage';