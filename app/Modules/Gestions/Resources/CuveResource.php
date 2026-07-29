<?php

namespace App\Modules\Gestions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CuveResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,
            'reference' => $this->reference,
            'libelle' => $this->libelle,
            'capacite' => $this->capacite !== null ? (int) $this->capacite : null,
            'is_active' => (bool) $this->is_active,

            'station' => $this->whenLoaded('station', function () {
                return [
                    'id' => $this->station?->id,
                    'reference' => $this->station?->reference,
                    'libelle' => $this->station?->libelle,
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
