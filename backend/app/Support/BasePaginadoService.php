<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Servicio base de listados paginados (capa transversal).
 *
 * Centraliza el armado de respuestas paginadas de los listados de los módulos
 * (trámites, tesis y usuarios): acota `per_page` para evitar respuestas
 * gigantes, ejecuta la paginación (sin COUNT → máxima velocidad) y normaliza
 * el formato de salida `{ data, meta }`. Los servicios de dominio heredan esta
 * clase para no repetir la lógica de paginación (DRY) y solo aportan su
 * consulta con selección estricta de columnas.
 */
abstract class BasePaginadoService
{
    /** Tope duro de elementos por página (0 < per_page <= 100). */
    private const PER_PAGE_MAX = 100;

    /**
     * Normaliza y acota el `per_page` solicitado por el cliente.
     *
     * @param int|null $solicitado Valor crudo del query param (o null).
     * @param int      $defecto    Páginas por defecto cuando no se indica.
     */
    protected function porPagina(?int $solicitado, int $defecto = 20): int
    {
        $porPagina = $solicitado ?: $defecto;

        return max(1, min($porPagina, self::PER_PAGE_MAX));
    }

    /**
     * Pagina una consulta y devuelve la respuesta estandarizada `{ data, meta }`.
     *
     * Usa `simplePaginate` (sin COUNT) para latencia mínima; `meta.has_more`
     * indica si resta siguiente página. Permite un transformador de ítems
     * opcional (p. ej. para enriquecer cada fila antes de responder).
     *
     * @param  Builder      $query       Consulta con `select` estricto.
     * @param  int          $porPagina   Filas por página (ya acotado).
     * @param  callable|null $transformar Callable que recibe el modelo y devuelve su forma final.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    protected function paginar(Builder $query, int $porPagina, ?callable $transformar = null): array
    {
        $paginada = $query->simplePaginate($porPagina)->withQueryString();

        $items = $paginada->items();

        if ($transformar !== null) {
            $items = array_map($transformar, $items);
        }

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginada->currentPage(),
                'per_page' => $paginada->perPage(),
                'has_more' => $paginada->hasMorePages(),
            ],
        ];
    }
}