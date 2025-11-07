<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Claustro extends Model
{
    use HasFactory;

    protected $table = 'claustros';

    protected $fillable = ['nombre'];

    // Un claustro puede tener muchos padrones
    public function padrones()
    {
        return $this->hasMany(Padron::class);
    }
}
