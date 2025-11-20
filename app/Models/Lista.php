<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lista extends Model
{
    use HasFactory;

    protected $table = 'listas';

    protected $fillable = ['anio', 'tipo', 'nombre', 'sigla', 'numero', 'id_facultad', 'id_claustro', 'id_apoderado'];

    public function apoderado() 
    {
        return $this->belongsTo(Persona::class, 'id_apoderado');
    }

    public function postulantes()
    {
        return $this->hasMany(ListaPostulante::class, 'id_lista');
    }

    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'id_facultad');
    }

    public function claustro()
    {
        return $this->belongsTo(Claustro::class, 'id_claustro');
    }
}
