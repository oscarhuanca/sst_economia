<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Eloquent de la tabla `estudiantes`.
 *
 * Representa el PERFIL del estudiante: datos personales, institucionales y de
 * contacto (`ci`, `nombres`, `apellidos`, `fecha_nacimiento`, `email`,
 * `telefono`, `registro_universitario`) y, opcionalmente, su `promedio_global`.
 * El estudiante puede auto-registrarse desde el login; la cuenta de acceso
 * (`users`) se vincula de forma independiente mediante
 * `estudiantes → users.estudiante_id` cuando la instancia académica genera las
 * credenciales (CREAR USUARIO).
 */
class Estudiante extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_estudiante';

    protected $fillable = [
        'ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'email', 'telefono',
        'registro_universitario', 'promedio_global', 'numero_intentos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'promedio_global' => 'decimal:2',
            'numero_intentos' => 'integer',
        ];
    }

    /**
     * Nº de intentos de Examen de Grado ya utilizados por el estudiante.
     *
     * Lo incrementa el módulo de KARDEX al crear una postulación y al reabrir
     * una solicitud reprobada o con ausencia. El módulo 1 del flujo lo compara
     * con `config('examen.maximo_intentos')` para decidir si la solicitud se
     * rechaza por haber agotado los intentos.
     */
    public function intentosAgotados(): bool
    {
        return $this->numero_intentos >= (int) config('examen.maximo_intentos', 3);
    }

    /**
     * Relación opcional con la cuenta de acceso del estudiante.
     *
     * Es la relación inversa de `User::estudiante()`: la cuenta se vincula
     * mediante `users.estudiante_id`, no mediante una columna en `estudiantes`.
     *
     * @return HasOne Cuenta `\App\Models\User`
     *                vinculada a este perfil (o null si aún no tiene cuenta).
     */
    public function user()
    {
        return $this->hasOne(User::class, 'estudiante_id', 'id_estudiante');
    }

    /**
     * Relación uno a muchos con los trámites del estudiante.
     *
     * @return HasMany Trámites `\App\Models\Tramite`
     *                 presentados por este estudiante.
     */
    public function tramites()
    {
        return $this->hasMany(Tramite::class, 'id_estudiante', 'id_estudiante');
    }
}
