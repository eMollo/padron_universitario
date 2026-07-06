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
            'id_claustro' => $request['id_claustro'] ?? null,
            'id_facultad' => $request['id_facultad'] ?? null,
            'apoderado' => $request['apoderado'] ?? [],
            'postulantes' => $request['postulantes'] ?? [],
        ];

        $validation = $this->validationService->validateAll($payload);

        if ($validation['ok']) {
            return [
                'ok' => false,
                'status' => 422,
                'error' => 'Validación de lista fallida',
                'details' => $validation['errors']
            ];
        }

        $apoderado = $this->crearActualizarApoderado($payload['apoderado']);

        try{
            $lista = DB::transaction(function () use(
                $request, $payload, $validation, $apoderado
            ){
                $numero = $this->numberService->nextNumber(
                    $payload['anio'], $payload['tipo'], $payload['id_claustro']
                );

                $lista = Lista::create([
                    'anio' => $payload['anio'],
                    'tipo' => $payload['tipo'],
                    'nombre' => $request['nombre'],
                    'sigla' => $request['sigla'] ?? null,
                    'numero' => $numero,
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
        }catch (\Throwable $e) {
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
    
}
