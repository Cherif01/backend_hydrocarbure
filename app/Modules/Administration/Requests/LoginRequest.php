<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'telephone' => "required|string|min:9",
            'password' => "required|string|min:6"
        ];
    }
}
