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
use Illuminate\Support\Facades\DB;

class PadronImport implements ToCollection, WithHeadingRow
{
    protected int $id_padron;

    public function __construct(int $id_padron)
    {
        $this->id_padron = $id_padron;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $duplicados = [];
            $vistos = [];

            foreach ($rows as $row) {

                if (empty($row['dni'])) {
                    continue;
                }

                $dni = trim($row['dni']);
                $legajo = $row['legajo'] ?? null;

                $partes = explode(',', $row['apellido_y_nombre'] ?? '');
                $apellido = trim($partes[0] ?? '');
                $nombre = trim($partes[1] ?? '');

                $persona = Persona::firstOrCreate(
                    ['dni' => $dni],
                    [
                        'apellido' => $apellido,
                        'nombre' => $nombre
                    ]
                );

                $key = $persona->id . '-' . $this->id_padron;

                if (
                    isset($vistos[$key]) ||
                    Inscripcion::where('id_persona', $persona->id)
                        ->where('id_padron', $this->id_padron)
                        ->exists()
                ) {
                    $duplicados[] = [
                        'dni' => $persona->dni,
                        'nombre' => "{$persona->apellido}, {$persona->nombre}",
                        'motivo' => 'Persona duplicada en el mismo padrón'
                    ];

                    continue;
                }

                $vistos[$key] = true;

                Inscripcion::create([
                    'id_persona' => $persona->id,
                    'id_padron' => $this->id_padron,
                    'legajo' => $legajo,
                ]);
            }

            if (!empty($duplicados)) {
                throw new \RuntimeException(json_encode($duplicados));
            }

        });
    }
}
