<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';

    protected $fillable = ['id_persona', 'id_padron', 'legajo'];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function padron()
    {
        return $this->belongsTo(Padron::class, 'id_padron');
    }
}
