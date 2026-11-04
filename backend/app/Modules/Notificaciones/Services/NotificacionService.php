<?php

namespace App\Modules\Notificaciones\Services;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Servicio de creación de notificaciones internas.
 *
 * Centraliza el alta de avisos dirigidos a un único usuario o a todos los
 * usuarios de ciertos roles. Es invocado por los listeners de este módulo en
 * respuesta a los eventos de dominio del módulo de Trámites.
 */
class NotificacionService
{
    /**
     * Crea una notificación para todos los usuarios con alguno de los roles.
     *
     * @param  array<int, string> $roles
     */
    public static function paraUsuarios(array $roles, string $tipo, string $titulo, ?string $mensaje = null, ?string $enlace = null): void
    {
        $ids = User::whereIn('rol', $roles)->pluck('id_usuario');

        static::insertar($ids, $tipo, $titulo, $mensaje, $enlace);
    }

    /**
     * Crea una notificación para un usuario concreto.
     */
    public static function paraUsuario(int $idUsuario, string $tipo, string $titulo, ?string $mensaje = null, ?string $enlace = null): void
    {
        static::insertar(collect([$idUsuario]), $tipo, $titulo, $mensaje, $enlace);
    }

/**
     * Inserta las notificaciones por lotes (bulk insert) en vez de un INSERT
     * por usuario: reduce drásticamente el número de round-trips a la BD cuando
     * se notifica a todos los usuarios de un rol.
     *
     * @param  Collection<int, int> $ids
     */
    private static function insertar(Collection $ids, string $tipo, string $titulo, ?string $mensaje, ?string $enlace): void
    {
        if ($ids->isEmpty()) {
            return;
        }

        $ahora = now()->toDateTimeString();

        $filas = $ids->map(fn (int $id) => [
            'id_usuario' => $id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'enlace' => $enlace,
            'leida' => false,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ])->all();

        foreach (array_chunk($filas, 500) as $lote) {
            Notificacion::insert($lote);
        }
    }
}