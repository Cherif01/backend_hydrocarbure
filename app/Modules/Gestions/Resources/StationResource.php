<?php

namespace App\Modules\Gestions\Resources;

use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class StationResource extends JsonResource
{
    use CloudflareUpload;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'image' => $this->image,
            'image_url' => $this->image ? $this->getImageUrl($this->image, 'stations') : null,
            'is_active' => (bool) $this->is_active,

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
