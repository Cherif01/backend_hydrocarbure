<?php

namespace App\Modules\ResourceHumaine\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'email'],
            'adresse' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],

            'hydrocarbure' => ['required', 'array'],
            'hydrocarbure.*.hydrocarbure_id' => ['required', 'integer', 'exists:hydrocarbures,id'],
            'hydrocarbure.*.max_litre' => ['required', 'numeric', 'min:0'],
            'hydrocarbure.*.prix' => ['required', 'numeric', 'min:0'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'name.required' => "Le nom ou raison sociale est obligatoire.",
            'telephone.required' => "Le telephone est obligatoire.",
            'email.required' => "L'email est obligatoire.",
            'adresse.required' => "L'adresse est obligatoire.",
            'avatar.image' => "L'image doit etre une image valide.",
            'avatar.mimes' => "L'image doit etre au format PNG, JPG ou JPEG.",
            'avatar.max' => "L'image ne peut pas depasser :max Ko.",
            'hydrocarbure.required' => "L'hydrocarbure est obligatoire.",
            'hydrocarbure.*.hydrocarbure_id.required' => "L'hydrocarbure est obligatoire.",
            'hydrocarbure.*.max_litre.required' => "Le max_litre est obligatoire.",
            'hydrocarbure.*.prix.required' => "Le prix est obligatoire.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}
