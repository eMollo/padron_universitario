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
    protected ListaValidationService $validationService;
    protected ListaCreationService $creationService;

    public function __construct(
        ListaValidationService $validationService,
        ListaCreationService $creationService
    ){
        $this->validationService = $validationService;
        $this->creationService = $creationService;
    }

    //Listar todas las listas
    public function index(): JsonResponse
    {
        $listas = Lista::with(['apoderado', 'postulantes.persona', 'facultad', 'claustro'])->get();
        return response()->json($listas, 200);
    }

    //Ver una lista
    public function show($id): JsonResponse
    {
        $lista = Lista::with(['apoderado', 'postulantes.persona', 'facultad', 'claustro'])->find($id);
        if (!$lista) {
            return response()->json(['message' => 'Lista no encontrada'], 404);
        }
        return response()->json($lista, 200);
    }

    //Crear lista (usa los services)

    public function store(Request $request): JsonResponse
{
    //dd($request->postulantes);
    $request->validate([
        'anio' => 'required|integer',
        'tipo' => ['required', 'string', Rule::in(['superior','directivo','decano','rector'])],
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

    
    $payload = [
        'anio'        => $request->anio,
        'tipo'        => $request->tipo,
        'id_claustro' => $request->id_claustro,
        'id_facultad' => $request->id_facultad,
        'postulantes' => $request->input('postulantes', []),
        'apoderado'   => $request->input('apoderado', []),
    ];

    $validationResult = $this->validationService->validateAll($payload);

    if (!$validationResult['ok']) {
        return response()->json([
            'error' => 'Validación de lista fallida',
            'details' => $validationResult['errors']
        ], 422);
    }

    $validPostulantes = $validationResult['postulantes'] ?? [];

    // Crear o actualizar apoderado
    $ap = $payload['apoderado'];
    $apoderado = Persona::firstOrNew(['dni' => $ap['dni']]);
    $apoderado->nombre = $ap['nombre'];
    $apoderado->apellido = $ap['apellido'];
    $apoderado->telefono = $ap['telefono'] ?? null;
    $apoderado->email = $ap['email'] ?? null;
    $apoderado->save();

    $data = [
        'anio'         => $payload['anio'],
        'tipo'         => $payload['tipo'],
        'nombre'       => $request->nombre,
        'sigla'        => $request->sigla,
        'id_facultad'  => $payload['id_facultad'],
        'id_claustro'  => $payload['id_claustro'],
        'id_apoderado' => $apoderado->id,
        'postulantes'  => $validPostulantes,
    ];

    $createResult = $this->creationService->create($data);

    if (!$createResult['ok']) {
        return response()->json([
            'error' => 'No se pudo crear la lista',
            'details' => $createResult['error']
        ], 500);
    }

    $lista = $createResult['lista']->load(['apoderado', 'postulantes.persona', 'facultad', 'claustro']);

    return response()->json([
        'message' => 'Lista creada exitosamente',
        'lista' => $lista
    ], 201);
}

}
