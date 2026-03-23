<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function login(Request $request)
{
    $credentials = $request->validate([
        'user'     => 'required|string',
        'password' => 'required|string',
    ]);

    if (!Auth::attempt([
        'user' => $credentials['user'],
        'password' => $credentials['password'],
        'is_active' => 1
    ])) {
        return response()->json([
            'message' => 'Credenciales inválidas'
        ], 401);
    }

    $request->session()->regenerate();

    return response()->json([
        'success' => true,
        'user' => auth()->user()->only('id','name','user')
    ]);
}

    public function logout(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['success' => true]);
}
}
