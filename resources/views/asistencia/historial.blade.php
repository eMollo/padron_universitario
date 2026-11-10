@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('asistencia.index') }}" class="text-muted text-decoration-none small">
                ← Volver
            </a>
            <h3 class="mb-0 mt-1">Historial de Reuniones</h3>
        </div>
        @if(auth()->user()?->hasAnyRole(['admin','consejo_superior']))
        <a href="{{ route('asistencia.crear') }}" class="btn btn-primary btn-sm">
            + Nueva reunión
        </a>
        @endif
    </div>

    {{-- FILTROS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <label class="form-label">Año</label>
            <input type="number" id="filtroAnio" class="form-control"
                value="{{ date('Y') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select id="filtroTipo" class="form-select">
                <option value="">Todos</option>
                <option value="sesion">Sesión</option>
                <option value="comision">Comisión</option>
                <option value="asamblea">Asamblea</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" onclick="cargarHistorial()">
                Filtrar
            </button>
        </div>
    </div>

    <div id="estado-carga" class="text-muted" style="display:none;">Cargando...</div>
    <div id="resultado"></div>

</div>

<script>

const ETIQUETAS_TIPO = {
    sesion:   'Sesión',
    comision: 'Comisión',
    asamblea: 'Asamblea',
};

const COLORES_TIPO = {
    sesion:   'primary',
    comision: 'success',
    asamblea: 'warning',
};

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

function formatearFecha(fecha) {
    if (!fecha) return '—';
    const [y, m, d] = String(fecha).substring(0, 10).split('-');
    return `${d}/${m}/${y}`;
}

async function cargarHistorial() {
    const anio = document.getElementById('filtroAnio').value;
    const tipo = document.getElementById('filtroTipo').value;

    const carga = document.getElementById('estado-carga');
    carga.style.display = 'block';
    document.getElementById('resultado').innerHTML = '';

    try {
        const params = new URLSearchParams();
        if (anio) params.append('fecha_desde', `${anio}-01-01`);
        if (tipo)  params.append('tipo', tipo);

        const data    = await apiRequest(`/api/reuniones?${params}`);
        const reunion = data.data ?? data;

        // Separar abiertas y cerradas
        const abiertas = reunion.filter(r => r.estado === 'abierta');
        const cerradas = reunion.filter(r => r.estado === 'cerrada');

        carga.style.display = 'none';
        renderResultado(abiertas, cerradas);

    } catch (e) {
        carga.textContent = 'Error al cargar el historial.';
        console.error(e);
    }
}

function renderResultado(abiertas, cerradas) {
    const cont = document.getElementById('resultado');
    let html   = '';

    // Reuniones abiertas arriba
    if (abiertas.length > 0) {
        html += `
            <h5 class="text-success mb-3">🟢 En curso</h5>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Inicio</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${abiertas.map(r => filaReunion(r, true)).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    if (cerradas.length === 0 && abiertas.length === 0) {
        cont.innerHTML = `<p class="text-muted">No hay reuniones para los filtros seleccionados.</p>`;
        return;
    }

    if (cerradas.length > 0) {
        html += `
            <h5 class="text-muted mb-3">Cerradas</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${cerradas.map(r => filaReunion(r, false)).join('')}
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mt-2">
                Total: ${cerradas.length} reunión${cerradas.length !== 1 ? 'es' : ''} cerrada${cerradas.length !== 1 ? 's' : ''}
            </p>
        `;
    }

    cont.innerHTML = html;
}

function filaReunion(r, abierta) {
    const color    = COLORES_TIPO[r.tipo] ?? 'secondary';
    const etiqueta = ETIQUETAS_TIPO[r.tipo] ?? esc(r.tipo);
    const subtipo  = r.tipo_reunion?.nombre ? esc(r.tipo_reunion.nombre) : '';

    return `
        <tr>
            <td>
                <span class="fw-semibold">${esc(r.nombre)}</span>
                ${subtipo ? `<span class="text-muted small d-block">${subtipo}</span>` : ''}
            </td>
            <td>
                <span class="badge bg-${color}">${etiqueta}</span>
            </td>
            <td>${formatearFecha(r.fecha)}</td>
            <td class="text-muted">${r.hora_inicio ?? '—'}</td>
            ${!abierta ? `<td class="text-muted">${r.hora_fin ?? '—'}</td>` : ''}
            <td class="text-end">
                ${abierta
                    ? `<a href="/asistencia/${r.id}" class="btn btn-sm btn-success">
                           Ingresar →
                       </a>`
                    : `<a href="/asistencia/${r.id}/detalle" class="btn btn-sm btn-outline-secondary">
                           Ver detalle
                       </a>`
                }
            </td>
        </tr>
    `;
}

cargarHistorial();

</script>

@endsection