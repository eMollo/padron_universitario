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
        Schema::table('lista_postulantes', function (Blueprint $table) {
            $table->unique(
                ['id_lista', 'tipo', 'orden'],
                'lista_tipo_orden_unico'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lista_postulantes', function (Blueprint $table) {
            $table->dropUnique('lista_tipo_orden_unico');
        });
    }
};
