<?php

namespace App\Modules\Transport\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CiterneResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'immatriculation' => $this->immatriculation,
            'type_citerne' => $this->type_citerne,
            'marque' => $this->marque,
            'modele' => $this->modele,
            'statut' => $this->statut,
            'etat' => $this->etat,
            'annee_fabrication' => $this->annee_fabrication,
            'capacite_nominale_litres' => $this->capacite_nominale_litres !== null ? (float) $this->capacite_nominale_litres : null,
            'capacite_utile_litres' => $this->capacite_utile_litres !== null ? (float) $this->capacite_utile_litres : null,
            'is_active' => (bool) $this->is_active,

            'compartiments' => $this->whenLoaded('compartiments', function () {
                return CiterneCompartimentResource::collection($this->compartiments);
            }),
            'documents' => $this->whenLoaded('documents', function () {
                return CiterneDocumentResource::collection($this->documents);
            }),
            'maintenances' => $this->whenLoaded('maintenances', function () {
                return MaintenanceCiterneResource::collection($this->maintenances);
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

