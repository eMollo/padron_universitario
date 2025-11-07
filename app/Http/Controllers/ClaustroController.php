<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claustro;

class ClaustroController extends Controller
{
    public function index()
    {
        //Retorna todos los claustros en JSON
        return response()->json(Claustro::all(), 200);
    }
}
