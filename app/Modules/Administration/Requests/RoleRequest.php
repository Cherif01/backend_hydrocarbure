<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->route('role'),
            'description' => "nullable|string|max:255"
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'name.required' => "Le nom du role est requis.",
            'name.unique' => "Le nom du role doit être unique.",
            'description.max' => "La description du role doit être de plus de 255 caractères.",
        ];
    }
}
