<?php

namespace App\Support;

/**
 * Catálogo centralizado de roles del sistema.
 *
 * Evita que los nombres de rol ("admin", "estudiante", ...) estén dispersos en
 * controladores, servicios, middleware y rutas. Cualquier cambio se hace aquí.
 */
final class Roles
{
    public const ADMIN = 'admin';
    public const ESTUDIANTE = 'estudiante';
    public const DOCENTE = 'docente';
    public const KARDEX = 'kardex';
    public const SECRETARIA = 'secretaria';
    public const DIRECCION = 'direccion';

    /** Todos los roles del sistema. */
    public const TODOS = [
        self::ADMIN,
        self::ESTUDIANTE,
        self::DOCENTE,
        self::KARDEX,
        self::SECRETARIA,
        self::DIRECCION,
    ];

    /** Roles que gestionan trámites (revisión, asignación, paneles y avances). */
    public const GESTION = [
        self::ADMIN,
        self::KARDEX,
        self::SECRETARIA,
        self::DIRECCION,
    ];
}