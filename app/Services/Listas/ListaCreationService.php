<?php

namespace App\Services\Listas;

use App\Models\Lista;
use App\Models\ListaPostulante;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;

class ListaCreationService
{
    protected ListaNumberService $numberService;
    protected ListaValidationService $validationService;

    public function __construct(ListaNumberService $numberService, ListaValidationService $validationService) {
        $this->numberService = $numberService;
        $this->validationService = $validationService;
    }

    public function create(array $request): array {
        $payload =[
            'anio' => $request['anio'],
            'tipo' => $request['tipo'],

            'modo_carga' => $request['modo_carga'] ?? 'normal',
            'numero' => $request['numero'] ?? null,

            'id_claustro' => $request['id_claustro'] ?? null,
            'id_facultad' => $request['id_facultad'] ?? null,
            'apoderado' => $request['apoderado'] ?? [],
            'postulantes' => $request['postulantes'] ?? [],
        ];

        $validation = $this->validationService->validateAll($payload);

        if (!$validation['ok']) {
            return [
                'ok' => false,
                'status' => 422,
                'error' => 'Validación de lista fallida',
                'details' => $validation['errors']
            ];
        }

        try{
            $lista = DB::transaction(function () use(
                $request, $payload, $validation
            ){

                $apoderado = $this->crearActualizarApoderado($payload['apoderado']);

                $numero = $this->obtenerNumeroLista($payload);

                $lista = Lista::create([
                    'anio' => $payload['anio'],
                    'tipo' => $payload['tipo'],
                    'nombre' => $request['nombre'],
                    'sigla' => $request['sigla'] ?? null,
                    'numero' => $numero,
                    'modo_carga' => $payload['modo_carga'],
                    'id_facultad' => $payload['id_facultad'],
                    'id_claustro' => $payload['id_claustro'],
                    'id_apoderado' => $apoderado->id,
                ]);

                foreach ($validation['postulantes'] as $p){
                    ListaPostulante::create([
                        'id_lista' => $lista->id,
                        'id_persona' => $p['persona']->id,
                        'tipo' => $p['tipo'],
                        'orden' => $p['orden'],
                        'legajo' => $p['legajo'] ?? null,
                    ]);
                }

                return $lista;
            });

            return [
                'ok' => true,
                'lista' => $lista->load([
                    'apoderado',
                    'postulantes.persona',
                    'facultad',
                    'claustro'
                ])
            ];
        }
        catch (\Illuminate\Database\QueryException $e) {
            //PostgreSQL UNIQUE VIOLATION
            if ($e->getCode() === '23505') {
                return [
                    'ok' => false,
                    'status' => 422,
                    'error' => 'El número de lista ya existe.',
                    'details' => []
                ];
            }
            throw $e;
        }

        catch (\InvalidArgumentException $e) {
            return [
                'ok' => false,
                'status' => 422,
                'error' => $e->getMessage(),
                'details' => []
            ];
        }

        catch (\RuntimeException $e) {
            return [
                'ok' => false,
                'status' => 422,
                'error' => $e->getMessage(),
                'details' => []
            ];
        }
        
        catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 500,
                'error' => 'No se pudo crear la lista',
                'details' => $e->getMessage()
            ];
        }
    }

    //CREA O ACTUALIZA EL APODERADO
    private function crearActualizarApoderado(array $datos): Persona {
        $persona = Persona::firstOrNew([
            'dni' => $datos['dni']
        ]);

        $persona->nombre = $datos['nombre'];
        $persona->apellido = $datos['apellido'];
        $persona->telefono = $datos['telefono'] ?? null;
        $persona->email = $datos['email'] ?? null;

        $persona->save();

        return $persona;
    }

    private function obtenerNumeroLista(array $payload): int {

        if ($payload['modo_carga'] === 'normal' && $payload['numero'] !== null) {
            throw new \InvalidArgumentException(
                'No debe enviar número de lista en modo normal.'
            );
        }

    //CARGA HISTORICA
        if ($payload['modo_carga'] === 'historica') {

            if ($payload['numero'] === null) {
                throw new \InvalidArgumentException(
                    'Debe indicar el número de lista.'
                );
            }

            $numero = (int) $payload['numero'];

            $query = Lista::where('anio', $payload['anio'])
                ->where('tipo', $payload['tipo'])
                ->where('numero', $numero);

            if (in_array($payload['tipo'], ['superior', 'directivo'])) {
                $query->where('id_claustro', $payload['id_claustro']);
            }

            if ($query->exists()) {
                throw new \RuntimeException(
                    "El número {$numero} ya está utilizado."
                );
            }

            return $numero;
        }

        //NUMERACION AUTOMATICA
        return $this->numberService->nextNumber(
            $payload['anio'],
            $payload['tipo'],
            $payload['id_claustro']
        );
    }
    
}
