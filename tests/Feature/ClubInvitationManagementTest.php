<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubInvitation;
use App\Models\ClubJoinRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubInvitationManagementTest extends TestCase
{
    use RefreshDatabase;


    public function test_admin_can_send_a_pending_invitation_to_a_socorrista(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $club = Club::create([
            'name' => 'Club Invitador',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create(['rol' => 'atleta']);

        $response = $this
            ->actingAs($trainer)
            ->post(route('club-invitations.store'), [
                'invited_user_id' => $athlete->id,
                'search' => $athlete->email,
            ]);

        $response
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('club_invitations', [
            'club_id' => $club->id,
            'inviter_user_id' => $trainer->id,
            'invited_user_id' => $athlete->id,
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_pending_invitation_is_blocked(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $club = Club::create([
            'name' => 'Club Sin Duplicados',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create(['rol' => 'atleta']);

        ClubInvitation::create([
            'club_id' => $club->id,
            'inviter_user_id' => $trainer->id,
            'invited_user_id' => $athlete->id,
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($trainer)
            ->from(route('dashboard'))
            ->post(route('club-invitations.store'), [
                'invited_user_id' => $athlete->id,
                'search' => $athlete->email,
            ]);

        $response
            ->assertSessionHasErrors('invited_user_id')
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, ClubInvitation::count());
    }

    public function test_athlete_dashboard_shows_pending_invitations(): void
    {
        $trainer = User::factory()->create([
            'name' => 'Carlos Entrenador',
            'email' => 'carlos@example.com',
            'rol' => 'entrenador',
        ]);

        $club = Club::create([
            'name' => 'Club Visible',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create(['rol' => 'atleta']);

        ClubInvitation::create([
            'club_id' => $club->id,
            'inviter_user_id' => $trainer->id,
            'invited_user_id' => $athlete->id,
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($athlete)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Invitaciones')
            ->assertSee('Club Visible')
            ->assertSee('Carlos Entrenador');
    }

    public function test_athlete_can_accept_invitation_and_join_club(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $athlete = User::factory()->create(['rol' => 'atleta']);

        $club = Club::create([
            'name' => 'Club Aceptado',
            'admin_user_id' => $trainer->id,
        ]);

        $otherTrainer = User::factory()->create(['rol' => 'entrenador']);
        $otherClub = Club::create([
            'name' => 'Club Alternativo',
            'admin_user_id' => $otherTrainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);
        $otherTrainer->update(['club_id' => $otherClub->id]);

        $invitation = ClubInvitation::create([
            'club_id' => $club->id,
            'inviter_user_id' => $trainer->id,
            'invited_user_id' => $athlete->id,
            'status' => 'pending',
        ]);

        ClubInvitation::create([
            'club_id' => $otherClub->id,
            'inviter_user_id' => $otherTrainer->id,
            'invited_user_id' => $athlete->id,
            'status' => 'pending',
        ]);

        ClubJoinRequest::create([
            'user_id' => $athlete->id,
            'club_id' => $otherClub->id,
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($athlete)
            ->post(route('club-invitations.accept', $invitation));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $athlete->id,
            'club_id' => $club->id,
        ]);
        $this->assertDatabaseCount('club_invitations', 0);
        $this->assertDatabaseCount('club_join_requests', 0);
    }

    public function test_athlete_can_reject_invitation_without_joining_a_club(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $athlete = User::factory()->create(['rol' => 'atleta']);

        $club = Club::create([
            'name' => 'Club Rechazado',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $invitation = ClubInvitation::create([
            'club_id' => $club->id,
            'inviter_user_id' => $trainer->id,
            'invited_user_id' => $athlete->id,
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($athlete)
            ->post(route('club-invitations.reject', $invitation));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('club_invitations', ['id' => $invitation->id]);
        $this->assertDatabaseHas('users', [
            'id' => $athlete->id,
            'club_id' => null,
        ]);
    }

}
