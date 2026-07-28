<?php

namespace Tests\Feature;

use App\Modules\Administration\Models\Module;
use App\Modules\Administration\Models\User;
use App\Modules\Administration\Models\UserModule;
use App\Modules\Gestions\Models\AffectationStation;
use App\Modules\Gestions\Models\Hydrocarbure;
use App\Modules\Gestions\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HydrocarbureManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_refused(): void
    {
        $this->getJson('/api/v1/gestions/hydrocarbures')->assertUnauthorized();
    }

    public function test_ordinary_authenticated_user_is_refused(): void
    {
        Sanctum::actingAs($this->createUser());

        $this->getJson('/api/v1/gestions/hydrocarbures')->assertForbidden();
    }

    public function test_admin_and_super_admin_have_global_read_access(): void
    {
        Hydrocarbure::create([
            'libelle' => 'Gasoil',
            'prix_achat' => 700,
            'prix_vente' => 750,
        ]);

        foreach (['admin', 'super_admin'] as $role) {
            Sanctum::actingAs($this->createUser($role));

            $this->getJson('/api/v1/gestions/hydrocarbures')
                ->assertOk()
                ->assertJsonCount(1, 'data');
        }
    }

    public function test_manager_without_active_station_assignment_is_refused(): void
    {
        Sanctum::actingAs($this->createManager());

        $this->getJson('/api/v1/gestions/hydrocarbures')->assertForbidden();
    }

    public function test_assigned_manager_reads_global_hydrocarbures(): void
    {
        $manager = $this->createManager($this->createStation());
        Hydrocarbure::create([
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => 850,
        ]);
        Hydrocarbure::create([
            'libelle' => 'Gasoil',
            'prix_achat' => 700,
            'prix_vente' => 750,
        ]);
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/gestions/hydrocarbures')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_manager_cannot_create_or_update_hydrocarbure(): void
    {
        $manager = $this->createManager($this->createStation());
        $hydrocarbure = Hydrocarbure::create([
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => 850,
        ]);
        Sanctum::actingAs($manager);

        $payload = [
            'libelle' => 'Essence super',
            'prix_achat' => 810,
            'prix_vente' => 870,
        ];

        $this->postJson('/api/v1/gestions/hydrocarbures', $payload)->assertForbidden();
        $this->putJson("/api/v1/gestions/hydrocarbures/{$hydrocarbure->id}", $payload)->assertForbidden();

        $this->assertDatabaseHas('hydrocarbures', [
            'id' => $hydrocarbure->id,
            'libelle' => 'Essence',
        ]);
    }

    public function test_admin_can_create_and_update_with_audit_fields(): void
    {
        $admin = $this->createUser('admin');
        $updater = $this->createUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/gestions/hydrocarbures', [
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => 850,
        ])->assertOk()
            ->assertJsonPath('data.prix_achat', '800.00')
            ->assertJsonPath('data.prix_vente', '850.00');

        $hydrocarbureId = $response->json('data.id');
        $this->assertDatabaseHas('hydrocarbures', [
            'id' => $hydrocarbureId,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($updater);
        $this->putJson("/api/v1/gestions/hydrocarbures/{$hydrocarbureId}", [
            'libelle' => 'Essence super',
            'prix_achat' => 810,
            'prix_vente' => 875,
        ])->assertOk();

        $this->assertDatabaseHas('hydrocarbures', [
            'id' => $hydrocarbureId,
            'libelle' => 'Essence super',
            'updated_by' => $updater->id,
        ]);
    }

    public function test_hydrocarbure_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/hydrocarbures', [
            'prix_achat' => -1,
            'prix_vente' => 'invalide',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['libelle', 'prix_achat', 'prix_vente']);
    }

    public function test_delete_route_is_absent_and_data_is_preserved(): void
    {
        $admin = $this->createUser('admin');
        $hydrocarbure = Hydrocarbure::create([
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => 850,
        ]);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/gestions/hydrocarbures/{$hydrocarbure->id}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('hydrocarbures', ['id' => $hydrocarbure->id]);
    }

    private function createUser(string $role = 'user'): User
    {
        $number = User::count() + 1;

        return User::create([
            'name' => ucfirst($role).' '.$number,
            'telephone' => '620'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
            'role' => $role,
            'password' => 'password',
        ]);
    }

    private function createManager(?Station $station = null): User
    {
        $manager = $this->createUser();
        $module = Module::firstOrCreate(
            ['name' => 'gerant_station'],
            ['description' => 'Gestion station', 'is_active' => true]
        );

        UserModule::create([
            'user_id' => $manager->id,
            'module_id' => $module->id,
            'code_acces' => 'GM'.str_pad((string) $manager->id, 6, '0', STR_PAD_LEFT),
            'is_active' => true,
        ]);

        if ($station) {
            AffectationStation::create([
                'station_id' => $station->id,
                'user_id' => $manager->id,
                'is_active' => true,
            ]);
        }

        return $manager;
    }

    private function createStation(): Station
    {
        $number = Station::count() + 1;

        return Station::create([
            'reference' => 'STA'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
            'libelle' => 'Station '.$number,
        ]);
    }
}
