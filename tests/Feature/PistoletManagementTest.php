<?php

namespace Tests\Feature;

use App\Modules\Administration\Models\Module;
use App\Modules\Administration\Models\User;
use App\Modules\Administration\Models\UserModule;
use App\Modules\Gestions\Models\AffectationStation;
use App\Modules\Gestions\Models\Hydrocarbure;
use App\Modules\Gestions\Models\Pistolet;
use App\Modules\Gestions\Models\Pompe;
use App\Modules\Gestions\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PistoletManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_requires_authentication_and_an_authorized_profile(): void
    {
        $this->getJson('/api/v1/gestions/pistolets')->assertUnauthorized();

        Sanctum::actingAs($this->createUser());
        $this->getJson('/api/v1/gestions/pistolets')->assertForbidden();
    }

    public function test_admin_and_super_admin_have_global_access(): void
    {
        $hydrocarbure = $this->createHydrocarbure();
        $firstPompe = $this->createPompe($this->createStation(), 'POM01');
        $secondPompe = $this->createPompe($this->createStation(), 'POM02');
        $this->createPistolet($firstPompe, $hydrocarbure, 'Pistolet 1');
        $this->createPistolet($secondPompe, $hydrocarbure, 'Pistolet 2');

        foreach (['admin', 'super_admin'] as $role) {
            Sanctum::actingAs($this->createUser($role));

            $this->getJson('/api/v1/gestions/pistolets')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        }
    }

    public function test_manager_without_active_station_assignment_is_refused(): void
    {
        Sanctum::actingAs($this->createManager());

        $this->getJson('/api/v1/gestions/pistolets')->assertForbidden();
    }

    public function test_manager_list_is_scoped_and_foreign_pistolet_returns_404(): void
    {
        $managerStation = $this->createStation();
        $otherStation = $this->createStation();
        $hydrocarbure = $this->createHydrocarbure();
        $local = $this->createPistolet(
            $this->createPompe($managerStation, 'POM01'),
            $hydrocarbure,
            'Pistolet local'
        );
        $foreign = $this->createPistolet(
            $this->createPompe($otherStation, 'POM02'),
            $hydrocarbure,
            'Pistolet distant'
        );
        Sanctum::actingAs($this->createManager($managerStation));

        $this->getJson('/api/v1/gestions/pistolets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $local->id);

        $this->getJson("/api/v1/gestions/pistolets/{$foreign->id}")->assertNotFound();
    }

    public function test_manager_cannot_create_pistolet_on_foreign_pump(): void
    {
        $managerStation = $this->createStation();
        $foreignPompe = $this->createPompe($this->createStation(), 'POM01');
        $hydrocarbure = $this->createHydrocarbure();
        Sanctum::actingAs($this->createManager($managerStation));

        $this->postJson('/api/v1/gestions/pistolets', [
            'pompe_id' => $foreignPompe->id,
            'hydrocarbure_id' => $hydrocarbure->id,
            'libelle' => 'Pistolet interdit',
        ])->assertNotFound();

        $this->assertDatabaseMissing('pistolets', ['libelle' => 'Pistolet interdit']);
    }

    public function test_manager_cannot_move_pistolet_to_foreign_pump(): void
    {
        $managerStation = $this->createStation();
        $hydrocarbure = $this->createHydrocarbure();
        $localPompe = $this->createPompe($managerStation, 'POM01');
        $foreignPompe = $this->createPompe($this->createStation(), 'POM02');
        $pistolet = $this->createPistolet($localPompe, $hydrocarbure, 'Pistolet local');
        Sanctum::actingAs($this->createManager($managerStation));

        $this->putJson("/api/v1/gestions/pistolets/{$pistolet->id}", [
            'pompe_id' => $foreignPompe->id,
            'hydrocarbure_id' => $hydrocarbure->id,
            'libelle' => 'Pistolet deplace',
        ])->assertNotFound();

        $this->assertDatabaseHas('pistolets', [
            'id' => $pistolet->id,
            'pompe_id' => $localPompe->id,
            'libelle' => 'Pistolet local',
        ]);
    }

    public function test_manager_can_create_and_update_local_pistolet_with_global_hydrocarbure(): void
    {
        $station = $this->createStation();
        $pompe = $this->createPompe($station, 'POM01');
        $firstHydrocarbure = $this->createHydrocarbure('Essence');
        $secondHydrocarbure = $this->createHydrocarbure('Gasoil');
        $manager = $this->createManager($station);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/gestions/pistolets', [
            'pompe_id' => $pompe->id,
            'hydrocarbure_id' => $firstHydrocarbure->id,
            'libelle' => 'Pistolet 1',
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('data.hydrocarbure_id', $firstHydrocarbure->id);

        $pistoletId = $response->json('data.id');
        $this->assertDatabaseHas('pistolets', [
            'id' => $pistoletId,
            'created_by' => $manager->id,
        ]);

        $this->putJson("/api/v1/gestions/pistolets/{$pistoletId}", [
            'pompe_id' => $pompe->id,
            'hydrocarbure_id' => $secondHydrocarbure->id,
            'libelle' => 'Pistolet 1 actualise',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.hydrocarbure_id', $secondHydrocarbure->id);

        $this->assertDatabaseHas('pistolets', [
            'id' => $pistoletId,
            'hydrocarbure_id' => $secondHydrocarbure->id,
            'updated_by' => $manager->id,
        ]);
    }

    public function test_foreign_keys_are_validated(): void
    {
        Sanctum::actingAs($this->createUser('admin'));

        $this->postJson('/api/v1/gestions/pistolets', [
            'pompe_id' => 999998,
            'hydrocarbure_id' => 999999,
            'libelle' => 'Invalide',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['pompe_id', 'hydrocarbure_id']);
    }

    public function test_delete_route_is_absent_and_data_is_preserved(): void
    {
        $pistolet = $this->createPistolet(
            $this->createPompe($this->createStation(), 'POM01'),
            $this->createHydrocarbure(),
            'Pistolet conserve'
        );
        Sanctum::actingAs($this->createUser('admin'));

        $this->deleteJson("/api/v1/gestions/pistolets/{$pistolet->id}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('pistolets', ['id' => $pistolet->id]);
    }

    private function createUser(string $role = 'user'): User
    {
        $number = User::count() + 1;

        return User::create([
            'name' => ucfirst($role).' '.$number,
            'telephone' => '622'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
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
            'code_acces' => 'PI'.str_pad((string) $manager->id, 6, '0', STR_PAD_LEFT),
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
            'reference' => 'STI'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
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

    private function createHydrocarbure(string $libelle = 'Essence'): Hydrocarbure
    {
        return Hydrocarbure::create([
            'libelle' => $libelle,
            'prix_achat' => 800,
            'prix_vente' => 850,
        ]);
    }

    private function createPistolet(
        Pompe $pompe,
        Hydrocarbure $hydrocarbure,
        string $libelle
    ): Pistolet {
        return Pistolet::create([
            'pompe_id' => $pompe->id,
            'hydrocarbure_id' => $hydrocarbure->id,
            'libelle' => $libelle,
        ]);
    }
}
