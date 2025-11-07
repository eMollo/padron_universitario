<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PruebaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Personas
        $persona1 = DB::table('personas')->insertGetId([
            'dni' => '12345678',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $persona2 = DB::table('personas')->insertGetId([
            'dni' => '87654321',
            'nombre' => 'María',
            'apellido' => 'García',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Facultades (para probar, agarremos una existente)
        $facultad = DB::table('facultad')->where('nombre', 'Facultad de Ingeniería')->first();

        // Padrones de ejemplo
        $padron1 = DB::table('padrones')->insertGetId([
            'anio' => 2025,
            'id_facultad' => $facultad->id,
            'id_claustro' => 1, // Docentes
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $padron2 = DB::table('padrones')->insertGetId([
            'anio' => 2025,
            'id_facultad' => $facultad->id,
            'id_claustro' => 3, // Estudiantes
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Inscripciones
        DB::table('inscripciones')->insert([
            'id_persona' => $persona1,
            'id_padron' => $padron1,
            'legajo' => 'DOC123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inscripciones')->insert([
            'id_persona' => $persona2,
            'id_padron' => $padron2,
            'legajo' => 'EST456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
