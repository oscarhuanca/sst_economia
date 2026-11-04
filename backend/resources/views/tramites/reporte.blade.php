{{--
    Reporte de Titulación (PDF) — Módulo "Reporte" del flujo de titulación.
    Renderizado con DomPDF, por eso el CSS es simple y autocontenido.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.45;
        }
        h1 { font-size: 15pt; margin: 0; color: #0f172a; }
        h2 {
            font-size: 10.5pt; margin: 0 0 6px; color: #b45309;
            text-transform: uppercase; letter-spacing: .4px;
        }
        .encabezado {
            text-align: center;
            border-bottom: 2px solid #b45309;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .sistema, .emitido { font-size: 9pt; color: #64748b; margin-top: 3px; }
        .seccion { margin-bottom: 14px; }
        .seccion:last-child { margin-bottom: 0; }

        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.datos td { padding: 3px 4px; vertical-align: top; }
        table.datos td.etiqueta {
            width: 30%; font-size: 8pt; text-transform: uppercase;
            letter-spacing: .4px; color: #64748b; font-weight: 600;
        }
        table.datos td.valor { font-weight: 700; color: #0f172a; }
        table.datos td.valor.ligero { font-weight: 400; }

        table.cuadro { width: 100%; border-collapse: collapse; }
        table.cuadro th, table.cuadro td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            text-align: left;
            font-size: 9pt;
        }
        table.cuadro th {
            background: #f1f5f9;
            color: #334155;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: .4px;
        }
        table.cuadro .nota { text-align: center; font-weight: 700; font-size: 12pt; color: #15803d; }
        table.cuadro .fila-destacada { background: #f8fafc; }

        .firma { margin-top: 30px; text-align: center; color: #475569; font-size: 9pt; }
        .firma .linea { border-top: 1px solid #94a3b8; width: 260px; margin: 26px auto 4px; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>Reporte de Titulación</h1>
        <div class="sistema">{{ $sistema }}</div>
        <div class="emitido">Emitido el {{ $emitido }} · Modalidad: {{ $modalidad?->nombre }}</div>
    </div>

    {{-- 1. Datos del postulante --}}
    <div class="seccion">
        <h2>1. Datos del postulante</h2>
        <table class="datos">
            <tr>
                <td class="etiqueta">Nombres y apellidos</td>
                <td class="valor">{{ $estudiante?->nombres }} {{ $estudiante?->apellidos }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Cédula de identidad</td>
                <td class="valor">{{ $estudiante?->ci }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Registro universitario</td>
                <td class="valor">{{ $estudiante?->registro_universitario }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Correo</td>
                <td class="valor ligero">{{ $estudiante?->email ?? '—' }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Teléfono</td>
                <td class="valor ligero">{{ $estudiante?->telefono ?? '—' }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Promedio global</td>
                <td class="valor ligero">{{ $estudiante?->promedio_global ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- 2. Trámite --}}
    <div class="seccion">
        <h2>2. Trámite</h2>
        <table class="datos">
            <tr>
                <td class="etiqueta">N.º de trámite</td>
                <td class="valor">{{ $tramite->id_tramite }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Modalidad</td>
                <td class="valor">{{ $modalidad?->nombre }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Estado actual</td>
                <td class="valor">{{ $etiquetaEstado }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Fecha de presentación</td>
                <td class="valor ligero">{{ $tramite->created_at ? $tramite->created_at->format('d/m/Y') : '—' }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Observaciones</td>
                <td class="valor ligero">{{ $tramite->observaciones ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- 3. Tema, tutor y tribunal --}}
    <div class="seccion">
        <h2>3. Tema de investigación y tribunal</h2>
        <table class="datos">
            <tr>
                <td class="etiqueta">Tema de investigación</td>
                <td class="valor">{{ $modulos['aprobacion_tema']['tema_investigacion'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Resolución del tema</td>
                <td class="valor ligero">
                    {{ $modulos['aprobacion_tema']['numero_resolucion'] ?? '—' }}
                    @if (! empty($modulos['aprobacion_tema']['fecha_resolucion']))
                        · {{ \Illuminate\Support\Carbon::parse($modulos['aprobacion_tema']['fecha_resolucion'])->format('d/m/Y') }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="etiqueta">Tutor asignado</td>
                <td class="valor">{{ $tutor ? $tutor->nombres.' '.$tutor->apellidos : '—' }}</td>
            </tr>
        </table>

        @php
            $tribunal = $modulos['tribunal_revisor']['tribunal'] ?? [];
            $filasTribunal = collect($tribunal)->map(function ($m) {
                $rol = $m['rol'] ?? '';
                return [ucfirst($rol), $m['nombre'] ?? '—'];
            })->toArray();
        @endphp
        @if (count($filasTribunal))
            <table class="cuadro">
                <thead>
                    <tr><th style="width:30%">Rol</th><th>Miembro</th></tr>
                </thead>
                <tbody>
                    @foreach ($filasTribunal as $fila)
                        <tr><td>{{ $fila[0] }}</td><td>{{ $fila[1] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- 4. Defensa: Resolución de Aprobación Final, fecha y nota --}}
    <div class="seccion">
        <h2>4. Defensa de Tesis de Grado</h2>
        @php $defensa = $modulos['defensa'] ?? []; @endphp
        <table class="cuadro">
            <tr>
                <th style="width:38%">Resolución de aprobación final</th>
                <td>{{ $defensa['numero_resolucion'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Fecha de la defensa</th>
                <td>
                    {{ ! empty($defensa['fecha_defensa'])
                        ? \Illuminate\Support\Carbon::parse($defensa['fecha_defensa'])->format('d/m/Y')
                        : '—' }}
                </td>
            </tr>
            <tr class="fila-destacada">
                <th>Nota final de la defensa</th>
                <td class="nota">{{ isset($defensa['nota_final']) ? $defensa['nota_final'].' / 100' : '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- 5. Hitos y plazos --}}
    @php
        $hitos = $hitos ?? [];
        $hitosVisibles = array_filter($hitos, fn ($k) => $k !== 'modulos', ARRAY_FILTER_USE_KEY);
        $etiquetasHitos = [
            'estado' => 'Cambio de estado',
            'perfil_aprobado' => 'Perfil aprobado',
            'tema_aprobado' => 'Tema aprobado',
            'tribunal_asignado' => 'Tribunal asignado',
            'defensa_aprobada' => 'Defensa aprobada',
            'reporte_generado' => 'Reporte generado',
            'titulado' => 'Titulado',
            'fecha_defensa' => 'Fecha de defensa',
            'fecha_defensa_solicitada' => 'Fecha de defensa solicitada',
            'fecha_defensa_sugerida' => 'Fecha sugerida por el estudiante',
            'limite_presentacion' => 'Límite de presentación',
            'limite_correccion' => 'Límite de correcciones',
            'resultado_comision' => 'Resultado de la comisión',
            'solicitud_fecha_defensa' => 'Fecha de defensa solicitada',
            'defensa_programada' => 'Defensa programada',
        ];
    @endphp
    @if (count($hitosVisibles))
        <div class="seccion">
            <h2>5. Hitos y plazos</h2>
            <table class="cuadro">
                <thead>
                    <tr><th style="width:55%">Hito</th><th>Fecha</th></tr>
                </thead>
                <tbody>
                    @foreach ($hitosVisibles as $clave => $fecha)
                        <tr>
                            <td>{{ $etiquetasHitos[$clave] ?? $clave }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- 6. Historial del trámite --}}
    <div class="seccion">
        <h2>6. Historial del trámite</h2>
        <table class="cuadro">
            <thead>
                <tr>
                    <th style="width:40%">Estado</th>
                    <th style="width:25%">Fecha</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($historial as $registro)
                    <tr>
                        <td>{{ $etiquetasEstado[$registro->nombre_estado] ?? $registro->nombre_estado }}</td>
                        <td>{{ $registro->created_at ? $registro->created_at->format('d/m/Y') : '—' }}</td>
                        <td>{{ $registro->observaciones ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="firma">
        <div class="linea"></div>
        Responsable de la Secretaría de Titulación
    </div>
</body>
</html>