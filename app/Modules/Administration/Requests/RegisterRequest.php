<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'telephone' => ['required', 'string', 'min:9', 'max:14', 'unique:users,telephone'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'role' => ['nullable', 'string', 'in:user,super_admin,admin,client'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'password' => ['required', 'string', 'min:6', 'confirmed']

        ];
    }

    #[Override]
    public function messages()
    {
        return [
            // Name
            'name.required' => "Le nom complet est obligatoire.",
            'name.string'   => "Le nom complet doit être une chaîne de caractères.",
            'name.min'      => "Le nom complet doit contenir au moins :min caractères.",
            'name.max'      => "Le nom complet ne peut pas dépasser :max caractères.",

            // Telephone
            'telephone.required' => "Le numéro de téléphone est obligatoire.",
            'telephone.string'   => "Le numéro de téléphone doit être une chaîne de caractères.",
            'telephone.min'      => "Le numéro de téléphone doit contenir au moins :min caractères.",
            'telephone.max'      => "Le numéro de téléphone ne peut pas dépasser :max caractères.",
            'telephone.unique'   => "Ce numéro de téléphone est déjà utilisé.",

            // Email
            'email.email'    => "L'adresse email n'est pas valide.",
            'email.max'      => "L'adresse email ne peut pas dépasser :max caractères.",
            'email.unique'   => "Cette adresse email est déjà utilisée.",

            // Role
            'role.in' => "Ce rôle est invalide.",

            // Avatar
            'avatar.image' => "L'avatar n'est pas une image valide.",
            'avatar.mimes' => "L'avatar doit être une image PNG, JPG ou JPEG.",
            'avatar.max' => "L'avatar ne peut pas dépasser :max Mo.",

            // Password
            'password.required'  => "Le mot de passe est obligatoire.",
            'password.string'    => "Le mot de passe doit être une chaîne de caractères.",
            'password.min'       => "Le mot de passe doit contenir au moins :min caractères.",
            'password.confirmed' => "La confirmation du mot de passe ne correspond pas.",
        ];
    }
}
