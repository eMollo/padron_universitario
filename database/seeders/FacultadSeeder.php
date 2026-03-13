<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class FacultadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('facultad')->insert([
            ['nombre' => 'Administración Central', 'sigla' => 'RECT'],
            ['nombre' => 'Centro Regional Universitario Bariloche', 'sigla' => 'CRUB'],
            ['nombre' => 'Complejo Universitario Regional Zona Atlántica y Sur', 'sigla' => 'CURZAS'],
            ['nombre' => 'Facultad de Ciencias Marinas', 'sigla' => 'FACIMAR'],
            ['nombre' => 'Facultad de Ciencias Médicas', 'sigla' => 'FAME'],
            ['nombre' => 'Facultad de Ciencias del Ambiente y la Salud', 'sigla' => 'FAAS'],
            ['nombre' => 'Facultad de Ciencias Agrarias', 'sigla' => 'FACA'],
            ['nombre' => 'Facultad de Ciencias de la Educación y Psicología', 'sigla' => 'FACEP'],
            ['nombre' => 'Facultad de Derecho y Ciencias Sociales', 'sigla' => 'FADE'],
            ['nombre' => 'Facultad de Economía y Administración', 'sigla' => 'FAEA'],
            ['nombre' => 'Facultad de Humanidades', 'sigla' => 'FAHU'],
            ['nombre' => 'Facultad de Informática', 'sigla' => 'FAIF'],
            ['nombre' => 'Facultad de Ingeniería', 'sigla' => 'FAIN'],
            ['nombre' => 'Facultad de Lenguas', 'sigla' => 'FALE'],
            ['nombre' => 'Facultad de Ciencias y Tecnología de los Alimentos', 'sigla' => 'FATA'],
            ['nombre' => 'Facultad de Turismo', 'sigla' => 'FATU'],
            ['nombre' => 'Centro Regional Universitario San Martín de los Andes', 'sigla' => 'CREUSMA'],
            ['nombre' => 'Centro Regional Universitario Zapala', 'sigla' => 'CREUZA'],            
        ]);
    }
}
