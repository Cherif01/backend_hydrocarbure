<?php

namespace Database\Seeders;

use App\Modules\Administration\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'description' => 'Super Administrateur'],
            ['name' => 'admin', 'description' => 'Administrateur'],
            ['name' => 'gerant', 'description' => 'Gerant d\'une station'],
            ['name' => 'user', 'description' => 'Utilisateur Standard'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description'], 'is_active' => true]
            );
        }
    }
}
