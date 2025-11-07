<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inscripcion;

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
}
