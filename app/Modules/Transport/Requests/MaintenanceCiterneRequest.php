<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class MaintenanceCiterneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'citerne_id' => [$presenceRule, 'integer', 'exists:citernes,id'],
            'type_maintenance' => [$presenceRule, Rule::in(['preventive', 'corrective', 'reglementaire'])],
            'nature' => [$presenceRule, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_prevue' => ['nullable', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'kilometrage_intervention' => ['nullable', 'integer', 'min:0'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'prestataire' => ['nullable', 'string', 'max:255'],
            'facture_scan' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5024'],
            'status' => ['nullable', Rule::in(['planifiee', 'en_cours', 'terminee', 'annulee'])],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'citerne_id.required' => "La citerne est obligatoire.",
            'citerne_id.exists' => "La citerne selectionnee n'existe pas.",
            'type_maintenance.required' => "Le type de maintenance est obligatoire.",
            'type_maintenance.in' => "Le type de maintenance est invalide.",
            'nature.required' => "La nature est obligatoire.",
            'date_fin.after_or_equal' => "La date de fin doit etre superieure ou egale a la date de debut.",
            'kilometrage_intervention.integer' => "Le kilometrage doit etre un entier.",
            'kilometrage_intervention.min' => "Le kilometrage doit etre superieur ou egal a 0.",
            'cout.numeric' => "Le cout doit etre un nombre valide.",
            'cout.min' => "Le cout doit etre superieur ou egal a 0.",
            'status.in' => "Le statut est invalide.",
            'facture_scan.required' => "La facture scan est obligatoire.",
            'facture_scan.mimes' => "La facture scan doit etre un PDF, JPEG, JPG ou PNG.",
            'facture_scan.max' => "La facture scan doit etre inferieur a 5Mo.",
        ];
    }
}
