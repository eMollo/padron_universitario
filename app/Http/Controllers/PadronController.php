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
        try{
            $request->headers->set('Accept', 'application/json');

            $request->validate([
                'archivo' => 'required|file|mimes:xlsx,csv,xls',
                'anio' => 'required|integer',
                'id_facultad' => 'required|exists:facultad,id',
                'id_claustro' => 'required|exists:claustros,id',
                'id_sede' => 'nullable|exists:sede,id',
            ]);

        //CREAR EL PADRON ANTES DE IMPORTAR
            $padron = Padron::create([
                'anio' => $request->anio,
                'id_facultad' => $request->id_facultad,
                'id_claustro' => $request->id_claustro,
                'id_sede' => $request->id_sede,
                'origen_archivo' => $request->file('archivo')->getClientOriginalName(),
                'importado_por' => auth()->user()->name ?? 'sistema',
                'importado_el' => now(),
            ]);

        //IMPORTAR LOS DATOS DEL EXCEL
            Excel::import(new PadronImport($padron->id, $request->id_sede), $request->file('archivo'));

            return response()->json([
                'message' => 'Padrón importado correctamente',
                'padron' => $padron,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error' => 'Error de validación',
                'detalles' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error inesperado',
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
}

