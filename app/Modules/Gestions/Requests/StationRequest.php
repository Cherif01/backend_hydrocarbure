<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $station = $this->route('station');
        $stationId = is_object($station) ? $station->id : $station;

        return [
            'reference' => ['nullable', 'string', 'max:255', Rule::unique('stations', 'reference')->ignore($stationId)],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'longitude' => ['nullable', 'numeric'],
            'latitude' => ['nullable', 'numeric'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'reference.string' => "La reference doit etre une chaine de caracteres.",
            'reference.max' => "La reference ne peut pas depasser :max caracteres.",
            'reference.unique' => "Cette reference est deja utilisee.",
            'libelle.required' => "Le libelle est obligatoire.",
            'libelle.string' => "Le libelle doit etre une chaine de caracteres.",
            'libelle.max' => "Le libelle ne peut pas depasser :max caracteres.",
            'description.string' => "La description doit etre une chaine de caracteres.",
            'adresse.string' => "L'adresse doit etre une chaine de caracteres.",
            'adresse.max' => "L'adresse ne peut pas depasser :max caracteres.",
            'ville.string' => "La ville doit etre une chaine de caracteres.",
            'ville.max' => "La ville ne peut pas depasser :max caracteres.",
            'longitude.numeric' => "La longitude doit etre un nombre valide.",
            'latitude.numeric' => "La latitude doit etre un nombre valide.",
            'image.image' => "L'image doit etre une image valide.",
            'image.mimes' => "L'image doit etre au format PNG, JPG ou JPEG.",
            'image.max' => "L'image ne peut pas depasser :max Ko.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}
