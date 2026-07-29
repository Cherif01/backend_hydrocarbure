<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class CiterneDocumentRequest extends FormRequest
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
            'type_document' => [$presenceRule, Rule::in(['agrement_transport', 'controle_technique', 'assurance', 'certificat_jaugeage'])],
            'numero_document' => ['nullable', 'string', 'max:255'],
            'date_emission' => [$presenceRule, 'date'],
            'date_expiration' => [$presenceRule, 'date', 'after_or_equal:date_emission'],
            'fichier_scan' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5024'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'citerne_id.required' => "La citerne est obligatoire.",
            'citerne_id.exists' => "La citerne selectionnee n'existe pas.",
            'type_document.required' => "Le type de document est obligatoire.",
            'type_document.in' => "Le type de document est invalide.",
            'date_emission.required' => "La date d'emission est obligatoire.",
            'date_emission.date' => "La date d'emission est invalide.",
            'date_expiration.required' => "La date d'expiration est obligatoire.",
            'date_expiration.date' => "La date d'expiration est invalide.",
            'date_expiration.after_or_equal' => "La date d'expiration doit etre superieure ou egale a la date d'emission.",
            'fichier_scan.required' => "Le fichier scan est obligatoire.",
            'fichier_scan.mimes' => "Le fichier scan doit etre un PDF, JPEG, JPG ou PNG.",
            'fichier_scan.max' => "Le fichier scan doit etre inferieur a 5Mo.",
        ];
    }
}
