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
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_persona')->constrained('personas')->onDelete('cascade');
            $table->foreignId('id_padron')->constrained('padrones')->onDelete('cascade');
            $table->string('legajo')->nullable();
            $table->text('motivo_baja')->nullable();
            $table->foreignId('baja_realizada_por')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            //Una persona solo puede estar una vez en el mismo padrón
            $table->unique(['id_persona','id_padron'], 'inscripcion_unica');

            //Evitar legajos repetidos dentro de un mismo padrón (opcional, útil para validaciones)
            $table->unique(['id_padron', 'legajo'], 'padron_legajo_unico');

            $table->index(['legajo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
