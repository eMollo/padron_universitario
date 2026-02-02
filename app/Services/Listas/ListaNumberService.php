<?php

namespace App\Services\Listas;

use App\Models\Lista;

class ListaNumberService
{
    public function nextNumber(int $anio, string $tipo, ?int $id_claustro): int
    {
        $query = Lista::where('anio', $anio)
            ->where('tipo', $tipo);

        if (in_array($tipo, ['superior', 'directivo'])){
            $query->where('id_claustro', $id_claustro);
        }else {
            //decano / rector
            $query->whereNull('id_claustro');
        }

        $ultimo = $query->max('numero');

        return ($ultimo ?? 0) + 1;
        /*$ultimo = Lista::where('anio', $anio)
            ->where('tipo', $tipo)
            ->where('id_claustro', $id_claustro)
            ->max('numero');
        return (($ultimo ?? 0) + 1);*/
    }
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
