<?php

namespace App\Http\Controllers;

use App\Models\Lista;
use App\Services\Listas\AvalValidationService;
use App\Imports\AvalesImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class AvalController extends Controller
{
    public function importar(
        Request $request,
        int $idLista,
        AvalValidationService $validationService
    ): JsonResponse {

        $request->validate([
            'archivo' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        $lista = Lista::find($idLista);

        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        if (!in_array($lista->tipo, ['superior', 'directivo', 'decano', 'rector'])) {
            return response()->json([
                'error' => 'Este tipo de lista no admite avales',
            ], 422);
        }

        try {
            $import = new AvalesImport($lista, $validationService);
            Excel::import(
                $import,
                $request->file('archivo')
            );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al importar avales',
                'details' => $e->getMessage(),
            ], 500);
        }

        $lista->refresh();

        return response()->json([
            'message' => 'Archivo de avales procesado correctamente',
            'estado_lista' => $lista->estado_lista,
            'resumen' => $import->resultado,
        ]);
    }
}


