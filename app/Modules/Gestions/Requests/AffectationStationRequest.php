<?php

namespace App\Modules\Gestions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class AffectationStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'user_id' => ['required', 'integer', 'exists:users,id']
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'station_id.required' => "La station est obligatoire.",
            'station_id.integer' => "La station selectionnee est invalide.",
            'station_id.exists' => "La station selectionnee n'existe pas.",
            'user_id.required' => "L'utilisateur est obligatoire.",
            'user_id.integer' => "L'utilisateur selectionne est invalide.",
            'user_id.exists' => "L'utilisateur selectionne n'existe pas.",
        ];
    }
}
