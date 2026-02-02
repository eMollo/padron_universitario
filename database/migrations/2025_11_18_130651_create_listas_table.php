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
        Schema::create('listas', function (Blueprint $table) {
            $table->id();
            $table->integer('anio')->index();
            $table->string('tipo'); //ej: 'superior', 'directivo', 'estudiante'
            $table->string('nombre');
            $table->string('sigla')->nullable();
            $table->integer('numero'); //numeracion por anio+tipo
            $table->foreignId('id_facultad')->nullable()->constrained('facultad')->onDelete('cascade');
            $table->foreignId('id_claustro')->nullable()->constrained('claustros')->onDelete('cascade');
            $table->foreignId('id_apoderado')->constrained('personas')->onDelete('cascade');
            $table->string('estado_lista')->default('cargada'); //avales_faltantes , oficializada
            $table->timestamps();

            $table->unique(['anio', 'tipo', 'numero', 'id_claustro']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listas');
    }
};
