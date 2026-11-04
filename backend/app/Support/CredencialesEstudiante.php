<?php

namespace App\Support;

use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Generador centralizado de credenciales de acceso automáticas para estudiantes.
 *
 * Reglas del sistema (DRY: un único lugar para las reglas de username y
 * contraseña; la usa el alta automática de usuarios, el seeder y las pruebas):
 *
 *  - `username`: primer nombre con su inicial en mayúscula + "_" + CI, sin
 *    espacios (ej: `Juan_202021`). Si colisiona con otro usuario existente se
 *    le agrega un sufijo aleatorio corto hasta garantizar unicidad.
 *  - `password`: fecha de nacimiento en formato DD-MM-AAAA (ej: una persona
 *    nacida el 14/03/2005 recibe `14-03-2005`).
 */
final class CredencialesEstudiante
{
    /**
     * Genera el nombre de usuario base a partir del perfil del estudiante.
     */
    public static function usernameDe(Estudiante $estudiante): string
    {
        $primerNombre = ucfirst(mb_strtolower(Str::ascii(
            explode(' ', trim((string) $estudiante->nombres))[0] ?? ''
        )));

        $ci = mb_strtolower(preg_replace('/[^a-z0-9]/u', '', Str::ascii((string) $estudiante->ci)) ?? '');

        return ($primerNombre !== '' ? $primerNombre : 'Estudiante').'_'.($ci !== '' ? $ci : Str::lower(Str::random(5)));
    }

    /**
     * Genera la contraseña temporal (fecha de nacimiento en DD-MM-AAAA).
     *
     * @throws \DomainException Si el estudiante no tiene fecha de nacimiento registrada.
     */
    public static function passwordDe(Estudiante $estudiante): string
    {
        if ($estudiante->fecha_nacimiento === null) {
            throw new \DomainException(
                'No se pueden generar las credenciales: el estudiante no tiene fecha de nacimiento registrada.'
            );
        }

        return $estudiante->fecha_nacimiento->format('d-m-Y');
    }

    /**
     * Devuelve un username garantizado único en la tabla `users`.
     */
    public static function usernameUnico(Estudiante $estudiante): string
    {
        $base = self::usernameDe($estudiante);
        $candidato = $base;

        while (User::query()->where('username', $candidato)->exists()) {
            $candidato = $base.'_'.Str::lower(Str::random(3));
        }

        return $candidato;
    }

    /**
     * Email que puede usarse en la cuenta de acceso sin violar
     * `users.users_email_unique`.
     *
     * `users.email` es opcional desde que el login pasó a basarse en el
     * username. Si el correo del perfil ya pertenece a otra cuenta, se omite en
     * la nueva cuenta (el dato real permanece en `estudiantes.email`) para no
     * reventar el alta automática con un error 500.
     */
    public static function emailDisponible(Estudiante $estudiante): ?string
    {
        $email = trim((string) $estudiante->email);

        if ($email === '') {
            return null;
        }

        return User::query()->where('email', $email)->exists() ? null : $email;
    }
}
