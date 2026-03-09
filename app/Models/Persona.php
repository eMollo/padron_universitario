<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\DniNormalizer;

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

    protected static function booted()
    {
        static::saving(function ($persona) {
            $persona->dni_normalizado = DniNormalizer::normalizar($persona->dni);
        });
    }
}
