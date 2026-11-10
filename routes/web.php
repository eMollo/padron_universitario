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
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ListaViewController;
use App\Http\Controllers\ProclamadosController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\ReunionViewController;

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
| VISTAS (Blade) — requieren auth
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | DASHBOARD PRINCIPAL — todos los roles
    |----------------------------------------------------------------------
    */
    Route::get('/', fn() => view('dashboard.index'))->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | ELECCIONES — todos los roles (solo lectura)
    |----------------------------------------------------------------------
    */
    Route::get('/padrones', fn() => view('padrones.index'))
        ->name('padrones.index');
    Route::get('/padrones/{id}/personas', fn($id) => view('padrones.personas', compact('id')));
    Route::get('/padrones/metricas', fn() => view('padrones.metricas'));
    Route::get('/personas/buscar', fn() => view('personas.buscar'));

    // Listas — lectura para todos
    Route::get('/listas', [ListaViewController::class, 'index'])
        ->name('listas.index');
    Route::get('/listas/{id}', [ListaViewController::class, 'ver'])
        ->whereNumber('id')
        ->name('listas.ver');

    /*
    |----------------------------------------------------------------------
    | ASISTENCIAS — admin + consejo_superior
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin,consejo_superior'])->group(function () {

        // Vistas
        Route::get('/asistencia', [ReunionViewController::class, 'index'])
            ->name('asistencia.index');
        Route::get('/asistencia/crear', [ReunionViewController::class, 'crear'])
            ->name('asistencia.crear');
        Route::get('/asistencia/historial', [ReunionViewController::class, 'historial'])
            ->name('asistencia.historial');
        Route::get('/asistencia/{id}', [ReunionViewController::class, 'sala'])
            ->whereNumber('id')
            ->name('asistencia.sala');
        Route::get('/asistencia/{id}/detalle', [ReunionViewController::class, 'detalle'])
            ->whereNumber('id')
            ->name('asistencia.detalle');
        Route::get('/asistencia/{id}/qr', [ReunionViewController::class, 'qr'])
            ->whereNumber('id')
            ->name('asistencia.qr');
    });

    /*
    |----------------------------------------------------------------------
    | SOLO ADMIN — vistas de gestión
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        // Padrones
        Route::get('/padrones/importar', fn() => view('padrones.importar'));
        Route::get('/admin/comparador', [AdminController::class, 'comparador']);
        Route::get('/admin/comparador/bajas', fn() => view('admin.padron-bajas'));
        Route::get('/admin/sedes', fn() => view('admin.sedes'));

        // Listas — gestión
        Route::get('/listas/crear', [ListaViewController::class, 'crear'])
            ->name('listas.crear');
        Route::get('/listas/{id}/editar', [ListaViewController::class, 'editar'])
            ->whereNumber('id')
            ->name('listas.editar');
        Route::get('/proclamados', fn() => view('listas.proclamados'))
            ->name('listas.proclamados');
        Route::get('/listas-eliminadas', [ListaViewController::class, 'eliminadas'])
            ->name('listas.eliminadas');
        Route::get('/listas-eliminadas/{id}', [ListaViewController::class, 'verEliminada'])
            ->whereNumber('id')
            ->name('listas.ver-eliminada');
    });

});

/*
|--------------------------------------------------------------------------
| API INTERNA (con sesión) — requieren auth
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('api')->group(function () {

    /*
    |----------------------------------------------------------------------
    | SOLO ADMIN
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        // Padrones
        Route::post('padrones/importar', [PadronController::class, 'importar']);
        Route::delete('padrones/{id}', [PadronController::class, 'destroy']);
        Route::post('padrones/previsualizar-baja', [PadronController::class, 'previsualizarBaja']);
        Route::post('padrones/baja-masiva', [PadronController::class, 'bajaMasiva']);
        Route::post('padrones/buscar', [PadronBusquedaController::class, 'buscar']);
        Route::post('padrones/{id}/agregar-persona', [PadronController::class, 'agregarPersona']);

        // Inscripciones
        Route::delete('inscripciones/{id}', [InscripcionController::class, 'destroy']);
        Route::post('inscripciones/{id}/restaurar', [InscripcionController::class, 'restaurar']);
        Route::put('inscripciones/{id}', [InscripcionController::class, 'update']);

        // Personas
        Route::put('personas/{id}', [PersonaController::class, 'update']);

        // Comparador
        Route::post('comparador/buscar', [PadronComparadorController::class, 'buscar']);
        Route::post('comparador/comparar', [PadronComparadorController::class, 'comparar']);
        Route::post('comparador/baja-inscripcion', [PadronComparadorController::class, 'bajaInscripcion']);
        Route::get('comparador/bajas', [PadronComparadorController::class, 'bajas']);
        Route::post('comparador/export-bajas', [PadronExportController::class, 'exportBajas']);

        // Sedes
        Route::get('/sedes', [SedeController::class, 'index']);
        Route::post('/sedes', [SedeController::class, 'store']);
        Route::delete('/sedes/{id}', [SedeController::class, 'destroy']);

        // Listas — gestión
        Route::post('listas', [ListaController::class, 'store']);
        Route::put('listas/{id}', [ListaController::class, 'update']);
        Route::delete('listas/{id}', [ListaController::class, 'destroy']);
        Route::post('listas/{idLista}/avales/importar', [AvalController::class, 'importar']);
        Route::get('listas/numeros-disponibles', [ListaController::class, 'numerosDisponibles']);
        Route::get('listas-eliminadas', [ListaController::class, 'indexEliminadas']);
        Route::get('listas-eliminadas/{id}', [ListaController::class, 'showEliminada']);

        // Proclamados
        Route::patch('listas/{id}/estado', [ProclamadosController::class, 'cambiarEstado']);
        Route::post('proclamados/por-tipo', [ProclamadosController::class, 'porTipo']);
        Route::post('proclamados/{id}/bancas', [ProclamadosController::class, 'asignarBancas']);

        // Asistencia — solo admin: gestionar tipos de comisión
        Route::post('tipos-reunion', [ReunionController::class, 'storeTipoReunion']);
    });

    /*
    |----------------------------------------------------------------------
    | ADMIN + CONSEJO_SUPERIOR — control de asistencias
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin,consejo_superior'])->group(function () {

        // Reuniones — crear, cerrar, gestionar
        Route::post('reuniones', [ReunionController::class, 'store']);
        Route::post('reuniones/{id}/cerrar', [ReunionController::class, 'cerrar']);
        Route::post('reuniones/traspasar-presentes', [ReunionController::class, 'traspasarPresentes']);

        // Movimientos de asistencia
        Route::post('reuniones/{id}/movimiento', [ReunionController::class, 'registrarMovimiento']);
        Route::post('reuniones/{id}/salida-masiva', [ReunionController::class, 'salidaMasiva']);
        Route::post('reuniones/{id}/qr', [ReunionController::class, 'registrarQr']);

        // Orden de palabra
        Route::post('reuniones/{id}/orden-palabra', [ReunionController::class, 'pedirPalabra']);
        Route::patch('reuniones/{id}/orden-palabra/{turnoId}/atender', [ReunionController::class, 'atenderPalabra']);

        // Votaciones nominales
        Route::post('reuniones/{id}/votaciones', [ReunionController::class, 'storeVotacion']);
        Route::post('reuniones/{id}/votaciones/{votacionId}/votos', [ReunionController::class, 'registrarVoto']);
    });

    /*
    |----------------------------------------------------------------------
    | ADMIN + CONSULTA — exportaciones
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin,consulta'])->group(function () {
        Route::post('padrones/export-filtrado', [PadronExportController::class, 'exportFiltrado']);
        Route::post('comparador/export', [PadronExportController::class, 'exportComparador']);
        Route::get('padrones/{id}/export', [PadronExportController::class, 'export']);
    });

    /*
    |----------------------------------------------------------------------
    | TODOS LOS ROLES AUTENTICADOS — lectura general
    |----------------------------------------------------------------------
    */

    // Métricas
    Route::get('metricas', [PadronMetricasController::class, 'index']);
    Route::post('metricas/recalcular', [PadronMetricasController::class, 'recalcular']);

    // Personas
    Route::post('personas/buscar', [PersonaController::class, 'buscar']);
    Route::get('personas/{id}', [PersonaController::class, 'show'])->whereNumber('id');
    Route::get('personas', [PersonaController::class, 'index']);
    Route::post('personas', [PersonaController::class, 'store']);
    Route::delete('personas/{id}', [PersonaController::class, 'destroy']);

    // Catálogos
    Route::get('claustros', [ClaustroController::class, 'index']);
    Route::get('facultad', [FacultadController::class, 'index']);
    Route::get('sede', [SedeController::class, 'index']);
    Route::get('sede/facultad/{id}', [SedeController::class, 'porFacultad']);
    Route::get('catalogos', [CatalogoController::class, 'index']);

    // Inscripciones
    Route::get('inscripciones', [InscripcionController::class, 'index']);
    Route::get('inscripciones/{id}', [InscripcionController::class, 'show']);

    // Padrones
    Route::get('padrones', [PadronController::class, 'index']);
    Route::get('padrones/{id}', [PadronController::class, 'show']);
    Route::get('padrones/{id}/personas', [PadronController::class, 'personas']);
    Route::get('padrones/resumen', [PadronController::class, 'resumen']);

    // Listas
    Route::get('listas', [ListaController::class, 'index']);
    Route::get('listas/{id}', [ListaController::class, 'show']);

    // Asistencias — lectura para todos
    Route::get('tipos-reunion', [ReunionController::class, 'tiposReunion']);
    Route::get('reuniones', [ReunionController::class, 'index']);
    Route::get('reuniones/{id}', [ReunionController::class, 'show']);
    Route::get('reuniones/{id}/participantes', [ReunionController::class, 'participantes']);
    Route::get('reuniones/{id}/orden-palabra', [ReunionController::class, 'ordenPalabra']);
    Route::get('reuniones/{id}/votaciones', [ReunionController::class, 'votaciones']);
});