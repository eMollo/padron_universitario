<?php

namespace App\Services\Listas;

use App\Models\Lista;

class ListaNumberService
{
    public function nextNumber(int $anio, string $tipo, ?int $id_claustro): int
    {

        $query = Lista::where('anio', $anio)
            ->where('tipo', $tipo);

        switch ($tipo) {
            case 'superior':
            case 'directivo':
                $query->where('id_claustro', $id_claustro);
                break;
            case 'decano':
            case 'rector':
                break;
        }

        $ultimo = $query->max('numero');

        return ($ultimo ?? 0) + 1;
    }
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
