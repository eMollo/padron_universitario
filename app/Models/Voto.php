<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voto extends Model
{
    protected $table = 'votos';
    protected $fillable = ['id_votacion', 'id_persona', 'voto'];

    public function persona() {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function votacion() {
        return $this->belongsTo(Votacion::class, 'id_votacion');
    }
}
