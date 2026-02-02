<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PadronResumenService;

class RecalcularPadronResumen extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'padron:recalcular {anio}';

    /**
     * The console command description.
     */
    protected $description = 'Recalcula el padrón resumen para un año electoral';

    /**
     * Execute the console command.
     */
    public function handle(PadronResumenService $service): int
    {
        $anio = (int) $this->argument('anio');

        if ($anio <= 0) {
            $this->error('El año debe ser un número válido.');
            return Command::FAILURE;
        }

        $this->info("Recalculando padrón resumen para el año {$anio}...");

        $service->recalcular($anio);

        $this->info('Padrón resumen recalculado correctamente.');

        return Command::SUCCESS;
    }
}
