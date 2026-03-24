<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;


class ComparadorExport implements WithMultipleSheets
{
    protected array $exactos;
    protected array $posibles;

    public function __construct(array $exactos, array $posibles)
    {
        $this->exactos = $exactos;
        $this->posibles = $posibles;
    }

    public function sheets(): array
    {
        return [
            new ExactosSheet($this->exactos),
            new PosiblesSheet($this->posibles),
        ];
    }
}

