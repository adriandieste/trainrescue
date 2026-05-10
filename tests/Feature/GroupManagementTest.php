<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_can_create_a_group(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Test',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $member1 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);
        $member2 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);

        $response = $this->actingAs($trainer)
            ->postJson(route('clubs.groups.store'), [
                'name' => 'Equipo A',
                'user_ids' => [$member1->id, $member2->id],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'name', 'user_ids']);
        $response->assertJsonFragment(['name' => 'Equipo A']);

        $this->assertDatabaseHas('groups', [
            'club_id' => $club->id,
            'name' => 'Equipo A',
        ]);

        $group = Group::where('name', 'Equipo A')->first();
        $this->assertTrue($group->users->contains($member1));
        $this->assertTrue($group->users->contains($member2));
    }

    public function test_trainer_can_list_groups(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Test',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $member1 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);
        $member2 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);

        $group1 = Group::create(['club_id' => $club->id, 'name' => 'Grupo 1']);
        $group1->users()->attach([$member1->id]);

        $group2 = Group::create(['club_id' => $club->id, 'name' => 'Grupo 2']);
        $group2->users()->attach([$member2->id]);

        $response = $this->actingAs($trainer)
            ->getJson(route('clubs.groups.list'));

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Grupo 1']);
        $response->assertJsonFragment(['name' => 'Grupo 2']);
    }

    public function test_group_creation_validates_user_ids(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Test',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        // Create an outsider user not in the club
        $outsider = User::factory()->create(['rol' => 'socorrista', 'club_id' => null]);

        $response = $this->actingAs($trainer)
            ->postJson(route('clubs.groups.store'), [
                'name' => 'Invalid Group',
                'user_ids' => [$outsider->id],
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Algunos usuarios seleccionados no pertenecen a tu club.']);
    }

    public function test_group_creation_requires_at_least_one_user(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Test',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $response = $this->actingAs($trainer)
            ->postJson(route('clubs.groups.store'), [
                'name' => 'Empty Group',
                'user_ids' => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('user_ids');
    }

    public function test_trainer_without_club_cannot_create_group(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador', 'club_id' => null]);

        $response = $this->actingAs($trainer)
            ->postJson(route('clubs.groups.store'), [
                'name' => 'Test Group',
                'user_ids' => [1],
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'No perteneces a ningún club.']);
    }
}
