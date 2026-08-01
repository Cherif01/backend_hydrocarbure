<?php

namespace App\Modules\Comptabilite\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class CompteResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte,
            'libelle' => $this->libelle,
            'solde_initial' => $this->solde_initial !== null ? (float) $this->solde_initial : null,
            'solde' => (float) ($this->solde_initial ?? 0)
                + (float) ($this->versements_confirmed_sum ?? 0)
                + (float) ($this->transactions_in_sum ?? 0)
                - (float) ($this->transactions_out_sum ?? 0),
            'devise' => $this->devise,
            'is_active' => (bool) $this->is_active,

            'versements' => $this->whenLoaded('versements', function () {
                return $this->versements?->map(function ($versement) {
                    return [
                        'id' => $versement->id,
                        'compte' => $versement->relationLoaded('compte') ? [
                            'id' => $versement->compte?->id,
                            'numero_compte' => $versement->compte?->numero_compte,
                            'libelle' => $versement->compte?->libelle,
                            'devise' => $versement->compte?->devise,
                            'is_active' => (bool) $versement->compte?->is_active,
                        ] : null,
                        'caisse' => $versement->relationLoaded('caisse') ? [
                            'id' => $versement->caisse?->id,
                            'reference' => $versement->caisse?->reference,
                            'libelle' => $versement->caisse?->libelle,
                            'station' => $versement->caisse?->relationLoaded('station') ? [
                                'id' => $versement->caisse?->station?->id,
                                'reference' => $versement->caisse?->station?->reference,
                                'libelle' => $versement->caisse?->station?->libelle,
                                'description' => $versement->caisse?->station?->description,
                                'adresse' => $versement->caisse?->station?->adresse,
                                'ville' => $versement->caisse?->station?->ville,
                            ] : null,
                        ] : null,
                        'type' => $versement->type,
                        'user' => $versement->relationLoaded('user') ? [
                            'id' => $versement->user?->id,
                            'name' => $versement->user?->name,
                            'telephone' => $versement->user?->telephone,
                        ] : null,
                        'created_by' => $versement->relationLoaded('createdBy') ? [
                            'id' => $versement->createdBy?->id,
                            'name' => $versement->createdBy?->name,
                            'telephone' => $versement->createdBy?->telephone,
                        ] : null,
                        'updated_by' => $versement->relationLoaded('updatedBy') ? [
                            'id' => $versement->updatedBy?->id,
                            'name' => $versement->updatedBy?->name,
                            'telephone' => $versement->updatedBy?->telephone,
                        ] : null,
                        'montant' => $versement->montant !== null ? (float) $versement->montant : null,
                        'date_versement' => $versement->date_versement?->format('d-m-Y H:i:s'),
                        'commentaire' => $versement->commentaire,
                        'status' => $versement->status,
                        'created_at' => $versement->created_at?->format('d-m-Y H:i:s'),
                        'updated_at' => $versement->updated_at?->format('d-m-Y H:i:s'),
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
