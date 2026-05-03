<?php

namespace Database\Seeders;

use App\Models\PredefinedExercise;
use Illuminate\Database\Seeder;

class PredefinedExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exercises = [
            [
                'name' => 'Arrastre de maniqui 50 m',
                'category' => 'rescate',
                'technical_description' => 'Nadar hasta el maniqui, realizar agarre reglamentario y completar arrastre continuo de 50 metros manteniendo control de via aerea.',
                'materials' => ['maniqui de rescate', 'aletas opcionales', 'cronometro'],
            ],
            [
                'name' => 'Natacion con obstaculos 100 m',
                'category' => 'tecnica',
                'technical_description' => 'Completar 100 metros atravesando obstaculos subacuaticos sin perder ritmo de nado ni tecnica de viraje.',
                'materials' => ['obstaculos de calle', 'pull buoy opcional', 'cronometro'],
            ],
            [
                'name' => 'Remolque de companero con tubo de rescate',
                'category' => 'rescate',
                'technical_description' => 'Simular aproximacion, contacto y remolque de victima consciente con tubo de rescate durante 75 metros.',
                'materials' => ['tubo de rescate', 'silbato', 'gorro'],
            ],
            [
                'name' => 'Series aerobia 8x200 m',
                'category' => 'resistencia',
                'technical_description' => 'Realizar 8 repeticiones de 200 metros a intensidad aerobia controlada, pausas de 30 segundos.',
                'materials' => ['cronometro', 'tabla opcional'],
            ],
            [
                'name' => 'Salidas y aceleracion 12x25 m',
                'category' => 'velocidad',
                'technical_description' => 'Trabajar reaccion de salida y aceleracion maxima en 25 metros con recuperacion completa.',
                'materials' => ['poyete de salida', 'cronometro'],
            ],
            [
                'name' => 'Apnea dinamica controlada',
                'category' => 'tecnica',
                'technical_description' => 'Recorrer distancia subacuatica con control tecnico y supervision de seguridad, enfocando deslizamiento y economia gestual.',
                'materials' => ['gafas', 'aletas opcionales', 'supervision obligatoria'],
            ],
            [
                'name' => 'Circuito de rescate combinado',
                'category' => 'rescate',
                'technical_description' => 'Combinar nado, recogida de material y remolque en circuito continuo para mejorar transiciones de rescate.',
                'materials' => ['maniqui', 'tubo de rescate', 'aletas', 'conos de senalizacion'],
            ],
            [
                'name' => 'Patada de crol 10x50 m',
                'category' => 'tecnica',
                'technical_description' => 'Mejorar propulsion de tren inferior con patada continua y alineacion corporal estable.',
                'materials' => ['tabla de flotacion', 'aletas cortas opcionales'],
            ],
            [
                'name' => 'Intervalos umbral 6x100 m',
                'category' => 'resistencia',
                'technical_description' => 'Series a ritmo umbral con descanso incompleto para tolerancia al lactato y mantenimiento tecnico bajo fatiga.',
                'materials' => ['cronometro', 'pizarra de tiempos'],
            ],
            [
                'name' => 'Relevos tecnicos de rescate 4x50 m',
                'category' => 'coordinacion',
                'technical_description' => 'Trabajo por equipos para mejorar coordinacion, entrega de material y comunicacion en situacion de rescate.',
                'materials' => ['tubo de rescate', 'testigo de relevo', 'silbato'],
            ],
        ];

        foreach ($exercises as $exercise) {
            PredefinedExercise::updateOrCreate(
                ['name' => $exercise['name']],
                [
                    'category' => $exercise['category'],
                    'technical_description' => $exercise['technical_description'],
                    'materials' => $exercise['materials'],
                    'is_active' => true,
                ]
            );
        }
    }
}

