<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';

    protected $fillable = ['nombre', 'apellido', 'dni', 'telefono', 'email'];

    // Una persona puede tener muchas inscripciones
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_persona');
    }
}
