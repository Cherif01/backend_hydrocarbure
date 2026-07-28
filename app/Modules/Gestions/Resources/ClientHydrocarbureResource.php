<?php

namespace App\Modules\Gestions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ClientHydrocarbureResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'hydrocarbure_id' => $this->hydrocarbure_id,
            'max_litre' => (int) $this->max_litre,
            'prix' => $this->prix !== null ? (float) $this->prix : null,
            'is_active' => (bool) $this->is_active,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'name' => $this->client?->name,
                    'telephone' => $this->client?->telephone,
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
