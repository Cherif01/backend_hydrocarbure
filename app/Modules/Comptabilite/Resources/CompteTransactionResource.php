<?php

namespace App\Modules\Comptabilite\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CompteTransactionResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'compte_source_id' => $this->compte_source_id,
            'compte_destination_id' => $this->compte_destination_id,
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'libelle' => $this->libelle,
            'commentaire' => $this->commentaire,
            'date_transaction' => $this->date_transaction?->format('d-m-Y H:i:s'),

            'compte_source' => $this->whenLoaded('compteSource', function () {
                return [
                    'id' => $this->compteSource?->id,
                    'numero_compte' => $this->compteSource?->numero_compte,
                    'libelle' => $this->compteSource?->libelle,
                    'devise' => $this->compteSource?->devise,
                    'is_active' => (bool) $this->compteSource?->is_active,
                ];
            }),
            'compte_destination' => $this->whenLoaded('compteDestination', function () {
                return [
                    'id' => $this->compteDestination?->id,
                    'numero_compte' => $this->compteDestination?->numero_compte,
                    'libelle' => $this->compteDestination?->libelle,
                    'devise' => $this->compteDestination?->devise,
                    'is_active' => (bool) $this->compteDestination?->is_active,
                ];
            }),

            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy?->id,
                    'name' => $this->createdBy?->name,
                    'telephone' => $this->createdBy?->telephone,
                ];
            }),
            'updated_by' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy?->id,
                    'name' => $this->updatedBy?->name,
                    'telephone' => $this->updatedBy?->telephone,
                ];
            }),

            'created_at' => $this->created_at?->format('d-m-Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d-m-Y H:i:s'),
        ];
    }
}

