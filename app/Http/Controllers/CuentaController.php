<?php

namespace App\Http\Controllers;

use App\Support\SesionesUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Mi cuenta": cada usuario (cualquier rol) cambia su propia contraseña.
 */
class CuentaController extends Controller
{
    public function cambiarPassword(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'password_actual' => ['required', 'current_password'],
            'password'        => ['required', 'string', 'min:8', 'max:72', 'confirmed', 'different:password_actual'],
        ], [
            'password_actual.required'         => 'Ingresá tu contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.required'                => 'Ingresá la contraseña nueva.',
            'password.min'                     => 'La contraseña nueva debe tener al menos 8 caracteres.',
            'password.max'                     => 'La contraseña nueva es demasiado larga.',
            'password.confirmed'               => 'La confirmación no coincide con la contraseña nueva.',
            'password.different'               => 'La contraseña nueva debe ser distinta de la actual.',
        ]);

        $usuario = $request->user();

        $usuario->forceFill([
            'password'       => $datos['password'],
            'remember_token' => Str::random(60),
        ])->save();

        // Cierra las demás sesiones abiertas de este usuario (conserva la actual)
        SesionesUsuario::cerrar($usuario->id, $request->session()->getId());

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }
}
