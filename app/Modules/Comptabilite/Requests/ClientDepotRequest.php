<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ClientDepotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $clientDepot = $this->route('client_depot');
        $clientDepotId = is_object($clientDepot) ? $clientDepot->id : $clientDepot;

        return [
            'client_id' => [$presenceRule, 'integer', 'exists:clients,id'],
            'reference' => ['nullable', 'string', 'max:255', Rule::unique('client_depots', 'reference')->ignore($clientDepotId)],
            'libelle' => [$presenceRule, 'string', 'max:255'],
            'commentaire' => ['nullable', 'string'],
            'date_depot' => ['nullable', 'date'],
            'montant' => [$presenceRule, 'numeric', 'min:0'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est requis.',
            'client_id.integer' => 'Le client selectionne est invalide.',
            'client_id.exists' => "Le client selectionne n'existe pas.",

            'reference.string' => 'La reference doit etre une chaine de caracteres.',
            'reference.unique' => 'Cette reference existe deja.',

            'libelle.required' => 'Le libelle est requis.',
            'libelle.string' => 'Le libelle doit etre une chaine de caracteres.',

            'commentaire.string' => 'Le commentaire doit etre une chaine de caracteres.',

            'date_depot.date' => 'La date du depot doit etre une date valide.',

            'montant.required' => 'Le montant est requis.',
            'montant.numeric' => 'Le montant doit etre un nombre valide.',
            'montant.min' => 'Le montant doit etre superieur ou egal a 0.',
        ];
    }
}

