<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // EXTENSION pg_trgm (PostgreSQL)

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // PERSONAS - índice funcional

        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_personas_apellido_nombre_upper
            ON personas (UPPER(apellido), UPPER(nombre))
        ');

        // PERSONAS - trigram búsquedas

        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_personas_apellido_trgm
            ON personas
            USING gin (apellido gin_trgm_ops)
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_personas_nombre_trgm
            ON personas
            USING gin (nombre gin_trgm_ops)
        ');

       
        // índice para búsquedas por DNI
        Schema::table('personas', function (Blueprint $table) {
            $table->index('dni', 'idx_personas_dni');
        });

        // índice para búsquedas por apellido
        Schema::table('personas', function (Blueprint $table) {
            $table->index('apellido', 'idx_personas_apellido');
        });

        // índice parcial para inscripciones activas
        DB::statement("
            CREATE INDEX idx_inscripciones_padron_activo
            ON inscripciones(id_padron)
            WHERE deleted_at IS NULL
        ");


        //ULTIMA PARTE AGREGADA

        Schema::table('inscripciones', function (Blueprint $table) {

            // búsqueda por padrón
            $table->index('id_padron', 'idx_inscripciones_padron');

            // persona dentro de padrón
            $table->index(['id_padron', 'id_persona'], 'idx_padron_persona');

            // padrones donde aparece persona
            $table->index(['id_persona', 'id_padron'], 'idx_persona_padron');

        });

        // índice para búsquedas de padrón
        Schema::table('padrones', function (Blueprint $table) {

            $table->index(
                ['anio','id_facultad','id_claustro','id_sede'],
                'idx_padron_filtros'
            );

        });

        // índice parcial para activos
        DB::statement("
            CREATE INDEX idx_inscripciones_activas
            ON inscripciones(id_padron)
            WHERE deleted_at IS NULL
        ");

    }

    public function down(): void
    {
        // PERSONAS

        DB::statement('
            DROP INDEX IF EXISTS idx_personas_apellido_nombre_upper
        ');

        DB::statement('
            DROP INDEX IF EXISTS idx_personas_apellido_trgm
        ');

        DB::statement('
            DROP INDEX IF EXISTS idx_personas_nombre_trgm
        ');

        
        
        Schema::table('personas', function (Blueprint $table) {
            $table->dropIndex('idx_personas_dni');
            $table->dropIndex('idx_personas_apellido');
        });

        DB::statement("
            DROP INDEX IF EXISTS idx_inscripciones_padron_activo
        ");


        //ULTIMA PARTE AGREGADA

        Schema::table('inscripciones', function (Blueprint $table) {

            $table->dropIndex('idx_inscripciones_padron');
            $table->dropIndex('idx_padron_persona');
            $table->dropIndex('idx_persona_padron');

        });

        Schema::table('padrones', function (Blueprint $table) {

            $table->dropIndex('idx_padron_filtros');

        });

        DB::statement("DROP INDEX IF EXISTS idx_inscripciones_activas");
    }
};

