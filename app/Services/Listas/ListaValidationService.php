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

        //VERIFICAR APODERADO

        if (!empty($payload['apoderado']['dni'])) {

            $apo = Persona::where('dni', $payload['apoderado']['dni'])->first();

            if (!$apo) {
                $errors['apoderado'][] = [
                    'message' => 'El apoderado no existe en el sistema',
                    'dni' => $payload['apoderado']['dni'],
                ];
            } elseif (!$this->personaEstaEnPadron($apo, $tipo, $anio, $id_claustro, $payload)) {
                $errors['apoderado'][] = [
                    'message' => 'El apoderado no pertenece al padrón correspondiente',
                    'dni' => $apo->dni,
                    'nombre' => "{$apo->apellido}, {$apo->nombre}",
                ];
            }
        }

        $result = $this->validarPostulantes(
        $payload['postulantes'],
        $tipo,
        $anio,
        $id_claustro,
        $payload
    );

    if (!$result['ok']) {
        return $result;
    }

    $postulantesValidos = $result['postulantes'];

    return [
        'ok' => true,
        'errors' => [],
        'postulantes' => $postulantesValidos
    ];
    }

    /**
    * Valida postulantes de una lista electoral.
    *
    * Reglas:
    * - DNI siempre obligatorio
    * - Legajo obligatorio solo para superior y directivo
    * - Apellido y nombre se ignoran (solo frontend)
    * - Si un postulante es inválido → falla toda la lista
    *
    * @return array ['ok'=>bool, 'errors'=>[], 'postulantes'=>[]]
    */
    private function validarPostulantes(
        array $postulantesInput,
        string $tipo,
        int $anio,
        ?int $id_claustro,
        array $payload
    ): array {
        $postulantesValidos = [];
        $postulantesIds = [];

        foreach (['titulares', 'suplentes'] as $rol) {

            if (empty($postulantesInput[$rol])) {
                continue;
            }

            foreach ($postulantesInput[$rol] as $index => $data) {


                // 1. Validación mínima de input


                if (empty($data['dni'])) {
                    return [
                        'ok' => false,
                        'errors' => [[
                            'message' => 'Falta DNI del postulante',
                            'rol'     => $rol,
                            'orden'   => $index + 1,
                        ]],
                        'postulantes' => []
                    ];
                }

                if (
                    in_array($tipo, ['superior', 'directivo']) &&
                    empty($data['legajo'])
                ) {
                    return [
                        'ok' => false,
                        'errors' => [[
                            'message' => 'Falta legajo del postulante',
                            'dni'     => $data['dni'],
                            'rol'     => $rol,
                            'orden'   => $index + 1,
                        ]],
                        'postulantes' => []
                    ];
                }


                // 2. Persona existente


                $persona = Persona::where('dni', $data['dni'])->first();

                if (!$persona) {
                    return [
                        'ok' => false,
                        'errors' => [[
                            'message' => 'Postulante inexistente en el sistema',
                            'dni'     => $data['dni'],
                        ]],
                        'postulantes' => []
                    ];
                }


                // 3. Pertenencia a padrón


                if (
                    !$this->personaEstaEnPadron(
                        $persona,
                        $tipo,
                        $anio,
                        $id_claustro,
                        $payload
                    )
                ) {
                    return [
                        'ok' => false,
                        'errors' => [[
                            'message' => 'Postulante fuera del padrón habilitado',
                            'dni'     => $persona->dni,
                            'nombre'  => "{$persona->apellido}, {$persona->nombre}",
                        ]],
                        'postulantes' => []
                    ];
                }


                // 4. Construcción final


                $postulantesValidos[] = [
                    'persona' => $persona,
                    'tipo'    => $rol === 'titulares' ? 'titular' : 'suplente',
                    'orden'   => $index + 1,
                    'legajo'  => in_array($tipo, ['superior', 'directivo'])
                                    ? $data['legajo']
                                    : null,
                ];

                $postulantesIds[] = $persona->id;
            }
        }


        // 5. Conflicto con otras listas


        if (!empty($postulantesIds)) {

            $conflictos = ListaPostulante::with(['persona', 'lista'])
                ->whereIn('id_persona', array_unique($postulantesIds))
                ->whereHas('lista', function ($q) use ($anio, $tipo) {
                    $q->where('anio', $anio)
                    ->where('tipo', $tipo);
                })
                ->get();

            if ($conflictos->isNotEmpty()) {

                $detalles = $conflictos->map(function ($lp) {
                    return [
                        'dni'          => $lp->persona->dni,
                        'nombre'       => "{$lp->persona->apellido}, {$lp->persona->nombre}",
                        'lista_tipo'   => $lp->lista->tipo,
                        'lista_nombre' => $lp->lista->nombre,
                        'lista_anio'   => $lp->lista->anio,
                    ];
                })->values();

                return [
                    'ok' => false,
                    'errors' => [[
                        'message'  => 'Algunos postulantes ya integran otra lista del mismo tipo y año',
                        'detalles' => $detalles,
                    ]],
                    'postulantes' => []
                ];
            }
        }

        return [
            'ok' => true,
            'errors' => [],
            'postulantes' => $postulantesValidos
        ];
    }


    private function personaEstaEnPadron(
        Persona $persona,
        string $tipo,
        int $anio,
        ?int $id_claustro,
        array $payload
    ): bool {
        
        // 1. Determinar si el tipo es válido y configurar la base de la query
        $tiposSoportados = ['superior', 'directivo', 'decano', 'rector'];
        if (!in_array($tipo, $tiposSoportados)) return false;
        
        // 2. Validaciones tempranas
        if (in_array($tipo, ['superior', 'directivo']) && empty($id_claustro)) return false;
        if (in_array($tipo, ['directivo', 'decano']) && empty($payload['id_facultad'])) return false;

        $padronQuery = Padron::where('anio', $anio);

        // 3. Aplicar filtros específicos según tipo
        match ($tipo) {
            'superior'  => $padronQuery->where('id_claustro', $id_claustro),
            'directivo' => $padronQuery->where('id_claustro', $id_claustro)
                                        ->where('id_facultad', $payload['id_facultad']),
            'decano'    => null,
            'rector'    => null, // Solo anio
        };

        // 4. Ejecutar la verificación final
        // Usamos el query builder directamente en el whereIn para máxima eficiencia
        return Inscripcion::where('id_persona', $persona->id)
            ->whereIn('id_padron', $padronQuery->select('id'))
            ->exists();
    }
    
    public function __construct()
    {
        //
    }
}
