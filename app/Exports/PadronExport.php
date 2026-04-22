<?php

namespace App\Exports;

use App\Models\Padron;
use Illuminate\Support\Collection;

use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;

class PadronExport implements FromCollection, WithHeadings, WithEvents
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

        $ordenados = $inscripciones
            ->sortBy(function ($inscripcion) {
                $p = $inscripcion->persona;
                return $p
                    ? $this->claveOrden($p->apellido . ' ' . $p->nombre)
                    : '';
            })
            ->values();

        //  CONTADOR GLOBAL
        $contador = 1;

        return $ordenados->map(function ($inscripcion) use (&$contador) {
            $p = $inscripcion->persona;

            return [
                'id' => $contador++, // NUEVO
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
            'ID', // NUEVO
            'Apellido y Nombre',
            'DNI',
            'Legajo',
        ];
    }

    /**
     * HEADER SUPERIOR (tipo imagen)
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;

                $padron = $this->padron;

                $sheet->insertNewRowBefore(1, 5);

                // Título principal
                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', 'PADRONES OFICIALIZADOS ' . $padron->anio);

                // Facultad
                $sheet->mergeCells('A2:D2');
                $sheet->setCellValue('A2', $padron->facultad->nombre ?? '');

                // Claustro
                $sheet->mergeCells('A3:D3');
                $sheet->setCellValue('A3', 'CLAUSTRO ' . ($padron->claustro->nombre ?? ''));

                // Sede
                $sheet->mergeCells('A4:D4');
                $sheet->setCellValue('A4', 'SEDE ' . ($padron->sede->nombre ?? ''));

                // Estilos básicos
                $sheet->getStyle('A1:A4')->getFont()->setBold(true);
                $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal('center');

                // Ajustar ancho columnas
                foreach (range('A','D') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }

    protected function claveOrden(string $texto): string
    {
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = str_replace('Ñ', 'NZ', $texto);
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        return $texto;
    }
}


