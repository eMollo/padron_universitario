<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Solo insertar si no existe ya
        $existe = DB::table('roles')->where('name', 'consejo_superior')->exists();

        if (!$existe) {
            DB::table('roles')->insert([
                'name'       => 'consejo_superior',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'consejo_superior')->delete();
    }
};