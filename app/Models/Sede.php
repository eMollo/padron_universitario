<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sede extends Model
{
    use HasFactory;

    protected $table = 'sede';

    protected $fillable = ['nombre', 'id_facultad'];

    // Una sede pertenece a una facultad
    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'id_facultad');
    }

    // Una sede puede tener muchos padrones
    public function padrones()
    {
        return $this->hasMany(Padron::class, 'id_sede');
    }
}
