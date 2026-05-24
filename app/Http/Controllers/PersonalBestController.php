<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePersonalBestRequest;
use App\Models\PerformanceTest;
use App\Models\PersonalBest;
use App\Models\User;
use App\Support\ChronoTime;
use App\Support\PersonalBestViewData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PersonalBestController extends Controller
{
    public function index(Request $request, ?User $user = null): JsonResponse
    {
        $target = $user ?? $request->user();

        Gate::authorize('viewForUser', [PersonalBest::class, $target]);

        return response()->json([
            'tests' => PersonalBestViewData::forUser($request->user(), $target),
        ]);
    }

    public function update(UpdatePersonalBestRequest $request, PerformanceTest $performanceTest): JsonResponse
    {
        $user = $request->user();

        $personalBest = PersonalBest::firstOrNew([
            'user_id' => $user->id,
            'performance_test_id' => $performanceTest->id,
        ]);

        if ($personalBest->exists) {
            Gate::authorize('update', $personalBest);
        } else {
            Gate::authorize('createForUser', [PersonalBest::class, $user]);
        }

        $personalBest->time_centiseconds = ChronoTime::toCentiseconds($request->validated('time'));
        $personalBest->recorded_at = now()->toDateString();
        $personalBest->save();

        return response()->json([
            'message' => 'Marca personal actualizada correctamente.',
            'personal_best' => [
                'id' => $personalBest->id,
                'time' => $personalBest->formatted_time,
                'time_centiseconds' => $personalBest->time_centiseconds,
                'recorded_at' => $personalBest->recorded_at?->toDateString(),
            ],
        ]);
    }
}

