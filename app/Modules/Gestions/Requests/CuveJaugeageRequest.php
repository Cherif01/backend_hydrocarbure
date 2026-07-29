<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class CuveJaugeageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'cuve_id' => [$presenceRule, 'integer', 'exists:cuves,id'],
            'date_jauge' => [$presenceRule, 'date'],
            'valeur_jauge' => ['nullable', 'numeric', 'min:0'],
            'volume_reel' => ['nullable', 'numeric', 'min:0'],
            'volume_theorique' => ['nullable', 'numeric', 'min:0'],
            'commentaire' => ['nullable', 'string'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'cuve_id.required' => 'La cuve est obligatoire.',
            'cuve_id.integer' => 'La cuve selectionnee est invalide.',
            'cuve_id.exists' => "La cuve selectionnee n'existe pas.",
            'date_jauge.required' => 'La date de jauge est obligatoire.',
            'date_jauge.date' => 'La date de jauge est invalide.',
            'valeur_jauge.numeric' => 'La valeur de jauge doit etre un nombre valide.',
            'valeur_jauge.min' => 'La valeur de jauge doit etre superieure ou egale a 0.',
            'volume_reel.numeric' => 'Le volume reel doit etre un nombre valide.',
            'volume_reel.min' => 'Le volume reel doit etre superieur ou egal a 0.',
            'volume_theorique.numeric' => 'Le volume theorique doit etre un nombre valide.',
            'volume_theorique.min' => 'Le volume theorique doit etre superieur ou egal a 0.',
            'commentaire.string' => 'Le commentaire doit etre une chaine de caracteres.',
        ];
    }
}

