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
            Schema::create('orden_palabra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_reunion')->constrained('reuniones')->cascadeOnDelete();
            $table->foreignId('id_persona')->constrained('personas')->cascadeOnDelete();
            $table->integer('orden');
            $table->boolean('atendido')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_palabra');
    }
};
