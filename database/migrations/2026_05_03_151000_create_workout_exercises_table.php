<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained('workouts')->cascadeOnDelete();
            $table->foreignId('predefined_exercise_id')->nullable()->constrained('predefined_exercises');
            $table->foreignId('custom_exercise_id')->nullable()->constrained('custom_exercises');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('sets');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->unsignedSmallInteger('meters')->nullable();
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->timestamps();

            $table->index(['workout_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
