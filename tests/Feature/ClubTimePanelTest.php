<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Group;
use App\Models\PerformanceTest;
use App\Models\PersonalBest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClubTimePanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_profile_includes_club_times_panel_data(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Mareas',
            'description' => 'Seguimiento técnico del club',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $groupA = Group::create([
            'club_id' => $club->id,
            'name' => 'Grupo A',
            'description' => 'Preparación básica',
        ]);

        $groupB = Group::create([
            'club_id' => $club->id,
            'name' => 'Grupo B',
            'description' => 'Alta competición',
        ]);

        $testOne = PerformanceTest::create([
            'name' => '100 m obstáculos',
            'structure' => 'Piscina olímpica',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $testTwo = PerformanceTest::create([
            'name' => '50 m rescate',
            'structure' => 'Sprint en piscina',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $alicia = User::factory()->create([
            'name' => 'Alicia',
            'rol' => 'socorrista',
            'club_id' => $club->id,
            'age_category' => 'junior',
        ]);
        $alicia->groups()->attach($groupA->id);

        $bruno = User::factory()->create([
            'name' => 'Bruno',
            'rol' => 'socorrista',
            'club_id' => $club->id,
            'age_category' => 'junior',
        ]);
        $bruno->groups()->attach($groupA->id);

        $carla = User::factory()->create([
            'name' => 'Carla',
            'rol' => 'atleta',
            'club_id' => $club->id,
            'age_category' => 'absoluto',
        ]);
        $carla->groups()->attach($groupB->id);

        $david = User::factory()->create([
            'name' => 'David',
            'rol' => 'socorrista',
            'club_id' => $club->id,
            'age_category' => 'absoluto',
        ]);

        PersonalBest::create([
            'user_id' => $alicia->id,
            'performance_test_id' => $testOne->id,
            'time_centiseconds' => 11000,
            'recorded_at' => '2026-05-01',
        ]);
        PersonalBest::create([
            'user_id' => $alicia->id,
            'performance_test_id' => $testTwo->id,
            'time_centiseconds' => 9000,
            'recorded_at' => '2026-05-02',
        ]);
        PersonalBest::create([
            'user_id' => $bruno->id,
            'performance_test_id' => $testOne->id,
            'time_centiseconds' => 12000,
            'recorded_at' => '2026-05-03',
        ]);
        PersonalBest::create([
            'user_id' => $carla->id,
            'performance_test_id' => $testOne->id,
            'time_centiseconds' => 10000,
            'recorded_at' => '2026-05-04',
        ]);
        PersonalBest::create([
            'user_id' => $david->id,
            'performance_test_id' => $testOne->id,
            'time_centiseconds' => 13000,
            'recorded_at' => '2026-05-05',
        ]);

        $response = $this
            ->actingAs($trainer)
            ->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('clubTimePanel.club.name', 'Club Mareas')
                ->where('clubTimePanel.options.groups.0.name', 'Grupo A')
                ->where('clubTimePanel.options.groups.1.name', 'Grupo B')
                ->where('clubTimePanel.preview.ranking_rows.0.athlete.name', 'Carla')
                ->where('clubTimePanel.preview.ranking_rows.0.rank', 1)
                ->where('clubTimePanel.preview.ranking_rows.0.medal.label', 'Oro')
                ->where('clubTimePanel.preview.ranking_rows.1.athlete.name', 'Alicia')
                ->where('clubTimePanel.preview.ranking_rows.1.medal.label', 'Plata')
                ->where('clubTimePanel.preview.ranking_rows.2.athlete.name', 'Bruno')
                ->where('clubTimePanel.preview.ranking_rows.2.medal.label', 'Bronce')
                ->where('clubTimePanel.preview.athlete_rows.0.name', '100 m obstáculos')
                ->where('clubTimePanel.preview.athlete_rows.0.personal_best.time_centiseconds', 11000)
                ->where('clubTimePanel.preview.athlete_rows.1.name', '50 m rescate')
                ->where('clubTimePanel.preview.athlete_rows.1.personal_best.time_centiseconds', 9000)
            );
    }

    public function test_swimmer_profile_does_not_include_club_times_panel_data(): void
    {
        $athlete = User::factory()->create([
            'rol' => 'socorrista',
        ]);

        $response = $this
            ->actingAs($athlete)
            ->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('clubTimePanel', null)
            );
    }
}





