<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sede;
use App\Models\Facultad;

class SedeController extends Controller
{
    public function index()
    {
        //Retorna todas las sedes en JSON
        return response()->json(Sede::with('facultad')->get(), 200);//con la facultad relacionada
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
