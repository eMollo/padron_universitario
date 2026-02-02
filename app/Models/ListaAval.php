<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListaAval extends Model
{
    use HasFactory;

    protected $table = 'lista_avales';

    protected $fillable = [
        'id_lista',
        'id_persona',
        'legajo',
        'id_facultad',
        'estado',
        'motivo_invalidez',
    ];

    public function lista()
    {
        return $this->belongsTo(Lista::class, 'id_lista');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'id_facultad');
    }
}
