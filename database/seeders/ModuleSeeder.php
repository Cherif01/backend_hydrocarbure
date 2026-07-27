<?php

namespace Database\Seeders;

use App\Modules\Administration\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'comptabilite', 'description' => 'Module Comptabilite'],
            ['name' => 'gerant_station', 'description' => 'Module Gerant Station'],
            ['name' => 'distribution', 'description' => 'Module Distribution'],
            ['name' => 'rh', 'description' => 'Module Ressource Humaine'],
            ['name' => 'administration', 'description' => 'Module Administration'],
            ['name' => 'pilotage', 'description' => 'Module Pilotage'],
            ['name' => 'rapport', 'description' => 'Module Rapport'],
        ];

        foreach ($modules as $module) {
            Module::firstOrCreate(
                ['name' => $module['name']],
                ['description' => $module['description'], 'is_active' => true]
            );
        }
    }
}
