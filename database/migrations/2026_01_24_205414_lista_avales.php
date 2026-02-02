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
        Schema::create('lista_avales', function (Blueprint $table) {
        $table->id();

        $table->foreignId('id_lista')->constrained('listas')->onDelete('cascade');
        $table->foreignId('id_persona')->nullable()->constrained('personas')->nullOnDelete();
        $table->string('legajo')->nullable();
        #$table->foreignId('id_facultad')->nullable()->constrained('facultad')->onDelete('cascade');
        #$table->foreignId('id_claustro')->nullable()->constrained('claustros')->onDelete('cascade');
        $table->enum('estado', ['valido', 'invalido']);
        $table->string('motivo_invalidez')->nullable();
        $table->timestamps();
        $table->index(['id_lista', 'estado']);
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
