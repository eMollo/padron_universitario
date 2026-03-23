<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Aseguramos que solo pueda hacer SELECT en las tablas actuales
        DB::statement('GRANT SELECT ON ALL TABLES IN SCHEMA public TO usuario_consulta');

        // 2. Por seguridad, nos aseguramos de que TAMBIÉN tenga permiso de lectura en las secuencias 
        // (necesario si el usuario de consulta necesita ver valores de IDs autoincrementales)
        DB::statement('GRANT SELECT ON ALL SEQUENCES IN SCHEMA public TO usuario_consulta');
    
        // 3. (Opcional pero recomendado) Aplicar privilegios por defecto para el FUTURO
        // Así, si mañana creas la tabla 'votos', ya nace con permiso de lectura para él.
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO usuario_consulta');
    }

    public function down()
    {
        // Si decides revertir la migración, le quitas todo
        DB::statement('REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA public FROM usuario_consulta');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE SELECT ON TABLES FROM usuario_consulta');
    }
};
