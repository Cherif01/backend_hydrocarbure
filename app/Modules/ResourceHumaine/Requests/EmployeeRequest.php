<?php

namespace App\Modules\ResourceHumaine\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = is_object($employee) ? $employee->id : $employee;

        return [
            'name' => ['required', 'string', 'max:255'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'telephone' => ['required', 'string', 'max:255', Rule::unique('employees', 'telephone')->ignore($employeeId)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'salaire_base' => ['nullable', 'numeric', 'min:0'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => "Le nom de l'employe est obligatoire.",
            'name.string' => "Le nom de l'employe doit etre une chaine de caracteres.",
            'name.max' => "Le nom de l'employe ne peut pas depasser :max caracteres.",
            'post_id.integer' => "Le poste selectionne est invalide.",
            'post_id.exists' => "Le poste selectionne n'existe pas.",
            'station_id.integer' => "La station selectionnee est invalide.",
            'station_id.exists' => "La station selectionnee n'existe pas.",
            'telephone.required' => "Le telephone est obligatoire.",
            'telephone.string' => "Le telephone doit etre une chaine de caracteres.",
            'telephone.max' => "Le telephone ne peut pas depasser :max caracteres.",
            'telephone.unique' => "Ce numero de telephone est deja utilise.",
            'adresse.string' => "L'adresse doit etre une chaine de caracteres.",
            'adresse.max' => "L'adresse ne peut pas depasser :max caracteres.",
            'salaire_base.numeric' => "Le salaire de base doit etre un nombre valide.",
            'salaire_base.min' => "Le salaire de base doit etre superieur ou egal a 0.",
            'avatar.image' => "L'avatar doit etre une image valide.",
            'avatar.mimes' => "L'avatar doit etre au format PNG, JPG ou JPEG.",
            'avatar.max' => "L'avatar ne peut pas depasser :max Ko.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}
