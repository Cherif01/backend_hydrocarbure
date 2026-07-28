<?php

namespace App\Services;

use App\Modules\Administration\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UserStationScopeService
{
    public function resolve(User $user, bool $strict = true): array
    {
        $hasGerantStationModule = $user->userModules()
            ->where('is_active', true)
            ->whereHas('module', function ($query) {
                $query->where('name', 'gerant_station')
                    ->where('is_active', true);
            })
            ->exists();

        if (! $hasGerantStationModule) {
            return [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        }

        $affectation = $user->affectations()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (! $affectation) {
            if ($strict) {
                throw new AuthorizationException("Vous n'avez pas d'affectation de station active.");
            }

            return [
                'is_station_scoped' => true,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        }

        return [
            'is_station_scoped' => true,
            'station_id' => $affectation->station_id,
            'affectation_station_id' => $affectation->id,
        ];
    }
}
