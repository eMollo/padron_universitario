<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Padron;
use App\Models\Inscripcion;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;

class PadronComparadorController extends Controller
{
    public function comparar(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'id_claustro' => 'nullable|integer',
            'id_facultad' => 'nullable|integer',
            'mode' => 'nullable|string' // nuevo
        ]);


        // Construcción dinámica del query de padrones


        $query = Padron::with(['facultad', 'claustro'])
            ->where('anio', $request->anio);

        // Modos posibles:
        // - mismo_claustro_global
        // - misma_facultad_entre_claustros
        // - facultad_vs_resto
        // - default (todos contra todos)

        if ($request->mode === 'mismo_claustro_global' && $request->id_claustro) {
            $query->where('id_claustro', $request->id_claustro);
        }

        if ($request->mode === 'misma_facultad_entre_claustros' && $request->id_facultad) {
            $query->where('id_facultad', $request->id_facultad);
        }

        if ($request->mode === 'facultad_vs_resto' && $request->id_facultad) {
            // Trae todos, pero luego el frontend puede diferenciar
            // (no filtramos acá porque justamente queremos comparar contra todos)
        }

        // Si no hay mode → todos contra todos del año

        $padrones = $query->get();

        if ($padrones->isEmpty()) {
            return response()->json([
                'mensaje' => 'No se encontraron padrones para los filtros indicados'
            ], 404);
        }


        // Obtener inscripciones activas (ignorar soft deletes)

        $inscripciones = Inscripcion::with([
                'persona',
                'padron.facultad',
                'padron.claustro'
            ])
            ->whereIn('id_padron', $padrones->pluck('id'))
            ->whereNull('deleted_at')
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json([
                'mensaje' => 'No hay inscripciones para comparar'
            ], 404);
        }


        // Duplicados exactos (por DNI)


        $duplicadosExactos = $inscripciones
            ->groupBy('persona.dni')
            ->filter(fn ($grupo) => $grupo->count() > 1)
            ->map(function ($grupo, $dni) {

                $persona = $grupo->first()->persona;

                return [
                    'dni' => $dni,
                    'nombre' => "{$persona->apellido}, {$persona->nombre}",
                    'cantidad' => $grupo->count(),
                    'ocurrencias' => $grupo->map(fn ($i) => [
                        'inscripcion_id' => $i->id, // importante para eliminar
                        'padron_id' => $i->id_padron,
                        'facultad' => $i->padron->facultad->nombre ?? '',
                        'claustro' => $i->padron->claustro->nombre ?? '',
                        'legajo' => $i->legajo
                    ])->values()
                ];
            })
            ->values();


        // Duplicados posibles (mismo nombre/apellido, distinto DNI)


        $porNombre = $inscripciones->groupBy(function ($i) {
            return mb_strtolower(
                trim("{$i->persona->apellido}, {$i->persona->nombre}")
            );
        });

        $duplicadosPosibles = collect();

        foreach ($porNombre as $nombreCompleto => $grupo) {

            $dnis = $grupo->pluck('persona.dni')->unique();

            if ($dnis->count() > 1) {

                $duplicadosPosibles->push([
                    'nombre' => $nombreCompleto,
                    'dnis' => $dnis->values(),
                    'cantidad' => $grupo->count(),
                    'ocurrencias' => $grupo->map(fn ($i) => [
                        'inscripcion_id' => $i->id,
                        'dni' => $i->persona->dni,
                        'padron_id' => $i->id_padron,
                        'facultad' => $i->padron->facultad->nombre ?? '',
                        'claustro' => $i->padron->claustro->nombre ?? '',
                        'legajo' => $i->legajo
                    ])->values()
                ]);
            }
        }


        //Respuesta final
   
        return response()->json([
            'filtros_aplicados' => [
                'anio' => $request->anio,
                'id_claustro' => $request->id_claustro,
                'id_facultad' => $request->id_facultad,
                'mode' => $request->mode ?? 'todos_contra_todos'
            ],
            'padrones_analizados' => $padrones->count(),
            'inscripciones_analizadas' => $inscripciones->count(),
            'DUPLICADOS_EXACTOS' => $duplicadosExactos,
            'DUPLICADOS_POSIBLES' => $duplicadosPosibles
        ]);
    }
}
