<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Lista;

class ProclamadosController extends Controller
{
    /**
     * Devuelve todas las listas oficializadas de un año+tipo
     * con sus postulantes y bancas asignadas.
     */
    public function porTipo(Request $request): JsonResponse
    {
        $request->validate([
            'anio' => 'required|integer',
            'tipo' => 'required|in:superior,directivo,decano,rector',
        ]);

        $listas = Lista::with([
            'facultad',
            'claustro',
            'postulantes' => fn($q) => $q->orderBy('tipo')->orderBy('orden'),
            'postulantes.persona',
        ])
        ->where('anio', $request->anio)
        ->where('tipo', $request->tipo)
        ->where('estado_lista', 'oficializada')
        ->orderBy('numero')
        ->get();

        return response()->json($listas);
    }

    /**
     * Guarda las bancas obtenidas por una lista
     * Los proclamados se derivan del orden: los primeros N titulares
     * y primeros N suplentes son los proclamados naturales.
     * Para decano/rector siempre es máximo 1 banca
     */
    public function asignarBancas(Request $request, $id): JsonResponse
    {
        $lista = Lista::find($id);

        if (!$lista) {
            return response()->json(['message' => 'Lista no encontrada'], 404);
        }

        if ($lista->estado_lista !== 'oficializada') {
            return response()->json([
                'message' => 'Solo se pueden asignar bancas a listas oficializadas',
            ], 422);
        }

        $request->validate([
            'bancas' => 'required|integer|min:0',
        ]);

        $bancas = (int) $request->bancas;

        //Decano/rector: máximo 1 banca
        if (in_array($lista->tipo, ['decano', 'rector'])) {
            $bancas = min($bancas, 1);
        }

        $lista->bancas = $bancas;
        $lista->save();

        return response()->json([
            'message' => 'Bancas guardadas correctamente',
            'bancas' => $bancas,
            'lista_id' => $lista->id,
        ]);
    }

    /**
     * Cambia el estado de una lista
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        $lista = Lista::find($id);

        if (!$lista) {
            return response()->json(['message' => 'Lista no encontrada'], 404);
        }

        $request->validate([
            'estado_lista' => 'required|in:cargada,avales_faltantes,oficializada',
        ]);

        $lista->estado_lista = $request->estado_lista;
        $lista->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'estado_lista' => $lista->estado_lista,
        ]);
    }
}