<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE inscripciones DROP CONSTRAINT IF EXISTS inscripcion_unica');

        DB::statement('
            CREATE UNIQUE INDEX inscripcion_unica_activa
            ON inscripciones (id_persona, id_padron)
            WHERE deleted_at IS NULL
        ');
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS inscripcion_unica_activa');

        DB::statement('
            ALTER TABLE inscripciones
            ADD CONSTRAINT inscripcion_unica
            UNIQUE (id_persona, id_padron)
        ');
    }

};
