<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class TypeOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeOperation = $this->route('type_operation');
        $typeOperationId = is_object($typeOperation) ? $typeOperation->id : $typeOperation;

        return [
            'libelle' => ['required', 'string', 'max:255', Rule::unique('type_operations', 'libelle')->ignore($typeOperationId)],
            'description' => ['nullable', 'string'],
            'nature' => ['required', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'libelle.required' => "Le libelle est obligatoire.",
            'libelle.string' => "Le libelle doit etre une chaine de caracteres.",
            'libelle.max' => "Le libelle ne peut pas depasser :max caracteres.",
            'libelle.unique' => "Ce libelle est deja utilise.",
            'description.string' => "La description doit etre une chaine de caracteres.",
            'nature.required' => "La nature de l'operation est obligatoire.",
            'nature.boolean' => "La nature doit etre vrai (entree) ou faux (sortie).",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}
