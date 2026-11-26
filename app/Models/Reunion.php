<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Reunion extends Model
{
    protected $table = 'reuniones';

    protected $fillable = [
        'tipo', 'id_tipo_reunion', 'nombre',
        'fecha', 'hora_inicio', 'hora_fin', 'estado',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    public function tipoReunion()
    {
        return $this->belongsTo(TipoReunion::class, 'id_tipo_reunion');
    }

    public function movimientos()
    {
        return $this->hasMany(AsistenciaMovimiento::class, 'id_reunion');
    }

    public function votaciones()
    {
        return $this->hasMany(Votacion::class, 'id_reunion')->orderBy('orden');
    }

    public function ordenPalabra()
    {
        return $this->hasMany(OrdenPalabra::class, 'id_reunion')->orderBy('orden');
    }

    /**
     * Devuelve el estado actual (último movimiento) de cada persona en esta reunión.
     * Retorna una colección indexada por id_persona con el tipo del último movimiento.
     */
    public function estadoActualPresentes(): \Illuminate\Support\Collection
    {
        return AsistenciaMovimiento::query()
            ->selectRaw('DISTINCT ON (id_persona) id_persona, tipo, created_at')
            ->where('id_reunion', $this->id)
            ->orderByRaw('id_persona, created_at DESC, id DESC')
            ->get()
            ->keyBy('id_persona');
    }

    /**
     * Cuenta las personas actualmente presentes (último movimiento = entrada o entrada_virtual).
     */
    public function contadorPresentes(): int
    {
        return $this->estadoActualPresentes()
            ->filter(fn($m) => in_array($m->tipo, ['entrada', 'entrada_virtual']))
            ->count();
    }

    /**
     * Ids de las personas presentes ahora (último movimiento = entrada o entrada_virtual).
     */
    public function idsPresentes(): \Illuminate\Support\Collection
    {
        return $this->estadoActualPresentes()
            ->filter(fn($m) => in_array($m->tipo, ['entrada', 'entrada_virtual']))
            ->keys()
            ->values();
    }

    /**
     * Resumen de la asistencia armado con TODOS los movimientos de la reunión, no con el
     * estado final de cada persona. Sirve para reportar una reunión cerrada, en la que al
     * final todos ya se retiraron.
     *
     * Devuelve:
     *  - 'personas': por id de cada persona que estuvo presente al menos una vez →
     *      modalidad ('presente' | 'virtual' | 'mixta'), primera_entrada, ultima_salida,
     *      minutos presente, entradas (cuántas veces ingresó) y sigue_presente (si seguía
     *      adentro cuando terminó la reunión).
     *  - 'pico': máximo de personas presentes al mismo tiempo.
     */
    public function historialAsistencia(): array
    {
        $movimientos = $this->movimientos()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Hasta cuándo se cuenta a quien sigue adentro: la hora de cierre (o ahora, si la
        // reunión sigue abierta). Nunca antes del último movimiento registrado.
        $fin = ($this->estado === 'cerrada' && $this->hora_fin)
            ? Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_fin)
            : now();

        $ultimo = $movimientos->last();
        if ($ultimo && $fin->timestamp < $ultimo->created_at->timestamp) {
            $fin = $ultimo->created_at;
        }

        $personas = [];
        $dentro   = [];   // id_persona => true mientras está presente
        $pico     = 0;

        foreach ($movimientos as $mov) {
            $id = $mov->id_persona;

            $p = $personas[$id] ?? [
                'desde'           => null,
                'segundos'        => 0,
                'primera_entrada' => null,
                'ultima_salida'   => null,
                'entradas'        => 0,
                'presencial'      => false,
                'virtual'         => false,
            ];

            if ($mov->tipo === 'salida') {
                if ($p['desde'] !== null) {
                    $p['segundos']     += $mov->created_at->timestamp - $p['desde']->timestamp;
                    $p['desde']         = null;
                    $p['ultima_salida'] = $mov->created_at;
                    unset($dentro[$id]);
                }
            } else {
                if ($p['desde'] === null) {
                    $p['desde'] = $mov->created_at;
                    $p['entradas']++;
                    $p['primera_entrada'] = $p['primera_entrada'] ?? $mov->created_at;
                    $dentro[$id] = true;
                }

                // Pasar de presente a virtual (o al revés) estando adentro no reinicia el tramo
                if ($mov->tipo === 'entrada') {
                    $p['presencial'] = true;
                } else {
                    $p['virtual'] = true;
                }
            }

            $personas[$id] = $p;
            $pico = max($pico, count($dentro));
        }

        $resultado = [];

        foreach ($personas as $id => $p) {
            if ($p['entradas'] === 0) {
                continue;   // solo tiene salidas sueltas: nunca estuvo presente
            }

            $sigueAdentro = $p['desde'] !== null;
            $segundos     = $p['segundos'];

            if ($sigueAdentro) {
                $segundos += max(0, $fin->timestamp - $p['desde']->timestamp);
            }

            $resultado[$id] = [
                'modalidad'       => ($p['presencial'] && $p['virtual'])
                    ? 'mixta'
                    : ($p['virtual'] ? 'virtual' : 'presente'),
                'primera_entrada' => $p['primera_entrada']?->format('H:i'),
                'ultima_salida'   => $p['ultima_salida']?->format('H:i'),
                'minutos'         => (int) round($segundos / 60),
                'entradas'        => $p['entradas'],
                'sigue_presente'  => $sigueAdentro,
            ];
        }

        return ['personas' => $resultado, 'pico' => $pico];
    }
}
