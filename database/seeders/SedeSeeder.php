<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class SedeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sede')->insert([
            ['nombre' => 'Neuquén (Administración Central)', 'id_facultad' => 1],
            ['nombre' => 'Bariloche (Centro Regional Universitario Bariloche)', 'id_facultad' => 2],
            ['nombre' => 'Viedma (Centro Regional Zona Atlántica)', 'id_facultad' => 3],
            ['nombre' => 'San Antonio Oeste (Inst de Biología Marina y Pesca)', 'id_facultad' => 4],
            ['nombre' => 'Cipolletti (Facultad de Ciencias Médicas)', 'id_facultad' => 5],
            ['nombre' => 'Neuquén (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Allen (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Zapala (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Esquel (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'San Martin de los Andes (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Puerto Madryn (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Choele Choel (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Chos Malal  (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Trelew (Fac de Cs del Ambiente y la Salud)', 'id_facultad' => 6],
            ['nombre' => 'Cinco Saltos (Facultad de Ciencias Agrarias)', 'id_facultad' => 7],
            ['nombre' => 'San Martin de los Andes (Facultad de Ciencias Agrarias)', 'id_facultad' => 7],
            ['nombre' => 'Cipolletti (Facultad de Ciencias de la Educación)', 'id_facultad' => 8],
            ['nombre' => 'Neuquén (Facultad de Derecho y Ciencias Sociales)', 'id_facultad' => 9],
            ['nombre' => 'General Roca  (Facultad de Derecho y Ciencias Sociales)', 'id_facultad' => 9],
            ['nombre' => 'Neuquén (Facultad de Economía y Administración)', 'id_facultad' => 10],
            ['nombre' => 'Neuquén (Facultad de Humanidades)', 'id_facultad' => 11],
            ['nombre' => 'Bariloche (Facultad de Humanidades)', 'id_facultad' => 11],
            ['nombre' => 'Zapala (Facultad de Humanidades)', 'id_facultad' => 11],
            ['nombre' => 'Chos Malal (Facultad de Humanidades)', 'id_facultad' => 11],
            ['nombre' => 'Neuquén (Facultad de Informática)', 'id_facultad' => 12],
            ['nombre' => 'Chos Malal (Facultad de Informática)', 'id_facultad' => 12],
            ['nombre' => 'Neuquén (Facultad de Ingeniería)', 'id_facultad' => 13],
            ['nombre' => 'Zapala (Facultad de Ingeniería)', 'id_facultad' => 13],
            ['nombre' => 'General Roca (Facultad de Lenguas)', 'id_facultad' => 14],
            ['nombre' => 'Villa Regina (Fac de Cs y Tecnología de los Alimentos)', 'id_facultad' => 15],
            ['nombre' => 'San Martin de los Andes (Facultad de Turismo)', 'id_facultad' => 16],
            ['nombre' => 'Neuquén(Facultad de Turismo)', 'id_facultad' => 16],
            ['nombre' => 'Zapala (Facultad de Turismo)', 'id_facultad' => 16],
            ['nombre' => 'San Martin de los Andes (Asentamiento Universitario San Martin - FACA)', 'id_facultad' => 17],
            ['nombre' => 'Zapala (Asentam Universitario Zapala - FAIN)', 'id_facultad' => 18],
        ]);
    }
}
