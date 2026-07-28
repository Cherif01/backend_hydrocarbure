<?php

namespace App\Modules\Gestions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class AffectationPistoletResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $sumTotalLitre = $this->relationLoaded('creances')
            ? (float) $this->creances->sum('total_litre')
            : null;

        $sumPaiements = $this->relationLoaded('creances')
            ? (float) $this->creances
                ->flatMap(fn($creance) => $creance->relationLoaded('paiementsCreances') ? $creance->paiementsCreances : collect())
                ->sum('montant')
            : null;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'pistolet_id' => $this->pistolet_id,

            'index_ouverture' => $this->index_ouverture !== null ? (float) $this->index_ouverture : null,
            'index_fermeture' => $this->index_fermeture !== null ? (float) $this->index_fermeture : null,
            'litre_vendu' => $this->litre_vendu !== null ? (float) $this->litre_vendu : null,
            'prix_vente_jour' => $this->prix_vente_jour !== null ? (float) $this->prix_vente_jour : null,
            'litre_retouner' => $this->litre_retouner !== null ? (float) $this->litre_retouner : null,
            'montant_attentu' => $this->montant_attentu !== null ? (float) $this->montant_attentu : null,
            'montant_recu' => $this->montant_recu !== null ? (float) $this->montant_recu : null,

            'commentaire' => $this->commentaire,
            'is_active' => (bool) $this->is_active,

            'total_litre_creance' => $sumTotalLitre,
            'montant_creance_payer' => $sumPaiements,

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee?->id,
                    'name' => $this->employee?->name,
                    'telephone' => $this->employee?->telephone,
                    'station_id' => $this->employee?->station_id,
                    'post' => $this->employee?->relationLoaded('post') ? [
                        'id' => $this->employee?->post?->id,
                        'libelle' => $this->employee?->post?->libelle,
                    ] : null,
                ];
            }),

            'pistolet' => $this->whenLoaded('pistolet', function () {
                return [
                    'id' => $this->pistolet?->id,
                    'libelle' => $this->pistolet?->libelle,
                    'hydrocarbure' => $this->pistolet?->relationLoaded('hydrocarbure') ? [
                        'id' => $this->pistolet?->hydrocarbure?->id,
                        'libelle' => $this->pistolet?->hydrocarbure?->libelle,
                        'prix_vente' => $this->pistolet?->hydrocarbure?->prix_vente !== null ? (float) $this->pistolet?->hydrocarbure?->prix_vente : null,
                    ] : null,
                    'pompe' => $this->pistolet?->relationLoaded('pompe') ? [
                        'id' => $this->pistolet?->pompe?->id,
                        'libelle' => $this->pistolet?->pompe?->libelle,
                        'station' => $this->pistolet?->pompe?->relationLoaded('station') ? [
                            'id' => $this->pistolet?->pompe?->station?->id,
                            'reference' => $this->pistolet?->pompe?->station?->reference,
                            'libelle' => $this->pistolet?->pompe?->station?->libelle,
                        ] : null,
                    ] : null,
                ];
            }),

            'creances' => $this->whenLoaded('creances', function () {
                return $this->creances->map(function ($creance) {
                    return [
                        'id' => $creance->id,
                        'client_id' => $creance->client_id,
                        'affectation_pistolet_id' => $creance->affectation_pistolet_id,
                        'date_creance' => $creance->date_creance?->format('d-m-Y H:i:s'),
                        'total_litre' => $creance->total_litre !== null ? (float) $creance->total_litre : null,
                        'montant' => $creance->montant !== null ? (float) $creance->montant : null,
                        'commentaire' => $creance->commentaire,
                        'paiements' => $creance->relationLoaded('paiementsCreances')
                            ? $creance->paiementsCreances->map(function ($paiement) {
                                return [
                                    'id' => $paiement->id,
                                    'reference' => $paiement->reference,
                                    'montant' => $paiement->montant !== null ? (float) $paiement->montant : null,
                                    'mode_paiement' => $paiement->mode_paiement,
                                    'date_paiement' => $paiement->date_paiement?->format('d-m-Y H:i:s'),
                                    'commentaire' => $paiement->commentaire,
                                ];
                            })
                            : [],
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
