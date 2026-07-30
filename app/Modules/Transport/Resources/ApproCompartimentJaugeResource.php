<?php

namespace App\Modules\Transport\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ApproCompartimentJaugeResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $volumeTheorique = $this->volume_theorique !== null ? (float) $this->volume_theorique : 0.0;
        $volumeReel = $this->volume_reel !== null ? (float) $this->volume_reel : 0.0;

        return [
            'id' => $this->id,
            'approvision_id' => $this->approvision_id,
            'hydrocarbure_id' => $this->hydrocarbure_id,
            'num_compartiment' => $this->num_compartiment,
            'valeur_jauge' => $this->valeur_jauge !== null ? (float) $this->valeur_jauge : null,
            'volume_reel' => $this->volume_reel !== null ? (float) $this->volume_reel : null,
            'volume_theorique' => $this->volume_theorique !== null ? (float) $this->volume_theorique : null,
            'ecart' => $volumeTheorique - $volumeReel,

            'hydrocarbure' => $this->whenLoaded('hydrocarbure', function () {
                return [
                    'id' => $this->hydrocarbure?->id,
                    'libelle' => $this->hydrocarbure?->libelle,
                    'prix_achat' => $this->hydrocarbure?->prix_achat,
                    'prix_vente' => $this->hydrocarbure?->prix_vente,
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
