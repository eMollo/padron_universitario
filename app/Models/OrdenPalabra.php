<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenPalabra extends Model
{
    protected $table = 'orden_palabra';
    public $timestamps = false;
    
    protected $fillable = ['id_reunion', 'id_persona', 'orden', 'atendido'];

    protected $casts = [
        'atendido' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function persona() {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
}
