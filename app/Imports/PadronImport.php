<?php

namespace App\Imports;

use App\Models\Padron;
use App\Models\Persona;
use App\Models\Inscripcion;
use App\Models\Sede;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;

class PadronImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    protected $id_padron;
    protected $id_sede;
    
    public function __construct($id_padron, $id_sede = null)
    {
        $this->id_padron = $id_padron;
        $this->id_sede = $id_sede;
    }

    public function model(array $row)
    {
        //Verificamos que tenga DNI
        if (empty($row['dni'])){
            return null;
        }

        //Dividir "Apellido y Nombre" (formato "Perez, Juan")
        $apellido = null;
        $nombre = null;

        if (!empty($row['apellido_y_nombre'])){
            $partes = explode(',', $row['apellido_y_nombre']);
            $apellido = trim($partes[0] ?? '');
            $nombre = trim($partes[1] ?? '');
        }

        //Buscar o crear persona
        $persona = Persona::firstOrCreate(
            ['dni' => $row['dni']],
            [
                'nombre' => $nombre,
                'apellido' => $apellido,
            ]
        );

        //Crear la inscripcion
        return new Inscripcion([
            'id_persona' => $persona->id,
            'id_padron' => $this->id_padron,
            'legajo' => $row['legajo'] ?? null,
        ]);
    }

}
