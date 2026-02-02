<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\PadronResumen;
use Illuminate\Support\Facades\DB;

class PadronResumenService
{
    /**
     * Recalcula el padrón resumen para un año electoral.
     *
     * @param int $anio
     */
    public function recalcular(int $anio): void
    {
        DB::transaction(function () use ($anio) {

            // 1. Limpiar resumen existente para el año
            PadronResumen::where('anio', $anio)->delete();

            // 2. Recalcular desde inscripciones
            $resumen = Inscripcion::query()
                ->join('padrones', 'inscripciones.id_padron', '=', 'padrones.id')
                ->selectRaw('
                    padrones.anio,
                    padrones.id_facultad,
                    padrones.id_claustro,
                    COUNT(*) as total
                ')
                ->where('padrones.anio', $anio)
                ->groupBy(
                    'padrones.anio',
                    'padrones.id_facultad',
                    'padrones.id_claustro'
                )
                ->get();

            // 3. Insertar nuevos registros
            foreach ($resumen as $row) {
                PadronResumen::create([
                    'anio'        => $row->anio,
                    'id_facultad' => $row->id_facultad,
                    'id_claustro' => $row->id_claustro,
                    'total'       => $row->total,
                ]);
            }
        });
    }
}
