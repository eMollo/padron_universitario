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
        Schema::table('listas', function (Blueprint $table) {
            $table->string('motivo_baja')->nullable()->after('deleted_at');
            $table->foreignId('eliminado_por')->nullable()->after('motivo_baja')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            $table->dropForeign(['eliminado_por']);
            $table->dropColumn(['motivo_baja', 'eliminado_por']);
        });
    }
};
