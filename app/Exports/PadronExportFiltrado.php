<?php

namespace App\Exports;

use App\Models\Padron;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class PadronExportFiltrado implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $anio = $this->filters['anio'];

        $query = DB::table('inscripciones as i')
            ->join('personas as p', 'p.id', '=', 'i.id_persona')
            ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
            ->whereNull('i.deleted_at')
            ->where('pad.anio', $anio);

        // filtros dinámicos
        if (!empty($this->filters['id_facultad'])) {
            $query->where('pad.id_facultad', $this->filters['id_facultad']);
        }

        if (!empty($this->filters['id_claustro'])) {
            $query->where('pad.id_claustro', $this->filters['id_claustro']);
        }

        if (!empty($this->filters['id_sede'])) {
            $query->where('pad.id_sede', $this->filters['id_sede']);
        }

        return $query
            ->orderByRaw("
                TRANSLATE(UPPER(p.apellido), 'ÁÉÍÓÚÑ', 'AEIOUN'),
                TRANSLATE(UPPER(p.nombre), 'ÁÉÍÓÚÑ', 'AEIOUN')
            ")
            ->selectRaw("
                TRIM(p.apellido || ', ' || p.nombre) as apellido_nombre,
                p.dni as dni,
                i.legajo as legajo
            ")
            ->get();
    }

    public function headings(): array
    {
        return [
            'Apellido y Nombre',
            'DNI',
            'Legajo',
        ];
    }
}
