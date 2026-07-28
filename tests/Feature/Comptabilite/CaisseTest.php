<?php

namespace Tests\Feature\Comptabilite;

use App\Modules\Comptabilite\Models\Caisse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaisseTest extends TestCase
{
    use RefreshDatabase, ComptabiliteTestHelpers;

    public function test_non_scoped_user_must_provide_station_id(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/caisses', [
            'reference' => 'CAISSE-001',
            'libelle' => 'Caisse principale',
            'solde_initial' => 1000,
        ])->assertStatus(422);
    }

    public function test_non_scoped_user_can_create_caisse_for_a_station(): void
    {
        $user = $this->createUser();
        $station = $this->createStation();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/caisses', [
            'station_id' => $station->id,
            'reference' => 'CAISSE-001',
            'libelle' => 'Caisse principale',
            'solde_initial' => 1000,
        ])->assertStatus(200)
            ->assertJsonPath('data.station_id', $station->id)
            ->assertJsonPath('data.reference', 'CAISSE-001')
            ->assertJsonPath('data.solde_initial', 1000);
    }

    public function test_reference_must_be_unique(): void
    {
        $user = $this->createUser();
        $station = $this->createStation();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/caisses', [
            'station_id' => $station->id,
            'reference' => 'CAISSE-DUP',
            'libelle' => 'Caisse A',
        ])->assertStatus(200);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/caisses', [
            'station_id' => $station->id,
            'reference' => 'CAISSE-DUP',
            'libelle' => 'Caisse B',
        ])->assertStatus(422);
    }

    public function test_update_ignores_own_reference_for_uniqueness(): void
    {
        $user = $this->createUser();
        $station = $this->createStation();

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/caisses', [
            'station_id' => $station->id,
            'reference' => 'CAISSE-SELF',
            'libelle' => 'Caisse self',
        ]);
        $id = $create->json('data.id');

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/comptabilite/caisses/{$id}", [
            'station_id' => $station->id,
            'reference' => 'CAISSE-SELF',
            'libelle' => 'Caisse self renommee',
        ])->assertStatus(200)->assertJsonPath('data.libelle', 'Caisse self renommee');
    }

    public function test_scoped_user_gets_station_id_auto_injected(): void
    {
        $station = $this->createStation();
        $user = $this->createStationScopedUser($station);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/caisses', [
            'reference' => 'CAISSE-SCOPED',
            'libelle' => 'Caisse scope',
        ])->assertStatus(200)->assertJsonPath('data.station_id', $station->id);
    }

    public function test_scoped_user_cannot_force_another_station_id(): void
    {
        $station = $this->createStation();
        $otherStation = $this->createStation();
        $user = $this->createStationScopedUser($station);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/caisses', [
            'station_id' => $otherStation->id,
            'reference' => 'CAISSE-FORCE',
            'libelle' => 'Caisse force',
        ])->assertStatus(200)->assertJsonPath('data.station_id', $station->id);
    }

    public function test_scoped_user_only_sees_caisses_of_own_station(): void
    {
        $station = $this->createStation();
        $otherStation = $this->createStation();

        Caisse::create(['station_id' => $station->id, 'reference' => 'CAISSE-MINE', 'libelle' => 'Ma caisse']);
        Caisse::create(['station_id' => $otherStation->id, 'reference' => 'CAISSE-OTHER', 'libelle' => 'Autre caisse']);

        $user = $this->createStationScopedUser($station);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/comptabilite/caisses');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertEquals('CAISSE-MINE', $response->json('data.0.reference'));
    }

    public function test_scoped_user_cannot_access_update_or_delete_caisse_of_another_station(): void
    {
        $station = $this->createStation();
        $otherStation = $this->createStation();

        $caisse = Caisse::create(['station_id' => $otherStation->id, 'reference' => 'CAISSE-OTHER', 'libelle' => 'Autre caisse']);

        $user = $this->createStationScopedUser($station);

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/comptabilite/caisses/{$caisse->id}")->assertStatus(404);

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/comptabilite/caisses/{$caisse->id}", [
            'reference' => 'CAISSE-OTHER',
            'libelle' => 'Tentative modif',
        ])->assertStatus(404);

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/comptabilite/caisses/{$caisse->id}")->assertStatus(404);

        $this->assertDatabaseHas('caisses', ['id' => $caisse->id]);
    }

    public function test_scoped_user_without_active_affectation_gets_403(): void
    {
        $station = $this->createStation();
        $user = $this->createStationScopedUser($station, withActiveAffectation: false);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/comptabilite/caisses')->assertStatus(403);
    }
}
