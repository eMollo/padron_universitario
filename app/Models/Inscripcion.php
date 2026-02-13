<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;


class Inscripcion extends Model
{
    use HasFactory, SoftDeletes;

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
