<?php

namespace App\Services\Listas;

use App\Models\Lista;
use App\Models\ListaPostulante;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;

class ListaCreationService
{
    protected ListaNumberService $numberService;
    
    /**
     * Create a new class instance.
     */
    public function __construct(ListaNumberService $numberService)
    {
        $this->numberService = $numberService;
    }

    /**
     * Crea lista y postulantes dentro de transacción.
     *
     * $data array esperado:
     *  - anio, tipo, nombre, sigla, id_facultad (nullable), id_claustro (nullable), id_apoderado
     *  - postulantes => array of items ['persona' => Persona, 'tipo'=>'titular'|'suplente', 'orden'=>int, 'legajo'=>string|null]
     *
     * Devuelve ['ok'=>true,'lista'=>$lista] or ['ok'=>false,'error'=>...]
     */
    public function create(array $data): array
    {
        $anio = $data['anio'];
        $tipo = $data['tipo'];
        $nombre = $data['nombre'];
        $sigla = $data['sigla'] ?? null;
        $id_facultad = $data['id_facultad'] ?? null;
        $id_claustro = $data['id_claustro'] ?? null;
        $id_apoderado = $data['id_apoderado'];

        $postulantes = $data['postulantes'] ?? [];

        try {
            $result = DB::transaction(function () use ($anio,$tipo,$nombre,$sigla,$id_facultad,$id_claustro,$id_apoderado,$postulantes) {

                $numero = $this->numberService->nextNumber($anio, $tipo);

                $lista = Lista::create([
                    'anio' => $anio,
                    'tipo' => $tipo,
                    'nombre' => $nombre,
                    'sigla' => $sigla,
                    'numero' => $numero,
                    'id_facultad' => $id_facultad,
                    'id_claustro' => $id_claustro,
                    'id_apoderado' => $id_apoderado,
                ]);

                foreach ($postulantes as $p) {
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

            return ['ok'=>true, 'lista'=>$result];
        }catch (\Throwable $e) {
            return ['ok'=>false, 'error'=>$e->getMessage()];
        }
    }
}
