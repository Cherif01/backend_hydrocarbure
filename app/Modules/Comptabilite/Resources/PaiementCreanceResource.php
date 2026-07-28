<?php

namespace App\Modules\Comptabilite\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class PaiementCreanceResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'client_id' => $this->client_id,
            'creance_id' => $this->creance_id,
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'mode_paiement' => $this->mode_paiement,
            'date_paiement' => $this->date_paiement?->format('d-m-Y H:i:s'),
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

            'creance' => $this->whenLoaded('creance', function () {
                return [
                    'id' => $this->creance?->id,
                    'client_id' => $this->creance?->client_id,
                    'affectation_pistolet_id' => $this->creance?->affectation_pistolet_id,
                    'date_creance' => $this->creance?->date_creance?->format('d-m-Y H:i:s'),
                    'total_litre' => $this->creance?->total_litre !== null ? (int) $this->creance?->total_litre : null,
                    'montant' => $this->creance?->montant !== null ? (float) $this->creance?->montant : null,
                    'commentaire' => $this->creance?->commentaire,
                    'station' => $this->creance?->relationLoaded('affectationPistolet')
                        && $this->creance?->affectationPistolet?->relationLoaded('pistolet')
                        && $this->creance?->affectationPistolet?->pistolet?->relationLoaded('pompe')
                        && $this->creance?->affectationPistolet?->pistolet?->pompe?->relationLoaded('station')
                        ? [
                            'id' => $this->creance?->affectationPistolet?->pistolet?->pompe?->station?->id,
                            'reference' => $this->creance?->affectationPistolet?->pistolet?->pompe?->station?->reference,
                            'libelle' => $this->creance?->affectationPistolet?->pistolet?->pompe?->station?->libelle,
                        ]
                        : null,
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
