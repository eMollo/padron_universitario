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
        Schema::create('padrones', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->foreignId('id_claustro')->constrained('claustros')->onDelete('cascade');
            $table->foreignId('id_facultad')->constrained('facultad')->onDelete('cascade');
            $table->foreignId('id_sede')->constrained('sede')->onDelete('cascade');
            //metadata útil
            $table->string('origen_archivo')->nullable();//nombre del xlsx importado
            $table->unsignedBigInteger('importado_por')->nullable();//user_id si luego se implementan users
            $table->timestamp('importado_el')->nullable();
            $table->timestamps();

            //Un único padrón por combinacion año+claustro+facultad+sede
            $table->unique(['anio','id_claustro','id_facultad','id_sede'], 'padron_unico');
            $table->index(['anio','id_facultad','id_claustro']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('padrones');
    }
};
