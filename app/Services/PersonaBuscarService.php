<?php

namespace App\Services;

use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PersonaBuscarService
{
    public static function ejecutar($request)
    {
        $dni = $request->input('dni');
        $apellido = $request->input('apellido');
        $nombre = $request->input('nombre');
        $anio = $request->input('anio');
        $idFacultad = $request->input('id_facultad');
        $idClaustro = $request->input('id_claustro');

        $orderBy = $request->input('order_by', 'apellido');
        $orderDir = $request->input('order', 'asc');
        $porPagina = $request->input('per_page', 15);

        $query = self::buildBaseQuery(
            $dni,
            $apellido,
            $nombre,
            $anio,
            $idFacultad,
            $idClaustro
        );

        $query = self::applyOrdering($query, $orderBy, $orderDir);

        // eager loading optimizado
        $query->with([
            'inscripciones' => function ($q) use ($anio, $idFacultad, $idClaustro) {

                if ($anio || $idFacultad || $idClaustro) {
                    $q->whereHas('padron', function ($sub) use ($anio, $idFacultad, $idClaustro) {

                        if ($anio) {
                            $sub->where('anio', $anio);
                        }

                        if ($idFacultad) {
                            $sub->where('id_facultad', $idFacultad);
                        }

                        if ($idClaustro) {
                            $sub->where('id_claustro', $idClaustro);
                        }
                    });
                }

                $q->withTrashed()
                  ->with([
                      'padron.facultad',
                      'padron.claustro',
                      'usuarioBaja'
                  ]);
            }
        ]);

        $personas = $query->paginate($porPagina);

        // transformación
        $resultado = $personas->getCollection()->map(function ($persona) {
            return self::transformPersona($persona);
        });

        return [
            'filtros_aplicados' => [
                'dni' => $dni,
                'apellido' => $apellido,
                'nombre' => $nombre,
                'anio' => $anio,
                'id_facultad' => $idFacultad,
                'id_claustro' => $idClaustro,
            ],
            'meta' => [
                'total' => $personas->total(),
                'por_pagina' => $personas->perPage(),
                'pagina_actual' => $personas->currentPage(),
                'ultima_pagina' => $personas->lastPage(),
            ],
            'resultado' => $resultado,
        ];
    }

    // BASE QUERY

    private static function buildBaseQuery(
        $dni,
        $apellido,
        $nombre,
        $anio,
        $idFacultad,
        $idClaustro
    ) {
        $query = Persona::query();

        if ($dni) {
            $query->where('dni', $dni);
        }

        if ($apellido) {
            $query->where('apellido', 'ILIKE', "%{$apellido}%");
        }

        if ($nombre) {
            $query->where('nombre', 'ILIKE', "%{$nombre}%");
        }

        // IMPORTANTE: filtrar personas que tengan inscripciones válidas
        if ($anio || $idFacultad || $idClaustro) {
            $query->whereHas('inscripciones', function ($q) use ($anio, $idFacultad, $idClaustro) {

                $q->whereHas('padron', function ($sub) use ($anio, $idFacultad, $idClaustro) {

                    if ($anio) {
                        $sub->where('anio', $anio);
                    }

                    if ($idFacultad) {
                        $sub->where('id_facultad', $idFacultad);
                    }

                    if ($idClaustro) {
                        $sub->where('id_claustro', $idClaustro);
                    }
                });
            });
        }

        return $query;
    }

    // ORDERING

    private static function applyOrdering($query, $orderBy, $orderDir)
    {
        $allowed = ['apellido', 'nombre', 'dni'];

        if (!in_array($orderBy, $allowed)) {
            $orderBy = 'apellido';
        }

        $query->orderBy($orderBy, $orderDir === 'desc' ? 'desc' : 'asc');

        return $query;
    }

    // TRANSFORM

    private static function transformPersona($persona)
    {
        return [
            'persona_id' => $persona->id,
            'dni' => $persona->dni,
            'apellido' => $persona->apellido,
            'nombre' => $persona->nombre,
            'inscripciones' => $persona->inscripciones->map(function ($i) {

                return [
                    'inscripcion_id' => $i->id,
                    'anio' => optional($i->padron)->anio,
                    'estado' => $i->deleted_at ? 'BAJA' : 'ACTIVA',
                    'facultad' => optional(optional($i->padron)->facultad)->sigla
                        ?? optional(optional($i->padron)->facultad)->nombre,
                    'claustro' => optional(optional($i->padron)->claustro)->nombre,
                    'legajo' => $i->legajo,
                    'motivo_baja' => $i->motivo_baja,
                    'baja_realizada_por' => optional($i->usuarioBaja)->name,
                    'fecha_baja' => $i->deleted_at,
                ];
            })->values()
        ];
    }
}

