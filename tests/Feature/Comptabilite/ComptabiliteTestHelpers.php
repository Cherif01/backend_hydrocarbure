<?php

namespace Tests\Feature\Comptabilite;

use App\Modules\Administration\Models\Module;
use App\Modules\Administration\Models\User;
use App\Modules\Administration\Models\UserModule;
use App\Modules\Gestions\Models\AffectationStation;
use App\Modules\Gestions\Models\Station;
use Illuminate\Support\Str;

trait ComptabiliteTestHelpers
{
    protected function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'telephone' => (string) random_int(600000000, 699999999),
            'email' => Str::random(10).'@example.com',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ], $overrides));
    }

    protected function createStation(array $overrides = []): Station
    {
        return Station::create(array_merge([
            'reference' => 'STA-'.Str::random(8),
            'libelle' => 'Station '.Str::random(5),
            'is_active' => true,
        ], $overrides));
    }

    protected function gerantStationModule(): Module
    {
        return Module::firstOrCreate(
            ['name' => 'gerant_station'],
            ['description' => 'Module Gerant Station', 'is_active' => true]
        );
    }

    protected function createStationScopedUser(Station $station, bool $withActiveAffectation = true): User
    {
        $user = $this->createUser();
        $module = $this->gerantStationModule();

        UserModule::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'code_acces' => Str::random(10),
            'is_active' => true,
        ]);

        if ($withActiveAffectation) {
            AffectationStation::create([
                'station_id' => $station->id,
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        return $user;
    }
}
