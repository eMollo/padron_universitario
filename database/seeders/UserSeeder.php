<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $consultaRole = Role::firstOrCreate(['name' => 'consulta']);

    // Admin
    $admin = User::firstOrCreate(
        ['user' => 'admin'],
        [
            'name' => 'Administrador',
            'password' => Hash::make('admin123'),
            'is_active' => true,
        ]
    );
    $admin->roles()->syncWithoutDetaching([$adminRole->id]);

    // Consulta
    $consulta = User::firstOrCreate(
        ['user' => 'consulta'],
        [
            'name' => 'Usuario Consulta',
            'password' => Hash::make('consulta123'),
            'is_active' => true,
        ]
    );
    $consulta->roles()->syncWithoutDetaching([$consultaRole->id]);

    }
}
