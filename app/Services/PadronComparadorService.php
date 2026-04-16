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
                'claustros_comparados' => [
                    $filters['id_claustro_1'] ?? null,
                    $filters['id_claustro_2'] ?? null
                ]
            ],
            'DUPLICADOS_EXACTOS' => $duplicadosExactos,
            'POSIBLES_DUPLICADOS' => $posiblesDuplicados,
        ];
    }

     private function buscarDuplicadosPorDni(array $filters): array
    {
        $anio = $filters['anio'];
        $mode = $filters['mode'] ?? 'global';

        $sub = DB::table('personas as p')
            ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
            ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
            ->where('pad.anio', $anio)
            ->whereNull('i.deleted_at');

        $this->aplicarMode($sub, $mode, $filters);

        $sub->select(
            'p.dni_normalizado',
            DB::raw('COUNT(i.id) as cantidad')
        );

        if ($mode === 'entre_claustros') {
            $c1 = (int)($filters['id_claustro_1'] ?? 0);
            $c2 = (int)($filters['id_claustro_2'] ?? 0);

            $sub->havingRaw("SUM(CASE WHEN pad.id_claustro = $c1 THEN 1 ELSE 0 END) > 0");
            $sub->havingRaw("SUM(CASE WHEN pad.id_claustro = $c2 THEN 1 ELSE 0 END) > 0");
        }

        $sub->groupBy('p.dni_normalizado')
            ->havingRaw('COUNT(i.id) > 1');

        $dniDuplicados = $sub->pluck('dni_normalizado');

        if ($dniDuplicados->isEmpty()) {
            return [];
        }

        $query = DB::table('personas as p')
            ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
            ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
            ->join('facultad as f', 'f.id', '=', 'pad.id_facultad')
            ->join('claustros as c', 'c.id', '=', 'pad.id_claustro')
            ->join('sede as s', 's.id', '=', 'pad.id_sede')
            ->where('pad.anio', $anio)
            ->whereIn('p.dni_normalizado', $dniDuplicados)
            ->whereNull('i.deleted_at');

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
                's.nombre as sede', 
                'pad.anio'
            )
            ->orderBy('p.dni_normalizado')
            ->get()
            ->groupBy('dni_normalizado')
            ->values()
            ->toArray();
    }

    private function buscarPosiblesDuplicados(array $filters): array
    {
        $anio = $filters['anio'];
        $mode = $filters['mode'] ?? 'global';

        $base = DB::table('personas as p')
            ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
            ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
            ->where('pad.anio', $anio)
            ->whereNull('i.deleted_at');

        $this->aplicarMode($base, $mode, $filters);

        $sub = clone $base;

        $sub->selectRaw('
            LOWER(TRIM(p.apellido)) as apellido_norm,
            LOWER(TRIM(p.nombre)) as nombre_norm
        ')
        ->groupByRaw('LOWER(TRIM(p.apellido)), LOWER(TRIM(p.nombre))')
        ->havingRaw('COUNT(DISTINCT p.id) > 1')
        ->havingRaw('COUNT(DISTINCT p.dni_normalizado) > 1');

        if ($mode === 'entre_claustros') {
            $c1 = (int)($filters['id_claustro_1'] ?? 0);
            $c2 = (int)($filters['id_claustro_2'] ?? 0);

            $sub->havingRaw("SUM(CASE WHEN pad.id_claustro = $c1 THEN 1 ELSE 0 END) > 0");
            $sub->havingRaw("SUM(CASE WHEN pad.id_claustro = $c2 THEN 1 ELSE 0 END) > 0");
        }

        $query = DB::table('personas as p')
            ->join('inscripciones as i', 'i.id_persona', '=', 'p.id')
            ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
            ->join('facultad as f', 'f.id', '=', 'pad.id_facultad')
            ->join('claustros as c', 'c.id', '=', 'pad.id_claustro')
            ->join('sede as s', 's.id', '=', 'pad.id_sede') 
            ->joinSub($sub, 'dup', function ($join) {
                $join->on(DB::raw('LOWER(TRIM(p.apellido))'), '=', 'dup.apellido_norm')
                     ->on(DB::raw('LOWER(TRIM(p.nombre))'), '=', 'dup.nombre_norm');
            })
            ->where('pad.anio', $anio)
            ->whereNull('i.deleted_at');

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
                s.nombre as sede,
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

            case 'entre_claustros': // NUEVO
                if (!empty($filters['id_claustro_1']) && !empty($filters['id_claustro_2'])) {
                    $query->whereIn('pad.id_claustro', [
                        $filters['id_claustro_1'],
                        $filters['id_claustro_2']
                    ]);
                }
                break;

            case 'facultad_vs_resto':
            case 'global':
            default:
                break;
        }
    }
}
