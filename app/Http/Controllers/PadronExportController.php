<?php

namespace App\Http\Controllers;

use App\Models\Padron;
use App\Exports\PadronExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Exports\PadronExportFiltrado;

class PadronExportController extends Controller
{
    public function export($id)
    {
        $padron = Padron::findOrFail($id);

        return Excel::download(
            new PadronExport($padron),
            "padron_{$padron->id}.xlsx"
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
}
