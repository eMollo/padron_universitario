@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Usuarios</h3>
            <span class="text-muted small">Altas, roles, contraseñas y acceso al sistema</span>
        </div>
        <button class="btn btn-primary" onclick="abrirNuevo()">+ Nuevo usuario</button>
    </div>

    <div id="mensaje"></div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-usuarios">
                        <tr><td colspan="5" class="text-muted text-center py-4">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-light border small mt-4 mb-0">
        <strong>Qué puede hacer cada rol</strong>
        <ul class="mb-0 mt-1">
            <li><strong>Administrador:</strong> todo el sistema.</li>
            <li><strong>Consulta:</strong> solo lectura de elecciones (padrones, personas y listas).</li>
            <li><strong>Consejo Superior:</strong> lo mismo que Consulta, y además controla las asistencias
                (abrir y cerrar reuniones, marcar presentes, orden de palabra y votaciones).</li>
        </ul>
        <div class="mt-2 text-muted">
            Un usuario desactivado no puede entrar; si tenía una sesión abierta, se le cierra en su próxima acción.
        </div>
    </div>

</div>


{{-- ================================ --}}
{{-- MODAL: crear / editar usuario --}}
{{-- ================================ --}}
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="titulo-modal-usuario">Nuevo usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div id="vista-form-usuario">
                <div class="modal-body">
                    <div id="error-usuario" class="alert alert-danger py-2 small" style="display:none;"></div>

                    <div class="mb-3">
                        <label class="form-label" for="u-name">Nombre completo</label>
                        <input type="text" class="form-control" id="u-name" maxlength="100" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="u-user">Usuario (para iniciar sesión)</label>
                        <input type="text" class="form-control" id="u-user" maxlength="50" autocomplete="off">
                        <div class="form-text">Letras, números, punto, guion y guion bajo. Sin espacios.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="u-rol">Rol</label>
                        <select class="form-select" id="u-rol"></select>
                        <div id="u-aviso-roles" class="form-text text-warning" style="display:none;"></div>
                    </div>

                    <div id="grupo-password">
                        <label class="form-label" for="u-password">Contraseña inicial</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="u-password" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="generarEnInput('u-password')">Generar</button>
                        </div>
                        <div class="form-text">Mínimo 8 caracteres. Después de guardar vas a poder copiarla.</div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="u-activo" checked>
                            <label class="form-check-label" for="u-activo">Usuario activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-guardar-usuario" onclick="guardarUsuario()">
                        Guardar
                    </button>
                </div>
            </div>

            <div id="vista-credenciales-usuario" style="display:none;">
                <div class="modal-body" id="cuerpo-credenciales-usuario"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Listo</button>
                </div>
            </div>

        </div>
    </div>
</div>


{{-- ================================ --}}
{{-- MODAL: cambiar contraseña --}}
{{-- ================================ --}}
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="titulo-modal-password">Cambiar contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div id="vista-form-password">
                <div class="modal-body">
                    <div id="error-password" class="alert alert-danger py-2 small" style="display:none;"></div>

                    <label class="form-label" for="p-password">Contraseña nueva</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="p-password" autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="generarEnInput('p-password')">Generar</button>
                    </div>
                    <div class="form-text">
                        Mínimo 8 caracteres. Al guardar, se cierran las sesiones abiertas de este usuario.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="btn-guardar-password" onclick="guardarPassword()">
                        Cambiar contraseña
                    </button>
                </div>
            </div>

            <div id="vista-credenciales-password" style="display:none;">
                <div class="modal-body" id="cuerpo-credenciales-password"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Listo</button>
                </div>
            </div>

        </div>
    </div>
</div>


<script>

const YO_ID = {{ auth()->id() }};

const ETIQUETAS_ROL = {
    admin:            'Administrador',
    consulta:         'Consulta',
    consejo_superior: 'Consejo Superior',
};

let usuarios = [];
let roles    = [];
let editandoId       = null;   // null = creando
let passwordUsuarioId = null;
let credencialesActuales = null;
let modalUsuario  = null;
let modalPassword = null;

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

function etiquetaRol(nombre) {
    return ETIQUETAS_ROL[nombre] ?? nombre;
}

// Arma un mensaje legible a partir de un error de apiRequest (422 con errores o mensaje simple)
function mensajeError(err) {
    const errores = err?.data?.errors;
    if (errores) return Object.values(errores).flat().join(' ');
    return err?.data?.message ?? err?.message ?? 'Ocurrió un error inesperado.';
}

function aviso(texto, tipo = 'success') {
    const cont = document.getElementById('mensaje');
    cont.innerHTML = `<div class="alert alert-${tipo} py-2">${esc(texto)}</div>`;
    clearTimeout(cont._t);
    cont._t = setTimeout(() => { cont.innerHTML = ''; }, 6000);
}

// ============================================================
// CONTRASEÑAS
// ============================================================

// Sin caracteres ambiguos (0/O, 1/l/I) para que se pueda dictar o copiar sin errores
function generarPassword(largo = 10) {
    const alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    const bytes = new Uint32Array(largo);
    crypto.getRandomValues(bytes);
    return Array.from(bytes, b => alfabeto[b % alfabeto.length]).join('');
}

function generarEnInput(idInput) {
    document.getElementById(idInput).value = generarPassword();
}

async function copiarTexto(texto) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(texto);
            return true;
        }
    } catch (e) { /* cae al método alternativo */ }

    // Alternativa para http sin contexto seguro
    const ta = document.createElement('textarea');
    ta.value = texto;
    ta.style.position = 'fixed';
    ta.style.opacity  = '0';
    document.body.appendChild(ta);
    ta.select();
    let ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    document.body.removeChild(ta);
    return ok;
}

async function copiarCredenciales(btn) {
    if (!credencialesActuales) return;
    const texto = `Usuario: ${credencialesActuales.usuario}\nContraseña: ${credencialesActuales.password}`;
    const ok = await copiarTexto(texto);
    btn.textContent = ok ? '✓ Copiado' : 'No se pudo copiar: seleccionala a mano';
    setTimeout(() => { btn.textContent = '📋 Copiar usuario y contraseña'; }, 2500);
}

function htmlCredenciales(usuario, password, titulo) {
    return `
        <div class="alert alert-success py-2">${esc(titulo)}</div>
        <p class="text-muted small mb-2">
            Copiá estos datos ahora: la contraseña no se vuelve a mostrar.
        </p>
        <table class="table table-sm mb-3">
            <tbody>
                <tr>
                    <th class="text-muted fw-normal" style="width:8rem;">Usuario</th>
                    <td><code>${esc(usuario)}</code></td>
                </tr>
                <tr>
                    <th class="text-muted fw-normal">Contraseña</th>
                    <td><code>${esc(password)}</code></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="copiarCredenciales(this)">
            📋 Copiar usuario y contraseña
        </button>
    `;
}

// Al cerrar un modal no queda ninguna contraseña en pantalla ni en memoria
function limpiarSecretos() {
    credencialesActuales = null;
    ['cuerpo-credenciales-usuario', 'cuerpo-credenciales-password'].forEach(id => {
        document.getElementById(id).innerHTML = '';
    });
    document.getElementById('u-password').value = '';
    document.getElementById('p-password').value = '';
}

// ============================================================
// CARGA Y TABLA
// ============================================================

async function cargar() {
    try {
        const data = await apiRequest('/api/usuarios');
        usuarios = data.usuarios;
        roles    = data.roles;
        renderTabla();
    } catch (e) {
        console.error(e);
        document.getElementById('tabla-usuarios').innerHTML =
            '<tr><td colspan="5" class="text-danger text-center py-4">No se pudieron cargar los usuarios.</td></tr>';
    }
}

function renderTabla() {
    const tbody = document.getElementById('tabla-usuarios');

    if (usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-muted text-center py-4">No hay usuarios.</td></tr>';
        return;
    }

    tbody.innerHTML = usuarios.map(u => {
        const yo = u.id === YO_ID;

        const rolesHtml = u.roles.length
            ? u.roles.map(r => `<span class="badge bg-primary me-1">${esc(etiquetaRol(r))}</span>`).join('')
            : '<span class="text-muted small">sin rol</span>';

        const estado = u.is_active
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';

        const btnPassword = yo ? '' : `
            <button class="btn btn-sm btn-outline-warning" onclick="abrirPassword(${u.id})">Contraseña</button>`;

        const btnEstado = yo ? '' : `
            <button class="btn btn-sm ${u.is_active ? 'btn-outline-danger' : 'btn-outline-success'}"
                onclick="cambiarEstado(${u.id}, ${!u.is_active})">
                ${u.is_active ? 'Desactivar' : 'Activar'}
            </button>`;

        return `
            <tr class="${u.is_active ? '' : 'text-muted'}">
                <td>
                    ${esc(u.name)}
                    ${yo ? '<span class="badge bg-light text-dark border ms-1">vos</span>' : ''}
                </td>
                <td><code>${esc(u.user)}</code></td>
                <td>${rolesHtml}</td>
                <td>${estado}</td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="abrirEditar(${u.id})">Editar</button>
                    ${btnPassword}
                    ${btnEstado}
                </td>
            </tr>
        `;
    }).join('');
}

// ============================================================
// MODAL USUARIO (crear / editar)
// ============================================================

function llenarSelectRoles(seleccionado) {
    const sel = document.getElementById('u-rol');
    sel.innerHTML = roles
        .map(r => `<option value="${r.id}">${esc(etiquetaRol(r.name))}</option>`)
        .join('');
    if (seleccionado) sel.value = seleccionado;
}

function mostrarVistaUsuario(vista) {
    document.getElementById('vista-form-usuario').style.display =
        vista === 'form' ? 'block' : 'none';
    document.getElementById('vista-credenciales-usuario').style.display =
        vista === 'credenciales' ? 'block' : 'none';
}

function abrirNuevo() {
    editandoId = null;
    mostrarVistaUsuario('form');

    document.getElementById('titulo-modal-usuario').textContent = 'Nuevo usuario';
    document.getElementById('error-usuario').style.display = 'none';
    document.getElementById('u-name').value = '';
    document.getElementById('u-user').value = '';
    document.getElementById('u-activo').checked = true;
    document.getElementById('u-password').value = generarPassword();   // sugerida, se puede cambiar
    document.getElementById('grupo-password').style.display = 'block';
    document.getElementById('u-aviso-roles').style.display = 'none';
    llenarSelectRoles(null);

    modalUsuario = modalUsuario ?? new bootstrap.Modal(document.getElementById('modalUsuario'));
    modalUsuario.show();
}

function abrirEditar(id) {
    const u = usuarios.find(x => x.id === id);
    if (!u) return;

    editandoId = id;
    mostrarVistaUsuario('form');

    document.getElementById('titulo-modal-usuario').textContent = 'Editar usuario';
    document.getElementById('error-usuario').style.display = 'none';
    document.getElementById('u-name').value = u.name;
    document.getElementById('u-user').value = u.user;
    document.getElementById('grupo-password').style.display = 'none';
    llenarSelectRoles(u.role_id);

    // El rol propio no se puede cambiar
    document.getElementById('u-rol').disabled = (id === YO_ID);

    const aviso = document.getElementById('u-aviso-roles');
    if (id === YO_ID) {
        aviso.textContent = 'No podés cambiar tu propio rol.';
        aviso.style.display = 'block';
    } else if (u.roles.length > 1) {
        aviso.textContent = `Este usuario tiene varios roles (${u.roles.map(etiquetaRol).join(', ')}). ` +
                            'Al guardar va a quedar solo con el rol elegido.';
        aviso.style.display = 'block';
    } else {
        aviso.style.display = 'none';
    }

    modalUsuario = modalUsuario ?? new bootstrap.Modal(document.getElementById('modalUsuario'));
    modalUsuario.show();
}

async function guardarUsuario() {
    const errorBox = document.getElementById('error-usuario');
    const btn      = document.getElementById('btn-guardar-usuario');
    errorBox.style.display = 'none';

    const payload = {
        name:    document.getElementById('u-name').value,
        user:    document.getElementById('u-user').value,
        role_id: parseInt(document.getElementById('u-rol').value, 10),
    };

    btn.disabled = true;

    try {
        if (editandoId === null) {
            payload.password  = document.getElementById('u-password').value;
            payload.is_active = document.getElementById('u-activo').checked;

            await apiRequest('/api/usuarios', 'POST', payload);

            credencialesActuales = { usuario: payload.user, password: payload.password };
            document.getElementById('titulo-modal-usuario').textContent = 'Usuario creado';
            document.getElementById('cuerpo-credenciales-usuario').innerHTML =
                htmlCredenciales(payload.user, payload.password, 'El usuario se creó correctamente.');
            mostrarVistaUsuario('credenciales');

        } else {
            await apiRequest(`/api/usuarios/${editandoId}`, 'PUT', payload);
            modalUsuario.hide();
            aviso('Usuario actualizado.');
        }

        await cargar();

    } catch (err) {
        errorBox.textContent   = mensajeError(err);
        errorBox.style.display = 'block';
    } finally {
        btn.disabled = false;
    }
}

// ============================================================
// MODAL CONTRASEÑA
// ============================================================

function mostrarVistaPassword(vista) {
    document.getElementById('vista-form-password').style.display =
        vista === 'form' ? 'block' : 'none';
    document.getElementById('vista-credenciales-password').style.display =
        vista === 'credenciales' ? 'block' : 'none';
}

function abrirPassword(id) {
    const u = usuarios.find(x => x.id === id);
    if (!u) return;

    passwordUsuarioId = id;
    mostrarVistaPassword('form');

    document.getElementById('titulo-modal-password').textContent = `Contraseña de ${u.name}`;
    document.getElementById('error-password').style.display = 'none';
    document.getElementById('p-password').value = generarPassword();   // sugerida, se puede cambiar

    modalPassword = modalPassword ?? new bootstrap.Modal(document.getElementById('modalPassword'));
    modalPassword.show();
}

async function guardarPassword() {
    const errorBox = document.getElementById('error-password');
    const btn      = document.getElementById('btn-guardar-password');
    const password = document.getElementById('p-password').value;
    const u        = usuarios.find(x => x.id === passwordUsuarioId);

    errorBox.style.display = 'none';

    if (password.length < 8) {
        errorBox.textContent   = 'La contraseña debe tener al menos 8 caracteres.';
        errorBox.style.display = 'block';
        return;
    }

    btn.disabled = true;

    try {
        await apiRequest(`/api/usuarios/${passwordUsuarioId}/password`, 'POST', { password });

        credencialesActuales = { usuario: u.user, password };
        document.getElementById('titulo-modal-password').textContent = 'Contraseña actualizada';
        document.getElementById('cuerpo-credenciales-password').innerHTML =
            htmlCredenciales(u.user, password, 'La contraseña se cambió correctamente.');
        mostrarVistaPassword('credenciales');

    } catch (err) {
        errorBox.textContent   = mensajeError(err);
        errorBox.style.display = 'block';
    } finally {
        btn.disabled = false;
    }
}

// ============================================================
// ACTIVAR / DESACTIVAR
// ============================================================

async function cambiarEstado(id, activar) {
    const u = usuarios.find(x => x.id === id);
    if (!u) return;

    const pregunta = activar
        ? `¿Activar a ${u.name}?`
        : `¿Desactivar a ${u.name}?\n\nNo va a poder entrar al sistema y, si tiene una sesión abierta, se le va a cerrar en su próxima acción.`;

    if (!confirm(pregunta)) return;

    try {
        await apiRequest(`/api/usuarios/${id}/estado`, 'PATCH', { is_active: activar });
        await cargar();
        aviso(activar ? 'Usuario activado.' : 'Usuario desactivado.');
    } catch (err) {
        aviso(mensajeError(err), 'danger');
    }
}

// ============================================================
// ARRANQUE
// ============================================================

document.getElementById('modalUsuario').addEventListener('hidden.bs.modal', limpiarSecretos);
document.getElementById('modalPassword').addEventListener('hidden.bs.modal', limpiarSecretos);

cargar();

</script>

@endsection