<?php

namespace App\Http\Controllers;

use App\Models\Padron;
use App\Models\Inscripcion;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\Facultad;
use App\Models\Claustro;
use App\Imports\PadronImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class PadronController extends Controller {
    
    public function index(Request $request)
    {
        $query = Padron::query();

        // Filtros opcionales
        if ($request->filled('anio')) {
            $query->where('anio', $request->anio);
        }

        if ($request->filled('id_claustro')) {
            $query->where('id_claustro', $request->id_claustro);
        }

        if ($request->filled('id_facultad')) {
            $query->where('id_facultad', $request->id_facultad);
        }

        if ($request->filled('id_sede')) {
            $query->where('id_sede', $request->id_sede);
        }

        return $query
            ->with(['facultad', 'claustro', 'sede'])
            ->withCount([
                // solo activas
                'inscripciones as inscripciones_activas_count' => function ($q) {
                    $q->whereNull('deleted_at');
                },
                // totales (incluye soft deleted)
                'inscripciones as inscripciones_totales_count'
            ])
            ->orderBy('anio', 'desc')
            ->orderBy('id_facultad')
            ->orderBy('id_claustro')
            ->get();
    }

    public function show($id)
    {
        $padron = Padron::with(['facultad', 'claustro', 'sede'])->find($id);

        if(!$padron) {
            return response()->json(['message' => 'Padrón no encontrado'], 404);
        }

        return response()->json($padron);
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,csv,xls',
            'anio' => 'required|integer',
            'id_facultad' => 'required|exists:facultad,id',
            'id_claustro' => 'required|exists:claustros,id',
            'id_sede' => 'nullable|exists:sede,id',
        ]);

        DB::beginTransaction();

        try {

            $exists = Padron::where([
            'anio' => $request->anio,
            'id_claustro' => $request->id_claustro,
            'id_facultad' => $request->id_facultad,
            'id_sede' => $request->id_sede,
            ])->exists();

            if ($exists) {
            return response()->json([
                'error' => 'Ya existe un padrón para esa combinación'
            ], 422);
            }

            $padron = Padron::create([
                'anio' => $request->anio,
                'id_facultad' => $request->id_facultad,
                'id_claustro' => $request->id_claustro,
                'id_sede' => $request->id_sede,
                'origen_archivo' => $request->file('archivo')->getClientOriginalName(),
                'importado_por' => auth()->id(),
                'importado_el' => now(),
            ]);

            Excel::import(new PadronImport($padron->id), $request->file('archivo'));

            DB::commit();

            return response()->json([
                'mensaje' => 'Padrón importado correctamente',
                'padron' => $padron
            ]);

        } catch (\RuntimeException $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'El padrón contiene personas duplicadas',
                'duplicados' => json_decode($e->getMessage(), true)
            ], 422);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Error al importar padrón',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        $padron = Padron::find($id);

        if (!$padron) {
            return response()->json(['message' => 'Padrón no encontrado'], 404);
        }

        $padron->delete();

        return response()->json(['message' => 'Padrón eliminado correctamente']);
    }

    //ULTIMA PARTE AGREGADA
    
    private function construirQueryBaja(array $filters)
    {
        $query = DB::table('inscripciones as i')
            ->join('padrones as p', 'p.id', '=', 'i.id_padron')
            ->whereNull('i.deleted_at')
            ->where('p.anio', $filters['anio']);

        if (!empty($filters['id_padron'])) {
            $query->where('p.id', $filters['id_padron']);
        }

        if (!empty($filters['id_facultad'])) {
            $query->where('p.id_facultad', $filters['id_facultad']);
        }

        if (!empty($filters['id_claustro'])) {
            $query->where('p.id_claustro', $filters['id_claustro']);
        }

        if (!empty($filters['id_sede'])) {
            $query->where('p.id_sede', $filters['id_sede']);
        }

        return $query;
    }

    public function previsualizarBaja(Request $request)
    {
        $filters = $request->validate([
            'anio' => 'required|integer',
            'id_facultad' => 'nullable|integer',
            'id_claustro' => 'nullable|integer',
            'id_sede' => 'nullable|integer',
            'id_padron' => 'nullable|integer',
        ]);

        $query = $this->construirQueryBaja($filters);

        $cantidad = $query->count();

        return response()->json([
            'registros_encontrados' => $cantidad
        ]);
    }

    public function bajaMasiva(Request $request)
    {
        $filters = $request->validate([
            'anio' => 'required|integer',
            'id_facultad' => 'nullable|integer',
            'id_claustro' => 'nullable|integer',
            'id_sede' => 'nullable|integer',
            'id_padron' => 'nullable|integer',
            'confirmar' => 'required|boolean'
        ]);

        if (!$filters['confirmar']) {
            return response()->json([
                'error' => 'Debe confirmar la operación enviando confirmar=true'
            ], 400);
        }

        $query = $this->construirQueryBaja($filters);

        $ids = $query->pluck('i.id');

        if ($ids->isEmpty()) {
            return response()->json([
                'mensaje' => 'No se encontraron registros para eliminar'
            ]);
        }

        $deleted = DB::table('inscripciones')
            ->whereIn('id', $ids)
            ->update([
                'deleted_at' => now()
            ]);

        return response()->json([
            'mensaje' => 'Baja masiva realizada correctamente',
            'registros_afectados' => $deleted
        ]);
    }

    public function resumen()
    {
        $resumen = DB::table('inscripciones as i')
            ->join('padrones as p', 'p.id', '=', 'i.id_padron')
            ->join('facultades as f', 'f.id', '=', 'p.id_facultad')
            ->join('claustros as c', 'c.id', '=', 'p.id_claustro')
            ->leftJoin('sedes as s', 's.id', '=', 'p.id_sede')
            ->whereNull('i.deleted_at')
            ->select(
                'p.anio',
                'p.id',
                'f.nombre as facultad',
                'c.nombre as claustro',
                's.nombre as sede',
                DB::raw('COUNT(i.id) as total')
            )
            ->groupBy(
                'p.id',
                'p.anio',
                'f.nombre',
                'c.nombre',
                's.nombre'
            )
            ->orderByDesc('p.anio')
            ->orderBy('facultad')
            ->get();

        return response()->json($resumen);
    }

    public function personas(Request $request, $id)
    {
        $perPage = $request->get('per_page', 50);
        $buscar = $request->get('buscar');

        $query = DB::table('inscripciones as i')
            ->join('personas as p', 'p.id', '=', 'i.id_persona')
            ->where('i.id_padron', $id)
            ->whereNull('i.deleted_at')
            ->select(
                'p.apellido',
                'p.nombre',
                'p.dni',
                'i.legajo'
            );

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('p.dni', 'like', "%{$buscar}%")
                ->orWhere('p.apellido', 'ilike', "%{$buscar}%")
                ->orWhere('p.nombre', 'ilike', "%{$buscar}%");
            });
        }

        $personas = $query
            ->orderBy('p.apellido')
            ->orderBy('p.nombre')
            ->paginate($perPage);

        return response()->json($personas);
    }

}

