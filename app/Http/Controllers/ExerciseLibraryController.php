<?php

namespace App\Http\Controllers;

use App\Models\PredefinedExercise;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $exercises = PredefinedExercise::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (PredefinedExercise $exercise) => [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'category' => $exercise->category,
                'technical_description' => $exercise->technical_description,
                'materials' => $exercise->materials ?? [],
            ])
            ->values();

        $categories = $exercises
            ->pluck('category')
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Ejercicios/Biblioteca', [
            'exercises' => $exercises,
            'categories' => $categories,
        ]);
    }
}

