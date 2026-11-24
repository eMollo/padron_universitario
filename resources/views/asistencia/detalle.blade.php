@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <a href="{{ route('asistencia.historial') }}" class="text-muted text-decoration-none small">
                ← Historial
            </a>
            <h3 class="mb-0 mt-1" id="titulo-reunion">Cargando...</h3>
            <span class="text-muted small" id="subtitulo-reunion"></span>
        </div>
        <div class="d-flex gap-2 align-items-center" id="acciones" style="display:none !important;">
            {{-- Se muestra si la reunión está abierta todavía --}}
        </div>
    </div>

    <div id="estado-carga" class="text-muted">Cargando datos...</div>
    <div id="estado-error" class="alert alert-danger" style="display:none;"></div>
    <div id="contenido" style="display:none;">

        {{-- FILA SUPERIOR: datos + resumen --}}
        <div class="row g-4 mb-4">

            {{-- Datos generales --}}
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Datos de la reunión</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted fw-normal">Tipo</th>
                                    <td id="dato-tipo">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Fecha</th>
                                    <td id="dato-fecha">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Inicio</th>
                                    <td id="dato-inicio">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Fin</th>
                                    <td id="dato-fin">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Estado</th>
                                    <td id="dato-estado">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Resumen de presencia --}}
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Resumen de presencia</div>
                    <div class="card-body" id="resumen-presencia">
                        <p class="text-muted">Cargando...</p>
                    </div>
                </div>
            </div>

            {{-- Resumen votaciones --}}
            <div class="col-md-4" id="card-votaciones-resumen">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Votaciones nominales</div>
                    <div class="card-body p-0" id="resumen-votaciones">
                        <p class="text-muted p-3">Cargando...</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ASISTENTES POR CLAUSTRO --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span>Asistentes</span>
                <span class="text-muted small" id="subtitulo-asistentes"></span>
            </div>
            <div class="card-body p-0" id="tabla-asistentes"></div>
        </div>

        {{-- ORDEN DE PALABRA --}}
        <div class="card mb-4" id="card-orden-palabra">
            <div class="card-header fw-semibold">Orden de palabra</div>
            <div class="card-body p-0" id="tabla-orden-palabra"></div>
        </div>

        {{-- DETALLE DE VOTACIONES --}}
        <div id="detalle-votaciones"></div>

    </div>

</div>

<script>

const REUNION_ID = {{ $id }};

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
    const [y, m, d] = (typeof fecha === 'string' ? fecha : fecha.toISOString()).substring(0,10).split('-');
    return `${d}/${m}/${y}`;
}

function formatearDatetime(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function setText(id, valor) {
    const el = document.getElementById(id);
    if (el) el.textContent = valor ?? '—';
}

// ============================================================
// CARGA
// ============================================================

async function cargarDetalle() {
    try {
        const [data, votaciones, ordenPalabra] = await Promise.all([
            apiRequest(`/api/reuniones/${REUNION_ID}/participantes`),
            apiRequest(`/api/reuniones/${REUNION_ID}/votaciones`),
            apiRequest(`/api/reuniones/${REUNION_ID}/orden-palabra`),
        ]);

        renderEncabezado(data.reunion);
        renderDatosGenerales(data.reunion);
        renderResumenPresencia(data.participantes, data.presentes);
        renderAsistentes(data.participantes, data.personas_extra ?? []);
        renderVotaciones(votaciones, data.reunion.tipo);
        renderOrdenPalabra(ordenPalabra);

        document.getElementById('estado-carga').style.display = 'none';
        document.getElementById('contenido').style.display    = 'block';

        // Si está abierta, mostrar botón para ir a la sala
        if (data.reunion.estado === 'abierta') {
            const acc = document.getElementById('acciones');
            acc.style.display = 'flex';
            acc.innerHTML = `
                <a href="/asistencia/${REUNION_ID}" class="btn btn-success btn-sm">
                    Ir a la sala →
                </a>
            `;
        }

    } catch (e) {
        document.getElementById('estado-carga').style.display = 'none';
        const err = document.getElementById('estado-error');
        err.textContent = 'Error al cargar el detalle de la reunión.';
        err.style.display = 'block';
        console.error(e);
    }
}

// ============================================================
// ENCABEZADO
// ============================================================

function renderEncabezado(reunion) {
    const color    = COLORES_TIPO[reunion.tipo] ?? 'secondary';
    const etiqueta = ETIQUETAS_TIPO[reunion.tipo] ?? reunion.tipo;

    document.getElementById('titulo-reunion').textContent = reunion.nombre;
    document.getElementById('subtitulo-reunion').innerHTML =
        `<span class="badge bg-${color} me-2">${etiqueta}</span>` +
        `${formatearFecha(reunion.fecha)} · ${reunion.hora_inicio ?? ''} → ${reunion.hora_fin ?? 'en curso'}`;
}

// ============================================================
// DATOS GENERALES
// ============================================================

function renderDatosGenerales(reunion) {
    const color    = COLORES_TIPO[reunion.tipo] ?? 'secondary';
    const etiqueta = ETIQUETAS_TIPO[reunion.tipo] ?? reunion.tipo;
    const subtipo  = reunion.tipo_reunion?.nombre ?? '';

    document.getElementById('dato-tipo').innerHTML =
        `<span class="badge bg-${color}">${etiqueta}</span>` +
        (subtipo ? ` <span class="text-muted small">${esc(subtipo)}</span>` : '');

    setText('dato-fecha',  formatearFecha(reunion.fecha));
    setText('dato-inicio', reunion.hora_inicio ?? '—');
    setText('dato-fin',    reunion.hora_fin    ?? '—');

    document.getElementById('dato-estado').innerHTML = reunion.estado === 'cerrada'
        ? '<span class="badge bg-secondary">Cerrada</span>'
        : '<span class="badge bg-success">Abierta</span>';
}

// ============================================================
// RESUMEN DE PRESENCIA
// ============================================================

function renderResumenPresencia(participantes, presentes) {
    const cont = document.getElementById('resumen-presencia');

    // Habilitados = solo los proclamados (la "P" de la sala: titulares y suplentes dentro
    // de las bancas de cada lista). Los presentes se cuentan entre todos los integrantes
    // de las listas, porque en lista abierta también puede asistir quien no fue proclamado.
    let totalHabilitados = 0;
    let totalPresentes   = 0;
    let totalVirtuales   = 0;

    Object.values(participantes).forEach(listas => {
        listas.forEach(lista => {
            lista.postulantes.forEach(p => {
                if (p.proclamado) totalHabilitados++;
                if (p.estado === 'entrada')         totalPresentes++;
                if (p.estado === 'entrada_virtual') totalVirtuales++;
            });
        });
    });

    const pct = totalHabilitados > 0
        ? Math.round(((totalPresentes + totalVirtuales) / totalHabilitados) * 100)
        : 0;

    cont.innerHTML = `
        <div class="row g-3 text-center">
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fs-3 fw-bold text-success">${totalPresentes}</div>
                    <div class="text-muted small">Presentes</div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fs-3 fw-bold text-info">${totalVirtuales}</div>
                    <div class="text-muted small">Virtuales</div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fs-3 fw-bold text-muted">${totalHabilitados}</div>
                    <div class="text-muted small" title="Total de proclamados (titulares y suplentes)">Habilitados</div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fs-3 fw-bold">${pct}%</div>
                    <div class="text-muted small">Asistencia</div>
                </div>
            </div>
        </div>
    `;
}

// ============================================================
// TABLA DE ASISTENTES
// ============================================================

function renderAsistentes(participantes, personasExtra) {
    const cont     = document.getElementById('tabla-asistentes');
    const subtitulo = document.getElementById('subtitulo-asistentes');
    let html       = '';
    let totalMostrados = 0;

    Object.entries(participantes).forEach(([claustro, listas]) => {
        html += `
            <div class="px-3 pt-3 pb-1">
                <strong>${esc(claustro)}</strong>
            </div>
        `;

        listas.forEach(lista => {
            // Solo mostrar listas donde alguien tuvo movimiento
            const conMovimiento = lista.postulantes.filter(p => p.estado !== 'ausente');
            if (conMovimiento.length === 0) return;

            html += `
                <div class="px-3 pb-1">
                    <span class="text-muted small">
                        Lista #${esc(lista.numero)} — ${esc(lista.nombre)}
                    </span>
                </div>
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Apellido y nombre</th>
                            <th>Estado final</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${lista.postulantes.map(p => {
                            if (p.estado === 'ausente') return '';
                            totalMostrados++;
                            const persona = `${esc(p.apellido)}, ${esc(p.nombre)}`;
                            const badge   = badgeEstado(p.estado);
                            const proc    = p.proclamado
                                ? '<span class="badge bg-success ms-1" style="font-size:.6rem;">P</span>'
                                : '';
                            return `
                                <tr>
                                    <td class="ps-4 text-muted">${p.orden}</td>
                                    <td>${persona}${proc}</td>
                                    <td>${badge}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            `;
        });
    });

    // Personas extra (asamblea)
    if (personasExtra.length > 0) {
        html += `
            <div class="px-3 pt-3 pb-1"><strong>Invitados / Extra</strong></div>
            <table class="table table-sm mb-0">
                <tbody>
                    ${personasExtra.map(p => `
                        <tr>
                            <td class="ps-4 text-muted">—</td>
                            <td>${esc(p.apellido)}, ${esc(p.nombre)}</td>
                            <td>${badgeEstado(p.estado)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    if (!html) {
        html = `<p class="text-muted p-3 mb-0">No se registraron asistentes.</p>`;
    }

    subtitulo.textContent = `${totalMostrados} persona${totalMostrados !== 1 ? 's' : ''} con movimiento`;
    cont.innerHTML = html;
}

function badgeEstado(estado) {
    switch (estado) {
        case 'entrada':         return '<span class="badge bg-success">Presente</span>';
        case 'entrada_virtual': return '<span class="badge bg-info text-dark">Virtual</span>';
        case 'salida':          return '<span class="badge bg-secondary">Se retiró</span>';
        default:                return '<span class="badge bg-light text-muted">Ausente</span>';
    }
}

// ============================================================
// VOTACIONES
// ============================================================

function renderVotaciones(votaciones, tipoReunion) {
    // Resumen en card superior
    const resumen = document.getElementById('resumen-votaciones');
    if (tipoReunion !== 'sesion' || votaciones.length === 0) {
        document.getElementById('card-votaciones-resumen').style.display = 'none';
        document.getElementById('detalle-votaciones').innerHTML = '';
        return;
    }

    resumen.innerHTML = `
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Descripción</th>
                    <th class="text-success text-center">✅</th>
                    <th class="text-danger text-center">❌</th>
                    <th class="text-muted text-center">⬜</th>
                </tr>
            </thead>
            <tbody>
                ${votaciones.map(v => `
                    <tr>
                        <td class="ps-3 text-muted">${v.orden}</td>
                        <td>${esc(v.descripcion)}</td>
                        <td class="text-center fw-bold text-success">${v.resumen.afirmativo}</td>
                        <td class="text-center fw-bold text-danger">${v.resumen.negativo}</td>
                        <td class="text-center fw-bold text-muted">${v.resumen.abstencion}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;

    // Detalle completo de cada votación
    const detalleCont = document.getElementById('detalle-votaciones');
    detalleCont.innerHTML = votaciones.map(v => `
        <div class="card mb-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>${v.orden}. ${esc(v.descripcion)}</span>
                <div class="d-flex gap-3 small">
                    <span class="text-success fw-bold">Afirmativo ${v.resumen.afirmativo}</span>
                    <span class="text-danger fw-bold">Negativo ${v.resumen.negativo}</span>
                    <span class="text-muted fw-bold">Abstención ${v.resumen.abstencion}</span>
                    <span class="fw-bold border-start ps-3">Total: ${v.resumen.total}</span>
                </div>
            </div>
            ${v.votos?.length > 0 ? `
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            ${v.votos.map(voto => `
                                <tr>
                                    <td class="ps-3">
                                        ${esc(voto.persona?.apellido ?? '—')},
                                        ${esc(voto.persona?.nombre   ?? '—')}
                                    </td>
                                    <td>${badgeVoto(voto.voto)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            ` : ''}
        </div>
    `).join('');
}

function badgeVoto(voto) {
    switch (voto) {
        case 'afirmativo': return '<span class="badge bg-success">Afirmativo</span>';
        case 'negativo':   return '<span class="badge bg-danger">Negativo</span>';
        case 'abstencion': return '<span class="badge bg-secondary">Abstención</span>';
        default:           return '';
    }
}

// ============================================================
// ORDEN DE PALABRA
// ============================================================

function renderOrdenPalabra(turnos) {
    const card = document.getElementById('card-orden-palabra');
    const cont = document.getElementById('tabla-orden-palabra');

    if (turnos.length === 0) {
        card.style.display = 'none';
        return;
    }

    cont.innerHTML = `
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Orador</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                ${turnos.map(t => `
                    <tr class="${t.atendido ? 'opacity-50' : ''}">
                        <td class="ps-3 text-muted">${t.orden}</td>
                        <td>${esc(t.persona?.apellido ?? '—')}, ${esc(t.persona?.nombre ?? '—')}</td>
                        <td>
                            ${t.atendido
                                ? '<span class="badge bg-light text-muted">Atendido</span>'
                                : '<span class="badge bg-warning text-dark">Pendiente</span>'}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// ============================================================
// ARRANQUE
// ============================================================

cargarDetalle();

</script>

@endsection