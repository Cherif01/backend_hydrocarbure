<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class VersementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $versement = $this->route('versement');
        $currentType = is_object($versement) ? $versement->type : null;
        $currentUserId = is_object($versement) ? $versement->user_id : null;

        $requestedType = $this->input('type');
        $isTypeBecomingIndirect = $requestedType === 'indirect' && $currentType !== 'indirect';
        $isIndirectAndMissingUser = ($requestedType === 'indirect') && empty($currentUserId);

        $requiresUserId = $isTypeBecomingIndirect || $isIndirectAndMissingUser;

        return [
            'compte_id' => [$presenceRule, 'integer', 'exists:comptes,id'],
            'caisse_id' => [$presenceRule, 'integer', 'exists:caisses,id'],
            'type' => [$presenceRule, Rule::in(['direct', 'indirect'])],
            'user_id' => [$requiresUserId ? 'required' : 'sometimes', 'nullable', 'integer', 'exists:users,id'],
            'montant' => [$presenceRule, 'numeric', 'min:0'],
            'date_versement' => [$presenceRule, 'date'],
            'commentaire' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['en_cours', 'rejeter', 'annuler', 'confirmer'])],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'compte_id.required' => 'Le compte est requis.',
            'compte_id.integer' => 'Le compte selectionne est invalide.',
            'compte_id.exists' => "Le compte selectionne n'existe pas.",

            'caisse_id.required' => 'La caisse est requise.',
            'caisse_id.integer' => 'La caisse selectionnee est invalide.',
            'caisse_id.exists' => "La caisse selectionnee n'existe pas.",

            'type.required' => 'Le type est requis.',
            'type.in' => 'Le type doit etre direct ou indirect.',

            'user_id.required' => "L'utilisateur est requis lorsque le type est indirect.",
            'user_id.integer' => "L'utilisateur selectionne est invalide.",
            'user_id.exists' => "L'utilisateur selectionne n'existe pas.",

            'montant.required' => 'Le montant est requis.',
            'montant.numeric' => 'Le montant doit etre un nombre valide.',
            'montant.min' => 'Le montant doit etre superieur ou egal a 0.',

            'date_versement.required' => 'La date de versement est requise.',
            'date_versement.date' => 'La date de versement doit etre une date valide.',

            'commentaire.string' => 'Le commentaire doit etre une chaine de caracteres.',

            'status.in' => 'Le statut est invalide.',
        ];
    }
}

