@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('listas.index') }}" class="text-muted text-decoration-none small">
                ← Volver a Listas
            </a>
            <h3 class="mb-0 mt-1">Listas Eliminadas</h3>
        </div>
    </div>

    {{-- Alerta informativa --}}
    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <span class="me-2" style="font-size:1.2rem;">⚠</span>
        <div>
            Este registro es de solo lectura. Las listas eliminadas se conservan por
            razones de <strong>trazabilidad y transparencia electoral</strong>.
        </div>
    </div>

    {{-- Filtro de año --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <label class="form-label">Año</label>
            <div class="input-group">
                <input type="number" id="filtroAnio" class="form-control" value="{{ date('Y') }}">
                <button class="btn btn-outline-secondary" onclick="cargarEliminadas()">Filtrar</button>
            </div>
        </div>
    </div>

    <div id="estado-carga" class="text-muted">Cargando...</div>
    <div id="resultado"></div>

</div>

<script>

const ETIQUETAS_TIPO = {
    superior:  'Consejo Superior',
    directivo: 'Consejo Directivo',
    decano:    'Decano',
    rector:    'Rector',
};

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

function formatearFecha(isoString) {
    if (!isoString) return '—';
    const d = new Date(isoString);
    return d.toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

async function cargarEliminadas() {
    const anio = document.getElementById('filtroAnio').value;
    document.getElementById('estado-carga').style.display = 'block';
    document.getElementById('resultado').innerHTML = '';

    try {
        const res = await fetch(`/api/listas-eliminadas?anio=${anio}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) throw new Error(`Error ${res.status}`);

        const listas = await res.json();
        document.getElementById('estado-carga').style.display = 'none';
        renderTabla(listas);

    } catch (e) {
        document.getElementById('estado-carga').textContent = 'Error al cargar las listas eliminadas.';
        console.error(e);
    }
}

function renderTabla(listas) {
    const contenedor = document.getElementById('resultado');

    if (listas.length === 0) {
        contenedor.innerHTML = `<p class="text-muted">No hay listas eliminadas para el año seleccionado.</p>`;
        return;
    }

    const filas = listas.map(l => {
        const tipo      = ETIQUETAS_TIPO[l.tipo] ?? esc(l.tipo);
        const facultad  = l.facultad?.nombre  ? esc(l.facultad.nombre)  : '—';
        const claustro  = l.claustro?.nombre  ? esc(l.claustro.nombre)  : '—';
        const eliminador = l.eliminado_por?.name ? esc(l.eliminado_por.name) : '—';
        const fecha     = formatearFecha(l.deleted_at);

        return `
            <tr>
                <td>${l.numero ?? '—'}</td>
                <td>${esc(l.nombre)}${l.sigla ? ` <span class="text-muted small">(${esc(l.sigla)})</span>` : ''}</td>
                <td>${tipo}</td>
                <td>${facultad}</td>
                <td>${claustro}</td>
                <td class="small text-muted">${esc(l.motivo_baja ?? '—')}</td>
                <td class="small text-muted">${eliminador}</td>
                <td class="small text-muted">${fecha}</td>
                <td>
                    <a href="/listas-eliminadas/${l.id}" class="btn btn-sm btn-outline-secondary">
                        Ver
                    </a>
                </td>
            </tr>
        `;
    }).join('');

    contenedor.innerHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Facultad</th>
                        <th>Claustro</th>
                        <th>Motivo</th>
                        <th>Eliminada por</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
        <p class="text-muted small mt-2">
            Total: ${listas.length} lista${listas.length !== 1 ? 's' : ''} eliminada${listas.length !== 1 ? 's' : ''}
        </p>
    `;
}

cargarEliminadas();

</script>

@endsection