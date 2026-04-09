<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PadronResumenService;

class PadronMetricasController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->input('anio');

        // TOTAL GENERAL
        $totalGeneral = DB::table('padron_resumen')
            ->when($anio, fn($q) => $q->where('anio', $anio))
            ->sum('total');

        // POR FACULTAD
        $porFacultad = DB::table('padron_resumen as pr')
            ->join('facultad as f', 'f.id', '=', 'pr.id_facultad')
            ->select('f.nombre', DB::raw('SUM(pr.total) as total'))
            ->when($anio, fn($q) => $q->where('pr.anio', $anio))
            ->groupBy('f.nombre')
            ->orderByDesc('total')
            ->get();

        // POR CLAUSTRO
        $porClaustro = DB::table('padron_resumen as pr')
            ->join('claustros as c', 'c.id', '=', 'pr.id_claustro')
            ->select('c.nombre', DB::raw('SUM(pr.total) as total'))
            ->when($anio, fn($q) => $q->where('pr.anio', $anio))
            ->groupBy('c.nombre')
            ->orderByDesc('total')
            ->get();

        // POR FACULTAD + CLAUSTRO
        $porFacultadClaustro = DB::table('padron_resumen as pr')
            ->join('facultad as f', 'f.id', '=', 'pr.id_facultad')
            ->join('claustros as c', 'c.id', '=', 'pr.id_claustro')
            ->select(
                'f.nombre as facultad',
                'c.nombre as claustro',
                DB::raw('SUM(pr.total) as total')
            )
            ->when($anio, fn($q) => $q->where('pr.anio', $anio))
            ->groupBy('f.nombre', 'c.nombre')
            ->orderBy('f.nombre')
            ->orderBy('c.nombre')
            ->get();

        return response()->json([
            'total' => $totalGeneral,
            'por_facultad' => $porFacultad,
            'por_claustro' => $porClaustro,
            'por_facultad_claustro' => $porFacultadClaustro // 👈 NUEVO
        ]);
    }


    public function recalcular(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer'
        ]);

        $anio = $request->input('anio');
        //$anio = $request->input('anio', now()->year);

        app(PadronResumenService::class)->recalcular($anio);

        return response()->json([
            'message' => 'Métricas recalculadas correctamente'
        ]);
    }
}
