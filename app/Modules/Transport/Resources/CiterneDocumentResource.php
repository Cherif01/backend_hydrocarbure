<?php

namespace App\Modules\Transport\Resources;

use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CiterneDocumentResource extends JsonResource
{
    use CloudflareUpload;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'citerne_id' => $this->citerne_id,
            'type_document' => $this->type_document,
            'numero_document' => $this->numero_document,
            'date_emission' => $this->date_emission?->format('Y-m-d'),
            'date_expiration' => $this->date_expiration?->format('Y-m-d'),
            'fichier_scan' => $this->fichier_scan ? $this->getCloudflareUrl($this->fichier_scan, 'citernes_documents') : null,

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
