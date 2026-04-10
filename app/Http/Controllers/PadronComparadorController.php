<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Padron;
use App\Models\Inscripcion;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use App\Services\PadronComparadorService;
use App\Services\PersonaBuscarService;

class PadronComparadorController extends Controller
{
    public function buscar(Request $request)
    {
        $resultado = PersonaBuscarService::ejecutar($request);

        return response()->json($resultado);
    }

    public function comparar(Request $request, PadronComparadorService $service)
    {
        $data = $request->validate([
            'anio' => 'required|integer',
            'mode' => 'nullable|string',
            'id_facultad' => 'nullable|integer',
            'id_claustro' => 'nullable|integer',
            'id_claustro_1' => 'nullable|integer',
            'id_claustro_2' => 'nullable|integer',
        ]);

        // Validación extra
        if (
            ($data['mode'] ?? null) === 'entre_claustros' &&
            !empty($data['id_claustro_1']) &&
            !empty($data['id_claustro_2']) &&
            $data['id_claustro_1'] == $data['id_claustro_2']
        ) {
            return response()->json([
                'error' => 'Debe seleccionar dos claustros distintos'
            ], 400);
        }

        return response()->json(
            $service->comparar($data)
        );
    }

    public function bajaInscripcion(Request $request)
    {
        $data = $request->validate([
            'inscripcion_id' => 'required|integer',
            'motivo' => 'nullable|string|max:255'
        ]);

        $inscripcion = Inscripcion::findOrFail($data['inscripcion_id']);

        if ($inscripcion->deleted_at) {
            return response()->json([
                'error' => 'La inscripción ya estaba dada de baja'
            ], 400);
        }

        $inscripcion->motivo_baja = $data['motivo'] ?? 'Duplicado de padrón';
        $inscripcion->baja_realizada_por = auth()->id();
        $inscripcion->save();

        $inscripcion->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function bajas(Request $request)
    {
        $anio = $request->query('anio');

        $query = DB::table('inscripciones as i')
            ->join('personas as p', 'p.id', '=', 'i.id_persona')
            ->join('padrones as pad', 'pad.id', '=', 'i.id_padron')
            ->join('facultad as f', 'f.id', '=', 'pad.id_facultad')
            ->join('claustros as c', 'c.id', '=', 'pad.id_claustro')
            ->leftJoin('users as u', 'u.id', '=', 'i.baja_realizada_por')
            ->whereNotNull('i.deleted_at');

        if ($anio) {
            $query->where('pad.anio', $anio);
        }

        return $query
            ->select(
                'i.id as inscripcion_id',
                'p.dni',
                'p.apellido',
                'p.nombre',
                'f.sigla as facultad',
                'c.nombre as claustro',
                'i.motivo_baja',
                'u.name as usuario_baja',
                'i.deleted_at'
            )
            ->orderByDesc('i.deleted_at')
            ->get();
    }
}
