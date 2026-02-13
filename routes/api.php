<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ClaustroController;
use App\Http\Controllers\FacultadController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\PadronController;
use App\Http\Controllers\PadronComparadorController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\PadronExportController;

use App\Http\Controllers\AvalController;
use App\Http\Controllers\Api\AuthController;


Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::get('/padrones', [PadronController::class, 'index']);

    Route::delete('inscripciones/{id}', [InscripcionController::class, 'destroy']);
    Route::delete('padrones/{id}', [PadronController::class, 'destroy']);
    Route::post('padrones/importar', [PadronController::class, 'importar']);
    Route::post('listas', [ListaController::class, 'store']);
    Route::post('/listas/{idLista}/avales/importar', [AvalController::class, 'importar']);
});

Route::middleware(['auth:sanctum', 'role:admin,consulta'])->group(function () {
    Route::get('padrones/{id}/export', [PadronExportController::class, 'export']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // PERSONAS
    Route::get('personas', [PersonaController::class, 'index']);
    Route::get('personas/{id}', [PersonaController::class, 'show']);
    Route::post('personas', [PersonaController::class, 'store']);
    Route::put('personas/{id}', [PersonaController::class, 'update']);
    Route::delete('personas/{id}', [PersonaController::class, 'destroy']);

    // CLAUSTROS
    Route::get('claustros', [ClaustroController::class, 'index']);

    // FACULTADES
    Route::get('facultad', [FacultadController::class, 'index']);

    // SEDES
    Route::get('sede', [SedeController::class, 'index']);

    // INSCRIPCIONES
    Route::get('inscripciones', [InscripcionController::class, 'index']);
    Route::get('inscripciones/{id}', [InscripcionController::class, 'show']);
    #Route::delete('inscripciones/{id}', [InscripcionController::class, 'destroy']);

    // COMPARADOR DE PADRONES
    //Route::post('comparar/entre-facultades', [PadronComparadorController::class, 'compararEntreFacultades']);
    //Route::post('comparar/dentro-facultad', [PadronComparadorController::class, 'compararDentroDeFacultad']);

    // PADRONES
    Route::get('padrones', [PadronController::class, 'index']);
    Route::get('padrones/{id}', [PadronController::class, 'show']);
    #Route::post('padrones/importar', [PadronController::class, 'importar']);
    #Route::delete('padrones/{id}', [PadronController::class, 'destroy']);

    Route::post('padrones/comparar', [PadronComparadorController::class, 'comparar']);

    //LISTAS
    Route::get('listas', [ListaController::class, 'index']);
    Route::get('listas/{id}', [ListaController::class, 'show']);
    #Route::post('listas', [ListaController::class, 'store']); //crea lista


    #Route::post('/listas/{idLista}/avales/importar', [AvalController::class, 'importar']);
    });