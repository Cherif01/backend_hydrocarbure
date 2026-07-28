<?php

namespace App\Modules\Gestions\Requests;

use App\Services\UserStationScopeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class PompeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $scope = $user
            ? app(UserStationScopeService::class)->resolve($user, false)
            : ['is_station_scoped' => false, 'station_id' => null];

        if (($scope['is_station_scoped'] ?? false) && ($scope['station_id'] ?? null)) {
            $this->merge([
                'station_id' => $scope['station_id'],
            ]);
        }
    }

    public function rules(): array
    {
        $pompe = $this->route('pompe');
        $pompeId = is_object($pompe) ? $pompe->id : $pompe;
        $user = $this->user();
        $scope = $user
            ? app(UserStationScopeService::class)->resolve($user, false)
            : ['is_station_scoped' => false, 'station_id' => null];
        $isPatch = $this->isMethod('PATCH');
        $stationRule = $isPatch || ($scope['is_station_scoped'] ?? false)
            ? 'sometimes'
            : 'required';
        $libelleRule = $isPatch ? 'sometimes' : 'required';

        return [
            'reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('pompes', 'reference')->ignore($pompeId),
            ],
            'station_id' => [$stationRule, 'integer', 'exists:stations,id'],
            'libelle' => [$libelleRule, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
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
