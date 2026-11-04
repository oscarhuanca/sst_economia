/**
 * Utilidades del storage público de Laravel (archivos adjuntos de trámites).
 *
 * La URL base se lee de `VITE_STORAGE_URL` (equivalente a APP_URL/storage del
 * backend) a través de `core/config/env`. Centraliza el fallback de desarrollo
 * y evita duplicar la variable de entorno en varias vistas.
 */
import { STORAGE_URL } from '@/core/config/env';

/**
 * Convierte una ruta relativa del storage (ej. `documentos_tramites/a.pdf`)
 * en una URL absoluta descargable.
 *
 * @param {string} ruta Ruta relativa devuelta por la API.
 * @returns {string} URL absoluta del archivo.
 */
export function assetUrl(ruta) {
    const limpia = String(ruta || '').replace(/^\/+/, '');
    return `${STORAGE_URL}/${limpia}`;
}