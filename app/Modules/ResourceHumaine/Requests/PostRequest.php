<?php

namespace App\Modules\ResourceHumaine\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $post = $this->route('post');
        $postId = is_object($post) ? $post->id : $post;

        return [
            'libelle' => ['required', 'string', 'max:255', Rule::unique('posts', 'libelle')->ignore($postId)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'libelle.required' => "Le libelle du poste est obligatoire.",
            'libelle.string' => "Le libelle du poste doit etre une chaine de caracteres.",
            'libelle.max' => "Le libelle du poste ne peut pas depasser :max caracteres.",
            'libelle.unique' => "Ce libelle de poste est deja utilise.",
            'is_active.boolean' => "Le statut doit etre vrai ou faux.",
        ];
    }
}
