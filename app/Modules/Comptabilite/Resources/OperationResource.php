<?php

namespace App\Modules\Comptabilite\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class OperationResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_operation_id' => $this->type_operation_id,
            'station_id' => $this->station_id,
            'caisse_id' => $this->caisse_id,
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'commentaire' => $this->commentaire,
            'date_operation' => $this->date_operation?->format('d-m-Y H:i:s'),

            'type_operation' => $this->whenLoaded('typeOperation', function () {
                return [
                    'id' => $this->typeOperation?->id,
                    'libelle' => $this->typeOperation?->libelle,
                    'nature' => (bool) $this->typeOperation?->nature,
                    'nature_libelle' => $this->typeOperation?->nature ? 'entree' : 'sortie',
                ];
            }),

            'caisse' => $this->whenLoaded('caisse', function () {
                return [
                    'id' => $this->caisse?->id,
                    'reference' => $this->caisse?->reference,
                    'libelle' => $this->caisse?->libelle,
                ];
            }),

            'station' => $this->whenLoaded('station', function () {
                return [
                    'id' => $this->station?->id,
                    'reference' => $this->station?->reference,
                    'libelle' => $this->station?->libelle,
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
