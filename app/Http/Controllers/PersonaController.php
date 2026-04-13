<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;
use App\Services\PersonaBuscarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Support\DniNormalizer;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
    // GET /api/personas
    public function index()
    {
        return response()->json(Persona::paginate(20), 200);
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

        $dni_norm = null;

        if ($request->has('dni')) {
            $dni_norm = DniNormalizer::normalizar($request->dni);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'dni' => [
                'sometimes',
                'string',
                Rule::unique('personas', 'dni_normalizado')
                    ->ignore($persona->id)
            ],
        ]);

        if ($dni_norm !== null) {
            $validated['dni_normalizado'] = $dni_norm;
        }

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

    public function buscar(Request $request)
    {
        $request->validate([
            'dni' => 'nullable|numeric',
            'apellido' => 'nullable|string',
            'nombre' => 'nullable|string',
            'anio' => 'nullable|integer',
            'id_facultad' => 'nullable|integer',
            'id_claustro' => 'nullable|integer',
            'order_by' => 'nullable|string',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $resultado = PersonaBuscarService::ejecutar($request);

        if (empty($resultado['resultado']) || count($resultado['resultado']) === 0) {
            return response()->json([
                'mensaje' => 'No se encontraron personas'
            ], 404);
        }

        return response()->json($resultado);
    }


}