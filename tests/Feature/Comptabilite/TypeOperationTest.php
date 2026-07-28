<?php

namespace Tests\Feature\Comptabilite;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypeOperationTest extends TestCase
{
    use RefreshDatabase, ComptabiliteTestHelpers;

    public function test_it_creates_a_type_operation(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/type-operations', [
            'libelle' => 'Vente carburant',
            'nature' => true,
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.libelle', 'Vente carburant')
            ->assertJsonPath('data.nature', true)
            ->assertJsonPath('data.nature_libelle', 'entree');

        $this->assertDatabaseHas('type_operations', ['libelle' => 'Vente carburant']);
    }

    public function test_libelle_must_be_unique(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/type-operations', [
            'libelle' => 'Achat carburant',
            'nature' => false,
        ])->assertStatus(200);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/type-operations', [
            'libelle' => 'Achat carburant',
            'nature' => false,
        ])->assertStatus(422);
    }

    public function test_nature_is_required(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/type-operations', [
            'libelle' => 'Sans nature',
        ])->assertStatus(422);
    }

    public function test_it_lists_updates_and_deletes_a_type_operation(): void
    {
        $user = $this->createUser();

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/comptabilite/type-operations', [
            'libelle' => 'Depense diverse',
            'nature' => false,
        ]);
        $id = $create->json('data.id');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/comptabilite/type-operations')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/comptabilite/type-operations/{$id}", [
            'libelle' => 'Depense diverse modifiee',
            'nature' => false,
        ])->assertStatus(200)->assertJsonPath('data.libelle', 'Depense diverse modifiee');

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/comptabilite/type-operations/{$id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('type_operations', ['id' => $id]);
    }
}
