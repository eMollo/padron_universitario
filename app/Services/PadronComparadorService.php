<?php

namespace App\Services;

use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use InvalidArgumentException;

class PadronComparadorService
{
    public function comparar(array $filters): array
    {
        $anio = $filters['anio'] ?? null;

        if (!$anio) {
            throw new InvalidArgumentException('El año es obligatorio');
        }

        $mode = $filters['mode'] ?? 'global';

        $duplicadosExactos = $this->buscarDuplicadosPorDni($filters);
        $posiblesDuplicados = $this->buscarPosiblesDuplicados($filters);

        return [
            'meta' => [
                'anio' => $anio,
                'mode' => $mode,
                'total_exactos' => count($duplicadosExactos),
                'total_posibles' => count($posiblesDuplicados),
            ],
            'DUPLICADOS_EXACTOS' => $duplicadosExactos,
            'POSIBLES_DUPLICADOS' => $posiblesDuplicados,
        ];
    }

    // DUPLICADOS EXACTOS (por DNI normalizado)

    private function buscarDuplicadosPorDni(array $filters): array
{
    $anio = $filters['anio'];
    $mode = $filters['mode'] ?? 'global';
    $facultadId = $filters['id_facultad'] ?? null;

    // SUBQUERY: detectar DNIs repetidos

    $sub = DB::table('personas as p')
        ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
        ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
        ->where('pad.anio', $anio)
        ->whereNull('i.deleted_at'); // solo inscripciones activas

    $this->aplicarMode($sub, $mode, $filters);

    $sub->select(
        'p.dni_normalizado',
        DB::raw('COUNT(i.id) as cantidad'),
        DB::raw('COUNT(DISTINCT pad.id_facultad) as facultades_distintas')
    );

    // SOLO si corresponde al mode
    if ($mode === 'facultad_vs_resto' && $facultadId) {
        $sub->addSelect(
            DB::raw(
                'SUM(CASE WHEN pad.id_facultad = ' . (int)$facultadId . ' THEN 1 ELSE 0 END) as en_facultad'
            )
        );
    }

    $sub->groupBy('p.dni_normalizado')
        ->havingRaw('COUNT(i.id) > 1');

    if ($mode === 'facultad_vs_resto' && $facultadId) {
        $sub->havingRaw('COUNT(DISTINCT pad.id_facultad) > 1')
            ->havingRaw(
                'SUM(CASE WHEN pad.id_facultad = ' . (int)$facultadId . ' THEN 1 ELSE 0 END) > 0'  
            );
    }


    $dniDuplicados = $sub->pluck('dni_normalizado');

    if ($dniDuplicados->isEmpty()) {
        return [];
    }

    // DETALLE

    $query = DB::table('personas as p')
        ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
        ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
        ->join('facultad as f', 'f.id', '=', 'pad.id_facultad')
        ->join('claustros as c', 'c.id', '=', 'pad.id_claustro')
        ->where('pad.anio', $anio)
        ->whereIn('p.dni_normalizado', $dniDuplicados)
        ->whereNull('i.deleted_at'); // solo inscripciones activas

    $this->aplicarMode($query, $mode, $filters);

    return $query
        ->select(
            'p.id as id_persona',
            'p.dni_normalizado',
            'p.apellido',
            'p.nombre',
            'i.id as inscripcion_id',
            'f.sigla as facultad',
            'c.nombre as claustro',
            'pad.anio'
        )
        ->orderBy('p.dni_normalizado')
        ->get()
        ->groupBy('dni_normalizado')
        ->values()
        ->toArray();
}


    // POSIBLES DUPLICADOS (apellido + nombre)

    private function buscarPosiblesDuplicados(array $filters): array
{
    $anio = $filters['anio'];
    $mode = $filters['mode'] ?? 'global';
    $facultadId = $filters['id_facultad'] ?? null;

    $base = DB::table('personas as p')
        ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
        ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
        ->where('pad.anio', $anio)
        ->whereNull('i.deleted_at'); // solo inscripciones activas

    $this->aplicarMode($base, $mode, $filters);

    // SUBQUERY SQL PURA
    $sub = clone $base;

    $sub->selectRaw('
        LOWER(TRIM(p.apellido)) as apellido_norm,
        LOWER(TRIM(p.nombre)) as nombre_norm
    ')
    ->groupByRaw('LOWER(TRIM(p.apellido)), LOWER(TRIM(p.nombre))')
    ->havingRaw('COUNT(DISTINCT p.id) > 1')
    ->havingRaw('COUNT(DISTINCT p.dni_normalizado) > 1');

    if ($mode === 'facultad_vs_resto' && $facultadId) {
        $sub->havingRaw('COUNT(DISTINCT pad.id_facultad) > 1')
            ->havingRaw(
                'SUM(CASE WHEN pad.id_facultad = ' . (int)$facultadId . ' THEN 1 ELSE 0 END) > 0'
            );
    }

    // JOIN directo contra subquery
    $query = DB::table('personas as p')
        ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
        ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
        ->join('facultad as f', 'f.id', '=', 'pad.id_facultad')
        ->join('claustros as c', 'c.id', '=', 'pad.id_claustro')
        ->joinSub($sub, 'dup', function ($join) {
            $join->on(DB::raw('LOWER(TRIM(p.apellido))'), '=', 'dup.apellido_norm')
                 ->on(DB::raw('LOWER(TRIM(p.nombre))'), '=', 'dup.nombre_norm');
        })
        ->where('pad.anio', $anio)
        ->whereNull('i.deleted_at'); // solo inscripciones activas

    $this->aplicarMode($query, $mode, $filters);

    return $query
        ->selectRaw('
            p.id as id_persona,
            p.dni,
            p.apellido,
            p.nombre,
            i.id as inscripcion_id,
            f.sigla as facultad,
            c.nombre as claustro,
            pad.anio,
            LOWER(TRIM(p.apellido)) as apellido_norm,
            LOWER(TRIM(p.nombre)) as nombre_norm
        ')
        ->orderBy('apellido_norm')
        ->get()
        ->groupBy(fn($row) => $row->apellido_norm . "|" . $row->nombre_norm)
        ->values()
        ->toArray();
}


    // MODE

    private function aplicarMode($query, string $mode, array $filters): void
    {
        switch ($mode) {

            case 'mismo_claustro_global':
                if (!empty($filters['id_claustro'])) {
                    $query->where('pad.id_claustro', $filters['id_claustro']);
                }
                break;

            case 'misma_facultad_entre_claustros':
                if (!empty($filters['id_facultad'])) {
                    $query->where('pad.id_facultad', $filters['id_facultad']);
                }
                break;

            case 'facultad_vs_resto':
            case 'global':
            default:
                // sin filtro directo (la magia está en whereExists)
                break;
        }
    }
}
