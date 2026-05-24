<?php

namespace Database\Seeders;

use App\Models\PerformanceTest;
use Illuminate\Database\Seeder;

class PerformanceTestSeeder extends Seeder
{
    public function run(): void
    {
        $tests = [
            ['name' => '200 m obstáculos', 'structure' => '200 m piscina con obstáculos', 'sort_order' => 10],
            ['name' => '100 m socorrista', 'structure' => '50 m nado + 50 m arrastre con maniquí', 'sort_order' => 20],
            ['name' => '50 m arrastre de maniquí', 'structure' => '50 m arrastre reglamentario', 'sort_order' => 30],
            ['name' => '100 m combinada de salvamento', 'structure' => '50 m nado + apnea + 50 m arrastre', 'sort_order' => 40],
            ['name' => '100 m rescate con tubo', 'structure' => '50 m nado + 50 m remolque con tubo', 'sort_order' => 50],
            ['name' => '200 m supersocorrista', 'structure' => 'Obstáculos + arrastre + tubo', 'sort_order' => 60],
            ['name' => 'Lanzamiento de cuerda', 'structure' => '12,5 m + recogida reglamentaria', 'sort_order' => 70],
            ['name' => '4x50 m relevo obstáculos', 'structure' => 'Relevo técnico de 4 postas', 'sort_order' => 80],
        ];

        foreach ($tests as $test) {
            PerformanceTest::updateOrCreate(
                ['name' => $test['name']],
                [
                    'structure' => $test['structure'],
                    'sort_order' => $test['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}

