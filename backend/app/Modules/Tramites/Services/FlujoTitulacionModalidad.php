<?php

namespace App\Modules\Tramites\Services;

/**
 * Registro de las modalidades que comparten el FLUJO DE TITULACIÓN por módulos.
 *
 * Tesis de Grado y Trabajo Dirigido recorren exactamente la misma secuencia de
 * estados (`solicitud_presentada` → `perfil_aprobado` → `tema_aprobado` →
 * `tribunal_asignado` → `defensa_aprobada` → `publicado` → `titulado`), pero se
 * diferencian en el identificador del primer módulo (el perfil), en el espacio
 * de configuración que usan y en las etiquetas que ve el usuario.
 *
 * Esta clase es la ÚNICA fuente de verdad de esas diferencias: la consultan la
 * máquina de estados, el motor de módulos, el servicio del estudiante, el
 * listener de hitos y las notificaciones, de modo que agregar una modalidad
 * gemela no obliga a tocar cada uno de esos puntos.
 */
final class FlujoTitulacionModalidad
{
    /** Modalidad de Tesis de Grado. */
    public const MODALIDAD_TESIS = 'Tesis de Grado';

    /** Modalidad de Trabajo Dirigido. */
    public const MODALIDAD_TRABAJO_DIRIGIDO = 'Trabajo Dirigido';

    /**
     * Descripción de cada modalidad que sigue el flujo de titulación por módulos.
     *
     * - `perfil`: identificador del módulo del perfil. Es también el tipo
     *   documental del perfil (`perfil_tesis` / `perfil_proyecto`), porque en
     *   ambas modalidades el documento y el módulo comparten nombre.
     * - `config`: espacio de configuración del módulo (`tesis` /
     *   `trabajodirigido`) donde se leen los plazos y la nota mínima.
     * - `etiqueta`: cómo se nombra el trabajo en los textos del estudiante.
     * - `articulo`: artículo que acompaña a `etiqueta` ("una tesis de grado",
     *   "un trabajo dirigido").
     * - `etiqueta_titulo`: cómo se nombra el TÍTULO del trabajo (el que se
     *   registra y se verifica por unicidad).
     * - `etiqueta_perfil`: cómo se nombra el primer módulo del flujo, en minúscula
     *   para poder intercalarlo en una frase.
     * - `ruta`: ruta del frontend donde el estudiante sigue su trámite.
     * - `prefijo`: prefijo de los tipos de notificación de la modalidad.
     *
     * @var array<string, array<string, string>>
     */
    private const MODALIDADES = [
        self::MODALIDAD_TESIS => [
            'perfil' => 'perfil_tesis',
            'config' => 'tesis',
            'etiqueta' => 'tesis de grado',
            'articulo' => 'una',
            'etiqueta_titulo' => 'tesis',
            'etiqueta_perfil' => 'perfil de tesis',
            'ruta' => '/estudiante/tesis',
            'prefijo' => 'tesis',
        ],
        self::MODALIDAD_TRABAJO_DIRIGIDO => [
            'perfil' => 'perfil_proyecto',
            'config' => 'trabajodirigido',
            'etiqueta' => 'trabajo dirigido',
            'articulo' => 'un',
            'etiqueta_titulo' => 'trabajo dirigido',
            'etiqueta_perfil' => 'perfil de proyecto',
            'ruta' => '/estudiante/trabajo-dirigido',
            'prefijo' => 'trabajo_dirigido',
        ],
    ];

    /**
     * Indica si la modalidad sigue el flujo de titulación por módulos.
     */
    public static function esTitulacion(string $modalidad): bool
    {
        return isset(self::MODALIDADES[$modalidad]);
    }

    /**
     * Identificador del módulo del perfil de la modalidad.
     */
    public static function perfilDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'perfil');
    }

    /**
     * Espacio de configuración de la modalidad (`tesis` / `trabajodirigido`).
     */
    public static function configDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'config');
    }

    /**
     * Cómo se nombra el trabajo de la modalidad en los textos del estudiante.
     */
    public static function etiquetaDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'etiqueta');
    }

    /**
     * Artículo que acompaña a la etiqueta del trabajo ("una" / "un").
     */
    public static function articuloDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'articulo');
    }

    /**
     * Cómo se nombra el TÍTULO del trabajo en los textos del estudiante.
     */
    public static function etiquetaTituloDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'etiqueta_titulo');
    }

    /**
     * Cómo se nombra el primer módulo del flujo, en minúscula.
     */
    public static function etiquetaPerfilDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'etiqueta_perfil');
    }

    /**
     * Ruta del frontend donde el estudiante sigue el trámite de la modalidad.
     */
    public static function rutaDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'ruta');
    }

    /**
     * Prefijo de los tipos de notificación de la modalidad.
     */
    public static function prefijoDe(string $modalidad): string
    {
        return self::datoDe($modalidad, 'prefijo');
    }

    /**
     * Catálogo de los tipos documentales de la fase de solicitud de la modalidad.
     *
     * @return array<string, string>
     */
    public static function tiposDocumentoDe(string $modalidad): array
    {
        return (array) config(self::configDe($modalidad).'.tipos_documento', []);
    }

    /**
     * Catálogo de los tipos documentales de la publicación de la modalidad.
     *
     * @return array<string, string>
     */
    public static function tiposDocumentoPublicacionDe(string $modalidad): array
    {
        return (array) config(self::configDe($modalidad).'.tipos_documento_publicacion', []);
    }

    /**
     * Identificadores de todos los módulos de perfil registrados.
     *
     * Lo usa la verificación de unicidad de títulos para buscar el título en
     * cualquiera de las modalidades del flujo.
     *
     * @return array<int, string>
     */
    public static function perfiles(): array
    {
        return array_values(array_unique(array_column(self::MODALIDADES, 'perfil')));
    }

    /**
     * Lee un dato de la modalidad registrada.
     *
     * @param  string  $modalidad  Nombre de la modalidad.
     * @param  string  $clave  Clave del dato a leer.
     */
    private static function datoDe(string $modalidad, string $clave): string
    {
        return self::MODALIDADES[$modalidad][$clave] ?? $modalidad;
    }
}
