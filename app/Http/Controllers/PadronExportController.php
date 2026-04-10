<?php

namespace App\Http\Controllers;

use App\Models\Padron;
use App\Exports\PadronExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Exports\PadronExportFiltrado;
use App\Exports\ComparadorExport;
use App\Services\PadronComparadorService;
use App\Exports\BajasExport;
use Illuminate\Support\Str;

class PadronExportController extends Controller
{
    /*public function export($id)
    {
        $padron = Padron::findOrFail($id);

        return Excel::download(
            new PadronExport($padron),
            "padron_{$padron->id}.xlsx"
        );
    }*/


public function export($id)
    {
        $padron = Padron::with(['facultad', 'claustro', 'sede'])->findOrFail($id);

        // Función helper para limpiar texto
        $limpiar = function ($texto) {
            return Str::of($texto ?? '')
                ->ascii()                // quita tildes
                ->replace(' ', '_')      // espacios → _
                ->replace('/', '_')      // por seguridad
                ->replace('\\', '_')
                ->toString();
        };

        $facultad = $limpiar($padron->facultad->sigla ?? 'SinFacultad');
        $claustro = $limpiar($padron->claustro->nombre ?? 'SinClaustro');
        $sede     = $limpiar($padron->sede->nombre ?? 'SinSede');
        $anio     = $padron->anio ?? 'SinAnio';

        $filename = "{$facultad}_{$claustro}_{$sede}_{$anio}.xlsx";

        return Excel::download(
            new PadronExport($padron),
            $filename
        );
    }

    public function exportFiltrado(Request $request)
    {
        $filters = $request->validate([
            'anio' => 'required|integer',
            'id_facultad' => 'nullable|integer',
            'id_claustro' => 'nullable|integer',
            'id_sede' => 'nullable|integer',
        ]);

        $filename = 'padron_filtrado_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new PadronExportFiltrado($filters),
            $filename
        );
    }

    public function exportComparador(Request $request, PadronComparadorService $service)
    {
        $filters = $request->validate([
            'anio' => 'required|integer',
            'mode' => 'required|string',
            'id_facultad' => 'nullable|integer',
            'id_claustro' => 'nullable|integer',
            'id_claustro_1' => 'nullable|integer',
    'id_claustro_2' => 'nullable|integer',
        ]);

        $resultado = $service->comparar($filters);

        return Excel::download(
            new ComparadorExport(
                $resultado['DUPLICADOS_EXACTOS'],
                $resultado['POSIBLES_DUPLICADOS']
            ),
            'Duplicados.xlsx'
        );
    }

    public function exportBajas(Request $request)
    {
        $anio = $request->input('anio');

        $filename = 'bajas_' . ($anio ?? 'todas') . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new BajasExport($anio),
            $filename
        );
    }
}
