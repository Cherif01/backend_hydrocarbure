<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class AffectationPistoletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presenceRule = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        $affectation = $this->route('affectation_pistolet');
        $currentIsActive = is_object($affectation) ? (bool) $affectation->is_active : null;
        $requestedIsActive = $this->has('is_active')
            ? filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $isClosingUpdate = $currentIsActive === true && $requestedIsActive === false;
        $isClosingStore = $this->isMethod('POST') && $requestedIsActive === false;
        $requiresCloseFields = $isClosingUpdate || $isClosingStore;
        $closePresenceRule = $requiresCloseFields ? 'required' : 'sometimes';

        return [
            'employee_id' => [$presenceRule, 'integer', 'exists:employees,id'],
            'pistolet_id' => [$presenceRule, 'integer', 'exists:pistolets,id'],
            'index_ouverture' => [$presenceRule, 'numeric', 'min:0'],
            'index_fermeture' => [$closePresenceRule, 'numeric', 'min:0'],
            'litre_retouner' => [$closePresenceRule, 'numeric', 'min:0'],
            'montant_recu' => [$closePresenceRule, 'numeric', 'min:0'],
            'commentaire' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'employee_id.required' => "L'employe est obligatoire.",
            'employee_id.integer' => "L'employe selectionne est invalide.",
            'employee_id.exists' => "L'employe selectionne n'existe pas.",
            'pistolet_id.required' => 'Le pistolet est obligatoire.',
            'pistolet_id.integer' => 'Le pistolet selectionne est invalide.',
            'pistolet_id.exists' => "Le pistolet selectionne n'existe pas.",
            'index_ouverture.required' => "L'index d'ouverture est obligatoire.",
            'index_ouverture.numeric' => "L'index d'ouverture doit etre un nombre valide.",
            'index_ouverture.min' => "L'index d'ouverture doit etre superieur ou egal a 0.",
            'index_fermeture.required' => "L'index de fermeture est obligatoire lors de la cloture.",
            'index_fermeture.numeric' => "L'index de fermeture doit etre un nombre valide.",
            'index_fermeture.min' => "L'index de fermeture doit etre superieur ou egal a 0.",
            'litre_retouner.required' => "Le litre retourne est obligatoire lors de la cloture.",
            'litre_retouner.numeric' => "Le litre retourne doit etre un nombre valide.",
            'litre_retouner.min' => "Le litre retourne doit etre superieur ou egal a 0.",
            'montant_recu.required' => "Le montant recu est obligatoire lors de la cloture.",
            'montant_recu.numeric' => "Le montant recu doit etre un nombre valide.",
            'montant_recu.min' => "Le montant recu doit etre superieur ou egal a 0.",
            'commentaire.string' => 'Le commentaire doit etre une chaine de caracteres.',
            'is_active.boolean' => 'Le statut doit etre vrai ou faux.',
        ];
    }
}
