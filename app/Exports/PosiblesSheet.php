<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PosiblesSheet implements FromCollection, WithHeadings
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->data as $grupo) {
            foreach ($grupo as $persona) {
                $rows->push([
                    $persona->dni,
                    $persona->apellido,
                    $persona->nombre,
                    $persona->facultad,
                    $persona->claustro,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['DNI', 'Apellido', 'Nombre', 'Facultad', 'Claustro'];
    }
}