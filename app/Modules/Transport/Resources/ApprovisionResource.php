<?php

namespace App\Modules\Transport\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ApprovisionResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $totalTheorique = $this->total_litre_theorique !== null ? (float) $this->total_litre_theorique : 0.0;
        $storedTotalReel = $this->total_litre_reel !== null ? (float) $this->total_litre_reel : 0.0;

        $sumVolumeReel = $this->relationLoaded('compartimentJauges')
            ? (float) $this->compartimentJauges->sum(function ($jauge) {
                return (float) ($jauge->volume_reel ?? 0);
            })
            : 0.0;

        $effectiveTotalReel = $storedTotalReel > 0 ? $storedTotalReel : $sumVolumeReel;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'affectation_citerne_id' => $this->affectation_citerne_id,
            'station_id' => $this->station_id,
            'date_approvision' => $this->date_approvision?->format('d-m-Y H:i:s'),
            'total_litre_theorique' => $this->total_litre_theorique !== null ? (int) $this->total_litre_theorique : null,
            'total_litre_reel' => $effectiveTotalReel,
            'ecart' => $totalTheorique - $effectiveTotalReel,

            'station' => $this->whenLoaded('station', function () {
                return [
                    'id' => $this->station?->id,
                    'reference' => $this->station?->reference,
                    'libelle' => $this->station?->libelle,
                ];
            }),

            'affectation_citerne' => $this->whenLoaded('affectationCiterne', function () {
                return [
                    'id' => $this->affectationCiterne?->id,
                    'employee_id' => $this->affectationCiterne?->employee_id,
                    'citerne_id' => $this->affectationCiterne?->citerne_id,
                    'status' => $this->affectationCiterne?->status,
                    'date_affectation' => $this->affectationCiterne?->date_affectation?->format('Y-m-d'),
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
                ];
            }),

            'appro_compartiment_jauges' => $this->whenLoaded('compartimentJauges', function () {
                return ApproCompartimentJaugeResource::collection($this->compartimentJauges);
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

