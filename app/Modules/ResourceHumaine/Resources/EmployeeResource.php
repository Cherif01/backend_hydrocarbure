<?php

namespace App\Modules\ResourceHumaine\Resources;

use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class EmployeeResource extends JsonResource
{
    use CloudflareUpload;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'post_id' => $this->post_id,
            'station_id' => $this->station_id,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'salaire_base' => $this->salaire_base !== null ? (float) $this->salaire_base : null,

            'avatar_url' => $this->avatar ? $this->getImageUrl($this->avatar, 'employees') : null,
            'is_active' => (bool) $this->is_active,

            'post' => $this->whenLoaded('post', function () {
                return [
                    'id' => $this->post?->id,
                    'libelle' => $this->post?->libelle,
                    'is_active' => $this->post?->is_active,
                ];
            }),

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
