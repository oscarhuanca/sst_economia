<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de autorización por rol.
 *
 * Uso declarativo en rutas: `Route::middleware('role:admin')` o
 * `Route::middleware('role:admin,kardex,secretaria')`. Centraliza la
 * autorización y elimina la lógica de rol repetida en los controladores.
 */
class EnsureRole
{
    /**
     * Aborta con 403 si el usuario autenticado no tiene uno de los roles
     * indicados en la declaración de la ruta.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->rol, $roles, true)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}