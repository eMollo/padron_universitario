<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reunion extends Model
{
    protected $table = 'reuniones';
 
    protected $fillable = [
        'tipo', 'id_tipo_reunion', 'nombre',
        'fecha', 'hora_inicio', 'hora_fin', 'estado',
    ];
 
    protected $casts = [
        'fecha' => 'date',
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
            ->orderByRaw('id_persona, created_at DESC')
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

}
