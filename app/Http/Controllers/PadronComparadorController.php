<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Padron;
use App\Models\Inscripcion;
use App\Models\Persona;

class PadronComparadorController extends Controller
{
    //
    public function comparar(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'id_claustro' => 'nullable|integer',
            'id_facultad' => 'nullable|integer',
        ]);

        //Buscar los padrones del año correspondiente

        $query = Padron::with(['facultad', 'claustro'])
            ->where('anio', $request->anio);
        
        if ($request->id_claustro) {
            $query->where('id_claustro', $request->id_claustro);
        }

        if ($request->id_facultad) {
            $query->where('id_facultad', $request->id_facultad);
        }

        $padrones = $query->get();

        if ($padrones->isEmpty()) {
            return response()->json(['mensaje'=>'No se encontraron padrones para los filtros indicados'],404);
        }

        //Obtenemos todas las inscripciones de esos padrones
        $inscripciones = Inscripcion::with(['persona', 'padron.facultad', 'padron.claustro'])
            ->whereIn('id_padron', $padrones->pluck('id'))
            ->get();

        //Duplicados exactos (por DNI)
        $duplicadosExactos = $inscripciones->groupBy('persona.dni')
            ->filter(fn($grupo) => $grupo->count() > 1)
            ->map(function ($grupo, $dni) {
                $persona = $grupo->first()->persona;
                return[
                    'dni' => $dni,
                    'nombre' => "{$persona->apellido}, {$persona->nombre}",
                    'ocurrencias' => $grupo->map(fn($i) => [
                        'padron_id' => $i->id_padron,
                        'facultad' => $i->padron->facultad->nombre ?? '',
                        'claustro' => $i->padron->claustro->nombre ?? '',
                    ])->values()
                    ];
            })->values();

        //Duplicados posibles (mismo nombre y apellido, distinto DNI)
        $porNombre = $inscripciones->groupBy(fn($i) => mb_strtolower(trim("{$i->persona->apellido}, {$i->persona->nombre}")));
        $duplicadosPosibles = collect();

        foreach ($porNombre as $nombreCompleto => $grupo) {
            $dnis = $grupo->pluck('persona.dni')->unique();
            if ($dnis->count() > 1) {
                $duplicadosPosibles->push([
                    'nombre' => $nombreCompleto,
                    'dnis' => $dnis->values(),
                    'padrones' => $grupo->map(fn($i) => [
                        'padron_id' => $i->id_padron,
                        'facultad' => $i->padron->facultad->nombre ?? '',
                        'claustro' => $i->padron->claustro->nombre ?? '',
                    ])->values()
                    ]);
            }
        }

        //Devolver los resultados
        return response()->json([
            'DUPLICADOS_EXACTOS' => $duplicadosExactos,
            'DUPLICADOS_POSIBLES' => $duplicadosPosibles,
        ]);
    }


}
