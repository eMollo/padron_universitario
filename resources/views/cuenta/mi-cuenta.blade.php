@extends('layouts.app')

@section('content')

@php
    $etiquetasRol = [
        'admin'            => 'Administrador',
        'consulta'         => 'Consulta',
        'consejo_superior' => 'Consejo Superior',
    ];
    $rolesUsuario = auth()->user()->roles
        ->pluck('name')
        ->map(fn($n) => $etiquetasRol[$n] ?? $n)
        ->join(', ');
@endphp

<div class="container" style="max-width:560px;">

    <h3 class="mb-4">Mi cuenta</h3>

    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tbody>
                    <tr>
                        <th class="text-muted fw-normal" style="width:8rem;">Nombre</th>
                        <td>{{ auth()->user()->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Usuario</th>
                        <td><code>{{ auth()->user()->user }}</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Rol</th>
                        <td>{{ $rolesUsuario ?: 'Sin rol asignado' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Cambiar contraseña</div>
        <div class="card-body">

            <div id="mensaje"></div>

            <form id="formPassword" autocomplete="off">

                <div class="mb-3">
                    <label class="form-label" for="password_actual">Contraseña actual</label>
                    <input type="password" class="form-control" id="password_actual"
                        autocomplete="current-password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_nueva">Contraseña nueva</label>
                    <input type="password" class="form-control" id="password_nueva"
                        autocomplete="new-password" minlength="8" required>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmacion">Repetí la contraseña nueva</label>
                    <input type="password" class="form-control" id="password_confirmacion"
                        autocomplete="new-password" minlength="8" required>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="mostrar">
                    <label class="form-check-label" for="mostrar">Mostrar contraseñas</label>
                </div>

                <button type="submit" class="btn btn-primary" id="btn-cambiar">
                    Cambiar contraseña
                </button>

            </form>
        </div>
    </div>

</div>

<script>

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

function mensajeError(err) {
    const errores = err?.data?.errors;
    if (errores) return Object.values(errores).flat().join(' ');
    return err?.data?.message ?? err?.message ?? 'Ocurrió un error inesperado.';
}

function aviso(texto, tipo = 'success') {
    document.getElementById('mensaje').innerHTML =
        `<div class="alert alert-${tipo} py-2">${esc(texto)}</div>`;
}

const CAMPOS = ['password_actual', 'password_nueva', 'password_confirmacion'];

document.getElementById('mostrar').addEventListener('change', (e) => {
    CAMPOS.forEach(id => {
        document.getElementById(id).type = e.target.checked ? 'text' : 'password';
    });
});

document.getElementById('formPassword').addEventListener('submit', async (e) => {
    e.preventDefault();

    const actual       = document.getElementById('password_actual').value;
    const nueva        = document.getElementById('password_nueva').value;
    const confirmacion = document.getElementById('password_confirmacion').value;
    const btn          = document.getElementById('btn-cambiar');

    if (nueva !== confirmacion) {
        aviso('Las contraseñas nuevas no coinciden.', 'danger');
        return;
    }

    btn.disabled = true;

    try {
        await apiRequest('/api/mi-cuenta/password', 'POST', {
            password_actual:       actual,
            password:              nueva,
            password_confirmation: confirmacion,
        });

        aviso('Contraseña actualizada correctamente.');
        document.getElementById('formPassword').reset();
        document.getElementById('mostrar').dispatchEvent(new Event('change'));

    } catch (err) {
        aviso(mensajeError(err), 'danger');
    } finally {
        btn.disabled = false;
    }
});

</script>

@endsection