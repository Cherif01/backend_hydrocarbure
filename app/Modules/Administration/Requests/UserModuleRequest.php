<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class UserModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_id' => 'required|integer|exists:modules,id',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'module_id.required' => 'Le module est requis',
            'user_id.required' => 'L\'utilisateur est requis',
            'module_id.exists' => 'Le module n\'existe pas',
            'user_id.exists' => 'L\'utilisateur n\'existe pas',
        ];
    }
}
