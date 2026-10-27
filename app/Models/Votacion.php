<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Votacion extends Model
{
    protected $table = 'votaciones';
    protected $fillable = ['id_reunion', 'descripcion', 'orden'];
 
    public function votos()
    {
        return $this->hasMany(Voto::class, 'id_votacion');
    }
 
    public function resumen(): array
    {
        $conteo = $this->votos()
            ->selectRaw('voto, COUNT(*) as total')
            ->groupBy('voto')
            ->pluck('total', 'voto')
            ->toArray();
 
        return [
            'afirmativo' => $conteo['afirmativo'] ?? 0,
            'negativo'   => $conteo['negativo']   ?? 0,
            'abstencion' => $conteo['abstencion'] ?? 0,
            'total'      => array_sum($conteo),
        ];
    }

}
