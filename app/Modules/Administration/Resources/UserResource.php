<?php

namespace App\Modules\Administration\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class UserResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,

            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'description' => $this->role->description,
            ] : null,

            'created_at' => $this->created_at?->format('d-m-Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d-m-Y H:i:s'),
        ];
    }
}
