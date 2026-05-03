<?php

namespace Database\Factories;

use App\Models\CustomExercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomExercise>
 */
class CustomExerciseFactory extends Factory
{
    protected $model = CustomExercise::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'materials' => fake()->words(4, true),
            'video_url' => null,
        ];
    }
}

