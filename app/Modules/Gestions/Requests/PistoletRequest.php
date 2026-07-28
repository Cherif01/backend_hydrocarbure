<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class PistoletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'pompe_id' => [$presenceRule, 'integer', 'exists:pompes,id'],
            'hydrocarbure_id' => [$presenceRule, 'integer', 'exists:hydrocarbures,id'],
            'libelle' => [$presenceRule, 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'pompe_id.required' => 'La pompe est obligatoire.',
            'pompe_id.integer' => 'La pompe selectionnee est invalide.',
            'pompe_id.exists' => "La pompe selectionnee n'existe pas.",
            'hydrocarbure_id.required' => "L'hydrocarbure est obligatoire.",
            'hydrocarbure_id.integer' => "L'hydrocarbure selectionne est invalide.",
            'hydrocarbure_id.exists' => "L'hydrocarbure selectionne n'existe pas.",
            'libelle.required' => 'Le libelle du pistolet est obligatoire.',
            'libelle.string' => 'Le libelle du pistolet doit etre une chaine de caracteres.',
            'libelle.max' => 'Le libelle du pistolet ne peut pas depasser :max caracteres.',
            'is_active.boolean' => 'Le statut doit etre vrai ou faux.',
        ];
    }
}
