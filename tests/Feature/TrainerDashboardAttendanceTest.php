<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainerDashboardAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_dashboard_exposes_attendance_metrics_per_member(): void
    {
        $trainer = User::factory()->create([
            'name' => 'Entrenador Admin',
            'rol' => 'entrenador',
        ]);

        $club = Club::create([
            'name' => 'Club Asistencia Dashboard',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $alicia = User::factory()->create([
            'name' => 'Alicia',
            'rol' => 'socorrista',
            'club_id' => $club->id,
        ]);

        $bruno = User::factory()->create([
            'name' => 'Bruno',
            'rol' => 'socorrista',
            'club_id' => $club->id,
        ]);

        $clubWorkout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Sesion club',
            'workout_date' => Carbon::yesterday()->toDateString(),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $groupWorkout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Sesion grupo A',
            'workout_date' => Carbon::yesterday()->subDay()->toDateString(),
            'target_scope' => 'grupo',
            'is_template' => false,
        ]);

        Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Sesion futura',
            'workout_date' => Carbon::tomorrow()->toDateString(),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        DB::table('workout_assignments')->insert([
            'workout_id' => $groupWorkout->id,
            'user_id' => $alicia->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('workout_completions')->insert([
            [
                'workout_id' => $clubWorkout->id,
                'user_id' => $alicia->id,
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workout_id' => $groupWorkout->id,
                'user_id' => $alicia->id,
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($trainer)->get(route('dashboard'));
        $response->assertOk();

        $members = collect($response->original?->getData()['page']['props']['members']['data'] ?? []);

        $aliciaRow = $members->firstWhere('id', $alicia->id);
        $brunoRow = $members->firstWhere('id', $bruno->id);

        $this->assertNotNull($aliciaRow);
        $this->assertNotNull($brunoRow);

        $this->assertSame(100, $aliciaRow['attendance_rate']);
        $this->assertSame(2, $aliciaRow['attendance_completed_sessions']);
        $this->assertSame(2, $aliciaRow['attendance_eligible_sessions']);

        $this->assertSame(0, $brunoRow['attendance_rate']);
        $this->assertSame(0, $brunoRow['attendance_completed_sessions']);
        $this->assertSame(1, $brunoRow['attendance_eligible_sessions']);
    }

    public function test_trainer_dashboard_defaults_attendance_metrics_to_zero_without_historic_sessions(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $club = Club::create([
            'name' => 'Club Sin Historico',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create([
            'name' => 'Nora',
            'rol' => 'socorrista',
            'club_id' => $club->id,
        ]);

        Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Solo futuro',
            'workout_date' => Carbon::tomorrow()->toDateString(),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $response = $this->actingAs($trainer)->get(route('dashboard'));
        $response->assertOk();

        $members = collect($response->original?->getData()['page']['props']['members']['data'] ?? []);
        $athleteRow = $members->firstWhere('id', $athlete->id);

        $this->assertNotNull($athleteRow);
        $this->assertSame(0, $athleteRow['attendance_rate']);
        $this->assertSame(0, $athleteRow['attendance_completed_sessions']);
        $this->assertSame(0, $athleteRow['attendance_eligible_sessions']);
    }
}

