<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SesionesUsuario
{
    /**
     * Cierra las sesiones abiertas de un usuario.
     *
     * Solo funciona si las sesiones se guardan en la base de datos
     * (SESSION_DRIVER=database). Con otro driver no hay forma de ubicar
     * las sesiones de un usuario; en ese caso el middleware EnsureUserIsActive
     * se encarga de bloquear a los usuarios desactivados en su próxima acción.
     *
     * $excepto: id de una sesión a conservar (ej: la actual, al cambiar la propia contraseña).
     */
    public static function cerrar(int $idUsuario, ?string $excepto = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $query = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $idUsuario);

        if ($excepto !== null) {
            $query->where('id', '!=', $excepto);
        }

        $query->delete();
    }
}