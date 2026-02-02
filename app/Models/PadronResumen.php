<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PadronResumen extends Model
{
    protected $table = 'padron_resumen';

    public $timestamps = false;

    protected $fillable = [
        'anio',
        'id_facultad',
        'id_claustro',
        'total',
    ];

    protected $primaryKey = null;
    public $incrementing = false;

    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'id_facultad');
    }

    public function claustro()
    {
        return $this->belongsTo(Claustro::class, 'id_claustro');
    }
}
