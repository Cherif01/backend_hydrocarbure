<?php

namespace App\Modules\Comptabilite\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class TypeOperationResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'nature' => (bool) $this->nature,
            'nature_libelle' => $this->nature ? 'entree' : 'sortie',
            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at?->format('d-m-Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d-m-Y H:i:s'),
        ];
    }
}
