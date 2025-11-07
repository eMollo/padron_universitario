<?php

use Illuminate\Support\Facades\Route;
#use App\Http\Controllers\FacultadController;
#use App\Http\Controllers\SedeController;
#use App\Http\Controllers\ClaustroController;
#use App\Http\Controllers\PersonaController;
#use App\Http\Controllers\PadronController;

/*Route::get('/facultad', [FacultadController::class, 'index']);
Route::get('/sede', [SedeController::class, 'index']);
Route::get('/claustros', [ClaustroController::class, 'index']);
#Route::resource('personas', PersonaController::class);*/
#Route::get('/personas', [PersonaController::class, 'indexView'])->name('personas.index');

#Route::get('/', function () {
#    return view('welcome');
#});

/*Route::resource('padrones', PadronController::class)->only([
    'index', 'create', 'store', 'show', 'destroy'
]);*/