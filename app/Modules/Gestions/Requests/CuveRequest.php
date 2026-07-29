<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class CuveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $cuve = $this->route('cuve');
        $cuveId = is_object($cuve) ? $cuve->id : $cuve;

        return [
            'station_id' => [$presenceRule, 'integer', 'exists:stations,id'],
            'hydrocarbure_id' => [$presenceRule, 'integer', 'exists:hydrocarbures,id'],
            'reference' => ['nullable', 'string', 'max:255', Rule::unique('cuves', 'reference')->ignore($cuveId)],
            'libelle' => [$presenceRule, 'string', 'max:255'],
            'capacite' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'station_id.required' => 'La station est obligatoire.',
            'station_id.integer' => 'La station selectionnee est invalide.',
            'station_id.exists' => "La station selectionnee n'existe pas.",

            'hydrocarbure_id.required' => 'Le hydrocarbure est obligatoire.',
            'hydrocarbure_id.integer' => 'Le hydrocarbure selectionne est invalide.',
            'hydrocarbure_id.exists' => "Le hydrocarbure selectionne n'existe pas.",

            'reference.string' => 'La reference doit etre une chaine de caracteres.',
            'reference.max' => 'La reference ne peut pas depasser :max caracteres.',
            'reference.unique' => 'Cette reference est deja utilisee.',

            'libelle.required' => 'Le libelle est obligatoire.',
            'libelle.string' => 'Le libelle doit etre une chaine de caracteres.',
            'libelle.max' => 'Le libelle ne peut pas depasser :max caracteres.',

            'capacite.integer' => 'La capacite doit etre un nombre entier.',
            'capacite.min' => 'La capacite doit etre superieure ou egale a 0.',

            'is_active.boolean' => 'Le statut doit etre vrai ou faux.',
        ];
    }
}
