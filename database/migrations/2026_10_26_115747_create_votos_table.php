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
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_votacion')->constrained('votaciones')->cascadeOnDelete();
            $table->foreignId('id_persona')->constrained('personas')->cascadeOnDelete();
            $table->enum('voto', ['afirmativo', 'negativo', 'abstencion']);
            $table->timestamps();
 
            // Una persona vota una sola vez por votación
            $table->unique(['id_votacion', 'id_persona']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
