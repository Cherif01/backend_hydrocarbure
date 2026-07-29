<?php

namespace App\Modules\Transport\Resources;

use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class AffectationCiterneDepenseResource extends JsonResource
{
    use CloudflareUpload;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'affectation_citerne_id' => $this->affectation_citerne_id,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'date_depense' => $this->date_depense?->format('Y-m-d'),
            'facture' => $this->facture ? $this->getFileUrl($this->facture, 'citerne_depense') : null,

            'affectation_citerne' => $this->whenLoaded('affectationCiterne', function () {
                return [
                    'id' => $this->affectationCiterne?->id,
                    'employee_id' => $this->affectationCiterne?->employee_id,
                    'citerne_id' => $this->affectationCiterne?->citerne_id,
                    'employee' => $this->affectationCiterne?->relationLoaded('employee') ? [
                        'id' => $this->affectationCiterne?->employee?->id,
                        'name' => $this->affectationCiterne?->employee?->name,
                        'telephone' => $this->affectationCiterne?->employee?->telephone,
                    ] : null,
                    'citerne' => $this->affectationCiterne?->relationLoaded('citerne') ? [
                        'id' => $this->affectationCiterne?->citerne?->id,
                        'immatriculation' => $this->affectationCiterne?->citerne?->immatriculation,
                        'type_citerne' => $this->affectationCiterne?->citerne?->type_citerne,
                        'statut' => $this->affectationCiterne?->citerne?->statut,
                        'etat' => $this->affectationCiterne?->citerne?->etat,
                    ] : null,
                    'status' => $this->affectationCiterne?->status,
                    'date_affectation' => $this->affectationCiterne?->date_affectation?->format('Y-m-d'),
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
