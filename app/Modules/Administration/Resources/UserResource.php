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
            'role' => $this->role,

            'user_modules' => $this->userModules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'module_id' => $module->module->id,
                    'name' => $module->module->name,
                    'description' => $module->module->description,
                    'code_acces' => $module->code_acces,
                    'is_active' => $module->is_active,
                ];
            }),

            'affectations' => $this->affectations->map(function ($affectation) {
                return [
                    'id' => $affectation->id,
                    'station_id' => $affectation->station_id,
                    'is_active' => $affectation->is_active,
                    'station' => [
                        'reference' => $affectation->station?->reference,
                        'libelle' => $affectation->station?->libelle,
                        'description' => $affectation->station?->description,
                        'addresse' => $affectation->station?->adresse,
                        'ville' => $affectation->station?->ville,
                    ],
                ];
            }),

            'created_at' => $this->created_at?->format('d-m-Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d-m-Y H:i:s'),
        ];
    }
}
