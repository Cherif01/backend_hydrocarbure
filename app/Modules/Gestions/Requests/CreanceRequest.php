<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class CreanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'affectation_pistolet_id' => ['required', 'integer', 'exists:affectation_pistolets,id'],
            'date_creance' => ['required', 'date'],
            'total_litre' => ['required', 'integer', 'min:0'],
            'commentaire' => ['nullable', 'string'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.integer' => 'Le client selectionne est invalide.',
            'client_id.exists' => "Le client selectionne n'existe pas.",
            'affectation_pistolet_id.required' => "L'affectation pistolet est obligatoire.",
            'affectation_pistolet_id.integer' => "L'affectation pistolet selectionnee est invalide.",
            'affectation_pistolet_id.exists' => "L'affectation pistolet selectionnee n'existe pas.",
            'date_creance.required' => 'La date de la creance est obligatoire.',
            'date_creance.date' => 'La date de la creance doit etre une date valide.',
            'total_litre.required' => 'Le total litre est obligatoire.',
            'total_litre.integer' => 'Le total litre doit etre un nombre entier.',
            'total_litre.min' => 'Le total litre doit etre superieur ou egal a 0.',
            'commentaire.string' => 'Le commentaire doit etre une chaine de caracteres.',
        ];
    }
}
