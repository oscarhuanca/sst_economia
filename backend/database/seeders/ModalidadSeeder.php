<?php

namespace Database\Seeders;

use App\Models\Modalidad;
use Illuminate\Database\Seeder;

class ModalidadSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = [
            ['nombre' => 'Examen de Grado', 'descripcion' => 'Evaluación oral de conocimientos.', 'duracion_maxima_meses' => 3, 'requisitos_minimos' => 'Certificados Originales de Notas, Carta a Dirección.'],
            ['nombre' => 'Tesis de Grado', 'descripcion' => 'Investigación científica con aporte original.', 'duracion_maxima_meses' => 18, 'requisitos_minimos' => 'Perfil de Tesis, Visto bueno del Director.'],
            ['nombre' => 'Trabajo Dirigido', 'descripcion' => 'Investigación práctica en institución con convenio.', 'duracion_maxima_meses' => 12, 'requisitos_minimos' => 'Convenio de Libre Reciprocidad, Certificado de Conclusión.'],
            ['nombre' => 'Excelencia Académica', 'descripcion' => 'Titulación por promedio sobresaliente verificado por el HCC.', 'duracion_maxima_meses' => 6, 'requisitos_minimos' => 'Promedio >= 80, Certificados de calificaciones.'],
        ];

        foreach ($modalidades as $mod) {
            Modalidad::create($mod);
        }
    }
}
