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
        Schema::create('asistencia_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_reunion')->constrained('reuniones')->cascadeOnDelete();
            $table->foreignId('id_persona')->constrained('personas')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'entrada_virtual', 'salida']);
            $table->timestamp('created_at')->useCurrent();
            // Sin updated_at — los movimientos son inmutables

        });

            // Índice para consultas frecuentes: último movimiento por persona en una reunión
            \Illuminate\Support\Facades\DB::statement(
            'CREATE INDEX idx_asistencia_reunion_persona
             ON asistencia_movimientos (id_reunion, id_persona, created_at DESC)'
        );

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencia_movimientos');
    }
};
