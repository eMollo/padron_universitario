<?php

namespace App\Services\Listas;

use App\Models\Claustro;
use App\Models\Persona;
use App\Models\Padron;
use App\Models\Inscripcion;
use App\Models\ListaPostulante;
use Illuminate\Support\Collection;

use App\Models\Lista;
use App\Models\ListaAval;
use App\Models\PadronResumen;

use Illuminate\Support\Facades\DB;

class AvalValidationService
{
 
     // Procesa avales normalizados

    public function procesarAvales(Lista $lista, array $avales): array
    {
        $resumen = [
            'procesados' => count($avales),
            'insertados' => 0,
            'validos'    => 0,
            'invalidos'  => 0,
            'errores'    => [],
        ];

        // universo cerrado de personas habilitadas
        $personasValidas = $this->personasHabilitadasParaLista($lista);

        DB::transaction(function () use ($lista, $avales, &$resumen, $personasValidas) {

            foreach ($avales as $fila) {

                $dni = $fila['dni'] ?? null;

                if (!$dni) {
                    $resumen['errores'][] = [
                        'fila' => $fila,
                        'error' => 'DNI vacío'
                    ];
                    continue;
                }


                $persona = Persona::where('dni', $dni)->first();

                if (!$persona) {
                    $this->guardarAvalInvalido($lista, null, $fila, 'Persona inexistente');
                    $resumen['invalidos']++;
                    continue;
                }


                if (!$personasValidas->contains($persona->id)) {
                    $this->guardarAvalInvalido(
                        $lista,
                        $persona,
                        $fila,
                        'No pertenece al padrón correspondiente'
                    );
                    $resumen['invalidos']++;
                    continue;
                }

               
                 //  AVAL VÁLIDO
   
                ListaAval::create([
                    'id_lista'   => $lista->id,
                    'id_persona' => $persona->id,
                    'legajo'     => $fila['legajo'] ?? null,
                    'estado'     => 'valido',
                ]);

                $resumen['insertados']++;
                $resumen['validos']++;
            }

            $this->recalcularEstadoLista($lista);
        });

        // Resumen reglamentario

        $minimo = $this->calcularMinimoAvales($lista);

        $validos = ListaAval::where('id_lista', $lista->id)
            ->where('estado', 'valido')
            ->distinct('id_persona')
            ->count('id_persona');

        $resumen['reglamentarios'] = [
            'avales_requeridos' => $minimo,
            'avales_validos'    => $validos,
            'avales_faltantes'  => max(0, $minimo - $validos),
        ];

        $resumen['avales_invalidos_detalle'] = ListaAval::where('id_lista', $lista->id)
            ->where('estado', 'invalido')
            ->get([
                'id_persona',
                'legajo',
                'motivo_invalidez'
            ]);

        return $resumen;
    }


    private function personasHabilitadasParaLista(Lista $lista): Collection
    {
        $query = Inscripcion::query()
            ->join('padrones', 'inscripciones.id_padron', '=', 'padrones.id')
            ->where('padrones.anio', $lista->anio);

        switch ($lista->tipo) {

            case 'rector':
                // cualquiera empadronado (graduados se ajustará luego)
                break;

            case 'decano':
                $query->where('padrones.id_facultad', $lista->id_facultad);
                break;

            case 'superior':
                $query->where('padrones.id_claustro', $lista->id_claustro);
                break;

            case 'directivo':
                $query->where('padrones.id_facultad', $lista->id_facultad)
                      ->where('padrones.id_claustro', $lista->id_claustro);
                break;
        }

        return $query
            ->pluck('inscripciones.id_persona')
            ->unique()
            ->values();
    }


     // ESTADO DE LISTA


    public function recalcularEstadoLista(Lista $lista): void
    {
        $cantidadValidos = ListaAval::where('id_lista', $lista->id)
            ->where('estado', 'valido')
            ->distinct('id_persona')
            ->count('id_persona');

        $minimo = $this->calcularMinimoAvales($lista);

        if ($cantidadValidos >= $minimo && $this->distribucionCorrecta($lista)) {
            $lista->estado_lista = 'oficializada';
        } else {
            $lista->estado_lista = 'avales_faltantes';
        }

        $lista->save();
    }


     //  INVALIDOS


    private function guardarAvalInvalido(
        Lista $lista,
        ?Persona $persona,
        array $fila,
        string $motivo
    ): void {
        ListaAval::create([
            'id_lista'   => $lista->id,
            'id_persona' => $persona?->id,
            'legajo'     => $fila['legajo'] ?? null,
            'estado'     => 'invalido',
            'motivo_invalidez' => $motivo,
        ]);
    }


     //  CÁLCULOS REGLAMENTARIOS (tal como los definiste)


    private function calcularMinimoAvales(Lista $lista): int
    {
        switch ($lista->tipo) {

            case 'rector':
                return $this->porcentajeClaustro($lista->anio, 0.004);

            case 'decano':
                return $this->porcentajeFacultad($lista->anio, $lista->id_facultad, 0.02);

            case 'superior':
                return $this->porcentajeClaustro(
                    $lista->anio,
                    0.01
                );

            case 'directivo':
                return $this->porcentajeFacultadClaustro(
                    $lista->anio,
                    $lista->id_facultad,
                    $lista->id_claustro,
                    0.02
                );

            default:
                return 0;
        }
    }

    private function porcentajeClaustro(int $anio, float $porcentaje): int
    {
        $total = PadronResumen::where('anio', $anio)->sum('total');
        return (int) ceil($total * $porcentaje);
    }

    private function porcentajeFacultad(int $anio, int $idFacultad, float $porcentaje): int
    {
        $total = PadronResumen::where('anio', $anio)
            ->where('id_facultad', $idFacultad)
            ->sum('total');

        return (int) ceil($total * $porcentaje);
    }

    private function porcentajeFacultadClaustro(
        int $anio,
        int $idFacultad,
        int $idClaustro,
        float $porcentaje
    ): int {
        $total = PadronResumen::where('anio', $anio)
            ->where('id_facultad', $idFacultad)
            ->where('id_claustro', $idClaustro)
            ->sum('total');

        return (int) ceil($total * $porcentaje);
    }


     //  DISTRIBUCIÓN 


    private function distribucionCorrecta(Lista $lista): bool
    {
        if ($lista->tipo === 'rector') {
            return Inscripcion::query()
                ->join('padrones', 'inscripciones.id_padron', '=', 'padrones.id')
                ->whereIn(
                    'inscripciones.id_persona',
                    ListaAval::where('id_lista', $lista->id)
                        ->where('estado', 'valido')
                        ->pluck('id_persona')
                )
                ->where('padrones.anio', $lista->anio)
                ->distinct()
                ->count('padrones.id_facultad') > 1;
        }

        if ($lista->tipo === 'decano') {
            return Inscripcion::query()
                ->join('padrones', 'inscripciones.id_padron', '=', 'padrones.id')
                ->whereIn(
                    'inscripciones.id_persona',
                    ListaAval::where('id_lista', $lista->id)
                        ->where('estado', 'valido')
                        ->pluck('id_persona')
                )
                ->where('padrones.anio', $lista->anio)
                ->distinct()
                ->count('padrones.id_claustro') > 1;
        }

        return true;
    }
}



