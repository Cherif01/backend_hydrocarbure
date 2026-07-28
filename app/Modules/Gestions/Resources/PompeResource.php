<?php

namespace App\Modules\Gestions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class PompeResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'station_id' => $this->station_id,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'station' => $this->whenLoaded('station', function () {
                return [
                    'id' => $this->station?->id,
                    'reference' => $this->station?->reference,
                    'libelle' => $this->station?->libelle,
                    'description' => $this->station?->description,
                    'adresse' => $this->station?->adresse,
                    'ville' => $this->station?->ville,
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
