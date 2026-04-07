<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE padrones ALTER COLUMN id_sede SET NOT NULL');

        DB::statement('
            ALTER TABLE padrones
            ADD CONSTRAINT unique_padron
            UNIQUE (anio, id_facultad, id_claustro, id_sede)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE padrones ALTER COLUMN id_sede DROP NOT NULL');

        DB::statement('
            ALTER TABLE padrones
            DROP CONSTRAINT IF EXISTS unique_padron
        ');
    }
};
