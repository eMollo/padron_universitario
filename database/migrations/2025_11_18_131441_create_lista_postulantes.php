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
        Schema::create('lista_postulantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_lista')->constrained('listas')->onDelete('cascade');
            $table->foreignId('id_persona')->constrained('personas')->onDelete('cascade');
            $table->enum('tipo', ['titular', 'suplente']);
            $table->integer('orden')->nullable();
            $table->string('legajo')->nullable();
            $table->timestamps();

            $table->unique(['id_lista', 'id_persona'], 'lista_persona_unica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_postulantes');
    }
};
