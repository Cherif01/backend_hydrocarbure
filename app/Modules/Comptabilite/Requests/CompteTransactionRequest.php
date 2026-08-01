<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class CompteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $transaction = $this->route('compte_transaction');
        $transactionId = is_object($transaction) ? $transaction->id : $transaction;

        return [
            'reference' => ['nullable', 'string', 'max:255', Rule::unique('compte_transactions', 'reference')->ignore($transactionId)],
            'compte_source_id' => [$presenceRule, 'integer', 'exists:comptes,id', 'different:compte_destination_id'],
            'compte_destination_id' => [$presenceRule, 'integer', 'exists:comptes,id'],
            'montant' => [$presenceRule, 'numeric', 'min:0'],
            'libelle' => [$presenceRule, 'string', 'max:255'],
            'commentaire' => ['nullable', 'string'],
            'date_transaction' => [$presenceRule, 'date'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'reference.string' => 'La reference doit etre une chaine de caracteres.',
            'reference.unique' => 'Cette reference existe deja.',

            'compte_source_id.required' => 'Le compte source est requis.',
            'compte_source_id.integer' => 'Le compte source selectionne est invalide.',
            'compte_source_id.exists' => "Le compte source selectionne n'existe pas.",
            'compte_source_id.different' => 'Le compte source doit etre different du compte destination.',

            'compte_destination_id.required' => 'Le compte destination est requis.',
            'compte_destination_id.integer' => 'Le compte destination selectionne est invalide.',
            'compte_destination_id.exists' => "Le compte destination selectionne n'existe pas.",

            'montant.required' => 'Le montant est requis.',
            'montant.numeric' => 'Le montant doit etre un nombre valide.',
            'montant.min' => 'Le montant doit etre superieur ou egal a 0.',

            'libelle.required' => 'Le libelle est requis.',
            'libelle.string' => 'Le libelle doit etre une chaine de caracteres.',

            'commentaire.string' => 'Le commentaire doit etre une chaine de caracteres.',

            'date_transaction.required' => 'La date de la transaction est requise.',
            'date_transaction.date' => 'La date de la transaction doit etre une date valide.',
        ];
    }
}

