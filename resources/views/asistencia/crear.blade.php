@extends('layouts.app')

@section('content')

<div class="container">

    <div class="mb-4">
        <a href="{{ route('asistencia.index') }}" class="text-muted text-decoration-none small">
            ← Volver
        </a>
        <h3 class="mb-0 mt-1">Nueva Reunión</h3>
    </div>

    <div id="mensaje" class="mb-3"></div>

    <div class="card">
        <div class="card-body">
            <form id="formCrear">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Tipo de reunión</label>
                        <select class="form-select" id="tipo" required>
                            <option value="">Seleccionar...</option>
                            <option value="sesion">Sesión</option>
                            <option value="comision">Comisión</option>
                            <option value="asamblea">Asamblea</option>
                        </select>
                    </div>

                    <div class="col-md-4" id="cont-tipo-comision" style="display:none;">
                        <label class="form-label">Comisión</label>
                        <select class="form-select" id="id_tipo_reunion">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre"
                            placeholder="Ej: 9ª Sesión Ordinaria" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="fecha" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Hora inicio</label>
                        <input type="time" class="form-control" id="hora_inicio">
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnCrear">
                        Abrir reunión
                    </button>
                    <a href="{{ route('asistencia.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- Nueva comisión — solo admin puede agregar tipos al catálogo --}}
    @if(auth()->user()?->hasRole('admin'))
    <div class="card mt-4">
        <div class="card-header fw-semibold">Agregar nueva comisión al catálogo</div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <input type="text" class="form-control" id="nuevaComision"
                    placeholder="Nombre de la comisión" maxlength="100">
                <button class="btn btn-outline-success" onclick="agregarComision()">
                    Agregar
                </button>
            </div>
            <div id="msg-comision" class="mt-2 small"></div>
        </div>
    </div>
    @endif

</div>

<script>

// ============================================================
// ELEMENTOS Y ESTADO
// ============================================================
const tipoSel     = document.getElementById('tipo');
const comisionSel = document.getElementById('id_tipo_reunion');
const nombreInput = document.getElementById('nombre');
const params      = new URLSearchParams(window.location.search);

// Comisión que viene preseleccionada desde los íconos (se usa solo la primera vez)
let idComisionInicial = params.get('id_tipo_reunion');

// Si el usuario escribe el nombre a mano, dejamos de pisárselo
let nombreManual = false;
nombreInput.addEventListener('input', () => {
    nombreManual = nombreInput.value.trim() !== '';
});

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

// ============================================================
// FECHA Y HORA POR DEFECTO (hora local, no UTC)
// ============================================================
const ahora = new Date();
document.getElementById('fecha').value =
    `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}-${String(ahora.getDate()).padStart(2, '0')}`;
document.getElementById('hora_inicio').value =
    String(ahora.getHours()).padStart(2, '0') + ':' + String(ahora.getMinutes()).padStart(2, '0');

// ============================================================
// NOMBRE AUTOMÁTICO (igual venga de un ícono o del botón)
// ============================================================
function actualizarNombreAutomatico() {
    if (nombreManual) return;

    let nombre = '';

    if (tipoSel.value === 'sesion') {
        nombre = 'Sesión Ordinaria';
    } else if (tipoSel.value === 'asamblea') {
        nombre = 'Asamblea Universitaria';
    } else if (tipoSel.value === 'comision' && comisionSel.value) {
        nombre = `Comisión ${comisionSel.options[comisionSel.selectedIndex].text}`;
    }

    nombreInput.value = nombre;
}

// ============================================================
// TIPO DE REUNIÓN Y COMISIONES
// ============================================================
async function alCambiarTipo() {
    const esComision = tipoSel.value === 'comision';

    document.getElementById('cont-tipo-comision').style.display = esComision ? 'block' : 'none';
    comisionSel.required = esComision;

    if (esComision) {
        try {
            await cargarTiposComision();
        } catch (e) {
            console.error(e);
            document.getElementById('mensaje').innerHTML =
                '<div class="alert alert-danger">No se pudieron cargar las comisiones.</div>';
        }
    }

    actualizarNombreAutomatico();
}

async function cargarTiposComision() {
    const tipos = await apiRequest('/api/tipos-reunion');

    // Conserva lo elegido si se recarga (ej: al agregar una comisión al catálogo);
    // la primera vez usa la que viene preseleccionada desde los íconos.
    const seleccionada = comisionSel.value || idComisionInicial || '';
    idComisionInicial = null;

    comisionSel.innerHTML = '<option value="">Seleccionar...</option>';
    tipos.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.nombre;
        comisionSel.appendChild(opt);
    });

    comisionSel.value = seleccionada;
    if (comisionSel.selectedIndex === -1) comisionSel.selectedIndex = 0;
}

// Los manejadores se registran UNA sola vez
tipoSel.addEventListener('change', alCambiarTipo);
comisionSel.addEventListener('change', actualizarNombreAutomatico);

// Si se llegó desde un ícono (?tipo=...&id_tipo_reunion=...), se aplica ahora que
// los manejadores ya existen: así queda igual que elegirlo a mano en el formulario.
(async function inicializar() {
    const tipoParam = params.get('tipo');
    if (['sesion', 'comision', 'asamblea'].includes(tipoParam)) {
        tipoSel.value = tipoParam;
    }
    await alCambiarTipo();
})();

// Submit
document.getElementById('formCrear').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnCrear');
    btn.disabled = true;

    const tipo = document.getElementById('tipo').value;

    try {
        const reunion = await apiRequest('/api/reuniones', 'POST', {
            tipo,
            id_tipo_reunion: tipo === 'comision'
                ? (document.getElementById('id_tipo_reunion').value || null)
                : null,
            nombre:      document.getElementById('nombre').value,
            fecha:       document.getElementById('fecha').value,
            hora_inicio: document.getElementById('hora_inicio').value || null,
        });

        // Redirigir directo a la sala
        window.location.href = `/asistencia/${reunion.id}`;

    } catch (err) {
        document.getElementById('mensaje').innerHTML =
            `<div class="alert alert-danger">No se pudo crear la reunión: ${esc(mensajeError(err))}</div>`;
        console.error(err);
        btn.disabled = false;
    }
});

// Agregar comisión al catálogo
async function agregarComision() {
    const nombre = document.getElementById('nuevaComision').value.trim();
    const msg    = document.getElementById('msg-comision');
    if (!nombre) return;

    try {
        await apiRequest('/api/tipos-reunion', 'POST', { nombre });
        msg.innerHTML = `<span class="text-success">✓ Comisión "${esc(nombre)}" agregada.</span>`;
        document.getElementById('nuevaComision').value = '';
        await cargarTiposComision();
    } catch {
        msg.innerHTML = `<span class="text-danger">Error al agregar la comisión.</span>`;
    }
}

</script>

@endsection