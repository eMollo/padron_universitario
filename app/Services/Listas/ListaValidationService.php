<?php

namespace App\Services\Listas;

use App\Models\Claustro;
use App\Models\Persona;
use App\Models\Padron;
use App\Models\Inscripcion;
use App\Models\ListaPostulante;
use Illuminate\Support\Collection;

class ListaValidationService
{
    
    protected array $reglas = [
        'superior' => [
            'docentes' => [12,12],
            'graduados' => [4,4],
            'estudiantes' => [12,12],
            'nodocentes' => [12,12],
        ],
        'directivo' => [
            'docentes' => [8,8],
            'graduados' => [1,3],
            'estudiantes' => [4,4],
            'nodocentes' => [3,3],
        ],
        'decano' => [
            '*' => [1,1],
        ],
        'rector' => [
            '*' => [1,1],
        ],
    ];

    /**
     * Devuelve reglas (min/max titulares y suplentes) para tipo+claustro
     * Lanza Exception si no se reconoce
     */

    public function obtenerReglas(string $tipo, string $claustroNombre = null): array
    {
        $tipo = strtolower($tipo);

        if (in_array($tipo, ['decano', 'rector'])) {
            return [
                'min_titulares' => 1, 'max_titulares' => 1,
                'min_suplentes' => 1, 'max_suplentes' => 1,
            ];
        }

        if (!isset($this->reglas[$tipo])) {
            throw new \InvalidArgumentException("Tipo de lista no soportado: {$tipo}");
        }

        //si existe la clave '*' (aplica a todos)
        if (isset($this->reglas[$tipo]['*'])) {
            [$t,$s] = $this->reglas[$tipo]['*'];
            return ['min_titulares'=>1, 'max_titulares'=>$t, 'min_suplentes'=>1,'max_suplentes'=>$s];
        }

        if (!$claustroNombre) {
            throw new \InvalidArgumentException("Se requiere nombre de claustro para tipo {$tipo}");
        }

        $cn = mb_strtolower($claustroNombre);
        foreach ($this->reglas[$tipo] as $clave => [$t,$s]) {
            if (mb_strpos($cn, $clave) !== false) {
                return['min_titulares'=>1, 'max_titulares'=>$t, 'min_suplentes'=>1,'max_suplentes'=>$s];
            }
        }

        throw new \InvalidArgumentException("Claustro '{$claustroNombre}' no reconocido para tipo {$tipo}");
    }

    /**
     * Valida postulantes y apoderado, revisa padrón y pertenencia a otras listas.
     *
     * $payload expects:
     *  - anio
     *  - tipo
     *  - id_claustro (nullable for some types)
     *  - id_facultad (nullable)
     *  - apoderado array (dni,nombre,apellido,email?,telefono?)
     *  - postulantes: ['titulares'=>[], 'suplentes'=>[]] where each item has 'dni' and optional 'legajo'
     *
     * Returns array: ['ok'=>bool, 'errors'=>[], 'postulantes'=>[]]
     */
    public function validateAll(array $payload): array
    {
        $errors = [];
        $anio = $payload['anio'] ?? null;
        $tipo = $payload['tipo'] ?? null;
        $id_claustro = $payload['id_claustro'] ?? null;
        $postulanteInput = $payload['postulantes'] ?? ['titulares'=>[], 'suplentes'=>[]];

        if (!$anio || !$tipo) {
            return ['ok'=>false, 'errors'=>[['message'=>'Falta anio o tipo']], 'postulantes'=>[]];
        }

        //Obtener nombre del claustro
        $claustroNombre = null;
        if ($id_claustro) {
            $cl = Claustro::find($id_claustro);
            if (!$cl) return ['ok'=>false, 'errors'=>[['message'=>'Claustro no encontrado']], 'postulantes'=>[]];
            $claustroNombre = $cl->nombre;
        }

        try {
            $rules = $this->obtenerReglas($tipo, $claustroNombre);
        }catch (\Throwable $e) {
            return ['ok'=>false,'errors'=>[['message'=>$e->getMessage()]], 'postulantes'=>[]];
        }

        $titulares = $postulanteInput['titulares'] ?? [];
        $suplentes = $postulanteInput['suplentes'] ?? [];

        //chequear counts mínimos/máximos
        $cant = count($titulares);
        $cantS = count($suplentes);
        if (count($titulares) < $rules['min_titulares'] || count($titulares) > $rules['max_titulares']) {
            $errors[] = [
                'message' => "Cantidad de titulares debe ser entre {$rules['min_titulares']} y {$rules['max_titulares']}",
            ];
        }
        if (count($suplentes) < $rules['min_suplentes'] || count($suplentes) > $rules['max_suplentes']) {
            $errors[] = [
                'message' => "Cantidad de suplentes debe ser entre {$rules['min_suplentes']} y {$rules['max_suplentes']}",
            ];
        }

        if (!empty($errors)) return ['ok'=>false,'errors'=>$errors,'postulantes'=>[]];

        $validPostulantes = [];

        //chequear si persona existe y si está en los padrones (anio + claustro)
        $estaEnPadron = function (Persona $persona) use ($anio, $tipo, $payload, $id_claustro) : bool {
            //Superior: requiere claustro + año
            if ($tipo === 'superior') {
                $padronIds = Padron::where('anio', $anio)
                    ->where('id_claustro', $id_claustro)
                    ->pluck('id');
                if ($padronIds->isEmpty()) return false;
                return Inscripcion::whereIn('id_padron', $padronIds)
                    ->where('id_persona', $persona->id)
                    ->exists();
            }

            // Directivo: lista directivo tiene relacion con facultad + claustro
            if ($tipo === 'directivo') {
                // si viene id_facultad la lista es por facultad+claustro
                if (!empty($payload['id_facultad'])) {
                    $padronIds = Padron::where('anio', $anio)
                        ->where('id_claustro', $id_claustro)
                        ->where('id_facultad', $payload['id_facultad'])
                        ->pluck('id');
                } else {
                    // si no viene facultad, buscar en todo el anio+claustro
                    $padronIds = Padron::where('anio', $anio)
                        ->where('id_claustro', $id_claustro)
                        ->pluck('id');
                }
                if ($padronIds->isEmpty()) return false;
                return Inscripcion::whereIn('id_padron', $padronIds)
                    ->where('id_persona', $persona->id)
                    ->exists();
            }

            //Decano: por facultad + anio
            if ($tipo === 'decano') {
                if (empty($payload['id_facultad'])) return false;
                $padronIds = Padron::where('anio', $anio)
                    ->where('id_facultad', $payload['id_facultad'])
                    ->pluck('id');
                if ($padronIds->isEmpty()) return false;
                return Inscripcion::whereIn('id_padron', $padronIds)
                    ->where('id_persona', $persona->id)
                    ->exists();
            }

            //Rector -> busca en todos los padrones del año
            if ($tipo === 'rector') {
                $padronIds = Padron::where('anio', $anio)->pluck('id');
                if ($padronIds->isEmpty()) return false;
                return Inscripcion::whereIn('id_padron', $padronIds)
                    ->where('id_persona', $persona->id)
                    ->exists();
            }

            return false;
        };

        //Validar titulares
        foreach ($titulares as $idx => $t) {
            $dni = $t['dni'] ?? null;
            if (!$dni) {
                $errors[] = ['tipo'=>'titular','orden'=>$idx+1,'motivo'=>'Falta DNI'];
                continue;
            }
            $persona = Persona::where('dni', $dni)->first();
            if (!$persona) {
                $errors[] = ['tipo'=>'titular','orden'=>$idx+1,'dni'=>$dni,'motivo'=>'Persona no encontrada en tabla personas'];
                continue;
            }
            if (!$estaEnPadron($persona)) {
                $errors[] = ['tipo'=>'titular','orden'=>$idx+1,'dni'=>$dni,'motivo'=>'No figura en padrón correspondiente'];
                continue;
            }
            $validPostulantes[] = [
                'persona' =>$persona,
                'tipo' => 'titular',
                'orden' => $idx+1,
                'legajo' => $t['legajo'] ?? null,
            ];
        }
        
        //Validar suplentes
        foreach ($suplentes as $idx => $s) {
            $dni = $s['dni'] ?? null;
            if (!$dni) {
                $errors[] = ['tipo'=>'suplente','orden'=>$idx+1,'motivo'=>'Falta DNI'];
                continue;
            }
            $persona = Persona::where('dni', $dni)->first();
            if (!$persona) {
                $errors[] = ['tipo'=>'suplente','orden'=>$idx+1,'dni'=>$dni,'motivo'=>'Persona no encontrada en tabla personas'];
                continue;
            }
            if (!$estaEnPadron($persona)) {
                $errors[] = ['tipo'=>'suplente','orden'=>$idx+1,'dni'=>$dni,'motivo'=>'No figura en padrón correspondiente'];
                continue;
            }
            $validPostulantes[] = [
                'persona' => $persona,
                'tipo' => 'suplente',
                'orden' => $idx+1,
                'legajo' => $s['legajo'] ?? null,
            ];
        }

        if (!empty($errors)) {
            return ['ok'=>false, 'errors'=>$errors, 'postulantes'=>[]];
        }

        //Validar que ninguno de los postulantes ya pertenezca a otra lista del mismo año
        $idsDePostulantes = collect($validPostulantes)
            ->map(fn($p) => $p['persona']->id)
            ->unique()
            ->values()
            ->toArray();
        
        if (!empty($idsDePostulantes)) {
            $enOtrasListas = ListaPostulante::with('lista', 'persona')
                ->whereIn('id_persona', $idsDePostulantes)
                ->whereHas('lista', function($q) use ($anio) {
                    $q->where('anio', $anio);
                })->get();

            if ($enOtrasListas->isNotEmpty()) {
                $det = $enOtrasListas->map(function($lp){
                    return [
                        'dni' => $lp->persona?->dni,
                        'nombre' => $lp->persona ? $lp->persona->apellido . ', ' . $lp->persona->nombre : null,
                        'lista_tipo' => $lp->lista?->tipo,
                        'lista_nombre' => $lp->lista?->nombre,
                        'lista_anio' => $lp->lista?->anio,
                    ];
                })->values()->all();

                return ['ok'=>false,'errors'=>[['message'=>'Algunos postulantes ya están en otra lista del mismo año','detalles'=>$det]],'postulantes'=>[]];
            }
        }

        return ['ok'=>true,'errors'=>[],'postulantes'=>$validPostulantes];
    }
    
    public function __construct()
    {
        //
    }
}
