<?php

namespace App\Modules\Comptabilite\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class VersementResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caisse_id' => $this->caisse_id,
            'compte_id' => $this->compte_id,
            'type' => $this->type,
            'user_id' => $this->user_id,
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'date_versement' => $this->date_versement?->format('d-m-Y H:i:s'),
            'date_reception' => $this->date_reception?->format('d-m-Y H:i:s'),
            'commentaire' => $this->commentaire,
            'status' => $this->status,

            'compte' => $this->whenLoaded('compte', function () {
                return [
                    'id' => $this->compte?->id,
                    'numero_compte' => $this->compte?->numero_compte,
                    'libelle' => $this->compte?->libelle,
                    'devise' => $this->compte?->devise,
                ];
            }),

            'caisse' => $this->whenLoaded('caisse', function () {
                return [
                    'id' => $this->caisse?->id,
                    'reference' => $this->caisse?->reference,
                    'libelle' => $this->caisse?->libelle,
                    'station_id' => $this->caisse?->station_id,
                    'station' => $this->caisse?->relationLoaded('station') ? [
                        'id' => $this->caisse?->station?->id,
                        'reference' => $this->caisse?->station?->reference,
                        'libelle' => $this->caisse?->station?->libelle,
                    ] : null,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'telephone' => $this->user?->telephone,
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
