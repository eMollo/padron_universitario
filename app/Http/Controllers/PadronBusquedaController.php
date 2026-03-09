<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inscripcion;
use App\Models\Persona;
use App\Models\Padron;
use Illuminate\Support\Facades\DB;

class PadronBusquedaController extends Controller
{
    public function buscar(Request $request)
    {
        $query = Inscripcion::query()
            ->select(
                'inscripciones.id as inscripcion_id',
                'personas.id as id_persona',
                'personas.dni',
                'personas.apellido',
                'personas.nombre',
                'inscripciones.legajo',
                'padrones.id as id_padron',
                'padrones.anio',
                'padrones.id_facultad',
                'padrones.id_claustro',
                'padrones.id_sede'
            )
            ->join('personas', 'personas.id', '=', 'inscripciones.id_persona')
            ->join('padrones', 'padrones.id', '=', 'inscripciones.id_padron')
            ->whereNull('inscripciones.deleted_at');

        if ($request->anio) {
            $query->where('padrones.anio', $request->anio);
        }

        if ($request->id_facultad) {
            $query->where('padrones.id_facultad', $request->id_facultad);
        }

        if ($request->id_claustro) {
            $query->where('padrones.id_claustro', $request->id_claustro);
        }

        if ($request->sede) {
            $query->where('padrones.id_sede', $request->sede);
        }

        if ($request->dni) {
            $query->where('personas.dni_normalizado', $request->dni);
        }

        if ($request->apellido) {
            $query->where('personas.apellido', 'ILIKE', "%{$request->apellido}%");
        }

        if ($request->nombre) {
            $query->where('personas.nombre', 'ILIKE', "%{$request->nombre}%");
        }

        $resultados = $query
            ->orderBy('personas.apellido')
            ->orderBy('personas.nombre')
            ->limit(5000)
            ->get();

        return response()->json($resultados);
    }
}

