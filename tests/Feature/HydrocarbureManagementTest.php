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

    public function test_assigned_manager_can_show_hydrocarbure_with_audit_relations(): void
    {
        $creator = $this->createUser('admin');
        $updater = $this->createUser('super_admin');
        $manager = $this->createManager($this->createStation());
        $hydrocarbure = Hydrocarbure::create([
            'libelle' => 'Petrole',
            'prix_achat' => 600,
            'prix_vente' => 650,
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);
        Sanctum::actingAs($manager);

        $this->getJson("/api/v1/gestions/hydrocarbures/{$hydrocarbure->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $hydrocarbure->id)
            ->assertJsonPath('data.libelle', 'Petrole')
            ->assertJsonPath('data.prix_achat', '600.00')
            ->assertJsonPath('data.prix_vente', '650.00')
            ->assertJsonPath('data.created_by.id', $creator->id)
            ->assertJsonPath('data.updated_by.id', $updater->id);
    }

    public function test_show_returns_404_for_missing_hydrocarbure(): void
    {
        Sanctum::actingAs($this->createUser('admin'));

        $this->getJson('/api/v1/gestions/hydrocarbures/999999')->assertNotFound();
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
            'created_by' => $updater->id,
            'updated_by' => $updater->id,
        ])->assertOk()
            ->assertJsonPath('data.prix_achat', '800.00')
            ->assertJsonPath('data.prix_vente', '850.00')
            ->assertJsonPath('data.created_by.id', $admin->id)
            ->assertJsonPath('data.updated_by.id', null);

        $hydrocarbureId = $response->json('data.id');
        $this->assertDatabaseHas('hydrocarbures', [
            'id' => $hydrocarbureId,
            'created_by' => $admin->id,
            'updated_by' => null,
        ]);

        Sanctum::actingAs($updater);
        $this->putJson("/api/v1/gestions/hydrocarbures/{$hydrocarbureId}", [
            'libelle' => 'Essence super',
            'prix_achat' => 810,
            'prix_vente' => 875,
            'created_by' => $updater->id,
            'updated_by' => $admin->id,
        ])->assertOk()
            ->assertJsonPath('data.created_by.id', $admin->id)
            ->assertJsonPath('data.updated_by.id', $updater->id);

        $this->assertDatabaseHas('hydrocarbures', [
            'id' => $hydrocarbureId,
            'libelle' => 'Essence super',
            'created_by' => $admin->id,
            'updated_by' => $updater->id,
        ]);
    }

    public function test_patch_updates_only_received_fields_and_preserves_creation_audit(): void
    {
        $creator = $this->createUser('admin');
        $updater = $this->createUser('super_admin');
        $hydrocarbure = Hydrocarbure::create([
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => 850,
            'created_by' => $creator->id,
        ]);
        Sanctum::actingAs($updater);

        $this->patchJson("/api/v1/gestions/hydrocarbures/{$hydrocarbure->id}", [
            'prix_vente' => 875,
        ])->assertOk()
            ->assertJsonPath('data.libelle', 'Essence')
            ->assertJsonPath('data.prix_achat', '800.00')
            ->assertJsonPath('data.prix_vente', '875.00')
            ->assertJsonPath('data.created_by.id', $creator->id)
            ->assertJsonPath('data.updated_by.id', $updater->id);

        $this->assertDatabaseHas('hydrocarbures', [
            'id' => $hydrocarbure->id,
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => 875,
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);
    }

    public function test_post_and_put_require_all_business_fields(): void
    {
        $admin = $this->createUser('admin');
        $hydrocarbure = Hydrocarbure::create([
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => 850,
        ]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/gestions/hydrocarbures', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['libelle', 'prix_achat', 'prix_vente']);

        $this->putJson("/api/v1/gestions/hydrocarbures/{$hydrocarbure->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['libelle', 'prix_achat', 'prix_vente']);
    }

    public function test_hydrocarbure_validation_rejects_invalid_prices(): void
    {
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/hydrocarbures', [
            'prix_achat' => -1,
            'prix_vente' => 'invalide',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['libelle', 'prix_achat', 'prix_vente']);

        $this->postJson('/api/v1/gestions/hydrocarbures', [
            'libelle' => 'Essence',
            'prix_achat' => 800,
            'prix_vente' => -1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['prix_vente']);
    }

    public function test_hydrocarbure_validation_rejects_a_label_longer_than_255_characters(): void
    {
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/hydrocarbures', [
            'libelle' => str_repeat('a', 256),
            'prix_achat' => 800,
            'prix_vente' => 850,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['libelle']);
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
