<?php

namespace App\Modules\Comptabilite\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ClientDepotResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'reference' => $this->reference,
            'libelle' => $this->libelle,
            'commentaire' => $this->commentaire,
            'date_depot' => $this->date_depot?->format('d-m-Y H:i:s'),
            'montant' => $this->montant !== null ? (float) $this->montant : null,

            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'name' => $this->client?->name,
                    'telephone' => $this->client?->telephone,
                    'email' => $this->client?->email,
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

