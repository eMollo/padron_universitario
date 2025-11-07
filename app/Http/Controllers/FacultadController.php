<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facultad;

class FacultadController extends Controller
{
    public function index()
    {
        //Retorna todas las facultades en JSON
        return response()->json(Facultad::all(), 200);
    }
}