<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListaPostulante extends Model
{
    use HasFactory;

    protected $table = 'lista_postulantes';

    protected $fillable = ['id_lista', 'id_persona', 'tipo', 'orden', 'legajo'];
    
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
    
    public function lista()
    {
        return $this->belongsTo(Lista::class, 'id_lista');
    }

}
