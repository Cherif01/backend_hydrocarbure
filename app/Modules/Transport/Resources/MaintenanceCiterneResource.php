<?php

namespace App\Modules\Transport\Resources;

use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class MaintenanceCiterneResource extends JsonResource
{
    use CloudflareUpload;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'citerne_id' => $this->citerne_id,
            'type_maintenance' => $this->type_maintenance,
            'nature' => $this->nature,
            'description' => $this->description,
            'date_prevue' => $this->date_prevue?->format('Y-m-d'),
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'kilometrage_intervention' => $this->kilometrage_intervention,
            'cout' => $this->cout !== null ? (float) $this->cout : null,
            'prestataire' => $this->prestataire,
            'facture_scan' => $this->facture_scan ? $this->getFileUrl($this->facture_scan, 'maintenance_citernes') : null,
            'status' => $this->status,

            'citerne' => $this->whenLoaded('citerne', function () {
                return [
                    'id' => $this->citerne?->id,
                    'immatriculation' => $this->citerne?->immatriculation,
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
