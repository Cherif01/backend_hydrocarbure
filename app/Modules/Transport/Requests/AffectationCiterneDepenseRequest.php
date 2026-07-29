<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class AffectationCiterneDepenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'affectation_citerne_id' => [$presenceRule, 'integer', 'exists:affectation_citernes,id'],
            'libelle' => [$presenceRule, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'montant' => [$presenceRule, 'numeric', 'min:0'],
            'date_depense' => [$presenceRule, 'date'],
            'facture' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif', 'max:5024'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'affectation_citerne_id.required' => "L'affectation citerne est obligatoire.",
            'affectation_citerne_id.integer' => "L'affectation citerne selectionnee est invalide.",
            'affectation_citerne_id.exists' => "L'affectation citerne selectionnee n'existe pas.",

            'libelle.required' => "Le libelle est obligatoire.",
            'libelle.string' => "Le libelle doit etre une chaine de caracteres.",
            'libelle.max' => "Le libelle ne peut pas depasser :max caracteres.",

            'montant.required' => "Le montant est obligatoire.",
            'montant.numeric' => "Le montant doit etre un nombre valide.",
            'montant.min' => "Le montant doit etre superieur ou egal a 0.",

            'date_depense.required' => "La date de depense est obligatoire.",
            'date_depense.date' => "La date de depense est invalide.",
            'facture.required' => "La facture est obligatoire.",
            'facture.mimes' => "La facture doit etre un fichier PDF, JPG, JPEG, PNG ou GIF.",
            'facture.max' => "La facture ne peut pas depasser :max octets.",
        ];
    }
}
