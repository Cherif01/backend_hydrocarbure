<?php

namespace App\Modules\Transport\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CiterneCompartimentResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'citerne_id' => $this->citerne_id,
            'hydrocarbure_id' => $this->hydrocarbure_id,
            'numero_compartiment' => $this->numero_compartiment,
            'capacite_litres' => $this->capacite_litres !== null ? (float) $this->capacite_litres : null,

            'citerne' => $this->whenLoaded('citerne', function () {
                return [
                    'id' => $this->citerne?->id,
                    'immatriculation' => $this->citerne?->immatriculation,
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

