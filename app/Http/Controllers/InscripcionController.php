<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\Auth;

class InscripcionController extends Controller
{
    //

    public function index()
    {
        return response()->json(
            Inscripcion::with(['persona', 'padron'])->get(), 200
        );
    }

    public function show($id)
    {
        $inscripcion = Inscripcion::with(['persona', 'padron'])->find($id);

        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripcion no encontrada'], 404);
        }

        return response()->json($inscripcion, 200);
    }

    public function destroy($id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripcion no encontrada'], 404);
        }

        $inscripcion->delete();

        return response()->json(['message' => 'Inscripcion eliminada correctamente.'], 200);
    }

    public function darBaja(Request $request, $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:1000'
        ]);

        $inscripcion = Inscripcion::whereNull('deleted_at')->findOrFail($id);

        // Seguridad adicional: evitar doble baja
        if ($inscripcion->deleted_at) {
            return response()->json([
                'mensaje' => 'La inscripción ya está dada de baja'
            ], 400);
        }

        $inscripcion->motivo_baja = $request->motivo;
        $inscripcion->baja_realizada_por = Auth::id();
        $inscripcion->save();

        $inscripcion->delete(); // Soft delete

        return response()->json([
            'mensaje' => 'Inscripción dada de baja correctamente',
            'inscripcion_id' => $inscripcion->id
        ]);
    }

    public function restaurar($id)
    {
        $inscripcion = Inscripcion::withTrashed()->findOrFail($id);

        if (!$inscripcion->trashed()) {
            return response()->json([
                'mensaje' => 'La inscripción no está dada de baja'
            ], 400);
        }

        $inscripcion->restore();

        return response()->json([
            'mensaje' => 'Inscripción restaurada correctamente',
            'inscripcion_id' => $inscripcion->id
        ]);
    }

}
