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
        Schema::create('padron_resumen', function (Blueprint $table) {
        $table->integer('anio');
        $table->foreignId('id_facultad')->nullable();
        $table->foreignId('id_claustro')->constrained('claustros');
        $table->integer('total');
        $table->primary(['anio','id_facultad','id_claustro']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
