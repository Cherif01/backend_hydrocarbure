<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class PaiementCreanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paiement = $this->route('paiement_creance');
        $paiementId = is_object($paiement) ? $paiement->id : $paiement;

        return [
            'reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('paiement_creances', 'reference')->ignore($paiementId),
            ],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'creance_id' => ['required', 'integer', 'exists:creances,id'],
            'montant' => ['required', 'numeric', 'min:0'],
            'mode_paiement' => ['nullable', 'string', 'max:255'],
            'date_paiement' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'reference.string' => 'La reference doit etre une chaine de caracteres.',
            'reference.max' => 'La reference ne peut pas depasser :max caracteres.',
            'reference.unique' => 'Cette reference est deja utilisee.',
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.integer' => 'Le client selectionne est invalide.',
            'client_id.exists' => "Le client selectionne n'existe pas.",
            'creance_id.required' => 'La creance est obligatoire.',
            'creance_id.integer' => 'La creance selectionnee est invalide.',
            'creance_id.exists' => "La creance selectionnee n'existe pas.",
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit etre un nombre valide.',
            'montant.min' => 'Le montant doit etre superieur ou egal a 0.',
            'mode_paiement.string' => 'Le mode de paiement doit etre une chaine de caracteres.',
            'mode_paiement.max' => 'Le mode de paiement ne peut pas depasser :max caracteres.',
            'date_paiement.date' => 'La date de paiement doit etre une date valide.',
            'commentaire.string' => 'Le commentaire doit etre une chaine de caracteres.',
        ];
    }
}
