<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class OperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_operation_id' => ['required', 'integer', 'exists:type_operations,id'],
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'caisse_id' => ['nullable', 'integer', 'exists:caisses,id'],
            'montant' => ['required', 'numeric', 'min:0'],
            'commentaire' => ['nullable', 'string'],
            'date_operation' => ['required', 'date'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'type_operation_id.required' => "Le type d'operation est obligatoire.",
            'type_operation_id.integer' => "Le type d'operation selectionne est invalide.",
            'type_operation_id.exists' => "Le type d'operation selectionne n'existe pas.",
            'station_id.integer' => "La station selectionnee est invalide.",
            'station_id.exists' => "La station selectionnee n'existe pas.",
            'caisse_id.integer' => "La caisse selectionnee est invalide.",
            'caisse_id.exists' => "La caisse selectionnee n'existe pas.",
            'montant.required' => "Le montant est obligatoire.",
            'montant.numeric' => "Le montant doit etre un nombre valide.",
            'montant.min' => "Le montant doit etre superieur ou egal a 0.",
            'commentaire.string' => "Le commentaire doit etre une chaine de caracteres.",
            'date_operation.required' => "La date de l'operation est obligatoire.",
            'date_operation.date' => "La date de l'operation doit etre une date valide.",
        ];
    }
}
