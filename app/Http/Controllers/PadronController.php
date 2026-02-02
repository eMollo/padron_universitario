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
    public function index()
    {
        $padrones = Padron::with(['facultad', 'claustro', 'sede'])->get();
        return response()->json($padrones);
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
            'importado_por' => auth()->user()->name ?? 'sistema',
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

        return response()->json(['message' => 'Padrón elminado correctamente']);
    }

    /*private function detectarDuplicadosEnArchivo($archivo): array
    {
        $rows =\Maatwebsite\Excel\Facades\Excel::toArray([], $archivo)[0];

        $vistos = [];
        $duplicados = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; //encabezado

            $dni = trim($row['dni'] ?? '');

            if (!$dni) continue;

            if (isset($vistos[$dni])) {
                $duplicados[$dni] = [
                    'dni' => $dni,
                    'apellido_y_nombre' => $row['apellido_y_nombre'] ?? null,
                    'motivo' => 'Persona duplicada en el mismo padrón'
                ];
            } else {
                $vistos[$dni] = true;
            }
        }

        return array_values($duplicados);
    }*/
}

