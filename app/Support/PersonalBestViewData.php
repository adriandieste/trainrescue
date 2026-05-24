<?php

namespace App\Support;

use App\Models\PerformanceTest;
use App\Models\User;

class PersonalBestViewData
{
    public static function forUser(User $viewer, User $target): array
    {
        $bestsByTest = $target->personalBests()
            ->get(['id', 'performance_test_id', 'time_centiseconds', 'recorded_at'])
            ->keyBy('performance_test_id');

        return PerformanceTest::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'structure'])
            ->map(function (PerformanceTest $test) use ($bestsByTest, $viewer, $target) {
                $best = $bestsByTest->get($test->id);

                return [
                    'id' => $test->id,
                    'name' => $test->name,
                    'structure' => $test->structure,
                    'can_edit' => $viewer->is($target),
                    'personal_best' => $best ? [
                        'id' => $best->id,
                        'time' => ChronoTime::fromCentiseconds((int) $best->time_centiseconds),
                        'time_centiseconds' => (int) $best->time_centiseconds,
                        'recorded_at' => $best->recorded_at?->toDateString(),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }
}

