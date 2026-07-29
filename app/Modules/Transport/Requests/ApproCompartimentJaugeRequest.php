<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class ApproCompartimentJaugeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'approvision_id' => [$presenceRule, 'integer', 'exists:approvisions,id'],
            'hydrocarbure_id' => [$presenceRule, 'integer', 'exists:hydrocarbures,id'],
            'num_compartiment' => [$presenceRule, 'integer', 'min:1'],
            'valeur_jauge' => ['nullable', 'numeric', 'min:0'],
            'volume_reel' => [$presenceRule, 'numeric', 'min:0'],
            'volume_theorique' => [$presenceRule, 'numeric', 'min:0'],
        ];
    }

    #[Override]
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (array_key_exists('volume_theorique', $validated) && ! array_key_exists('volueme_theorique', $validated)) {
            $validated['volueme_theorique'] = $validated['volume_theorique'];
        }
        unset($validated['volume_theorique']);

        return $validated;
    }

    #[Override]
    public function messages(): array
    {
        return [
            'approvision_id.required' => "L'approvision est obligatoire.",
            'approvision_id.exists' => "L'approvision selectionne n'existe pas.",
            'hydrocarbure_id.required' => "L'hydrocarbure est obligatoire.",
            'hydrocarbure_id.exists' => "L'hydrocarbure selectionne n'existe pas.",
            'num_compartiment.required' => "Le numero de compartiment est obligatoire.",
            'num_compartiment.min' => "Le numero de compartiment doit etre superieur ou egal a 1.",
            'volume_reel.required' => "Le volume reel est obligatoire.",
            'volume_theorique.required' => "Le volume theorique est obligatoire.",
        ];
    }
}

