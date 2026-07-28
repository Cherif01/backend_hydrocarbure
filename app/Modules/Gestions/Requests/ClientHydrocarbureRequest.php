<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class ClientHydrocarbureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'hydrocarbure_id' => ['required', 'integer', 'exists:hydrocarbures,id'],
            'max_litre' => ['nullable', 'numeric', 'min:0'],
            'prix' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'client_id.required' => "Le client est obligatoire.",
            'client_id.integer' => "Le client selectionne est invalide.",
            'client_id.exists' => "Le client selectionne n'existe pas.",
            'hydrocarbure_id.required' => "L'hydrocarbure est obligatoire.",
            'hydrocarbure_id.integer' => "L'hydrocarbure selectionne est invalide.",
            'hydrocarbure_id.exists' => "L'hydrocarbure selectionne n'existe pas.",
            'max_litre.numeric' => "Le max litre doit etre un nombre valide.",
            'max_litre.min' => "Le max litre doit etre superieur ou egal a 0.",
            'prix.numeric' => "Le prix doit etre un nombre valide.",
            'prix.min' => "Le prix doit etre superieur ou egal a 0.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}
