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
        Schema::create('sede', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('id_facultad')->constrained('facultad')->onDelete('cascade');
            $table->timestamps();

            //Una sede no puede repetirse dentro de la misma facultad
            $table->unique(['id_facultad','nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sede');
    }
};
