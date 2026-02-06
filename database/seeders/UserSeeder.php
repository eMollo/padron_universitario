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
        $admin = User::firstOrCreate(
            ['user' => 'admin'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'is_active' => true,
            ]
        );

        $rolAdmin = Role::firstOrCreate(['name' => 'admin']);

        $admin->roles()->syncWithoutDetaching([$rolAdmin->id]);

    }
}
