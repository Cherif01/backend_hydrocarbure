<?php

namespace App\Modules\Gestions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CuveJaugeageResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $volumeTheorique = $this->volume_theorique !== null ? (float) $this->volume_theorique : 0.0;
        $volumeReel = $this->volume_reel !== null ? (float) $this->volume_reel : 0.0;

        return [
            'id' => $this->id,
            'cuve_id' => $this->cuve_id,
            'date_jauge' => $this->date_jauge?->format('d-m-Y H:i:s'),
            'valeur_jauge' => $this->valeur_jauge !== null ? (float) $this->valeur_jauge : null,
            'volume_reel' => $this->volume_reel !== null ? (float) $this->volume_reel : null,
            'volume_theorique' => $this->volume_theorique !== null ? (float) $this->volume_theorique : null,
            'ecart' => $volumeTheorique - $volumeReel,
            'commentaire' => $this->commentaire,

            'cuve' => $this->whenLoaded('cuve', function () {
                return [
                    'id' => $this->cuve?->id,
                    'station_id' => $this->cuve?->station_id,
                    'reference' => $this->cuve?->reference,
                    'libelle' => $this->cuve?->libelle,
                    'capacite' => $this->cuve?->capacite,
                    'is_active' => (bool) $this->cuve?->is_active,
                    'station' => $this->cuve?->relationLoaded('station') ? [
                        'id' => $this->cuve?->station?->id,
                        'reference' => $this->cuve?->station?->reference,
                        'libelle' => $this->cuve?->station?->libelle,
                    ] : null,
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

