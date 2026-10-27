<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Reunion;
use App\Models\TipoReunion;
use App\Models\AsistenciaMovimiento;
use App\Models\Lista;
use App\Models\ListaPostulante;
use App\Models\Persona;
use App\Models\OrdenPalabra;
use App\Models\Votacion;
use App\Models\Voto;

class ReunionController extends Controller
{
    // =====================================================
    // TIPOS DE REUNIÓN (comisiones)
    // =====================================================

    public function tiposReunion(): JsonResponse
    {
        return response()->json(TipoReunion::orderBy('orden')->get());
    }

    public function storeTipoReunion(Request $request): JsonResponse
    {
        $request->validate(['nombre' => 'required|string|max:100']);

        $ultimo = TipoReunion::max('orden') ?? 0;
        $tipo   = TipoReunion::create([
            'nombre' => $request->nombre,
            'orden'  => $ultimo + 1,
        ]);

        return response()->json($tipo, 201);
    }

    // =====================================================
    // REUNIONES
    // =====================================================

    public function index(Request $request): JsonResponse
    {
        $query = Reunion::with('tipoReunion')
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipo'            => 'required|in:sesion,comision,asamblea',
            'id_tipo_reunion' => 'nullable|exists:tipos_reunion,id',
            'nombre'          => 'required|string|max:150',
            'fecha'           => 'required|date',
            'hora_inicio'     => 'nullable|date_format:H:i',
        ]);

        if ($request->tipo === 'comision' && !$request->id_tipo_reunion) {
            return response()->json([
                'message' => 'Debe seleccionar el tipo de comisión'
            ], 422);
        }

        $reunion = Reunion::create([
            'tipo'            => $request->tipo,
            'id_tipo_reunion' => $request->tipo === 'comision' ? $request->id_tipo_reunion : null,
            'nombre'          => $request->nombre,
            'fecha'           => $request->fecha,
            'hora_inicio'     => $request->hora_inicio ?? now()->format('H:i'),
            'estado'          => 'abierta',
        ]);

        return response()->json($reunion->load('tipoReunion'), 201);
    }

    public function show($id): JsonResponse
    {
        $reunion = Reunion::with('tipoReunion')->find($id);
        if (!$reunion) {
            return response()->json(['message' => 'Reunión no encontrada'], 404);
        }
        return response()->json($reunion);
    }

    public function cerrar($id): JsonResponse
    {
        $reunion = Reunion::find($id);
        if (!$reunion) {
            return response()->json(['message' => 'Reunión no encontrada'], 404);
        }

        $reunion->estado    = 'cerrada';
        $reunion->hora_fin  = now()->format('H:i');
        $reunion->save();

        return response()->json(['message' => 'Reunión cerrada', 'hora_fin' => $reunion->hora_fin]);
    }

    // =====================================================
    // PARTICIPANTES — proclamados habilitados para la reunión
    // =====================================================

    public function participantes($id): JsonResponse
    {
        $reunion = Reunion::find($id);
        if (!$reunion) {
            return response()->json(['message' => 'Reunión no encontrada'], 404);
        }

        // Obtener último movimiento de cada persona en esta reunión
        $ultimosMovimientos = AsistenciaMovimiento::query()
            ->selectRaw('DISTINCT ON (id_persona) id_persona, tipo, created_at')
            ->where('id_reunion', $id)
            ->orderByRaw('id_persona, created_at DESC')
            ->get()
            ->keyBy('id_persona');

        // Obtener listas oficializadas con postulantes agrupadas por claustro
        // El anio lo inferimos del año de la reunión
        $anio = $reunion->fecha->year;

        $listas = Lista::with([
            'claustro',
            'facultad',
            'postulantes' => fn($q) => $q->orderBy('tipo')->orderBy('orden'),
            'postulantes.persona',
        ])
        ->whereIn('tipo', ['superior', 'decano'])
        ->where('anio', $anio)
        ->where('estado_lista', 'oficializada')
        ->whereNotNull('bancas')
        ->where('bancas', '>', 0)
        ->get();

        // Estructurar: claustro → listas → postulantes con estado actual
        $resultado = [];

        foreach ($listas as $lista) {
            $claustroNombre = $lista->tipo === 'decano'
                ? 'Decanos/as'
                : ($lista->claustro->nombre ?? 'Sin claustro');

            if (!isset($resultado[$claustroNombre])) {
                $resultado[$claustroNombre] = [];
            }

            $postulantes = $lista->postulantes->map(function ($p) use ($lista, $ultimosMovimientos) {
                $ultimo     = $ultimosMovimientos[$p->id_persona] ?? null;
                $estado     = $ultimo ? $ultimo->tipo : 'ausente';
                $proclamado = $p->orden <= $lista->bancas;

                return [
                    'id'            => $p->id_persona,
                    'orden'         => $p->orden,
                    'tipo_postulante' => $p->tipo, // titular / suplente
                    'proclamado'    => $proclamado,
                    'nombre'        => $p->persona->nombre   ?? '',
                    'apellido'      => $p->persona->apellido ?? '',
                    'dni'           => $p->persona->dni      ?? '',
                    'estado'        => $estado, // entrada / entrada_virtual / salida / ausente
                    'ultimo_movimiento_at' => $ultimo?->created_at,
                ];
            });

            $resultado[$claustroNombre][] = [
                'id_lista'  => $lista->id,
                'numero'    => $lista->numero,
                'nombre'    => $lista->nombre,
                'sigla'     => $lista->sigla,
                'bancas'    => $lista->bancas,
                'tipo'      => $lista->tipo,
                'postulantes' => $postulantes->values(),
            ];
        }

        // Para Asamblea: agregar personas extra registradas
        $personasExtra = [];
        if ($reunion->tipo === 'asamblea') {
            $idsYaIncluidos = collect($resultado)
                ->flatten(2)
                ->pluck('postulantes')
                ->flatten(1)
                ->pluck('id')
                ->unique();

            $personasExtra = AsistenciaMovimiento::query()
                ->with('persona')
                ->where('id_reunion', $id)
                ->whereNotIn('id_persona', $idsYaIncluidos)
                ->select('id_persona')
                ->distinct()
                ->get()
                ->map(function ($m) use ($ultimosMovimientos) {
                    $ultimo = $ultimosMovimientos[$m->id_persona] ?? null;
                    return [
                        'id'       => $m->id_persona,
                        'nombre'   => $m->persona->nombre   ?? '',
                        'apellido' => $m->persona->apellido ?? '',
                        'dni'      => $m->persona->dni      ?? '',
                        'estado'   => $ultimo ? $ultimo->tipo : 'ausente',
                        'extra'    => true,
                    ];
                });
        }

        return response()->json([
            'reunion'        => $reunion,
            'participantes'  => $resultado,
            'personas_extra' => $personasExtra,
            'presentes'      => $ultimosMovimientos
                ->filter(fn($m) => in_array($m->tipo, ['entrada', 'entrada_virtual']))
                ->count(),
        ]);
    }

    // =====================================================
    // MOVIMIENTOS DE ASISTENCIA
    // =====================================================

    public function registrarMovimiento(Request $request, $id): JsonResponse
    {
        $reunion = Reunion::find($id);
        if (!$reunion) {
            return response()->json(['message' => 'Reunión no encontrada'], 404);
        }
        if ($reunion->estado === 'cerrada') {
            return response()->json(['message' => 'La reunión está cerrada'], 422);
        }

        $request->validate([
            'id_persona' => 'required|exists:personas,id',
            'tipo'       => 'required|in:entrada,entrada_virtual,salida',
        ]);

        // Cooldown: evitar doble registro accidental en menos de 5 segundos
        $ultimoMovimiento = AsistenciaMovimiento::where('id_reunion', $id)
            ->where('id_persona', $request->id_persona)
            ->latest('created_at')
            ->first();

        if ($ultimoMovimiento && now()->diffInSeconds($ultimoMovimiento->created_at) < 5) {
            return response()->json(['message' => 'Movimiento demasiado reciente'], 429);
        }

        $movimiento = AsistenciaMovimiento::create([
            'id_reunion' => $id,
            'id_persona' => $request->id_persona,
            'tipo'       => $request->tipo,
        ]);

        // Contador actualizado
        $presentes = $reunion->contadorPresentes();

        return response()->json([
            'movimiento' => $movimiento,
            'presentes'  => $presentes,
        ], 201);
    }

    /**
     * Marca salida masiva de todos los presentes (fin de comisión).
     */
    public function salidaMasiva($id): JsonResponse
    {
        $reunion = Reunion::find($id);
        if (!$reunion) {
            return response()->json(['message' => 'Reunión no encontrada'], 404);
        }

        $presentes = AsistenciaMovimiento::query()
            ->selectRaw('DISTINCT ON (id_persona) id_persona, tipo')
            ->where('id_reunion', $id)
            ->orderByRaw('id_persona, created_at DESC')
            ->get()
            ->filter(fn($m) => in_array($m->tipo, ['entrada', 'entrada_virtual']))
            ->pluck('id_persona');

        $now = now();
        foreach ($presentes as $idPersona) {
            AsistenciaMovimiento::create([
                'id_reunion' => $id,
                'id_persona' => $idPersona,
                'tipo'       => 'salida',
                'created_at' => $now,
            ]);
        }

        return response()->json([
            'message'  => 'Salida masiva registrada',
            'cantidad' => $presentes->count(),
        ]);
    }

    /**
     * Pasa los presentes de una comisión a otra (marca salida en origen, entrada en destino).
     */
    public function traspasarPresentes(Request $request): JsonResponse
    {
        $request->validate([
            'id_reunion_origen'  => 'required|exists:reuniones,id',
            'id_reunion_destino' => 'required|exists:reuniones,id|different:id_reunion_origen',
        ]);

        $origen  = Reunion::find($request->id_reunion_origen);
        $destino = Reunion::find($request->id_reunion_destino);

        if ($destino->estado === 'cerrada') {
            return response()->json(['message' => 'La reunión destino está cerrada'], 422);
        }

        // Obtener presentes en origen
        $presentes = AsistenciaMovimiento::query()
            ->selectRaw('DISTINCT ON (id_persona) id_persona, tipo')
            ->where('id_reunion', $origen->id)
            ->orderByRaw('id_persona, created_at DESC')
            ->get()
            ->filter(fn($m) => in_array($m->tipo, ['entrada', 'entrada_virtual']))
            ->pluck('id_persona');

        $now = now();
        DB::transaction(function () use ($presentes, $origen, $destino, $now) {
            foreach ($presentes as $idPersona) {
                // Salida en origen
                AsistenciaMovimiento::create([
                    'id_reunion' => $origen->id,
                    'id_persona' => $idPersona,
                    'tipo'       => 'salida',
                    'created_at' => $now,
                ]);
                // Entrada en destino
                AsistenciaMovimiento::create([
                    'id_reunion' => $destino->id,
                    'id_persona' => $idPersona,
                    'tipo'       => 'entrada',
                    'created_at' => $now,
                ]);
            }
        });

        return response()->json([
            'message'   => 'Presentes traspasados correctamente',
            'cantidad'  => $presentes->count(),
            'destino'   => $destino->nombre,
        ]);
    }

    // =====================================================
    // QR — toggle entrada/salida por persona
    // =====================================================

    public function registrarQr(Request $request, $id): JsonResponse
    {
        $reunion = Reunion::find($id);
        if (!$reunion || $reunion->estado === 'cerrada') {
            return response()->json(['message' => 'Reunión no disponible'], 422);
        }

        $request->validate(['id_persona' => 'required|exists:personas,id']);

        $idPersona = $request->id_persona;

        // Cooldown 5 segundos
        $ultimo = AsistenciaMovimiento::where('id_reunion', $id)
            ->where('id_persona', $idPersona)
            ->latest('created_at')
            ->first();

        if ($ultimo && now()->diffInSeconds($ultimo->created_at) < 5) {
            return response()->json(['message' => 'Escaneo demasiado reciente', 'cooldown' => true], 429);
        }

        // Toggle: si está presente → salida; si no → entrada
        $estaPresente = $ultimo && in_array($ultimo->tipo, ['entrada', 'entrada_virtual']);
        $nuevoTipo    = $estaPresente ? 'salida' : 'entrada';

        $movimiento = AsistenciaMovimiento::create([
            'id_reunion' => $id,
            'id_persona' => $idPersona,
            'tipo'       => $nuevoTipo,
        ]);

        $persona   = Persona::find($idPersona);
        $presentes = $reunion->contadorPresentes();

        return response()->json([
            'tipo'     => $nuevoTipo,
            'persona'  => "{$persona->apellido}, {$persona->nombre}",
            'presentes' => $presentes,
        ]);
    }

    // =====================================================
    // ORDEN DE PALABRA
    // =====================================================

    public function pedirPalabra(Request $request, $id): JsonResponse
    {
        $reunion = Reunion::find($id);
        if (!$reunion) {
            return response()->json(['message' => 'Reunión no encontrada'], 404);
        }

        $request->validate(['id_persona' => 'required|exists:personas,id']);

        $ultimoOrden = OrdenPalabra::where('id_reunion', $id)->max('orden') ?? 0;

        $turno = OrdenPalabra::create([
            'id_reunion' => $id,
            'id_persona' => $request->id_persona,
            'orden'      => $ultimoOrden + 1,
            'atendido'   => false,
        ]);

        return response()->json($turno->load('persona'), 201);
    }

    public function atenderPalabra(Request $request, $id, $turnoId): JsonResponse
    {
        $turno = OrdenPalabra::where('id_reunion', $id)->find($turnoId);
        if (!$turno) {
            return response()->json(['message' => 'Turno no encontrado'], 404);
        }

        $turno->atendido = true;
        $turno->save();

        return response()->json(['message' => 'Turno atendido']);
    }

    public function ordenPalabra($id): JsonResponse
    {
        $turnos = OrdenPalabra::with('persona')
            ->where('id_reunion', $id)
            ->orderBy('orden')
            ->get();

        return response()->json($turnos);
    }

    // =====================================================
    // VOTACIONES NOMINALES
    // =====================================================

    public function storeVotacion(Request $request, $id): JsonResponse
    {
        $reunion = Reunion::find($id);
        if (!$reunion) {
            return response()->json(['message' => 'Reunión no encontrada'], 404);
        }

        $request->validate(['descripcion' => 'required|string|max:300']);

        $ultimoOrden = Votacion::where('id_reunion', $id)->max('orden') ?? 0;

        $votacion = Votacion::create([
            'id_reunion'  => $id,
            'descripcion' => $request->descripcion,
            'orden'       => $ultimoOrden + 1,
        ]);

        return response()->json($votacion, 201);
    }

    public function registrarVoto(Request $request, $id, $votacionId): JsonResponse
    {
        $votacion = Votacion::where('id_reunion', $id)->find($votacionId);
        if (!$votacion) {
            return response()->json(['message' => 'Votación no encontrada'], 404);
        }

        $request->validate([
            'id_persona' => 'required|exists:personas,id',
            'voto'       => 'required|in:afirmativo,negativo,abstencion',
        ]);

        $voto = Voto::updateOrCreate(
            ['id_votacion' => $votacionId, 'id_persona' => $request->id_persona],
            ['voto' => $request->voto]
        );

        return response()->json([
            'voto'    => $voto,
            'resumen' => $votacion->resumen(),
        ]);
    }

    public function votaciones($id): JsonResponse
    {
        $votaciones = Votacion::with('votos.persona')
            ->where('id_reunion', $id)
            ->orderBy('orden')
            ->get()
            ->map(fn($v) => array_merge($v->toArray(), ['resumen' => $v->resumen()]));

        return response()->json($votaciones);
    }
}
