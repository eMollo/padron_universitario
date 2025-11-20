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

class ListaController extends Controller
{
    //Listar
    public function index()
    {
        return response()->json(Lista::with(['apoderado', 'postulantes.persona', 'facultad', 'claustro'])->get(), 200);
    }

    //ver una lista
    public function show($id)
    {
        $lista = Lista::with(['apoderado', 'postulantes.persona', 'facultad', 'claustro'])->find($id);
        if (!$lista) return response()->json(['message'=>'Lista no encontrada'], 404);
        return response()->json($lista, 200);
    }

    //Store: crear lista (con validacion)
    public function store(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'tipo' => ['required','string', Rule::in(['superior','directivo','decano','rector'])],
            'nombre' => 'required|string|max:255',
            'sigla' => 'nullable|string|max:50',
            'id_claustro' => 'required_if:tipo,superior|nullable|exists:claustros,id',
            'id_facultad' => 'nullable|exists:facultad,id',
            // apoderado_data: array con dni, nombre, apellido, telefono?, email?
            'apoderado' => 'required|array',
            'apoderado.dni' => 'required|string',
            'apoderado.nombre' => 'required|string',
            'apoderado.apellido' => 'required|string',
            'apoderado.email' => 'nullable|email',
            'apoderado.telefono' => 'nullable|string',
            // postulantes: arrays
            'postulantes.titulares' => 'required|array',
            'postulantes.suplentes' => 'nullable|array',
        ]);

        //PARTE DONDE SOLO CONTROLAMOS SUPERIOR
        if ($request->tipo !== 'superior') {
            return response()->json(['error'=>'Este endpoint solo crea listas de tipo "superior" por ahora.'], 400);
        }

        $anio = $request->anio;
        $tipo = $request->tipo;
        $id_claustro = $request->id_claustro;

        //---Reglas de cantidad según claustro (superior)---
        //Mapear por nombre del claustro (ajusta si los nombres son distintos)
        $claustro = \App\Models\Claustro::find($id_claustro);
        if (!$claustro) {
            return response()->json(['error'=>'Claustro no encontrado'], 422);
        }
        $nombreClaustro = mb_strtolower($claustro->nombre);

        //reglas por claustro (titulo => [max_titulares, max_suplentes])
        $reglasPorClaustro = [
            'docentes' => [10,10],
            'graduados' => [4,4],
            'estudiantes' => [10,10],
            'nodocentes' => [10,10],
        ];

        //DETECTAR LA CLAVE POR COINCIDENCIA SIMPLE EN EL NOMBRE DEL CLAUSTRO
        $clave = null;
        foreach ($reglasPorClaustro as $k => $v) {
            if (mb_strpos($nombreClaustro, $k) !== false) {
                $clave = $k;
                break;
            }
        }
        if (!$clave) {
            // si no encontramos coincidencia exacta, rechazamos para ajustar
            return response()->json(['error'=>"No se reconoce el claustro '{$claustro->nombre}'. Ajustar de ser necesario"], 422);
        }

        [$maxTitulares, $maxSuplentes] = $reglasPorClaustro[$clave];

        $titulares = $request->input('postulantes.titulares', []);
        $suplentes = $request->input('postulantes.suplentes', []);

        if (count($titulares) == 0) {
            return response()->json(['error'=>'Debe haber al menos 1 (un) titular.'], 422);
        }
        if (count($titulares) > $maxTitulares) {
            return response()->json(['error'=>"Máximo titulares permitidos para '{$claustro->nombre}' es {$maxTitulares}."], 422);
        }
        if (count($suplentes) > $maxSuplentes) {
            return response()->json(['error'=>"Máximo suplentes permitidos para '{$claustro->nombre}' es {$maxSupletes}."], 422);
        }

        //---Validar apoderado: buscar o crear persona y actualizar contacto---
        $ap = $request->input('apoderado');
        $apoderado = Persona::firstOrNew(['dni' => $ap['dni']]);
        $apoderado->nombre = $ap['nombre'];
        $apoderado->apellido = $ap['apellido'];
        if (!empty($ap['telefono'])) $apoderado->telefono = $ap['telefono'];
        if (!empty($ap['email'])) $apoderado->email = $ap['email'];
        $apoderado->save();

        //---Validar cada postulante: existencia persona + pertenencia a padron---
        $errores = [];
        $validPostulantes = []; //cada item: ['persona' => PErsona, 'tipo' => titular/suplente, 'orden'=>n, 'legajo'=>... ]

        //Funcion auxiliar para chequear pertenencia a padron (superior => solo claustro + anio)
        $estaEnPadron = function ($personaId) use ($anio, $id_claustro) {
            //buscar padrones del anio y claustro
            $padronIds = Padron::where('anio', $anio)
                ->where('id_claustro', $id_claustro)
                ->pluck('id');

            if ($padronIds->isEmpty()) return false;

            return Inscripcion::whereIn('id_padron', $padronIds)
                ->where('id_persona', $personaId)
                ->exists();
        };

        //Validar titulares
        foreach ($titulares as $idx => $t) {
            //se espera que $t sea un array con al menos 'dni' (y opcional 'legajo')
            $dni = $t['dni'] ?? null;
            if (!$dni) {
                $errores[] = ['tipo'=>'titular', 'orden'=>$idx+1, 'motivo'=>'Falta DNI'];
                continue;
            }

            $persona = Persona::where('dni', $dni)->first();
            if (!$persona) {
                $errores[] = ['tipo'=>'titular', 'orden'=>$idx+1, 'dni'=>$dni, 'motivo'=>'Persona no encontrada en la tabla personas'];
                continue;
            }

            if (!$estaEnPadron($persona->id)) {
                $errores[] = ['tipo'=>'titular', 'orden'=>$idx+1, 'dni'=>$dni, 'motivo'=>'No figura en padrón'];
                continue;
            }

            $validPostulantes[] = [
                'persona' => $persona,
                'tipo' => 'titular',
                'orden' => $idx+1,
                'legajo' => $t['legajo'] ?? null,
            ];
        }

        //Validar suplentes
        foreach ($suplentes as $idx => $s) {
            $dni = $s['dni'] ?? null;
            if (!$dni) {
                $errores[] = ['tipo'=>'suplente','orden'=>$idx+1,'motivo'=>'Falta DNI'];
                continue;
            }
            $persona = Persona::where('dni', $dni)->first();
            if (!$persona) {
                $errores[] = ['tipo'=>'suplente','orden'=>$idx+1,'dni'=>$dni,'motivo'=>'Persona no encontrada en tabla personas'];
                continue;
            }
            if (!$estaEnPadron($persona->id)) {
                $errores[] = ['tipo'=>'suplente','orden'=>$idx+1,'dni'=>$dni,'motivo'=>'No figura en padrón (año/claustro)'];
                continue;
            }
            $validPostulantes[] = [
                'persona' => $persona,
                'tipo' => 'suplente',
                'orden' => $idx+1,
                'legajo' => $s['legajo'] ?? null,
            ];
        }

        if (!empty($errores)) {
            return response()->json([
                'error' => 'Validación fallida: algunos postulantes no están en el padrón o faltan datos',
                'detalles' => $errores
            ], 422);
        }

        // --------------- Crear lista + asignar numero incremental por (anio,tipo) ---------------
        return DB::transaction(function () use ($anio,$tipo,$request,$apoderado,$validPostulantes,$id_claustro) {
            // calcular numero
            $ultimo = Lista::where('anio', $anio)->where('tipo', $tipo)->max('numero');
            $numero = ($ultimo ?? 0) + 1;

            $lista = Lista::create([
                'anio' => $anio,
                'tipo' => $tipo,
                'nombre' => $request->nombre,
                'sigla' => $request->sigla,
                'numero' => $numero,
                'id_facultad' => $request->id_facultad ?? null,
                'id_claustro' => $id_claustro,
                'id_apoderado' => $apoderado->id,
            ]);

            // crear postulantes
            foreach ($validPostulantes as $p) {
                ListaPostulante::create([
                    'id_lista' => $lista->id,
                    'id_persona' => $p['persona']->id,
                    'tipo' => $p['tipo'],
                    'orden' => $p['orden'],
                    'legajo' => $p['legajo'],
                ]);
            }

            return response()->json([
                'message' => 'Lista superior creada correctamente',
                'lista' => $lista->load(['apoderado','postulantes.persona','claustro'])
            ], 201);
        });
    }
}
