@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Asistencia</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('asistencia.historial') }}" class="btn btn-outline-secondary btn-sm">
                Historial
            </a>
            @if(auth()->user()?->hasAnyRole(['admin','consejo_superior']))
            <a href="{{ route('asistencia.crear') }}" class="btn btn-primary btn-sm">
                + Nueva reunión
            </a>
            @endif
        </div>
    </div>

    {{-- Reuniones abiertas ahora --}}
    <div id="reuniones-abiertas" class="mb-5"></div>

    {{-- Grilla de tipos --}}
    <h5 class="text-muted mb-3">Iniciar reunión por tipo</h5>
    <div class="row g-3" id="grilla-tipos"></div>

</div>

<script>

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

const ICONOS = {
    sesion:   '🏛',
    comision: '📋',
    asamblea: '🏟',
};

const COLORES = {
    sesion:   'primary',
    comision: 'success',
    asamblea: 'warning',
};

async function cargarPantalla() {
    const [reuniones, tipos] = await Promise.all([
        apiRequest('/api/reuniones?estado=abierta'),
        apiRequest('/api/tipos-reunion'),
    ]);

    renderReunionesAbiertas(reuniones.data ?? reuniones);
    renderGrilla(tipos);
}

function renderReunionesAbiertas(reuniones) {
    const cont = document.getElementById('reuniones-abiertas');
    const abiertas = reuniones.filter ? reuniones.filter(r => r.estado === 'abierta') : [];

    if (abiertas.length === 0) {
        cont.innerHTML = '';
        return;
    }

    cont.innerHTML = `
        <h5 class="text-success mb-3">🟢 Reuniones en curso</h5>
        <div class="row g-3">
            ${abiertas.map(r => `
                <div class="col-md-4 col-lg-3">
                    <a href="/asistencia/${r.id}" class="text-decoration-none">
                        <div class="card border-success h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <span style="font-size:2rem;">${ICONOS[r.tipo] ?? '📌'}</span>
                                <div>
                                    <div class="fw-semibold">${esc(r.nombre)}</div>
                                    <div class="text-muted small">${esc(String(r.fecha).substring(0, 10).split('-').reverse().join('/'))} · ${esc(r.hora_inicio ?? '')}</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            `).join('')}
        </div>
    `;
}

function renderGrilla(tipos) {
    const cont = document.getElementById('grilla-tipos');

    // Tarjetas fijas: Sesión y Asamblea
    const fijos = [
        { tipo: 'sesion',   label: 'Sesión',   icono: '🏛' },
        { tipo: 'asamblea', label: 'Asamblea', icono: '🏟' },
    ];

    const cardsFijos = fijos.map(f => cardTipo(f.icono, f.label, f.tipo, null)).join('');

    // Tarjetas de comisiones
    const cardsComisiones = tipos.map(t =>
        cardTipo('📋', t.nombre, 'comision', t.id)
    ).join('');

    cont.innerHTML = cardsFijos + cardsComisiones;
}

function cardTipo(icono, label, tipo, idTipoReunion) {
    const params = new URLSearchParams({ tipo });
    if (idTipoReunion) params.append('id_tipo_reunion', idTipoReunion);

    const color = COLORES[tipo] ?? 'secondary';

    return `
        <div class="col-6 col-md-3 col-lg-2">
            <a href="{{ route('asistencia.crear') }}?${params}"
               class="text-decoration-none">
                <div class="card text-center h-100 border-${color}">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <div style="font-size:2.5rem;" class="mb-2">${icono}</div>
                        <div class="small fw-semibold text-${color}">${esc(label)}</div>
                    </div>
                </div>
            </a>
        </div>
    `;
}

cargarPantalla();

</script>

@endsection