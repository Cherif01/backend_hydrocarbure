<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class PompeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pompe = $this->route('pompe');
        $pompeId = is_object($pompe) ? $pompe->id : $pompe;
        $scope = $this->attributes->get('station_scope', []);
        $stationRule = ($scope['is_station_scoped'] ?? false) ? 'nullable' : 'required';

        return [
            'reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pompes', 'reference')->ignore($pompeId),
            ],
            'station_id' => [$stationRule, 'integer', 'exists:stations,id'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'reference.string' => 'La reference doit etre une chaine de caracteres.',
            'reference.max' => 'La reference ne peut pas depasser :max caracteres.',
            'reference.unique' => 'Cette reference de pompe est deja utilisee.',
            'station_id.required' => 'La station est obligatoire.',
            'station_id.integer' => 'La station selectionnee est invalide.',
            'station_id.exists' => "La station selectionnee n'existe pas.",
            'libelle.required' => 'Le libelle de la pompe est obligatoire.',
            'libelle.string' => 'Le libelle de la pompe doit etre une chaine de caracteres.',
            'libelle.max' => 'Le libelle de la pompe ne peut pas depasser :max caracteres.',
            'description.string' => 'La description doit etre une chaine de caracteres.',
            'is_active.boolean' => 'Le statut doit etre vrai ou faux.',
        ];
    }
}
