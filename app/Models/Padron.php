<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Padron extends Model
{
    use HasFactory;

    protected $table = 'padrones';

    protected $fillable = ['anio', 'id_claustro', 'id_facultad', 'id_sede', 'origen_archivo', 'importado_el'];

    public function claustro()
    {
        return $this->belongsTo(Claustro::class, 'id_claustro');
    }

    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'id_facultad');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_padron');
    }
}
