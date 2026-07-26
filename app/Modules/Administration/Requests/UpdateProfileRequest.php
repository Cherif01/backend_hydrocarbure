<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:160'],
            'telephone' => ['sometimes', 'string', 'min:9', 'max:14', Rule::unique('users', 'telephone')->ignore($userId)],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'name.string' => "Le nom complet doit être une chaîne de caractères.",
            'name.min'    => "Le nom complet doit contenir au moins :min caractères.",
            'name.max'    => "Le nom complet ne peut pas dépasser :max caractères.",

            'telephone.string' => "Le numéro de téléphone doit être une chaîne de caractères.",
            'telephone.min'    => "Le numéro de téléphone doit contenir au moins :min caractères.",
            'telephone.max'    => "Le numéro de téléphone ne peut pas dépasser :max caractères.",
            'telephone.unique' => "Ce numéro de téléphone est déjà utilisé.",

            'email.email'  => "L'adresse email n'est pas valide.",
            'email.unique' => "Cette adresse email est déjà utilisée.",
        ];
    }
}
