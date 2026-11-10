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

// Pre-llenar desde query params si viene de la grilla
const params = new URLSearchParams(window.location.search);
if (params.get('tipo')) {
    document.getElementById('tipo').value = params.get('tipo');
    document.getElementById('tipo').dispatchEvent(new Event('change'));
}

// Fecha de hoy por defecto
document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
// Hora actual
const ahora = new Date();
document.getElementById('hora_inicio').value =
    String(ahora.getHours()).padStart(2,'0') + ':' + String(ahora.getMinutes()).padStart(2,'0');

// Mostrar selector de comisión
document.getElementById('tipo').addEventListener('change', async function () {
    const esComision = this.value === 'comision';
    document.getElementById('cont-tipo-comision').style.display = esComision ? 'block' : 'none';
    if (esComision) await cargarTiposComision();

    // Nombre automático
    const nombres = {
        sesion:   'Sesión Ordinaria',
        asamblea: 'Asamblea Universitaria',
        comision: '',
    };
    if (nombres[this.value] !== undefined && !document.getElementById('nombre').value) {
        document.getElementById('nombre').value = nombres[this.value];
    }
});

// Si viene id_tipo_reunion desde query param, seleccionarlo
async function cargarTiposComision() {
    const tipos = await apiRequest('/api/tipos-reunion');
    const sel   = document.getElementById('id_tipo_reunion');
    sel.innerHTML = '<option value="">Seleccionar...</option>';
    tipos.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.nombre;
        if (params.get('id_tipo_reunion') == t.id) opt.selected = true;
        sel.appendChild(opt);
    });

    // Auto-nombre de comisión
    sel.addEventListener('change', function () {
        const texto = this.options[this.selectedIndex]?.text ?? '';
        document.getElementById('nombre').value = texto ? `Comisión ${texto}` : '';
    });
    sel.dispatchEvent(new Event('change'));
}

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
            `<div class="alert alert-danger">Error al crear la reunión.</div>`;
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
        msg.innerHTML = `<span class="text-success">✓ Comisión "${nombre}" agregada.</span>`;
        document.getElementById('nuevaComision').value = '';
        await cargarTiposComision();
    } catch {
        msg.innerHTML = `<span class="text-danger">Error al agregar la comisión.</span>`;
    }
}

</script>

@endsection