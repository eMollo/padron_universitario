<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route; // ¡Asegúrate de tener esta línea!

class RouteServiceProvider extends ServiceProvider
{
    // ... (resto del código del RouteServiceProvider)

    public function boot(): void
    {
        // Esta función 'configureRateLimiting' SÍ pertenece aquí
        $this->configureRateLimiting();

        $this->routes(function () {
            // Rutas de la API (Soluciona tu problema de visibilidad)
            Route::prefix('api')
                ->middleware('api')
                // ->namespace($this->namespace) // Puedes comentar o eliminar si tu Laravel es moderno
                ->group(base_path('routes/api.php'));

            // Rutas Web
            Route::middleware('web')
                // ->namespace($this->namespace) // Puedes comentar o eliminar
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     * Si esta función no existe en tu archivo, también debes agregarla
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

}