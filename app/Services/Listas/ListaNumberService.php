<?php

namespace App\Services\Listas;

use App\Models\Lista;

class ListaNumberService
{
    public function nextNumber(int $anio, string $tipo): int
    {
        $ultimo = Lista::where('anio', $anio)->where('tipo', $tipo)->max('numero');
        return (($ultimo ?? 0) + 1);
    }
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
