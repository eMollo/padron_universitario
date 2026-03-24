<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BajasExport implements FromCollection, WithHeadings
{
    protected ?int $anio;

    public function __construct(?int $anio)
    {
        $this->anio = $anio;
    }

    public function collection(): Collection
    {
        $query = DB::table('inscripciones as i')
            ->join('personas as p', 'p.id', '=', 'i.id_persona')
            ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
            ->join('facultad as f', 'f.id', '=', 'pad.id_facultad')
            ->join('claustros as c', 'c.id', '=', 'pad.id_claustro')
            ->leftJoin('users as u', 'u.id', '=', 'i.baja_realizada_por')
            ->whereNotNull('i.deleted_at');

        if ($this->anio) {
            $query->where('pad.anio', $this->anio);
        }

        return $query
            ->orderByDesc('i.deleted_at')
            ->select([
                DB::raw("TRIM(p.apellido || ', ' || p.nombre) as apellido_nombre"),
                'p.dni',
                'f.sigla as facultad',
                'c.nombre as claustro',
                'i.motivo_baja',
                'u.name as usuario_baja',
                'i.deleted_at'
            ])
            ->get();
    }

    public function headings(): array
    {
        return [
            'Apellido y Nombre',
            'DNI',
            'Facultad',
            'Claustro',
            'Motivo de baja',
            'Usuario que dio de baja',
            'Fecha de baja',
        ];
    }
}