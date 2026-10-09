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

    public function __construct(ListaCreationService $creationService){
        $this->creationService = $creationService;
    }

    //Listar todas las listas

    public function index(Request $request): JsonResponse {
        $query = Lista::with([
            'apoderado',
            'postulantes.persona',
            'facultad',
            'claustro'
        ])
        ->orderByDesc('anio')
        ->orderBy('tipo')
        ->orderBy('numero');

        if ($request->filled('anio')) {
            $query->where('anio', (int) $request->anio);
        }

        return response()->json($query->get());
    }

    //Ver una lista

    public function show($id): JsonResponse {
        $lista = Lista::with([
            'apoderado',
            'postulantes' => fn($q) => $q->orderBy('tipo')->orderBy('orden'),
            'postulantes.persona',
            'facultad',
            'claustro',
            'avales',
            'avales.persona',
        ])->find($id);

        if (!$lista) {
            return response()->json([
                'message' => 'Lista no encontrada'
            ], 404);
        }

        // Para Consejo Superior: enriquecer cada postulante con la facultad
        // de su inscripción activa en el mismo año y claustro de la lista.
        if ($lista->tipo === 'superior' && $lista->id_claustro) {
            $idsPersonas = $lista->postulantes->pluck('id_persona');

            // Una sola query: inscripciones activas del año+claustro para estas personas
            $facultadesPorPersona = \App\Models\Inscripcion::query()
                ->join('padrones', 'inscripciones.id_padron', '=', 'padrones.id')
                ->join('facultad', 'padrones.id_facultad', '=', 'facultad.id')
                ->whereIn('inscripciones.id_persona', $idsPersonas)
                ->where('padrones.anio', $lista->anio)
                ->where('padrones.id_claustro', $lista->id_claustro)
                ->whereNull('inscripciones.deleted_at')
                ->select(
                    'inscripciones.id_persona',
                    'facultad.nombre as facultad_nombre'
                )
                ->get()
                ->keyBy('id_persona');

            $lista->postulantes->each(function ($postulante) use ($facultadesPorPersona) {
                $postulante->facultad_nombre =
                    $facultadesPorPersona[$postulante->id_persona]->facultad_nombre ?? null;
            });
        }

        return response()->json($lista);
    }

    //Crear una lista

    public function store(Request $request): JsonResponse {
        $request->validate([
            'anio' => 'required|integer',
            'tipo' => ['required', 'string', Rule::in(['superior','directivo','decano','rector'])],
            'nombre' => 'required|string|max:90',
            'sigla' => 'nullable|string|max:13',

            'modo_carga' => ['nullable', 'string', Rule::in(['normal', 'historica'])],
            'numero' => 'nullable|integer|min:1',
            
            'id_claustro' => 'required_if:tipo,superior,directivo|nullable|exists:claustros,id',
            'id_facultad' => 'required_if:tipo,directivo,decano|nullable|exists:facultad,id',
            

            'apoderado' => 'required|array',
            'apoderado.dni' => 'required|string',
            'apoderado.nombre' => 'required|string',
            'apoderado.apellido' => 'required|string',
            'apoderado.telefono' => 'nullable|string',
            'apoderado.email' => 'nullable|email',

            'postulantes.titulares' => 'required|array',
            'postulantes.titulares.*.dni' => 'required|string',
            #'postulantes.titulares.*.legajo' => 'nullable|string',

            'postulantes.suplentes' => 'nullable|array',
            'postulantes.suplentes.*.dni' => 'required|string',
            #'postulantes.suplentes.*.legajo' => 'nullable|string',
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

    // Editar una lista (nombre, sigla, apoderado, postulantes)
    public function update(Request $request, $id): JsonResponse {
        $lista = Lista::with([
            'apoderado',
            'postulantes',
        ])->find($id);

        if (!$lista) {
            return response()->json(['message' => 'Lista no encontrada'], 404);
        }

        $request->validate([
            'nombre'   => 'required|string|max:90',
            'sigla'    => 'nullable|string|max:13',

            'apoderado'            => 'required|array',
            'apoderado.dni'        => 'required|string',
            'apoderado.nombre'     => 'required|string',
            'apoderado.apellido'   => 'required|string',
            'apoderado.telefono'   => 'nullable|string',
            'apoderado.email'      => 'nullable|email',

            'postulantes.titulares'       => 'required|array',
            'postulantes.titulares.*.dni' => 'required|string',
            'postulantes.suplentes'       => 'nullable|array',
            'postulantes.suplentes.*.dni' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // 1. Nombre y sigla
            $lista->nombre = $request->nombre;
            $lista->sigla  = $request->sigla;
            $lista->save();

            // 2. Apoderado: buscar o crear por DNI, luego actualizar datos
            $apoderadoData = $request->apoderado;
            $apoderado = Persona::firstOrNew([
                'dni_normalizado' => \App\Support\DniNormalizer::normalizar($apoderadoData['dni'])
            ]);
            $apoderado->nombre    = $apoderadoData['nombre'];
            $apoderado->apellido  = $apoderadoData['apellido'];
            $apoderado->dni       = $apoderadoData['dni'];
            $apoderado->telefono  = $apoderadoData['telefono'] ?? null;
            $apoderado->email     = $apoderadoData['email']    ?? null;
            $apoderado->save();

            $lista->id_apoderado = $apoderado->id;
            $lista->save();

            // 3. Postulantes: reemplazar titulares y suplentes
            // Usamos el mismo servicio de validación para verificar que
            // los DNIs pertenezcan al padrón habilitado.
            $validacion = app(\App\Services\Listas\ListaValidationService::class)
                ->validateAll([
                    'anio'             => $lista->anio,
                    'tipo'             => $lista->tipo,
                    'id_claustro'      => $lista->id_claustro,
                    'id_facultad'      => $lista->id_facultad,
                    'postulantes'      => $request->postulantes,
                    'id_lista_excluir' => $lista->id, // excluir esta lista del chequeo de conflictos
                ]);

            if (!$validacion['ok']) {
                DB::rollBack();
                return response()->json([
                    'error'   => 'Error en la validación de postulantes',
                    'details' => $validacion['errors'] ?? [],
                ], 422);
            }

            // Borrar postulantes actuales y reemplazar
            ListaPostulante::where('id_lista', $lista->id)->delete();

            foreach (['titulares', 'suplentes'] as $tipoPostulante) {
                $tipo_key = $tipoPostulante === 'titulares' ? 'titular' : 'suplente';
                foreach ($request->postulantes[$tipoPostulante] ?? [] as $index => $item) {
                    $persona = Persona::where(
                        'dni_normalizado',
                        \App\Support\DniNormalizer::normalizar($item['dni'])
                    )->first();

                    if ($persona) {
                        ListaPostulante::create([
                            'id_lista'  => $lista->id,
                            'id_persona' => $persona->id,
                            'tipo'      => $tipo_key,
                            'orden'     => $index + 1,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Lista actualizada correctamente',
                'lista'   => $lista->fresh(['apoderado', 'postulantes.persona', 'facultad', 'claustro']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la lista',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // Soft delete de una lista
    public function destroy(Request $request, $id): JsonResponse {
        $lista = Lista::find($id);

        if (!$lista) {
            return response()->json(['message' => 'Lista no encontrada'], 404);
        }

        $request->validate([
            'motivo_baja' => 'required|string|max:500',
        ]);

        $lista->motivo_baja  = $request->motivo_baja;
        $lista->eliminado_por = auth()->id();
        $lista->save();
        $lista->delete();

        return response()->json(['message' => 'Lista eliminada correctamente']);
    }

    // Listado de listas eliminadas (solo admin)
    public function indexEliminados(Request $request): JsonResponse {
        $query = Lista::onlyTrashed()
            ->with(['apoderado', 'facultad', 'claustro', 'eliminadoPor'])
            ->orderByDesc('deleted_at');

        if ($request->filled('anio')) {
            $query->where('anio', (int) $request->anio);
        }

        return response()->json($query->get());
    }

    // Detalle de una lista eliminada
    public function showEliminada($id): JsonResponse {
        $lista = Lista::onlyTrashed()
            ->with([
                'apoderado',
                'postulantes' => fn($q) => $q->orderBy('tipo')->orderBy('orden'),
                'postulantes.persona',
                'facultad',
                'claustro',
                'avales',
                'avales.persona',
                'eliminadoPor',
            ])
            ->find($id);

        if (!$lista) {
            return response()->json(['message' => 'Lista eliminada no encontrada'], 404);
        }

        return response()->json($lista);
    }

    //Para numeración en modo historico
    public function numerosDisponibles(Request $request) {
        $request->validate([
            'anio' => ['required', 'integer'],
            'tipo' => ['required', 'in:superior,directivo,decano,rector'],
            'id_claustro' => ['nullable', 'integer'],
        ]);

        $anio = $request->anio;
        $tipo = $request->tipo;
        $idClaustro = $request->id_claustro;

        $query = Lista::where('anio', $anio)
            ->where('tipo', $tipo);

        if (in_array($tipo, ['superior', 'directivo'])) {
            $query->where('id_claustro', $idClaustro);
        }

        $utilizados = $query
            ->whereNotNull('numero')
            ->pluck('numero')
            ->map(fn ($numero) => (int) $numero)
            ->toArray();

        $disponibles = [];

        for ($numero =1; $numero <= 50; $numero++) {
            if (!in_array($numero, $utilizados, true)) {
                $disponibles[] = $numero;
            }
        }

        return response()->json([
            'disponibles' => $disponibles,
        ]);
    }

}
