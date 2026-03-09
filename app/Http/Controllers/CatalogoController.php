<?php

namespace App\Http\Controllers;

use App\Models\Facultad;
use App\Models\Claustro;
use App\Models\Sede;

class CatalogoController extends Controller
{
    public function index()
    {
        return response()->json([
            'facultades' => Facultad::orderBy('nombre')->get(['id','nombre']),
            'claustros'  => Claustro::orderBy('nombre')->get(['id','nombre']),
            'sedes'      => Sede::orderBy('nombre')->get(['id','nombre']),
        ]);
    }
}
