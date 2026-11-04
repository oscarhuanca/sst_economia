<?php

namespace App\Modules\Kardex\Services;

use App\Models\Tramite;
use App\Modules\Tramites\Services\FlujoTitulacionModalidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Verificación de unicidad del título de un trabajo de grado.
 *
 * KARDEX necesita confirmar, antes de aprobar un perfil, que el trabajo no
 * repita (ni se parezca demasiado a) otro ya registrado. El título lo presenta
 * el estudiante junto con su perfil (`hitos.modulos.perfil_tesis.titulo` o
 * `hitos.modulos.perfil_proyecto.titulo`) y se confirma luego en el módulo de
 * aprobación del tema (`hitos.modulos.aprobacion_tema.tema_investigacion`), por
 * lo que la búsqueda se hace sobre el JSON `hitos` de los trámites.
 *
 * Estrategia en dos pasos para no cargar la tabla completa:
 *  1. Prefiltro barato en SQL con los términos significativos del título
 *     (descartando palabras vacías) usando `LIKE` sobre `hitos`.
 *  2. Puntuación de similitud en PHP sobre los candidatos resultantes,
 *     combinando similitud de cadena (`similar_text`) con el solapamiento de
 *     palabras (coeficiente de Dice).
 *
 * La comparación normaliza el texto (minúsculas, sin tildes ni signos de
 * puntuación) para que "Riego, Eficiente." y "riego eficiente" se reconozcan
 * como el mismo título.
 */
class VerificacionTituloService
{
    /** Similitud mínima (%) para reportar un título como "similar". */
    private const UMBRAL_SIMILAR = 55;

    /** Tope de coincidencias devueltas (ordenadas por similitud). */
    private const MAX_COINCIDENCIAS = 20;

    /** Columnas estrictas: solo lo necesario para comparar y mostrar. */
    private const COLUMNAS = [
        'id_tramite', 'id_estudiante', 'id_modalidad', 'estado_actual', 'hitos', 'created_at',
    ];

    /** Eager-loads mínimos de las coincidencias. */
    private const CARGAS = [
        'modalidad:id_modalidad,nombre',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario',
    ];

    /**
     * Palabras vacías del español: aparecen en casi todos los títulos, por lo
     * que sirven de filtro pero no sirven para prefiltrar en SQL.
     *
     * @var array<int, string>
     */
    private const VACIAS = [
        'para', 'como', 'entre', 'sobre', 'desde', 'mediante', 'durante', 'segun',
        'este', 'esta', 'esto', 'aqui', 'donde', 'cuando', 'cuanto', 'tambien',
        'the', 'and', 'for', 'with', 'from',
        'del', 'una', 'uno', 'dos', 'tres', 'sus', 'sin', 'por', 'con', 'las',
        'los', 'que', 'mas', 'the', 'and', 'for', 'with', 'from',
    ];

    /**
     * Busca trámites cuyo título de tesis coincide o se parece al indicado.
     *
     * @param  string  $titulo  Título a verificar.
     * @param  int|null  $excluirTramite  Trámite a omitir (para no comparar
     *                                    consigo mismo al re-verificar).
     * @param  int|null  $excluirEstudiante  Estudiante cuyos trámites anteriores
     *                                       se omiten (re-presentación del
     *                                       perfil por el mismo estudiante).
     * @return array{consulta: string, unico: bool, total: int, coincidencias: array<int, array<string, mixed>>}
     */
    public function verificar(string $titulo, ?int $excluirTramite = null, ?int $excluirEstudiante = null): array
    {
        $consulta = trim($titulo);
        $normalizado = $this->normalizar($consulta);

        if ($normalizado === '') {
            return [
                'consulta' => $consulta,
                'unico' => true,
                'total' => 0,
                'coincidencias' => [],
            ];
        }

        $terminos = $this->terminosDeFiltro($consulta, $normalizado);

        $coincidencias = Tramite::query()
            ->select(self::COLUMNAS)
            ->with(self::CARGAS)
            ->whereNotNull('hitos')
            ->when($excluirTramite !== null, fn (Builder $q) => $q->where('id_tramite', '!=', $excluirTramite))
            ->when($excluirEstudiante !== null, fn (Builder $q) => $q->where('id_estudiante', '!=', $excluirEstudiante))
            ->where(function (Builder $sub) use ($terminos) {
                foreach ($terminos as $termino) {
                    $sub->orWhereRaw('LOWER(tramites.hitos) LIKE ?', ['%'.addcslashes($termino, '%_\\').'%']);
                }
            })
            ->get()
            ->map(function (Tramite $tramite) use ($normalizado) {
                $registrado = $this->tituloDe($tramite);

                if ($registrado === null) {
                    return null;
                }

                $candidato = $this->normalizar($registrado);
                $exacto = $candidato === $normalizado;
                $similitud = $exacto ? 100 : $this->similitud($normalizado, $candidato);

                if (! $exacto && $similitud < self::UMBRAL_SIMILAR) {
                    return null;
                }

                return [
                    'id_tramite' => $tramite->id_tramite,
                    'titulo' => $registrado,
                    'similitud' => $similitud,
                    'exacto' => $exacto,
                    'nivel' => $exacto ? 'exacto' : 'similar',
                    'estado_actual' => $tramite->estado_actual,
                    'modalidad' => $tramite->modalidad?->nombre,
                    'estudiante' => [
                        'nombres' => $tramite->estudiante?->nombres,
                        'apellidos' => $tramite->estudiante?->apellidos,
                        'ci' => $tramite->estudiante?->ci,
                        'registro_universitario' => $tramite->estudiante?->registro_universitario,
                    ],
                    'created_at' => $tramite->created_at?->toDateString(),
                ];
            })
            ->filter()
            ->sortByDesc('similitud')
            ->take(self::MAX_COINCIDENCIAS)
            ->values()
            ->all();

        return [
            'consulta' => $consulta,
            'unico' => ! collect($coincidencias)->contains('exacto', true),
            'total' => count($coincidencias),
            'coincidencias' => $coincidencias,
        ];
    }

    /**
     * Devuelve el primer trabajo cuyo título es IDÉNTICO al indicado.
     *
     * Es la comprobación que bloquea tanto la aprobación de un perfil por parte
     * de KARDEX como la re-presentación de un título repetido por el estudiante.
     *
     * @param  string  $titulo  Título a comprobar.
     * @param  int|null  $excluirTramite  Trámite a omitir.
     * @param  int|null  $excluirEstudiante  Estudiante cuyos trámites se omiten.
     * @return array<string, mixed>|null Coincidencia exacta o null si el título es único.
     */
    public function duplicado(string $titulo, ?int $excluirTramite = null, ?int $excluirEstudiante = null): ?array
    {
        $verificacion = $this->verificar($titulo, $excluirTramite, $excluirEstudiante);

        foreach ($verificacion['coincidencias'] as $coincidencia) {
            if ($coincidencia['exacto']) {
                return $coincidencia;
            }
        }

        return null;
    }

    /**
     * Compara dos títulos con la misma normalización de la verificación, para
     * saber si se trata del mismo título escrito de otra forma.
     */
    public function esIgual(string $titulo, string $otro): bool
    {
        $normalizado = $this->normalizar($titulo);

        return $normalizado !== '' && $normalizado === $this->normalizar($otro);
    }

    /**
     * Extrae el título de tesis registrado en el trámite.
     *
     * El título propuesto por el estudiante vive en el módulo de perfil
     * (`perfil_tesis.titulo` / `perfil_proyecto.titulo`); si el trámite aún no
     * lo registra, se recurre al tema confirmado por KARDEX en el módulo de
     * aprobación del tema.
     */
    private function tituloDe(Tramite $tramite): ?string
    {
        $modulos = $tramite->hitos['modulos'] ?? [];

        foreach (FlujoTitulacionModalidad::perfiles() as $perfil) {
            $titulo = $modulos[$perfil]['titulo'] ?? null;

            if (is_string($titulo) && trim($titulo) !== '') {
                return trim($titulo);
            }
        }

        $titulo = $modulos['aprobacion_tema']['tema_investigacion'] ?? null;

        return is_string($titulo) && trim($titulo) !== '' ? trim($titulo) : null;
    }

    /**
     * Términos usados para el prefiltrado en SQL.
     *
     * Genera para cada palabra significativa su variante acentuada (como está
     * en la base) y su variante sin tildes (por si quien escribe las omite).
     *
     * @return array<int, string>
     */
    private function terminosDeFiltro(string $consulta, string $normalizado): array
    {
        $crudos = preg_split('/[\s\p{P}\p{S}]+/u', mb_strtolower($consulta), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $terminos = [];

        foreach ($crudos as $token) {
            $plano = $this->normalizar($token);

            if ($plano === '' || mb_strlen($plano) < 4 || in_array($plano, self::VACIAS, true)) {
                continue;
            }

            $terminos[$token] = true;
            $terminos[$plano] = true;
        }

        if ($terminos === []) {
            $terminos[mb_strtolower(trim($normalizado))] = true;
        }

        return array_keys($terminos);
    }

    /**
     * Normaliza un título para comparar: minúsculas, sin tildes, sin
     * puntuación y con espacios colapsados.
     */
    private function normalizar(string $texto): string
    {
        $limpio = preg_replace('/[^a-z0-9]+/u', ' ', Str::lower(Str::ascii($texto))) ?? '';

        return trim(preg_replace('/\s+/', ' ', $limpio) ?? '');
    }

    /**
     * Similitud porcentual entre dos títulos normalizados (0..100).
     *
     * Promedia la similitud de cadena con el coeficiente de Dice sobre las
     * palabras: la primera tolera errores de escritura, la segunda tolera
     * títulos reordenados. Devuelve el mayor de ambos.
     */
    private function similitud(string $consulta, string $candidato): int
    {
        if ($consulta === $candidato) {
            return 100;
        }

        if ($consulta === '' || $candidato === '') {
            return 0;
        }

        similar_text($consulta, $candidato, $porcentaje);

        $palabrasConsulta = array_count_values(explode(' ', $consulta));
        $palabrasCandidato = array_count_values(explode(' ', $candidato));

        $comunes = 0;
        foreach ($palabrasConsulta as $palabra => $veces) {
            $comunes += min($veces, $palabrasCandidato[$palabra] ?? 0);
        }

        $total = array_sum($palabrasConsulta) + array_sum($palabrasCandidato);
        $dice = $total > 0 ? (2 * $comunes) / $total : 0.0;

        return (int) round(max($porcentaje, $dice * 100));
    }
}
