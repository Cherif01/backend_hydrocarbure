<?php

namespace Tests\Feature;

use App\Modules\Administration\Models\Module;
use App\Modules\Administration\Models\User;
use App\Modules\Administration\Models\UserModule;
use App\Modules\Gestions\Models\AffectationStation;
use App\Modules\Gestions\Models\Pompe;
use App\Modules\Gestions\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PompeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_requires_authentication_and_an_authorized_profile(): void
    {
        $this->getJson('/api/v1/gestions/pompes')->assertUnauthorized();

        Sanctum::actingAs($this->createUser());
        $this->getJson('/api/v1/gestions/pompes')->assertForbidden();
    }

    public function test_admin_and_super_admin_have_global_access(): void
    {
        $firstStation = $this->createStation();
        $secondStation = $this->createStation();
        $this->createPompe($firstStation, 'POM01');
        $this->createPompe($secondStation, 'POM02');

        foreach (['admin', 'super_admin'] as $role) {
            Sanctum::actingAs($this->createUser($role));

            $this->getJson('/api/v1/gestions/pompes')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        }
    }

    public function test_admin_remains_global_when_also_assigned_to_gerant_module(): void
    {
        $firstStation = $this->createStation();
        $secondStation = $this->createStation();
        $this->createPompe($firstStation, 'POM01');
        $this->createPompe($secondStation, 'POM02');
        $admin = $this->createUser('admin');
        $module = Module::create([
            'name' => 'gerant_station',
            'description' => 'Gestion station',
            'is_active' => true,
        ]);
        UserModule::create([
            'user_id' => $admin->id,
            'module_id' => $module->id,
            'code_acces' => 'ADMIN1',
            'is_active' => true,
        ]);
        AffectationStation::create([
            'station_id' => $firstStation->id,
            'user_id' => $admin->id,
            'is_active' => true,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/gestions/pompes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_manager_without_active_station_assignment_is_refused(): void
    {
        Sanctum::actingAs($this->createManager());

        $this->getJson('/api/v1/gestions/pompes')->assertForbidden();
    }

    public function test_manager_list_is_scoped_and_foreign_pump_returns_404(): void
    {
        $managerStation = $this->createStation();
        $otherStation = $this->createStation();
        $localPompe = $this->createPompe($managerStation, 'POM01');
        $foreignPompe = $this->createPompe($otherStation, 'POM02');
        Sanctum::actingAs($this->createManager($managerStation));

        $this->getJson('/api/v1/gestions/pompes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $localPompe->id);

        $this->getJson("/api/v1/gestions/pompes/{$foreignPompe->id}")->assertNotFound();
    }

    public function test_manager_can_show_local_pump_with_main_resource_relations(): void
    {
        $creator = $this->createUser('admin');
        $updater = $this->createUser('super_admin');
        $station = $this->createStation();
        $pompe = $this->createPompe($station, 'POM01');
        $pompe->update([
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);
        Sanctum::actingAs($this->createManager($station));

        $this->getJson("/api/v1/gestions/pompes/{$pompe->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pompe->id)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.station.id', $station->id)
            ->assertJsonPath('data.created_by.id', $creator->id)
            ->assertJsonPath('data.updated_by.id', $updater->id);
    }

    public function test_manager_cannot_update_foreign_pump(): void
    {
        $managerStation = $this->createStation();
        $otherStation = $this->createStation();
        $foreignPompe = $this->createPompe($otherStation, 'POM01');
        Sanctum::actingAs($this->createManager($managerStation));

        $this->putJson("/api/v1/gestions/pompes/{$foreignPompe->id}", [
            'station_id' => $otherStation->id,
            'libelle' => 'Modification interdite',
        ])->assertNotFound();

        $this->assertDatabaseHas('pompes', [
            'id' => $foreignPompe->id,
            'station_id' => $otherStation->id,
            'libelle' => 'Pompe POM01',
            'updated_by' => null,
        ]);
    }

    public function test_manager_station_is_forced_on_creation(): void
    {
        $managerStation = $this->createStation();
        $otherStation = $this->createStation();
        $manager = $this->createManager($managerStation);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => $otherStation->id,
            'libelle' => 'Pompe locale',
            'description' => 'Description',
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('data.station_id', $managerStation->id)
            ->assertJsonPath('data.reference', 'POM01');

        $this->assertDatabaseHas('pompes', [
            'id' => $response->json('data.id'),
            'station_id' => $managerStation->id,
            'created_by' => $manager->id,
        ]);
    }

    public function test_invalid_station_sent_by_manager_is_ignored_before_validation(): void
    {
        $managerStation = $this->createStation();
        $manager = $this->createManager($managerStation);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => 999999,
            'libelle' => 'Pompe avec station imposee',
        ])->assertOk()
            ->assertJsonMissingValidationErrors(['station_id'])
            ->assertJsonPath('data.station_id', $managerStation->id);

        $this->assertDatabaseHas('pompes', [
            'id' => $response->json('data.id'),
            'station_id' => $managerStation->id,
        ]);
    }

    public function test_manager_cannot_move_pump_to_another_station(): void
    {
        $managerStation = $this->createStation();
        $otherStation = $this->createStation();
        $pompe = $this->createPompe($managerStation, 'POM01');
        $manager = $this->createManager($managerStation);
        Sanctum::actingAs($manager);

        $this->putJson("/api/v1/gestions/pompes/{$pompe->id}", [
            'reference' => $pompe->reference,
            'station_id' => $otherStation->id,
            'libelle' => 'Pompe mise a jour',
            'description' => null,
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.station_id', $managerStation->id);

        $this->assertDatabaseHas('pompes', [
            'id' => $pompe->id,
            'station_id' => $managerStation->id,
            'updated_by' => $manager->id,
        ]);
    }

    public function test_manager_station_is_also_forced_during_partial_update(): void
    {
        $managerStation = $this->createStation();
        $otherStation = $this->createStation();
        $pompe = $this->createPompe($managerStation, 'POM01');
        $manager = $this->createManager($managerStation);
        Sanctum::actingAs($manager);

        $this->patchJson("/api/v1/gestions/pompes/{$pompe->id}", [
            'station_id' => $otherStation->id,
            'description' => 'Description partielle',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01')
            ->assertJsonPath('data.station_id', $managerStation->id)
            ->assertJsonPath('data.libelle', 'Pompe POM01')
            ->assertJsonPath('data.description', 'Description partielle');

        $this->assertDatabaseHas('pompes', [
            'id' => $pompe->id,
            'station_id' => $managerStation->id,
            'updated_by' => $manager->id,
        ]);
    }

    public function test_admin_can_choose_and_change_station(): void
    {
        $firstStation = $this->createStation();
        $secondStation = $this->createStation();
        $admin = $this->createUser('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => $firstStation->id,
            'libelle' => 'Pompe administrative',
        ])->assertOk();

        $pompeId = $response->json('data.id');
        $this->putJson("/api/v1/gestions/pompes/{$pompeId}", [
            'reference' => $response->json('data.reference'),
            'station_id' => $secondStation->id,
            'libelle' => 'Pompe deplacee',
        ])->assertOk();

        $this->assertDatabaseHas('pompes', [
            'id' => $pompeId,
            'station_id' => $secondStation->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_super_admin_can_create_and_fully_update_pump(): void
    {
        $firstStation = $this->createStation();
        $secondStation = $this->createStation();
        $superAdmin = $this->createUser('super_admin');
        Sanctum::actingAs($superAdmin);

        $response = $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => $firstStation->id,
            'libelle' => 'Pompe super admin',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01')
            ->assertJsonPath('data.created_by.id', $superAdmin->id);

        $pompeId = $response->json('data.id');
        $this->putJson("/api/v1/gestions/pompes/{$pompeId}", [
            'station_id' => $secondStation->id,
            'libelle' => 'Pompe super admin actualisee',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01')
            ->assertJsonPath('data.station_id', $secondStation->id)
            ->assertJsonPath('data.updated_by.id', $superAdmin->id);

        $this->assertDatabaseHas('pompes', [
            'id' => $pompeId,
            'reference' => 'POM01',
            'station_id' => $secondStation->id,
            'created_by' => $superAdmin->id,
            'updated_by' => $superAdmin->id,
        ]);
    }

    public function test_patch_is_partial_and_preserves_absent_null_or_empty_reference(): void
    {
        $station = $this->createStation();
        $pompe = $this->createPompe($station, 'POM01');
        $admin = $this->createUser('admin');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/gestions/pompes/{$pompe->id}", [
            'description' => 'Premiere modification',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01')
            ->assertJsonPath('data.station_id', $station->id)
            ->assertJsonPath('data.libelle', 'Pompe POM01');

        $this->patchJson("/api/v1/gestions/pompes/{$pompe->id}", [
            'reference' => null,
            'description' => 'Deuxieme modification',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01');

        $this->patchJson("/api/v1/gestions/pompes/{$pompe->id}", [
            'reference' => '',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('pompes', [
            'id' => $pompe->id,
            'reference' => 'POM01',
            'station_id' => $station->id,
            'libelle' => 'Pompe POM01',
            'description' => 'Deuxieme modification',
            'is_active' => false,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_reference_generation_ignores_history_and_keeps_two_digits(): void
    {
        $station = $this->createStation();
        $this->createPompe($station, 'POM01');
        $this->createPompe($station, 'ANCIENNE');
        $this->createPompe($station, 'POM99');
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => $station->id,
            'libelle' => 'Pompe suivante',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM100');
    }

    public function test_automatic_reference_increments_from_pom01_to_pom02(): void
    {
        $station = $this->createStation();
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => $station->id,
            'libelle' => 'Premiere pompe',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01');

        $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => $station->id,
            'libelle' => 'Deuxieme pompe',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM02');
    }

    public function test_empty_references_are_generated_and_valid_custom_references_are_preserved(): void
    {
        $station = $this->createStation();
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/pompes', [
            'reference' => 'POM-CUSTOM',
            'station_id' => $station->id,
            'libelle' => 'Pompe personnalisee',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM-CUSTOM');

        $this->postJson('/api/v1/gestions/pompes', [
            'reference' => '0',
            'station_id' => $station->id,
            'libelle' => 'Pompe zero',
        ])->assertOk()
            ->assertJsonPath('data.reference', '0');

        $this->postJson('/api/v1/gestions/pompes', [
            'reference' => null,
            'station_id' => $station->id,
            'libelle' => 'Pompe sans reference',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM01');

        $this->postJson('/api/v1/gestions/pompes', [
            'reference' => '',
            'station_id' => $station->id,
            'libelle' => 'Pompe avec reference vide',
        ])->assertOk()
            ->assertJsonPath('data.reference', 'POM02');

        $this->assertDatabaseHas('pompes', [
            'reference' => '0',
            'station_id' => $station->id,
        ]);
    }

    public function test_reference_must_be_unique_and_station_must_exist(): void
    {
        $station = $this->createStation();
        $this->createPompe($station, 'POM01');
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/pompes', [
            'reference' => 'POM01',
            'station_id' => $station->id,
            'libelle' => 'Doublon',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reference']);

        $this->postJson('/api/v1/gestions/pompes', [
            'station_id' => 999999,
            'libelle' => 'Station inconnue',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['station_id']);
    }

    public function test_duplicate_reference_is_rejected_during_update(): void
    {
        $station = $this->createStation();
        $firstPompe = $this->createPompe($station, 'POM01');
        $secondPompe = $this->createPompe($station, 'POM02');
        Sanctum::actingAs($this->createUser('admin'));

        $this->putJson("/api/v1/gestions/pompes/{$secondPompe->id}", [
            'reference' => $firstPompe->reference,
            'station_id' => $station->id,
            'libelle' => 'Pompe en doublon',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reference']);

        $this->assertDatabaseHas('pompes', [
            'id' => $secondPompe->id,
            'reference' => 'POM02',
        ]);
    }

    public function test_null_active_status_is_rejected_when_present(): void
    {
        $station = $this->createStation();
        $pompe = $this->createPompe($station, 'POM01');
        Sanctum::actingAs($this->createUser('admin'));

        $this->patchJson("/api/v1/gestions/pompes/{$pompe->id}", [
            'is_active' => null,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);

        $this->assertDatabaseHas('pompes', [
            'id' => $pompe->id,
            'is_active' => true,
        ]);
    }

    public function test_incomplete_put_requires_label_and_admin_station(): void
    {
        $station = $this->createStation();
        $pompe = $this->createPompe($station, 'POM01');
        Sanctum::actingAs($this->createUser('admin'));

        $this->putJson("/api/v1/gestions/pompes/{$pompe->id}", [
            'description' => 'Payload incomplet',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['station_id', 'libelle']);

        $this->assertDatabaseHas('pompes', [
            'id' => $pompe->id,
            'description' => null,
        ]);
    }

    public function test_delete_route_is_absent_and_data_is_preserved(): void
    {
        $pompe = $this->createPompe($this->createStation(), 'POM01');
        Sanctum::actingAs($this->createUser('admin'));

        $this->deleteJson("/api/v1/gestions/pompes/{$pompe->id}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('pompes', ['id' => $pompe->id]);
    }

    private function createUser(string $role = 'user'): User
    {
        $number = User::count() + 1;

        return User::create([
            'name' => ucfirst($role).' '.$number,
            'telephone' => '621'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
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
            'code_acces' => 'PM'.str_pad((string) $manager->id, 6, '0', STR_PAD_LEFT),
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
            'reference' => 'STP'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
            'libelle' => 'Station '.$number,
        ]);
    }

    private function createPompe(Station $station, string $reference): Pompe
    {
        return Pompe::create([
            'reference' => $reference,
            'station_id' => $station->id,
            'libelle' => 'Pompe '.$reference,
        ]);
    }
}
