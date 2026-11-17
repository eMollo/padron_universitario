<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\SesionesUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Administración de usuarios (solo admin).
 *
 * Las contraseñas se pasan en texto plano al modelo: el cast 'hashed' de User
 * las encripta al asignarlas, por eso no se usa Hash::make acá.
 */
class UsuarioController extends Controller
{
    private const MENSAJES = [
        'name.required'     => 'Ingresá el nombre.',
        'name.max'          => 'El nombre es demasiado largo.',
        'user.required'     => 'Ingresá el nombre de usuario.',
        'user.min'          => 'El usuario debe tener al menos 3 caracteres.',
        'user.max'          => 'El usuario es demasiado largo.',
        'user.regex'        => 'El usuario solo puede tener letras, números, punto, guion y guion bajo.',
        'user.unique'       => 'Ese nombre de usuario ya existe.',
        'password.required' => 'Ingresá la contraseña.',
        'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
        'password.max'      => 'La contraseña es demasiado larga.',
        'role_id.required'  => 'Seleccioná un rol.',
        'role_id.exists'    => 'El rol seleccionado no existe.',
    ];

    // Listado de usuarios + catálogo de roles
    public function index(): JsonResponse
    {
        $usuarios = User::with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn(User $u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'user'       => $u->user,
                'is_active'  => (bool) $u->is_active,
                'role_id'    => $u->roles->first()?->id,
                'roles'      => $u->roles->pluck('name')->values(),
                'created_at' => $u->created_at,
            ]);

        return response()->json([
            'usuarios' => $usuarios,
            'roles'    => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // Crear usuario
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'user'      => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'user')],
            'password'  => ['required', 'string', 'min:8', 'max:72'],
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
        ], self::MENSAJES);

        $usuario = DB::transaction(function () use ($datos) {
            $nuevo = User::create([
                'name'      => $datos['name'],
                'user'      => $datos['user'],
                'password'  => $datos['password'],
                'is_active' => $datos['is_active'] ?? true,
            ]);

            $nuevo->roles()->sync([$datos['role_id']]);

            return $nuevo;
        });

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'id'      => $usuario->id,
        ], 201);
    }

    // Editar nombre, usuario y rol
    public function update(Request $request, $id): JsonResponse
    {
        $usuario = User::with('roles:id,name')->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $datos = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'user'    => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'user')->ignore($usuario->id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ], self::MENSAJES);

        $esYo      = (int) $usuario->id === (int) auth()->id();
        $rolActual = $usuario->roles->first()?->id;

        // Un admin no puede cambiarse el rol a sí mismo (evita quedarse sin acceso)
        if ($esYo && (int) $datos['role_id'] !== (int) $rolActual) {
            return response()->json(['message' => 'No podés cambiar tu propio rol.'], 422);
        }

        DB::transaction(function () use ($usuario, $datos, $esYo) {
            $usuario->update([
                'name' => $datos['name'],
                'user' => $datos['user'],
            ]);

            if (!$esYo) {
                $usuario->roles()->sync([$datos['role_id']]);
            }
        });

        return response()->json(['message' => 'Usuario actualizado correctamente']);
    }

    // Activar / desactivar
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $request->validate(['is_active' => ['required', 'boolean']]);

        $activo = $request->boolean('is_active');

        if ((int) $usuario->id === (int) auth()->id() && !$activo) {
            return response()->json(['message' => 'No podés desactivarte a vos mismo.'], 422);
        }

        $usuario->is_active = $activo;
        $usuario->save();

        if (!$activo) {
            SesionesUsuario::cerrar($usuario->id);
        }

        return response()->json([
            'message'   => $activo ? 'Usuario activado' : 'Usuario desactivado',
            'is_active' => $activo,
        ]);
    }

    // Cambiar la contraseña de otro usuario
    public function resetPassword(Request $request, $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // La propia contraseña se cambia desde "Mi cuenta" (pide la contraseña actual)
        if ((int) $usuario->id === (int) auth()->id()) {
            return response()->json(['message' => 'Para cambiar tu propia contraseña usá «Mi cuenta».'], 422);
        }

        $datos = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ], self::MENSAJES);

        $usuario->forceFill([
            'password'       => $datos['password'],
            'remember_token' => Str::random(60),
        ])->save();

        // Obliga al usuario a volver a entrar con la contraseña nueva
        SesionesUsuario::cerrar($usuario->id);

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }
}
