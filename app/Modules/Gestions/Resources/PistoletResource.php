<?php

namespace App\Modules\Gestions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class PistoletResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pompe_id' => $this->pompe_id,
            'hydrocarbure_id' => $this->hydrocarbure_id,
            'libelle' => $this->libelle,
            'is_active' => (bool) $this->is_active,
            'latest_index' => $this->relationLoaded('latestAffectationPistolet')
                ? ($this->latestAffectationPistolet?->index_fermeture !== null ? (float) $this->latestAffectationPistolet?->index_fermeture : 0)
                : 0,
            'pompe' => $this->whenLoaded('pompe', function () {
                return [
                    'id' => $this->pompe?->id,
                    'reference' => $this->pompe?->reference,
                    'station_id' => $this->pompe?->station_id,
                    'libelle' => $this->pompe?->libelle,
                    'station' => $this->pompe?->relationLoaded('station') ? [
                        'id' => $this->pompe?->station?->id,
                        'reference' => $this->pompe?->station?->reference,
                        'libelle' => $this->pompe?->station?->libelle,
                    ] : null,
                ];
            }),
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
