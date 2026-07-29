<?php

namespace App\Modules\Transport\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class AffectationCiterneResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'citerne_id' => $this->citerne_id,
            'date_affectation' => $this->date_affectation?->format('Y-m-d'),
            'date_depart_prevu' => $this->date_depart_prevu?->format('Y-m-d'),
            'date_arrive_prevu' => $this->date_arrive_prevu?->format('Y-m-d'),
            'date_depart_reel' => $this->date_depart_reel?->format('Y-m-d'),
            'date_arrive_reel' => $this->date_arrive_reel?->format('Y-m-d'),
            'date_retour_prevu' => $this->date_retour_prevu?->format('Y-m-d'),
            'date_retour_reel' => $this->date_retour_reel?->format('Y-m-d'),
            'ville_depart' => $this->ville_depart,
            'ville_destination' => $this->ville_destination,
            'longitude_depart' => $this->longitude_depart !== null ? (float) $this->longitude_depart : null,
            'latitude_depart' => $this->latitude_depart !== null ? (float) $this->latitude_depart : null,
            'longitude_destination' => $this->longitude_destination !== null ? (float) $this->longitude_destination : null,
            'latitude_destination' => $this->latitude_destination !== null ? (float) $this->latitude_destination : null,
            'status' => $this->status,

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee?->id,
                    'name' => $this->employee?->name,
                    'telephone' => $this->employee?->telephone,
                ];
            }),

            'citerne' => $this->whenLoaded('citerne', function () {
                return [
                    'id' => $this->citerne?->id,
                    'immatriculation' => $this->citerne?->immatriculation,
                    'type_citerne' => $this->citerne?->type_citerne,
                    'statut' => $this->citerne?->statut,
                    'etat' => $this->citerne?->etat,
                    'capacite_nominale_litres' => $this->citerne?->capacite_nominale_litres !== null ? (float) $this->citerne?->capacite_nominale_litres : null,
                    'capacite_utile_litres' => $this->citerne?->capacite_utile_litres !== null ? (float) $this->citerne?->capacite_utile_litres : null,
                ];
            }),

            'depenses' => $this->whenLoaded('depenses', function () {
                return AffectationCiterneDepenseResource::collection($this->depenses);
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

