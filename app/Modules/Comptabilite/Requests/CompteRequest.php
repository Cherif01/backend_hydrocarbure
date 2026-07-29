<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class CompteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $compte = $this->route('compte');
        $compteId = is_object($compte) ? $compte->id : $compte;

        return [
            'numero_compte' => ['required', 'string', 'max:255', Rule::unique('comptes', 'numero_compte')->ignore($compteId)],
            'libelle' => ['required', 'string', 'max:255'],
            'solde_initial' => ['required', 'numeric', 'min:0'],
            'devise' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'numero_compte.required' => 'Le champ numéro de compte est requis.',
            'numero_compte.unique' => 'Ce numéro de compte existe déjà.',
            'libelle.required' => 'Le champ libellé est requis.',
            'solde_initial.required' => 'Le champ solde initial est requis.',
            'devise.string' => 'Le champ devise doit être une chaîne de caractères.',
            'devise.max' => 'Le champ devise ne peut pas dépasser 255 caractères.',
            'is_active.boolean' => 'Le champ statut doit être un booléen.',
        ];
    }
}
