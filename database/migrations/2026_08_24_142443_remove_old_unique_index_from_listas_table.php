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
        Schema::table('listas', function (Blueprint $table) {
            $table->dropUnique('listas_anio_tipo_numero_id_claustro_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            $table->unique(
                ['anio', 'tipo', 'numero', 'id_claustro'],
                'listas_anio_tipo_numero_id_claustro_unique'
            );
        });
    }
};
