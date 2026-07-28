<?php

namespace App\Modules\Gestions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CreanceResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'affectation_pistolet_id' => $this->affectation_pistolet_id,
            'date_creance' => $this->date_creance ? $this->date_creance->format('d-m-Y H:i:s') : null,
            'total_litre' => (int) $this->total_litre,
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'commentaire' => $this->commentaire,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'name' => $this->client?->name,
                    'telephone' => $this->client?->telephone,
                    'email' => $this->client?->email,
                    'adresse' => $this->client?->adresse,
                ];
            }),
            'affectation_pistolet' => $this->whenLoaded('affectationPistolet', function () {
                return [
                    'id' => $this->affectationPistolet?->id,
                    'employee_id' => $this->affectationPistolet?->employee_id,
                    'pistolet_id' => $this->affectationPistolet?->pistolet_id,
                    'prix_vente_jour' => $this->affectationPistolet?->prix_vente_jour !== null ? (float) $this->affectationPistolet?->prix_vente_jour : null,
                    'is_active' => (bool) ($this->affectationPistolet?->is_active ?? false),
                    'pistolet' => $this->affectationPistolet?->relationLoaded('pistolet') ? [
                        'id' => $this->affectationPistolet?->pistolet?->id,
                        'libelle' => $this->affectationPistolet?->pistolet?->libelle,
                        'pompe' => $this->affectationPistolet?->pistolet?->relationLoaded('pompe') ? [
                            'id' => $this->affectationPistolet?->pistolet?->pompe?->id,
                            'reference' => $this->affectationPistolet?->pistolet?->pompe?->reference,
                            'libelle' => $this->affectationPistolet?->pistolet?->pompe?->libelle,
                            'station' => $this->affectationPistolet?->pistolet?->pompe?->relationLoaded('station') ? [
                                'id' => $this->affectationPistolet?->pistolet?->pompe?->station?->id,
                                'reference' => $this->affectationPistolet?->pistolet?->pompe?->station?->reference,
                                'libelle' => $this->affectationPistolet?->pistolet?->pompe?->station?->libelle,
                                'description' => $this->affectationPistolet?->pistolet?->pompe?->station?->description,
                                'adresse' => $this->affectationPistolet?->pistolet?->pompe?->station?->adresse,
                                'ville' => $this->affectationPistolet?->pistolet?->pompe?->station?->ville,
                            ] : null,
                        ] : null,
                    ] : null,
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
