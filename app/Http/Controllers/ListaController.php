<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Lista;
use App\Models\ListaPostulante;
use App\Models\Persona;
use App\Models\Inscripcion;
use App\Models\Padron;
use Illuminate\Support\Facades\DB;

use App\Services\Listas\ListaCreationService;
use App\Services\Listas\ListaNumberService;
use App\Services\Listas\ListaValidationService;
use Illuminate\Http\JsonResponse;

class ListaController extends Controller
{
    protected ListaCreationService $creationService;

    public function __construct(ListaCreatcionService $creationService){
        $this->creationService = $creationService;
    }

    //Listar todas las listas

    public function index(): JsonResponse {
        $listas = Lista::with([
            'apoderado',
            'postulantes.persona',
            'facultad',
            'claustro'
        ])->get();

        return response()->json($listas);
    }

    //Ver una lista

    public function show($id): JsonResponse {
        $lista = Lista::with([
            'apoderado',
            'postulantes.persona',
            'facultad',
            'claustro'
        ])->find($id);

        if (!$lista) {
            return response()->json([
                'message' => 'Lista no encontrada'
            ], 404);
        }

        return response()->json($lista);
    }

    //Crear una lista

    public function store(Request $request): JsonResponse {
        $request->validate([
            'anio' => 'required|integer',
            'tipo' => ['required', 'strin', Rule::in(['superior','directivo','decano','rector'])],
            'nombre' => 'required|string|max:90',
            'sigla' => 'nullable|string|max:10',
            'id_claustro' => 'required_if:tipo,superior,directivo|nullable|exists:claustros,id',
            'id_facultad' => 'required_if:tipo,directivo,decano|nullable|exists:facultad,id',

            'apoderado' => 'required|array',
            'apoderado.dni' => 'required|string',
            'apoderado.nombre' => 'required|string',
            'apoderado.apellido' => 'required|string',

            'postulantes.titulares' => 'required|array',
            'postulantes.titulares.*.dni' => 'required|string',
            'postulantes.titulares.*.legajo' => 'nullable|string',

            'postulantes.suplentes' => 'nullable|array',
            'postulantes.suplentes.*.dni' => 'required|string',
            'postulantes.suplentes.*.legajo' => 'nullable|string',
        ]);

        $resultado = $this->creationService->create($request->all());

        if (!$resultado['ok']) {

            $status = $resultado['status'] ?? 500;

            return response()->json([
                'error' => $resultado['error'],
                'details' => $resultado['details'] ?? []
            ], $status);
        }

        return response()->json([
            'message' => 'Lista creada exitosamente',
            'lista' => $resultado['lista']
        ], 201);
    }

}
