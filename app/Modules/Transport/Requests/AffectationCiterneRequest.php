<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class AffectationCiterneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'employee_id' => [$presenceRule, 'integer', 'exists:employees,id'],
            'citerne_id' => [$presenceRule, 'integer', 'exists:citernes,id'],
            'date_affectation' => [$presenceRule, 'date'],
            'date_depart_prevu' => ['nullable', 'date'],
            'date_arrive_prevu' => ['nullable', 'date'],
            'date_depart_reel' => ['nullable', 'date'],
            'date_arrive_reel' => ['nullable', 'date'],
            'date_retour_prevu' => ['nullable', 'date'],
            'date_retour_reel' => ['nullable', 'date'],
            'ville_depart' => [$presenceRule, 'string', 'max:255'],
            'ville_destination' => [$presenceRule, 'string', 'max:255'],
            'longitude_depart' => ['nullable', 'numeric'],
            'latitude_depart' => ['nullable', 'numeric'],
            'longitude_destination' => ['nullable', 'numeric'],
            'latitude_destination' => ['nullable', 'numeric'],
            'status' => ['sometimes', Rule::in(['en_cours', 'annuler', 'terminer'])],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'employee_id.required' => "Le chauffeur est obligatoire.",
            'employee_id.integer' => "Le chauffeur selectionne est invalide.",
            'employee_id.exists' => "Le chauffeur selectionne n'existe pas.",

            'citerne_id.required' => "La citerne est obligatoire.",
            'citerne_id.integer' => "La citerne selectionnee est invalide.",
            'citerne_id.exists' => "La citerne selectionnee n'existe pas.",

            'date_affectation.required' => "La date d'affectation est obligatoire.",
            'date_affectation.date' => "La date d'affectation est invalide.",

            'ville_depart.required' => "La ville de depart est obligatoire.",
            'ville_depart.string' => "La ville de depart doit etre une chaine de caracteres.",
            'ville_depart.max' => "La ville de depart ne peut pas depasser :max caracteres.",

            'ville_destination.required' => "La ville de destination est obligatoire.",
            'ville_destination.string' => "La ville de destination doit etre une chaine de caracteres.",
            'ville_destination.max' => "La ville de destination ne peut pas depasser :max caracteres.",

            'status.in' => "Le statut est invalide.",
        ];
    }
}

