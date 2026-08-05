<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //CONSEJO SUPERIOR Y CONSEJO DIRECTIVO
        //La numeración es independiente por claustro

        DB::statement("
            CREATE UNIQUE INDEX uq_listas_numero_por_claustro
            ON listas (anio, tipom id_claustro, numero)
            WHERE tipo IN ('superior', 'directivo');");
        
        //RECTOR Y DECANO
        //La numeración es global por tipo
        DB::statement("
            CREATE UNIQUE INDEX uq_listas_numero_global
            ON listas (anio, tipo, numero)
            WHERE tipo IN ('rector', 'decano');");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            DROP INDEX IF EXISTS uq_listas_numero_por_claustro;");
        
        DB::statement("
            DROP INDEX IF EXISTS uq_listas_numero_global;");
    }
};
