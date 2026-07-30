<?php

namespace App\Modules\Transport\Requests;

use App\Services\UserStationScopeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ApprovisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user || in_array($user->role, ['super_admin', 'admin'], true)) {
            return;
        }

        $hasGerantStation = $user->userModules()
            ->where('is_active', true)
            ->whereHas('module', function ($query) {
                $query->where('name', 'gerant_station')
                    ->where('is_active', true);
            })
            ->exists();

        if (! $hasGerantStation) {
            return;
        }

        $scope = app(UserStationScopeService::class)->resolve($user);

        if (! empty($scope['station_id'])) {
            $this->merge(['station_id' => $scope['station_id']]);
        }
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $approvision = $this->route('approvision');
        $approvisionId = is_object($approvision) ? $approvision->id : $approvision;

        return [
            'reference' => ['nullable', 'string', 'max:255'],
            'affectation_citerne_id' => [$presenceRule, 'integer', 'exists:affectation_citernes,id'],
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'date_approvision' => [$presenceRule, 'date'],
            'total_litre_theorique' => [$presenceRule, 'integer', 'min:0'],
            'total_litre_reel' => ['nullable', 'integer', 'min:0'],

            'appro_compartiment_jauges' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'array', 'min:1'],
            'appro_compartiment_jauges.*.id' => ['sometimes', 'integer', Rule::exists('appro_compartiment_jauges', 'id')->where(function ($query) use ($approvisionId) {
                if ($approvisionId) {
                    $query->where('approvision_id', $approvisionId);
                }
            })],
            'appro_compartiment_jauges.*.hydrocarbure_id' => ['required', 'integer', 'exists:hydrocarbures,id'],
            'appro_compartiment_jauges.*.num_compartiment' => ['required', 'integer', 'min:1', 'distinct'],
            'appro_compartiment_jauges.*.valeur_jauge' => ['nullable', 'numeric', 'min:0'],
            'appro_compartiment_jauges.*.volume_reel' => ['required', 'numeric', 'min:0'],
            'appro_compartiment_jauges.*.volume_theorique' => ['required', 'numeric', 'min:0'],
        ];
    }

    #[Override]
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (array_key_exists('appro_compartiment_jauges', $validated) && is_array($validated['appro_compartiment_jauges'])) {
            $validated['appro_compartiment_jauges'] = array_map(function (array $item): array {
                if (array_key_exists('volume_theorique', $item) && ! array_key_exists('volume_theorique', $item)) {
                    $item['volume_theorique'] = $item['volume_theorique'];
                }
                unset($item['volume_theorique']);
                return $item;
            }, $validated['appro_compartiment_jauges']);
        }

        return $validated;
    }

    #[Override]
    public function messages(): array
    {
        return [
            'affectation_citerne_id.required' => "L'affectation citerne est obligatoire.",
            'affectation_citerne_id.exists' => "L'affectation citerne selectionnee n'existe pas.",
            'station_id.exists' => "La station selectionnee n'existe pas.",
            'date_approvision.required' => "La date d'approvisionnement est obligatoire.",
            'total_litre_theorique.required' => "Le total litre theorique est obligatoire.",
            'total_litre_theorique.integer' => "Le total litre theorique doit etre un entier.",
            'total_litre_theorique.min' => "Le total litre theorique doit etre superieur ou egal a 0.",
            'total_litre_reel.integer' => "Le total litre reel doit etre un entier.",
            'total_litre_reel.min' => "Le total litre reel doit etre superieur ou egal a 0.",

            'appro_compartiment_jauges.required' => "Les jauges des compartiments sont obligatoires.",
            'appro_compartiment_jauges.array' => "Les jauges des compartiments sont invalides.",
            'appro_compartiment_jauges.min' => "Veuillez fournir au moins un compartiment.",
            'appro_compartiment_jauges.*.hydrocarbure_id.required' => "L'hydrocarbure est obligatoire.",
            'appro_compartiment_jauges.*.hydrocarbure_id.exists' => "L'hydrocarbure selectionne n'existe pas.",
            'appro_compartiment_jauges.*.num_compartiment.required' => "Le numero de compartiment est obligatoire.",
            'appro_compartiment_jauges.*.num_compartiment.distinct' => "Les numeros de compartiment doivent etre uniques dans la requete.",
            'appro_compartiment_jauges.*.volume_reel.required' => "Le volume reel est obligatoire.",
            'appro_compartiment_jauges.*.volume_theorique.required' => "Le volume theorique est obligatoire.",
        ];
    }
}
