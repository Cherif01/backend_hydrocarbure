<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class HydrocarbureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'prix_achat' => ['required', 'numeric', 'min:0'],
            'prix_vente' => ['required', 'numeric', 'min:0'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'libelle.required' => "Le libelle de l'hydrocarbure est obligatoire.",
            'libelle.string' => "Le libelle de l'hydrocarbure doit etre une chaine de caracteres.",
            'libelle.max' => "Le libelle de l'hydrocarbure ne peut pas depasser :max caracteres.",
            'prix_achat.required' => "Le prix d'achat est obligatoire.",
            'prix_achat.numeric' => "Le prix d'achat doit etre un nombre valide.",
            'prix_achat.min' => "Le prix d'achat doit etre superieur ou egal a 0.",
            'prix_vente.required' => 'Le prix de vente est obligatoire.',
            'prix_vente.numeric' => 'Le prix de vente doit etre un nombre valide.',
            'prix_vente.min' => 'Le prix de vente doit etre superieur ou egal a 0.',
        ];
    }
}
