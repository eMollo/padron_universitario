<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;

class PersonaController extends Controller
{
    // GET /api/personas
    public function index()
    {
        return response()->json(Persona::all(), 200);
    }

    // GET /api/personas/{id}
    public function show($id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        return response()->json($persona, 200);
    }

    // POST /api/personas
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'dni' => 'required|string|unique:personas,dni',
        ]);

        $persona = Persona::create($validated);

        return response()->json($persona, 201);
    }

    // PUT/PATCH /api/personas/{id}
    public function update(Request $request, $id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'dni' => 'sometimes|string|unique:personas,dni,' . $persona->id_persona . ',id_persona',
        ]);

        $persona->update($validated);

        return response()->json($persona, 200);
    }

    // DELETE /api/personas/{id}
    public function destroy($id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        $persona->delete();

        return response()->json(['message' => 'Persona eliminada'], 200);
    }

    public function indexView()
    {
        $personas = Persona::all();
        return view('personas.index', compact('personas'));
    }
}