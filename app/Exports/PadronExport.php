<?php

namespace App\Exports;

use App\Models\Padron;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PadronExport implements FromCollection, WithHeadings
{
    protected Padron $padron;

    public function __construct(Padron $padron)
    {
        $this->padron = $padron;
    }

    public function collection(): Collection
    {
        $inscripciones = $this->padron
            ->inscripciones()
            ->whereNull('deleted_at')
            ->with('persona')
            ->get();

        return $inscripciones
            ->sortBy(function ($inscripcion) {
                $p = $inscripcion->persona;
                return $p
                    ? $this->claveOrden($p->apellido . ' ' . $p->nombre)
                    : '';
            })
            ->values()
            ->map(function ($inscripcion) {
                $p = $inscripcion->persona;

                return [
                    'apellido_nombre' => trim(
                        ($p->apellido ?? '') . ', ' . ($p->nombre ?? '')
                    ),
                    'dni'    => $p->dni ?? '',
                    'legajo' => $inscripcion->legajo ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Apellido y Nombre',
            'DNI',
            'Legajo',
        ];
    }

    /**
     * Normaliza texto para orden alfabético español
     */
    protected function claveOrden(string $texto): string
    {
        $texto = mb_strtoupper($texto, 'UTF-8');

        // Ñ después de N
        $texto = str_replace('Ñ', 'NZ', $texto);

        // Eliminar acentos
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);

        return $texto;
    }
}


