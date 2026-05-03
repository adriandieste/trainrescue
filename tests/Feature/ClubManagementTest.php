<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubJoinRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClubManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_trainer_can_view_the_edit_club_page(): void
    {
        $trainer = User::factory()->create([
            'rol' => 'entrenador',
        ]);

        $club = Club::create([
            'name' => 'Club Admin',
            'description' => 'Club del entrenador administrador',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($trainer)
            ->get(route('clubs.edit', $club));

        $response
            ->assertOk()
            ->assertSee('Club Admin')
            ->assertSee('Club del entrenador administrador');
    }

    public function test_non_admin_trainer_cannot_update_a_club(): void
    {
        $admin = User::factory()->create([
            'rol' => 'entrenador',
        ]);

        $club = Club::create([
            'name' => 'Club Protegido',
            'admin_user_id' => $admin->id,
        ]);

        $admin->update(['club_id' => $club->id]);

        $otherTrainer = User::factory()->create([
            'rol' => 'entrenador',
            'club_id' => $club->id,
        ]);

        $response = $this
            ->actingAs($otherTrainer)
            ->patch(route('clubs.update', $club), [
                'name' => 'Intento de edición',
                'description' => 'No debería permitirse',
            ]);

        $response->assertForbidden();

        $this->assertSame('Club Protegido', $club->fresh()->name);
    }

    public function test_admin_can_update_club_information_and_replace_logo(): void
    {
        Storage::fake('public');

        $trainer = User::factory()->create([
            'rol' => 'entrenador',
        ]);

        Storage::disk('public')->put('clubs/logos/logo-anterior.png', 'old-logo');

        $club = Club::create([
            'name' => 'Club Original',
            'description' => 'Descripción original',
            'logo_path' => 'clubs/logos/logo-anterior.png',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($trainer)
            ->patch(route('clubs.update', $club), [
                'name' => 'Club Renovado',
                'description' => 'Descripción renovada',
                'logo' => UploadedFile::fake()->image('nuevo-logo.png'),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $club->refresh();

        $this->assertSame('Club Renovado', $club->name);
        $this->assertSame('Descripción renovada', $club->description);
        $this->assertNotSame('clubs/logos/logo-anterior.png', $club->logo_path);
        Storage::disk('public')->assertMissing('clubs/logos/logo-anterior.png');
        Storage::disk('public')->assertExists($club->logo_path);
    }

    public function test_updating_a_club_allows_keeping_the_current_name(): void
    {
        $trainer = User::factory()->create([
            'rol' => 'entrenador',
        ]);

        $club = Club::create([
            'name' => 'Club Estable',
            'description' => 'Texto inicial',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($trainer)
            ->patch(route('clubs.update', $club), [
                'name' => 'Club Estable',
                'description' => 'Texto actualizado',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame('Texto actualizado', $club->fresh()->description);
    }

    public function test_updating_a_club_rejects_duplicate_names_from_other_clubs(): void
    {
        $trainer = User::factory()->create([
            'rol' => 'entrenador',
        ]);

        $club = Club::create([
            'name' => 'Club Propio',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        Club::create([
            'name' => 'Club Existente',
        ]);

        $response = $this
            ->actingAs($trainer)
            ->from(route('clubs.edit', $club))
            ->patch(route('clubs.update', $club), [
                'name' => 'Club Existente',
                'description' => 'Intento duplicado',
            ]);

        $response
            ->assertSessionHasErrors('name')
            ->assertRedirect(route('clubs.edit', $club));
    }

    public function test_admin_can_dissolve_club_and_release_members(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['rol' => 'entrenador']);
        $athlete = User::factory()->create(['rol' => 'atleta']);

        Storage::disk('public')->put('clubs/logos/logo-disolver.png', 'logo-content');

        $club = Club::create([
            'name' => 'Club Disolucion',
            'description' => 'Club para prueba de borrado',
            'logo_path' => 'clubs/logos/logo-disolver.png',
            'admin_user_id' => $admin->id,
        ]);

        $admin->update(['club_id' => $club->id]);
        $athlete->update(['club_id' => $club->id]);

        $joinRequest = ClubJoinRequest::create([
            'user_id' => $athlete->id,
            'club_id' => $club->id,
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('clubs.destroy', $club), [
                'confirm_name' => 'Club Disolucion',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('clubs', ['id' => $club->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'club_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $athlete->id, 'club_id' => null]);
        $this->assertDatabaseMissing('club_join_requests', ['id' => $joinRequest->id]);
        Storage::disk('public')->assertMissing('clubs/logos/logo-disolver.png');
    }

    public function test_non_owner_trainer_cannot_dissolve_club(): void
    {
        $owner = User::factory()->create(['rol' => 'entrenador']);
        $otherTrainer = User::factory()->create(['rol' => 'entrenador']);

        $club = Club::create([
            'name' => 'Club Protegido Dissolve',
            'admin_user_id' => $owner->id,
        ]);

        $owner->update(['club_id' => $club->id]);
        $otherTrainer->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($otherTrainer)
            ->delete(route('clubs.destroy', $club), [
                'confirm_name' => 'Club Protegido Dissolve',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('clubs', ['id' => $club->id]);
    }

    public function test_dissolving_club_requires_exact_confirmation_name(): void
    {
        $owner = User::factory()->create(['rol' => 'entrenador']);

        $club = Club::create([
            'name' => 'Club Confirmacion',
            'admin_user_id' => $owner->id,
        ]);

        $owner->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($owner)
            ->from(route('dashboard'))
            ->delete(route('clubs.destroy', $club), [
                'confirm_name' => 'Nombre Incorrecto',
            ]);

        $response
            ->assertSessionHasErrors('confirm_name')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('clubs', ['id' => $club->id]);
    }

    public function test_admin_can_promote_a_club_member_to_trainer(): void
    {
        $admin = User::factory()->create(['rol' => 'entrenador']);
        $member = User::factory()->create(['rol' => 'atleta']);

        $club = Club::create([
            'name' => 'Club Promocion',
            'admin_user_id' => $admin->id,
        ]);

        $admin->update(['club_id' => $club->id]);
        $member->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('clubs.members.update-role', $member), [
                'rol' => 'entrenador',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'rol' => 'entrenador',
            'club_id' => $club->id,
        ]);
    }

    public function test_non_admin_trainer_cannot_promote_members(): void
    {
        $admin = User::factory()->create(['rol' => 'entrenador']);
        $otherTrainer = User::factory()->create(['rol' => 'entrenador']);
        $member = User::factory()->create(['rol' => 'atleta']);

        $club = Club::create([
            'name' => 'Club Permisos',
            'admin_user_id' => $admin->id,
        ]);

        $admin->update(['club_id' => $club->id]);
        $otherTrainer->update(['club_id' => $club->id]);
        $member->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($otherTrainer)
            ->patch(route('clubs.members.update-role', $member), [
                'rol' => 'entrenador',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'rol' => 'atleta',
        ]);
    }

    public function test_admin_cannot_promote_user_from_another_club(): void
    {
        $admin = User::factory()->create(['rol' => 'entrenador']);
        $otherClubMember = User::factory()->create(['rol' => 'atleta']);

        $club = Club::create([
            'name' => 'Club Admin',
            'admin_user_id' => $admin->id,
        ]);

        $anotherClub = Club::create([
            'name' => 'Club Externo',
        ]);

        $admin->update(['club_id' => $club->id]);
        $otherClubMember->update(['club_id' => $anotherClub->id]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('clubs.members.update-role', $otherClubMember), [
                'rol' => 'entrenador',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $otherClubMember->id,
            'rol' => 'atleta',
        ]);
    }

    public function test_socorrista_can_view_club_info_in_dashboard(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $socorrista = User::factory()->create(['rol' => 'socorrista']);

        $club = Club::create([
            'name' => 'Club Test',
            'description' => 'Test Club Description',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);
        $socorrista->update(['club_id' => $club->id]);

        $response = $this
            ->actingAs($socorrista)
            ->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_socorrista_without_club_sees_dashboard(): void
    {
        $socorrista = User::factory()->create(['rol' => 'socorrista']);

        $response = $this
            ->actingAs($socorrista)
            ->get(route('dashboard'));

        $response->assertOk();
    }
}
