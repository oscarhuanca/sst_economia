<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo Eloquent de la tabla `users`.
 *
 * Representa las CREDENCIALES de acceso al sistema. Desde el rediseño la tabla
 * está SEPARADA de `estudiantes`: almacena `username`, contraseña cifrada, rol
 * y estado activo, y se vincula opcionalmente con el perfil aislado del
 * estudiante mediante `estudiante_id` (solo para el rol estudiante). Los
 * usuarios administradores/docentes/gestión son independientes de `estudiantes`
 * y mantienen aquí sus datos de identificación (ci/nombres/email).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'ci', 'nombres', 'apellidos', 'email', 'telefono',
        'password', 'rol', 'activo', 'username', 'estudiante_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación opcional uno a uno con el perfil del estudiante.
     *
     * El usuario con rol `estudiante` apunta a su fila en `estudiantes` a
     * través de `estudiante_id`; los demás roles no tienen perfil vinculado.
     *
     * @return HasOne Perfil `\App\Models\Estudiante`
     *                asociado (o null para roles sin perfil de estudiante).
     */
    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'id_estudiante', 'estudiante_id');
    }

    /**
     * Relación uno a muchos con las notificaciones del usuario.
     *
     * @return HasMany Notificaciones `\App\Models\Notificacion`
     *                 dirigidas a este usuario.
     */
    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'id_usuario', 'id_usuario');
    }
}
