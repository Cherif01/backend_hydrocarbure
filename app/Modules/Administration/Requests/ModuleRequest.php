<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:modules,name,' . $this->route('module'),
            'description' => "nullable|string|max:255"
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'name.required' => "Le nom du module est requis.",
            'name.unique' => "Le nom du module doit être unique.",
            'description.max' => "La description du module doit être de plus de 255 caractères.",
        ];
    }
}
