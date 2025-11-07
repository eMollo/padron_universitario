<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facultad extends Model
{
    use HasFactory;

    protected $table = 'facultad';

    protected $fillable = ['nombre'];

    // Una facultad puede tener muchos padrones
    public function padrones()
    {
        return $this->hasMany(Padron::class);
    }
    // Una facultad puede tener muchas sedes
    public function sede()
    {
        return $this->hasMany(Sede::class, 'id_sede', 'id');
    }
}
