<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $client = $this->route('client');
        $clientId = is_object($client) ? $client->id : $client;
        $hydrocarburePresenceRule = $this->isMethod('POST') ? 'required' : 'sometimes';
        $hydrocarbureItemPresenceRule = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:255', Rule::unique('clients', 'telephone')->ignore($clientId)],
            'email' => ['nullable', 'string', 'max:255', 'email', Rule::unique('clients', 'email')->ignore($clientId)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],

            'hydrocarbure' => [$hydrocarburePresenceRule, 'array'],
            'hydrocarbure.*.hydrocarbure_id' => [$hydrocarbureItemPresenceRule, 'integer', 'exists:hydrocarbures,id', 'distinct'],
            'hydrocarbure.*.max_litre' => ['nullable', 'numeric', 'min:0'],
            'hydrocarbure.*.prix' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => "Le nom ou raison sociale est obligatoire.",
            'name.string' => "Le nom ou raison sociale doit etre une chaine de caracteres.",
            'name.max' => "Le nom ou raison sociale ne peut pas depasser :max caracteres.",
            'telephone.required' => "Le telephone est obligatoire.",
            'telephone.string' => "Le telephone doit etre une chaine de caracteres.",
            'telephone.max' => "Le telephone ne peut pas depasser :max caracteres.",
            'telephone.unique' => "Ce numero de telephone est deja utilise.",
            'email.string' => "L'email doit etre une chaine de caracteres.",
            'email.max' => "L'email ne peut pas depasser :max caracteres.",
            'email.email' => "L'email doit etre une adresse email valide.",
            'email.unique' => "Cet email est deja utilise.",
            'adresse.string' => "L'adresse doit etre une chaine de caracteres.",
            'adresse.max' => "L'adresse ne peut pas depasser :max caracteres.",
            'avatar.image' => "L'image doit etre une image valide.",
            'avatar.mimes' => "L'image doit etre au format PNG, JPG ou JPEG.",
            'avatar.max' => "L'image ne peut pas depasser :max Ko.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",

            'hydrocarbure.required' => "L'hydrocarbure est obligatoire.",
            'hydrocarbure.*.hydrocarbure_id.required' => "L'hydrocarbure est obligatoire.",
            'hydrocarbure.*.hydrocarbure_id.distinct' => "L'hydrocarbure est duplique dans la liste.",
            'hydrocarbure.*.max_litre.numeric' => "Le max litre doit etre un nombre valide.",
            'hydrocarbure.*.max_litre.min' => "Le max litre doit etre superieur ou egal a 0.",
            'hydrocarbure.*.prix.numeric' => "Le prix doit etre un nombre valide.",
            'hydrocarbure.*.prix.min' => "Le prix doit etre superieur ou egal a 0.",
        ];
    }
}
