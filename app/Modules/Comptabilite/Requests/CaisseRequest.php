<?php

namespace App\Modules\Comptabilite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class CaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $caisse = $this->route('caisse');
        $caisseId = is_object($caisse) ? $caisse->id : $caisse;

        return [
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'reference' => ['required', 'string', 'max:255', Rule::unique('caisses', 'reference')->ignore($caisseId)],
            'libelle' => ['required', 'string', 'max:255'],
            'solde_initial' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'station_id.integer' => "La station selectionnee est invalide.",
            'station_id.exists' => "La station selectionnee n'existe pas.",
            'reference.required' => "La reference est obligatoire.",
            'reference.string' => "La reference doit etre une chaine de caracteres.",
            'reference.max' => "La reference ne peut pas depasser :max caracteres.",
            'reference.unique' => "Cette reference est deja utilisee.",
            'libelle.required' => "Le libelle est obligatoire.",
            'libelle.string' => "Le libelle doit etre une chaine de caracteres.",
            'libelle.max' => "Le libelle ne peut pas depasser :max caracteres.",
            'solde_initial.numeric' => "Le solde initial doit etre un nombre valide.",
            'solde_initial.min' => "Le solde initial doit etre superieur ou egal a 0.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}
