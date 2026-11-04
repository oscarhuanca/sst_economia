<?php

namespace App\Modules\Docentes\Services;

use App\Models\Docente;
use App\Support\BasePaginadoService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Casos de uso del registro académico de docentes.
 *
 * Centraliza el CRUD de la tabla `docentes` (registro independiente de los
 * accesos: nombre, apellidos, CI, teléfono, email y materia opcional) y el
 * catálogo compacto para los roles de gestión (selección de tutor, asignación
 * de materias, etc.). La autorización por rol se resuelve en las rutas vía el
 * middleware `role`.
 */
class DocenteService extends BasePaginadoService
{
    /** Columnas estrictas expuestas en el listado administrativo. */
    private const COLUMNAS = [
        'id_docente', 'ci', 'nombre', 'apellidos', 'telefono', 'email',
        'materia', 'created_at', 'updated_at',
    ];

    /**
     * Lista paginada de docentes (solo admin), con búsqueda opcional por
     * nombre, apellido, CI o materia.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function index(?int $perPage = 20, ?string $q = null): array
    {
        $query = Docente::query()
            ->select(self::COLUMNAS)
            ->orderBy('nombre');

        if ($q !== null && trim($q) !== '') {
            $like = '%'.trim($q).'%';
            $query->where(function (Builder $busqueda) use ($like) {
                $busqueda
                    ->where('nombre', 'like', $like)
                    ->orWhere('apellidos', 'like', $like)
                    ->orWhere('ci', 'like', $like)
                    ->orWhere('materia', 'like', $like);
            });
        }

        return $this->paginar($query, $this->porPagina($perPage));
    }

    /**
     * Catálogo compacto de docentes activos ordenados por nombre.
     * Catálogo pequeño: solo columnas usadas por los selectores.
     */
    public function catalogo(): \Illuminate\Database\Eloquent\Collection
    {
        return Docente::query()
            ->select(['id_docente', 'ci', 'nombre', 'apellidos', 'email', 'materia'])
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Registra un nuevo docente.
     */
    public function crear(array $datos): Docente
    {
        return Docente::create($datos);
    }

    /**
     * Actualiza un docente existente.
     */
    public function actualizar(int $id, array $datos): Docente
    {
        $docente = Docente::findOrFail($id);
        $docente->update($datos);

        return $docente->fresh();
    }

    /**
     * Elimina un docente del registro.
     */
    public function eliminar(int $id): void
    {
        Docente::findOrFail($id)->delete();
    }
}