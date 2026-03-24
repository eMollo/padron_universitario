<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ClaustroController;
use App\Http\Controllers\FacultadController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\PadronController;
use App\Http\Controllers\PadronComparadorController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\PadronExportController;
use App\Http\Controllers\PadronBusquedaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\AvalController;
use App\Http\Controllers\PadronMetricasController;
use App\Services\PadronResumenService;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::get('/login', fn() => view('auth.login'))->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/test-auth', fn() => auth()->check() ? 'LOGUEADO' : 'NO LOGUEADO');

/*
|--------------------------------------------------------------------------
| VISTAS (Blade)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/', fn() => view('dashboard.index'));

    //Route::get('/padrones/importar', fn() => view('padrones.importar'))
       // ->middleware('role:admin');

    Route::get('/padrones', fn() => view('padrones.index'));

    Route::get('/padrones/{id}/personas', fn($id) => view('padrones.personas', compact('id')));

    Route::get('/admin/comparador', fn() => view('admin.padron-comparador'))
        ->middleware('role:admin');

    Route::get('/personas/buscar', fn() => view('personas.buscar'));

    Route::get('/api/metricas', [PadronMetricasController::class, 'index']);
    Route::get('/padrones/metricas', fn() => view('padrones.metricas'));
    Route::post('/api/metricas/recalcular', [PadronMetricasController::class, 'recalcular']);
});

Route::middleware(['auth', 'role:admin'])->group(function () {

        Route::get('/padrones/importar', fn() => view('padrones.importar'));
        Route::get('/admin/comparador', fn() => view('admin.padron-comparador'));
        Route::get('/admin/comparador/bajas', fn() => view('admin.padron-bajas'));

    });

/*
|--------------------------------------------------------------------------
| API INTERNA (CON SESIÓN)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('api')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | SOLO ADMIN
    |--------------------------------------------------------------------------
    */

    

    Route::middleware(['role:admin'])->group(function () {

        Route::delete('inscripciones/{id}', [InscripcionController::class, 'destroy']);
        Route::delete('padrones/{id}', [PadronController::class, 'destroy']);

        Route::post('padrones/importar', [PadronController::class, 'importar']);
        Route::post('listas', [ListaController::class, 'store']);
        Route::post('listas/{idLista}/avales/importar', [AvalController::class, 'importar']);

        Route::post('inscripciones/{id}/restaurar', [InscripcionController::class, 'restaurar']);
        Route::post('padrones/previsualizar-baja', [PadronController::class, 'previsualizarBaja']);
        Route::post('padrones/baja-masiva', [PadronController::class, 'bajaMasiva']);
        Route::post('padrones/buscar', [PadronBusquedaController::class, 'buscar']);

        Route::post('comparador/buscar', [PadronComparadorController::class, 'buscar']);
        Route::post('comparador/comparar', [PadronComparadorController::class, 'comparar']);
        Route::post('comparador/baja-inscripcion', [PadronComparadorController::class, 'bajaInscripcion']);

        Route::get('comparador/bajas', [PadronComparadorController::class, 'bajas']);

        Route::post('comparador/export-bajas', [PadronExportController::class, 'exportBajas']);
        

    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN + CONSULTA
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin,consulta'])->group(function () {
        Route::post('padrones/export-filtrado', [PadronExportController::class, 'exportFiltrado']);
        Route::post('comparador/export', [PadronExportController::class, 'exportComparador']);
        Route::get('padrones/{id}/export', [PadronExportController::class, 'export']);
    });

    /*
    |--------------------------------------------------------------------------
    | GENERALES (logueados)
    |--------------------------------------------------------------------------
    */

    // PERSONAS
    Route::post('personas/buscar', [PersonaController::class, 'buscar']);
    Route::get('personas/{id}', [PersonaController::class, 'show'])->whereNumber('id');
    Route::get('personas', [PersonaController::class, 'index']);
    Route::post('personas', [PersonaController::class, 'store']);
    Route::put('personas/{id}', [PersonaController::class, 'update']);
    Route::delete('personas/{id}', [PersonaController::class, 'destroy']);

    // CATÁLOGOS
    Route::get('claustros', [ClaustroController::class, 'index']);
    Route::get('facultad', [FacultadController::class, 'index']);
    Route::get('sede', [SedeController::class, 'index']);
    Route::get('sede/facultad/{id}', [SedeController::class,'porFacultad']);

    // INSCRIPCIONES
    Route::get('inscripciones', [InscripcionController::class, 'index']);
    Route::get('inscripciones/{id}', [InscripcionController::class, 'show']);

    // PADRONES
    Route::get('padrones', [PadronController::class, 'index']);
    Route::get('padrones/{id}', [PadronController::class, 'show']);
    Route::get('padrones/{id}/personas', [PadronController::class, 'personas']);
    Route::get('padrones/resumen', [PadronController::class, 'resumen']);

    // LISTAS
    Route::get('listas', [ListaController::class, 'index']);
    Route::get('listas/{id}', [ListaController::class, 'show']);

    // OTROS
    Route::get('catalogos', [CatalogoController::class, 'index']);
});
