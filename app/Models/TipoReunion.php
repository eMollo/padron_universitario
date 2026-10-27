<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoReunion extends Model
{
    protected $table = 'tipos_reunion';
    protected $fillable = ['nombre', 'orden'];
 
    public function reuniones()
    {
        return $this->hasMany(Reunion::class, 'id_tipo_reunion');
    }

}
