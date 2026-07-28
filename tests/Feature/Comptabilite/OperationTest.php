<?php

namespace Tests\Feature\Comptabilite;

use App\Modules\Comptabilite\Models\Caisse;
use App\Modules\Comptabilite\Models\Operation;
use App\Modules\Comptabilite\Models\TypeOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationTest extends TestCase
{
    use RefreshDatabase, ComptabiliteTestHelpers;

    private function createTypeOperation(bool $nature = true): TypeOperation
    {
        return TypeOperation::create([
            'libelle' => 'Type '.uniqid(),
            'nature' => $nature,
            'is_active' => true,
        ]);
    }

    public function test_type_operation_id_is_required(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/operations', [
            'montant' => 1000,
            'date_operation' => now()->toDateTimeString(),
        ])->assertStatus(422);
    }

    public function test_non_scoped_user_can_create_operation_without_caisse(): void
    {
        $user = $this->createUser();
        $typeOperation = $this->createTypeOperation();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/operations', [
            'type_operation_id' => $typeOperation->id,
            'montant' => 500,
            'date_operation' => now()->toDateTimeString(),
        ])->assertStatus(200)->assertJsonPath('data.type_operation_id', $typeOperation->id);
    }

    public function test_caisse_must_belong_to_given_station(): void
    {
        $user = $this->createUser();
        $typeOperation = $this->createTypeOperation();
        $station = $this->createStation();
        $otherStation = $this->createStation();

        $caisse = Caisse::create(['station_id' => $station->id, 'reference' => 'CAISSE-OP-1', 'libelle' => 'Caisse op']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/operations', [
            'type_operation_id' => $typeOperation->id,
            'station_id' => $otherStation->id,
            'caisse_id' => $caisse->id,
            'montant' => 200,
            'date_operation' => now()->toDateTimeString(),
        ])->assertStatus(422);
    }

    public function test_station_is_deduced_from_caisse_when_not_provided(): void
    {
        $user = $this->createUser();
        $typeOperation = $this->createTypeOperation();
        $station = $this->createStation();

        $caisse = Caisse::create(['station_id' => $station->id, 'reference' => 'CAISSE-OP-2', 'libelle' => 'Caisse op 2']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/operations', [
            'type_operation_id' => $typeOperation->id,
            'caisse_id' => $caisse->id,
            'montant' => 200,
            'date_operation' => now()->toDateTimeString(),
        ])->assertStatus(200)->assertJsonPath('data.station_id', $station->id);
    }

    public function test_scoped_user_station_is_forced_and_mismatched_caisse_rejected(): void
    {
        $station = $this->createStation();
        $otherStation = $this->createStation();
        $user = $this->createStationScopedUser($station);
        $typeOperation = $this->createTypeOperation();

        $caisseOther = Caisse::create(['station_id' => $otherStation->id, 'reference' => 'CAISSE-OP-3', 'libelle' => 'Caisse autre station']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/operations', [
            'type_operation_id' => $typeOperation->id,
            'caisse_id' => $caisseOther->id,
            'montant' => 200,
            'date_operation' => now()->toDateTimeString(),
        ])->assertStatus(422);
    }

    public function test_scoped_user_station_forced_with_matching_caisse_succeeds(): void
    {
        $station = $this->createStation();
        $user = $this->createStationScopedUser($station);
        $typeOperation = $this->createTypeOperation();

        $caisse = Caisse::create(['station_id' => $station->id, 'reference' => 'CAISSE-OP-4', 'libelle' => 'Caisse meme station']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/operations', [
            'type_operation_id' => $typeOperation->id,
            'caisse_id' => $caisse->id,
            'montant' => 200,
            'date_operation' => now()->toDateTimeString(),
        ])->assertStatus(200)->assertJsonPath('data.station_id', $station->id);
    }

    public function test_scoped_user_only_sees_own_station_operations(): void
    {
        $station = $this->createStation();
        $otherStation = $this->createStation();
        $typeOperation = $this->createTypeOperation();

        Operation::create(['type_operation_id' => $typeOperation->id, 'station_id' => $station->id, 'montant' => 100, 'date_operation' => now()]);
        Operation::create(['type_operation_id' => $typeOperation->id, 'station_id' => $otherStation->id, 'montant' => 200, 'date_operation' => now()]);

        $user = $this->createStationScopedUser($station);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/comptabilite/operations')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_scoped_user_cannot_access_operation_of_another_station(): void
    {
        $station = $this->createStation();
        $otherStation = $this->createStation();
        $typeOperation = $this->createTypeOperation();

        $operation = Operation::create(['type_operation_id' => $typeOperation->id, 'station_id' => $otherStation->id, 'montant' => 200, 'date_operation' => now()]);

        $user = $this->createStationScopedUser($station);

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/comptabilite/operations/{$operation->id}")->assertStatus(404);
    }

    public function test_destroy_soft_deletes_operation(): void
    {
        $user = $this->createUser();
        $typeOperation = $this->createTypeOperation();

        $operation = Operation::create([
            'type_operation_id' => $typeOperation->id,
            'montant' => 100,
            'date_operation' => now(),
        ]);

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/comptabilite/operations/{$operation->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('operations', ['id' => $operation->id]);
    }

    public function test_montant_must_be_at_least_zero(): void
    {
        $user = $this->createUser();
        $typeOperation = $this->createTypeOperation();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/operations', [
            'type_operation_id' => $typeOperation->id,
            'montant' => -50,
            'date_operation' => now()->toDateTimeString(),
        ])->assertStatus(422);
    }
}
