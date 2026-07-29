<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class CiterneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $citerne = $this->route('citerne');
        $citerneId = is_object($citerne) ? $citerne->id : $citerne;

        return [
            'immatriculation' => [
                $presenceRule,
                'string',
                'max:255',
                Rule::unique('citernes', 'immatriculation')->ignore($citerneId),
            ],
            'type_citerne' => [$presenceRule, Rule::in(['camion_citerne', 'semi_remorque', 'remorque'])],
            'marque' => ['nullable', 'string', 'max:255'],
            'modele' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', Rule::in(['disponible', 'en_mission', 'en_maintenance', 'hors_service'])],
            'etat' => ['nullable', Rule::in(['interne', 'externne'])],
            'annee_fabrication' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'capacite_nominale_litres' => [$presenceRule, 'numeric', 'min:0'],
            'capacite_utile_litres' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'immatriculation.required' => "L'immatriculation est obligatoire.",
            'immatriculation.unique' => "Cette immatriculation est deja utilisee.",
            'type_citerne.required' => "Le type de citerne est obligatoire.",
            'type_citerne.in' => "Le type de citerne est invalide.",
            'statut.in' => "Le statut est invalide.",
            'etat.in' => "L'etat est invalide.",
            'annee_fabrication.integer' => "L'annee de fabrication doit etre un entier.",
            'annee_fabrication.min' => "L'annee de fabrication est invalide.",
            'annee_fabrication.max' => "L'annee de fabrication est invalide.",
            'capacite_nominale_litres.required' => "La capacite nominale est obligatoire.",
            'capacite_nominale_litres.numeric' => "La capacite nominale doit etre un nombre.",
            'capacite_nominale_litres.min' => "La capacite nominale doit etre superieure ou egale a 0.",
            'capacite_utile_litres.numeric' => "La capacite utile doit etre un nombre.",
            'capacite_utile_litres.min' => "La capacite utile doit etre superieure ou egale a 0.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}

