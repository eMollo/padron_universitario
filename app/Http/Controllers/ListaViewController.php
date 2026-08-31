<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claustro;
use App\Models\Facultad;

class ListaViewController extends Controller
{
    //Listado de Listas
    public function index() {
        return view('listas.index');
    }

    //Formulario de creación
    public function crear() {
        return view('listas.crear', [
            'claustros' => Claustro::orderBy('nombre')->get(),
            'facultades' => Facultad::orderBy('nombre')->get(),
        ]);
    }

    //Ver una Lista
    public function ver(int $id) {
        return view('listas.ver', compact('id'));
    }

    //Editar una Lista
    public function editar(int $id) {
        return view('listas.editar', compact('id'));
    }
}
