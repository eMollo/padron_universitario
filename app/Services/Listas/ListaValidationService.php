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
        $tipo = mb_strtolower($tipo);

        //Decano y Rector tienen una única regla
        //Independientemente del claustro
        if (in_array($tipo, ['decano', 'rector'])) {
            return [
                'min_titulares' => 1,
                'max_titulares' => 1,
                'min_suplentes' => 1,
                'max_suplentes' => 1,
            ];
        }

        //Verificar que el tipo exista en las reglas
        if (!isset($this->reglas[$tipo])) {
            throw new \InvalidArgumentException(
                "Tipo de lista no soportado: {$tipo}"
            );
        }

        //Si existe una regla general (*), se aplica a todos
        if (isset($this->reglas[$tipo]['*'])){
            [$t, $s] = $this->reglas[$tipo]['*'];

            return [
                'min_titulares' => $t,
                'max_titulares' => $t,
                'min_suplentes' => 1,
                'max_suplentes' => $s,
            ];
        }

        //Para superior y directivo necesitamos conocer el claustro
        if (!$claustroNombre) {
            throw new \InvalidArgumentException(
                "Se requiere nombre de claustro para tipo {$tipo}"
            );
        }

        //Normalizamos el nombre del claustro para tener una
        //clave comun y coincida en las reglas

        $cn = mb_strtolower(trim($claustroNombre));

        $claveClaustro = null;

        if (str_contains($cn, 'docente') && !str_contains($cn, 'nodocente')) {
            $claveClaustro = 'docentes';
        } elseif (str_contains($cn, 'nodocente')){
            $claveClaustro = 'nodocentes';
        } elseif (str_contains($cn, 'graduad')){
            $claveClaustro = 'graduados';
        } elseif (str_contains($cn, 'estudiante')){
            $claveClaustro = 'estudiantes';
        }

        if (
            $claveClaustro === null ||
            !isset($this->reglas[$tipo][$claveClaustro])
        ) {
            throw new \InvalidArgumentException(
                "Claustro '{$claustroNombre}' no reconocido para tipo {$tipo}"
            );
        }

        [$t, $s] = $this->reglas[$tipo][$claveClaustro];

        return [
            'min_titulares' => $t,
            'max_titulares' => $t,
            'min_suplentes' => 1,
            'max_suplentes' => $s,
        ];
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
        // if ($cant != $rules['max_titulares']) {}
        if (count($titulares) < $rules['min_titulares'] || count($titulares) > $rules['max_titulares']) {
            $errors[] = [
                'message' => "Cantidad de titulares debe ser entre {$rules['min_titulares']} y {$rules['max_titulares']}",
            ];
        }
        // if ($cantS < $rules['min_suplentes'] || $cantS > $rules['max_suplentes'])
        if (count($suplentes) < $rules['min_suplentes'] || count($suplentes) > $rules['max_suplentes']) {
            $errors[] = [
                'message' => "Cantidad de suplentes debe ser entre {$rules['min_suplentes']} y {$rules['max_suplentes']}",
            ];
        }

        if (!empty($errors)) return ['ok'=>false,'errors'=>$errors,'postulantes'=>[]];

        
        $result = $this->validarPostulantes(
        $postulanteInput,
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
        $errores = [];

        foreach (['titulares', 'suplentes'] as $rol) {

            if (empty($postulantesInput[$rol])){
                continue;
            }

            foreach ($postulantesInput[$rol] as $index => $data) {
                $posicion = $index + 1;
                $rolTexto = $rol === 'titulares'
                    ? 'Titular'
                    : 'Suplente';

                $identificador = "{$rolTexto} {$posicion}";

                // DNI obligatorio

                if (empty($data['dni'])) {
                    $errores[] = [
                        'message' => "{$identificador}: falta ingresar el DNI.",
                        'rol' => $rol,
                        'orden' => $posicion,
                    ];

                    continue;
                }

                // BUSCAR PERSONA

                $persona = Persona::where('dni', $data['dni'])->first();

                if (!$persona) {
                    $errores[] = [
                        'message' => "{$identificador}: el DNI {$data['dni']} no corresponde a una persona registrada",
                        'dni' => $data['dni'],
                        'rol' => $rol,
                        'orden' => $posicion,
                    ];

                    continue;
                }

                // Buscar inscripción correspondiente al padron

                $inscripcion = $this->obtenerInscripcionEnPadron(
                    $persona,
                    $tipo,
                    $anio,
                    $id_claustro,
                    $payload
                );

                if (!$inscripcion) {
                    $errores[] = [
                        'message' => "{$identificador}: el DNI {$persona->dni} corresponde a una persona que no pertenece al padrón habilitado",
                        'dni' => $persona->dni,
                        'nombre' => "{$persona->apellido}, {$persona->nombre}",
                        'rol' => $rol,
                        'orden' => $posicion,
                    ];
                    continue;
                }

                //Verificar DNI repetido dentro de esta lista

                if (in_array($persona->id, $postulantesIds, true)) {
                    $errores[] = [
                        'message' => "{$identificador}: el DNI {$persona->dni} está repetido dentro de esta lista",
                        'dni' => $persona->dni,
                        'nombre' => "{$persona->apellido}, {$persona->nombre}",
                        'rol' => $rol,
                        'orden' => $posicion,
                    ];

                    continue;
                }

                $postulantesIds[] = $persona->id;

                // Agregar postulante válido

                $postulantesValidos[] = [
                    'persona' => $persona,
                    'tipo' => $rol === 'titulares'
                        ? 'titular'
                        : 'suplente',
                    'orden' => $posicion,

                    // El legajo ahora sale directamente
                    // de la inscripcion correspondiente
                    'legajo' => $inscripcion->legajo,
                ];
            }
        }

        // Conflicto con otras listas

        if (!empty($postulantesIds)) {
            $idListaExcluir = $payload['id_lista_excluir'] ?? null;

            $conflictos = ListaPostulante::with(['persona', 'lista'])
                ->whereIn('id_persona', array_unique($postulantesIds))
                ->whereHas('lista', function ($q) use ($anio, $tipo, $idListaExcluir) {
                    $q->where('anio', $anio)
                        ->where('tipo', $tipo);
                    if ($idListaExcluir) {
                        $q->where('id', '!=', $idListaExcluir);
                    }
                })
                ->get();

                foreach ($conflictos as $conflicto) {
                    $persona = $conflicto->persona;

                    $errores[] = [
                        'message' => "El DNI {$persona->dni} ya pertenece a otra lista del mismo año",
                        'dni' => $persona->dni,
                        'nombre' => "{$persona->apellido}, {$persona->nombre}",
                        'lista_tipo' => $conflicto->lista->tipo,
                        'lista_nombre' => $conflicto->lista->nombre,
                        'lista_anio' => $conflicto->lista->anio,
                    ];
                }
        }

        // Si hubo errores, se devuelven todos

        if (!empty($errores)) {
            return [
                'ok' => false,
                'errors' => $errores,
                'postulantes' => [],
            ];
        }

        // Todo correcto

        return [
            'ok' => true,
            'errors' => [],
            'postulantes' => $postulantesValidos,
        ];
    }


    
    private function claustroEsGraduados(?int $id_claustro): bool
    {
        if (empty($id_claustro)) {
            return false;
        }

        $claustro = Claustro::find($id_claustro);

        if (!$claustro) {
            return false;
        }

        return str_contains(
            mb_strtolower($claustro->nombre),
            'graduad'
        );
    }


    private function obtenerInscripcionEnPadron(
        Persona $persona,
        string $tipo,
        int $anio,
        ?int $id_claustro,
        array $payload
    ): ?Inscripcion 
    {

    // 1. Verificar si el tipo es válido
    $tiposSoportados = ['superior', 'directivo', 'decano', 'rector'];

    if (!in_array($tipo, $tiposSoportados)) {
        return null;
    }

    // 2. Validaciones tempranas
    if ( in_array($tipo, ['superior', 'directivo']) && 
        empty($id_claustro) )
    {
            return null;
    }

    if ( in_array($tipo, ['directivo', 'decano']) &&
        empty($payload['id_facultad'])
    ) {
        return null;
    }

    // 3. Construir query del padrón correspondiente
    $padronQuery = Padron::where('anio', $anio);

    match ($tipo) {
        'superior' => $padronQuery
            ->where('id_claustro', $id_claustro),
            
        'directivo' => $padronQuery 
            ->where('id_claustro', $id_claustro)
            ->where('id_facultad', $payload['id_facultad']),

        'decano' => $padronQuery
            ->where('id_facultad', $payload['id_facultad']),
        
        'rector' => null,
    };

    // 4. Obtener la inscripción concreta
    return Inscripcion::where('id_persona', $persona->id)
        ->whereIn('id_padron', $padronQuery->select('id'))
        ->first();
    }

    private function personaEstaEnPadron(
        Persona $persona,
        string $tipo,
        int $anio,
        ?int $id_claustro,
        array $payload
    ): bool {
        return $this->obtenerInscripcionEnPadron(
            $persona,
            $tipo,
            $anio,
            $id_claustro,
            $payload
        ) != null;
    }
    
    public function __construct()
    {
        //
    }
}
