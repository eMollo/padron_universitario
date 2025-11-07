<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sede;

class SedeController extends Controller
{
    public function index()
    {
        //Retorna todas las sedes en JSON
        return response()->json(Sede::with('facultad')->get(), 200);//con la facultad relacionada
    }
}
