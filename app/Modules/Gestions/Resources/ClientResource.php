<?php

namespace App\Modules\Gestions\Resources;

use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ClientResource extends JsonResource
{
    use CloudflareUpload;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'adresse' => $this->adresse,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar ? $this->getImageUrl($this->avatar, 'clients') : null,
            'is_active' => (bool) $this->is_active,
            'hydrocarbures' => $this->whenLoaded('hydrocarbures', function () {
                return $this->hydrocarbures->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'client_id' => $item->client_id,
                        'hydrocarbure_id' => $item->hydrocarbure_id,
                        'max_litre' => (int) $item->max_litre,
                        'prix' => $item->prix !== null ? (float) $item->prix : null,
                        'is_active' => (bool) $item->is_active,
                        'hydrocarbure' => $item->relationLoaded('hydrocarbure') ? [
                            'id' => $item->hydrocarbure?->id,
                            'libelle' => $item->hydrocarbure?->libelle,
                            'prix_achat' => $item->hydrocarbure?->prix_achat,
                            'prix_vente' => $item->hydrocarbure?->prix_vente,
                        ] : null,
                    ];
                });
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
