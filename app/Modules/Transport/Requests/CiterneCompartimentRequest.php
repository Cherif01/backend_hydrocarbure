<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class CiterneCompartimentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $compartiment = $this->route('citerne_compartiment');
        $compartimentId = is_object($compartiment) ? $compartiment->id : $compartiment;

        return [
            'citerne_id' => [$presenceRule, 'integer', 'exists:citernes,id'],
            'hydrocarbure_id' => ['nullable', 'integer', 'exists:hydrocarbures,id'],
            'numero_compartiment' => [
                $presenceRule,
                'integer',
                'min:1',
                Rule::unique('citerne_compartiments', 'numero_compartiment')->ignore($compartimentId),
            ],
            'capacite_litres' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'citerne_id.required' => "La citerne est obligatoire.",
            'citerne_id.exists' => "La citerne selectionnee n'existe pas.",
            'hydrocarbure_id.exists' => "L'hydrocarbure selectionne n'existe pas.",
            'numero_compartiment.required' => "Le numero de compartiment est obligatoire.",
            'numero_compartiment.unique' => "Ce numero de compartiment est deja utilise.",
            'numero_compartiment.min' => "Le numero de compartiment doit etre superieur ou egal a 1.",
            'capacite_litres.numeric' => "La capacite doit etre un nombre valide.",
            'capacite_litres.min' => "La capacite doit etre superieure ou egale a 0.",
        ];
    }
}

