<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sede;
use App\Models\Facultad;

class SedeController extends Controller
{

    // LISTAR SEDES (CON USO)

    public function index()
    {
        $sedes = Sede::with('facultad')
            ->withCount('padrones') // CUENTA USO
            ->orderBy('id_facultad')
            ->orderBy('nombre')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                    'facultad' => [
                        'nombre' => $s->facultad?->nombre,
                        'sigla' => $s->facultad?->sigla,
                    ],
                    'padrones_count' => $s->padrones_count,
                    'usada' => $s->padrones_count > 0
                ];
            });

        return response()->json($sedes);
    }

    // ELIMINAR SEDE (SEGURO)

    public function destroy($id)
    {
        $sede = Sede::withCount('padrones')->find($id);

        if (!$sede) {
            return response()->json(['error' => 'Sede no encontrada'], 404);
        }

        
        if ($sede->padrones_count > 0) {
            return response()->json([
                'error' => 'No se puede eliminar la sede porque está en uso',
                'detalle' => "Está asociada a {$sede->padrones_count} padrón(es)"
            ], 422);
        }

        $sede->delete();

        return response()->json([
            'message' => 'Sede eliminada correctamente'
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'id_facultad' => 'required|exists:facultad,id',
            'nombre' => 'required|string|max:255'
        ]);

        try {

            $facultad = Facultad::findOrFail($request->id_facultad);

            $nombreFinal = trim($request->nombre) . ' (' . $facultad->nombre . ')';

            // evitar duplicados
            $existe = Sede::where('nombre', $nombreFinal)->exists();

            if ($existe) {
                return response()->json([
                    'error' => 'La sede ya existe'
                ], 422);
            }

            $sede = Sede::create([
                'nombre' => $nombreFinal,
                'id_facultad' => $request->id_facultad
            ]);

            return response()->json([
                'mensaje' => 'Sede creada correctamente',
                'sede' => $sede
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'error' => 'Error al crear sede',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function porFacultad($id_facultad)
    {
        return Sede::where('id_facultad', $id_facultad)
            ->orderBy('nombre')
            ->get();
    }
}
