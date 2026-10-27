<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaMovimiento extends Model
{
    protected $table = 'asistencia_movimientos';
    public $timestamps = false;
 
    protected $fillable = ['id_reunion', 'id_persona', 'tipo'];
 
    protected $casts = [
        'created_at' => 'datetime',
    ];
 
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
 
    public function reunion()
    {
        return $this->belongsTo(Reunion::class, 'id_reunion');
    }

}
