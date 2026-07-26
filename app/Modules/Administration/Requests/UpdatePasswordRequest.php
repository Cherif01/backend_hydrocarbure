<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|confirmed',
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'current_password.required' => 'Veuillez entrer votre mot de passe actuel.',
            'new_password.required' => 'Veuillez entrer votre nouveau mot de passe.',
            'new_password.confirmed' => 'Veuillez confirmer votre nouveau mot de passe.',
        ];
    }
}
