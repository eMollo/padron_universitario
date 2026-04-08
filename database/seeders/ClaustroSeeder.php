<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ClaustroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('claustros')->insert([
            ['nombre' => 'Docente'],
            ['nombre' => 'Nodocente'],
            ['nombre' => 'Estudiantes'],
            ['nombre' => 'Personas Graduadas'],
        ]);
    }
}
