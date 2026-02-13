<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar unique actual
        DB::statement('DROP INDEX IF EXISTS inscripcion_unica');

        // Crear unique parcial
        DB::statement('
            CREATE UNIQUE INDEX inscripcion_unica_activa
            ON inscripciones (id_persona, id_padron)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS inscripcion_unica_activa');

        DB::statement('
            CREATE UNIQUE INDEX inscripcion_unica
            ON inscripciones (id_persona, id_padron)
        ');
    }
};
