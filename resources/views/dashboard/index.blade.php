@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="mb-0">Bienvenido</h3>
        <span class="text-muted small">{{ auth()->user()->name }}</span>
    </div>

    <div class="row g-4">

        {{-- ================================ --}}
        {{-- CARD: ELECCIONES --}}
        {{-- ================================ --}}
        <div class="col-md-6">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold fs-5">🗳 Elecciones</span>
                    <a href="/padrones" class="btn btn-sm btn-light">Ver padrones</a>
                </div>
                <div class="card-body">

                    <div id="stats-elecciones">
                        <div class="text-muted small">Cargando estadísticas...</div>
                    </div>

                    <hr>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="/padrones" class="btn btn-outline-primary btn-sm">
                            📋 Padrones
                        </a>
                        <a href="/personas/buscar" class="btn btn-outline-primary btn-sm">
                            🔍 Buscar persona
                        </a>
                        <a href="/listas" class="btn btn-outline-primary btn-sm">
                            📝 Listas
                        </a>
                        @if(auth()->user()?->hasRole('admin'))
                        <a href="/proclamados" class="btn btn-outline-success btn-sm">
                            🏆 Proclamados
                        </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- ================================ --}}
        {{-- CARD: ASISTENCIAS --}}
        {{-- ================================ --}}
        @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('consejo_superior'))
        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold fs-5">📌 Asistencias</span>
                    <a href="/asistencia" class="btn btn-sm btn-light">Ver todas</a>
                </div>
                <div class="card-body">

                    <div id="stats-asistencias">
                        <div class="text-muted small">Cargando estadísticas...</div>
                    </div>

                    <hr>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="/asistencia" class="btn btn-outline-success btn-sm">
                            📋 Reuniones
                        </a>
                        <a href="/asistencia/crear" class="btn btn-success btn-sm">
                            + Nueva reunión
                        </a>
                        <a href="/asistencia/historial" class="btn btn-outline-secondary btn-sm">
                            🕐 Historial
                        </a>
                    </div>

                </div>
            </div>
        </div>
        @endif

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

// ============================================================
// ESTADÍSTICAS DE ELECCIONES
// ============================================================

async function cargarStatsElecciones() {
    const cont = document.getElementById('stats-elecciones');
    try {
        const [padrones, listas] = await Promise.all([
            apiRequest('/api/padrones'),
            apiRequest('/api/listas'),
        ]);

        const anioActual = new Date().getFullYear();

        const padronesAnio = (padrones.data ?? padrones).filter(p => p.anio === anioActual);
        const listasAnio   = (listas.data   ?? listas).filter(l => l.anio === anioActual);

        const oficializadas = listasAnio.filter(l => l.estado_lista === 'oficializada').length;
        const cargadas      = listasAnio.filter(l => l.estado_lista === 'cargada').length;
        const conBancas     = listasAnio.filter(l => l.bancas > 0).length;

        cont.innerHTML = `
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="fs-3 fw-bold text-primary">${padronesAnio.length}</div>
                        <div class="text-muted small">Padrones ${anioActual}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="fs-3 fw-bold text-primary">${listasAnio.length}</div>
                        <div class="text-muted small">Listas ${anioActual}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="fs-3 fw-bold text-success">${oficializadas}</div>
                        <div class="text-muted small">Oficializadas</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="fs-3 fw-bold text-warning">${cargadas}</div>
                        <div class="text-muted small">En revisión</div>
                    </div>
                </div>
            </div>
            ${conBancas > 0 ? `
                <div class="mt-3 alert alert-success py-2 small mb-0">
                    🏆 ${conBancas} lista${conBancas !== 1 ? 's' : ''} con bancas asignadas
                </div>
            ` : ''}
        `;

    } catch {
        cont.innerHTML = `<span class="text-muted small">No se pudieron cargar las estadísticas.</span>`;
    }
}

// ============================================================
// ESTADÍSTICAS DE ASISTENCIAS
// ============================================================

async function cargarStatsAsistencias() {
    const cont = document.getElementById('stats-asistencias');
    if (!cont) return;

    try {
        const reuniones = await apiRequest('/api/reuniones');
        const data      = reuniones.data ?? reuniones;

        const abiertas  = data.filter(r => r.estado === 'abierta');
        const anioActual = new Date().getFullYear();
        const delAnio   = data.filter(r => r.fecha?.startsWith(String(anioActual)));

        const sesiones   = delAnio.filter(r => r.tipo === 'sesion').length;
        const comisiones = delAnio.filter(r => r.tipo === 'comision').length;

        const alertaAbierta = abiertas.length > 0
            ? `<div class="alert alert-success py-2 small mb-3">
                   🟢 ${abiertas.length} reunión${abiertas.length !== 1 ? 'es' : ''} en curso:
                   ${abiertas.map(r => `<a href="/asistencia/${r.id}" class="fw-semibold">${esc(r.nombre)}</a>`).join(', ')}
               </div>`
            : '';

        cont.innerHTML = `
            ${alertaAbierta}
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="fs-3 fw-bold text-success">${sesiones}</div>
                        <div class="text-muted small">Sesiones ${anioActual}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="fs-3 fw-bold text-success">${comisiones}</div>
                        <div class="text-muted small">Comisiones ${anioActual}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-2">
                        <div class="fs-3 fw-bold text-secondary">${delAnio.length}</div>
                        <div class="text-muted small">Total reuniones ${anioActual}</div>
                    </div>
                </div>
            </div>
        `;

    } catch {
        cont.innerHTML = `<span class="text-muted small">No se pudieron cargar las estadísticas.</span>`;
    }
}

// ============================================================
// ARRANQUE
// ============================================================

cargarStatsElecciones();
cargarStatsAsistencias();

</script>

@endsection