<?php

namespace App\Modules\Tramites\Exceptions;

/**
 * Se lanza cuando se intenta una transición de estado no permitida para la
 * modalidad/estado actual del trámite.
 */
class TransicionNoPermitidaException extends \RuntimeException
{
}