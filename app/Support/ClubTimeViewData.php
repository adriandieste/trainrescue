<?php

namespace App\Support;

use App\Models\Club;
use App\Models\PerformanceTest;
use App\Models\PersonalBest;
use App\Models\User;
use Illuminate\Support\Collection;

class ClubTimeViewData
{
    public static function forClub(Club $club): array
    {
        $athletes = self::loadAthletes($club);
        $athletesById = collect($athletes)->keyBy('id');
        $tests = self::loadTests();
        $testsById = collect($tests)->keyBy('id');
        $groups = self::loadGroups($club);
        $records = self::loadRecords($club, $athletesById, $testsById);

        return [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'description' => $club->description,
                'logo_url' => $club->logo_path ? asset('storage/' . $club->logo_path) : null,
                'admin_user_id' => $club->admin_user_id,
            ],
            'filters' => [
                'view_mode' => 'ranking',
                'test_id' => $tests[0]['id'] ?? null,
                'athlete_id' => $athletes[0]['id'] ?? null,
                'group_id' => 'all',
            ],
            'options' => [
                'groups' => $groups,
                'tests' => $tests,
                'athletes' => $athletes,
            ],
            'records' => $records,
            'preview' => [
                'ranking_rows' => self::buildRankingRows(
                    collect($records),
                    $tests[0]['id'] ?? null,
                ),
                'athlete_rows' => self::buildAthleteRows(
                    collect($tests),
                    collect($records),
                    $athletes[0]['id'] ?? null,
                ),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function loadAthletes(Club $club): array
    {
        return User::query()
            ->with(['groups:id,name'])
            ->where('club_id', $club->id)
            ->whereIn('rol', ['socorrista', 'atleta'])
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'rol', 'club_id'])
            ->map(function (User $athlete): array {
                $groupIds = $athlete->groups->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

                return [
                    'id' => $athlete->id,
                    'name' => $athlete->name,
                    'avatar_url' => $athlete->avatar ? asset('storage/' . $athlete->avatar) : null,
                    'rol' => $athlete->rol,
                    'group_ids' => $groupIds,
                    'group_names' => $athlete->groups->pluck('name')->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function loadTests(): array
    {
        return PerformanceTest::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'structure', 'sort_order'])
            ->map(fn (PerformanceTest $test): array => [
                'id' => $test->id,
                'name' => $test->name,
                'structure' => $test->structure,
                'sort_order' => $test->sort_order,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function loadGroups(Club $club): array
    {
        return $club->groups()
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->map(fn ($group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $athletesById
     * @param array<int, array<string, mixed>> $testsById
     * @return array<int, array<string, mixed>>
     */
    private static function loadRecords(Club $club, Collection $athletesById, Collection $testsById): array
    {
        return PersonalBest::query()
            ->join('users', 'users.id', '=', 'personal_bests.user_id')
            ->join('performance_tests', 'performance_tests.id', '=', 'personal_bests.performance_test_id')
            ->where('users.club_id', $club->id)
            ->whereIn('users.rol', ['socorrista', 'atleta'])
            ->where('performance_tests.is_active', true)
            ->orderBy('performance_tests.sort_order')
            ->orderBy('performance_tests.name')
            ->orderBy('personal_bests.time_centiseconds')
            ->orderBy('users.name')
            ->get([
                'personal_bests.id as personal_best_id',
                'personal_bests.user_id',
                'personal_bests.performance_test_id',
                'personal_bests.time_centiseconds',
                'personal_bests.recorded_at',
            ])
            ->map(function ($row) use ($athletesById, $testsById): array {
                $athlete = $athletesById->get((int) $row->user_id) ?? [];
                $test = $testsById->get((int) $row->performance_test_id) ?? [];

                return [
                    'id' => (int) $row->personal_best_id,
                    'time_centiseconds' => (int) $row->time_centiseconds,
                    'time' => ChronoTime::fromCentiseconds((int) $row->time_centiseconds),
                    'recorded_at' => $row->recorded_at ? (string) $row->recorded_at : null,
                    'athlete' => $athlete,
                    'test' => $test,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $records
     * @return array<int, array<string, mixed>>
     */
    private static function buildRankingRows(Collection $records, mixed $selectedTestId, string $groupId = 'all'): array
    {
        return $records
            ->filter(function (array $record) use ($selectedTestId, $groupId): bool {
                if ($selectedTestId !== null && (string) ($record['test']['id'] ?? '') !== (string) $selectedTestId) {
                    return false;
                }

                if ($groupId !== 'all' && ! in_array((string) $groupId, array_map('strval', $record['athlete']['group_ids'] ?? []), true)) {
                    return false;
                }

                return true;
            })
            ->sort(function (array $left, array $right): int {
                return $left['time_centiseconds'] <=> $right['time_centiseconds']
                    ?: strcmp($left['athlete']['name'] ?? '', $right['athlete']['name'] ?? '');
            })
            ->values()
            ->map(function (array $record, int $index): array {
                $rank = $index + 1;

                return [
                    ...$record,
                    'rank' => $rank,
                    'medal' => match ($rank) {
                        1 => ['label' => 'Oro', 'className' => 'border-amber-200 bg-amber-100 text-amber-800'],
                        2 => ['label' => 'Plata', 'className' => 'border-slate-200 bg-slate-100 text-slate-700'],
                        3 => ['label' => 'Bronce', 'className' => 'border-orange-200 bg-orange-100 text-orange-800'],
                        default => null,
                    },
                ];
            })
            ->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $tests
     * @param Collection<int, array<string, mixed>> $records
     * @return array<int, array<string, mixed>>
     */
    private static function buildAthleteRows(Collection $tests, Collection $records, mixed $selectedAthleteId, string $groupId = 'all'): array
    {
        $athleteRecords = $records
            ->filter(function (array $record) use ($selectedAthleteId, $groupId): bool {
                if ($selectedAthleteId !== null && (string) $record['athlete']['id'] !== (string) $selectedAthleteId) {
                    return false;
                }

                if ($groupId !== 'all' && ! in_array((string) $groupId, array_map('strval', $record['athlete']['group_ids'] ?? []), true)) {
                    return false;
                }

                return true;
            })
            ->keyBy(fn (array $record) => (string) $record['test']['id']);

        return $tests->map(function (array $test) use ($athleteRecords): array {
            $record = $athleteRecords->get((string) $test['id']);

            return [
                ...$test,
                'personal_best' => $record ? [
                    'id' => $record['id'],
                    'time' => $record['time'],
                    'time_centiseconds' => $record['time_centiseconds'],
                    'recorded_at' => $record['recorded_at'],
                ] : null,
            ];
        })->all();
    }
}



