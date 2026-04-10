<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facultad;
use App\Models\Claustro;

class AdminController extends Controller
{
    public function comparador()
    {
        return view('admin.padron-comparador', [
            'facultades' => Facultad::all(),
            'claustros' => Claustro::all()
        ]);
    }
}