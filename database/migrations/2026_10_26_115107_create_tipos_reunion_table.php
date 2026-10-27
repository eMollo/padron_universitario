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
        Schema::create('tipos_reunion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // Las 5 comisiones
        DB::table('tipos_reunion')->insert([
            ['nombre' => 'Interpretación y Reglamento', 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Investigación y Extensión', 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Presupuesto y Administración', 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Docencia y Asuntos Estudiantiles', 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Bienestar Universitario', 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_reunion');
    }
};
