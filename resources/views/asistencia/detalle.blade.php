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
        renderResumenPresencia(data.participantes, data.pico_presentes);
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

function renderResumenPresencia(participantes, pico) {
    const cont = document.getElementById('resumen-presencia');

    // Habilitados = solo los proclamados (la "P" de la sala).
    // Presentes / Virtuales = quienes ASISTIERON durante la reunión según el historial de
    // entradas y salidas, aunque ya se hayan retirado (al cerrar, todos suelen estar "afuera").
    let totalHabilitados = 0;
    let totalPresentes   = 0;
    let totalVirtuales   = 0;

    Object.values(participantes).forEach(listas => {
        listas.forEach(lista => {
            lista.postulantes.forEach(p => {
                if (p.proclamado) totalHabilitados++;
                if (p.asistio) {
                    if (p.modalidad === 'virtual') totalVirtuales++;
                    else                           totalPresentes++;   // presente o mixta
                }
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
                    <div class="text-muted small"
                         title="Asistieron de forma presencial (incluye a quienes estuvieron también de forma virtual)">Presentes</div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fs-3 fw-bold text-info">${totalVirtuales}</div>
                    <div class="text-muted small" title="Asistieron solo de forma virtual">Virtuales</div>
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
        <div class="text-muted small mt-3">
            Máximo de presentes al mismo tiempo: <strong>${pico ?? 0}</strong>
        </div>
    `;
}

// ============================================================
// TABLA DE ASISTENTES
// ============================================================

const ENCABEZADO_ASISTENTES = `
    <colgroup>
        <col style="width: 4%;">
        <col style="width: 40%;">
        <col style="width: 14%;">
        <col style="width: 13%;">
        <col style="width: 16%;">
        <col style="width: 13%;">
    </colgroup>

    <thead class="table-light">
        <tr>
            <th class="ps-4">#</th>
            <th>Apellido y nombre</th>
            <th>Modalidad</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Tiempo</th>
        </tr>
    </thead>
`;

function formatearMinutos(min) {
    if (!min || min < 1) return 'menos de 1 min';
    if (min < 60) return `${min} min`;
    const h = Math.floor(min / 60);
    const m = min % 60;
    return m === 0 ? `${h} h` : `${h} h ${m} min`;
}

function badgeModalidad(modalidad) {
    switch (modalidad) {
        case 'virtual': return '<span class="badge bg-info text-dark">Virtual</span>';
        case 'mixta':   return '<span class="badge bg-primary" title="Estuvo de forma presencial y virtual">Mixta</span>';
        default:        return '<span class="badge bg-success">Presente</span>';
    }
}

// Una fila de la tabla: qué hizo la persona durante toda la reunión
function filaAsistente(p, mostrarOrden) {
    const proclamado = p.proclamado
        ? '<span class="badge bg-success ms-1" style="font-size:.6rem;">P</span>'
        : '';

    const ingresos = p.entradas > 1
        ? ` <span class="text-muted small">(${p.entradas} ingresos)</span>`
        : '';

    const salida = p.sigue_presente
        ? '<span class="text-muted">hasta el cierre</span>'
        : esc(p.ultima_salida ?? '—');

    return `
        <tr>
            <td class="ps-4 text-muted">${mostrarOrden ? esc(p.orden) : '—'}</td>
            <td>${esc(p.apellido)}, ${esc(p.nombre)}${proclamado}</td>
            <td>${badgeModalidad(p.modalidad)}</td>
            <td>${esc(p.primera_entrada ?? '—')}${ingresos}</td>
            <td>${salida}</td>
            <td>${esc(formatearMinutos(p.minutos))}</td>
        </tr>
    `;
}

function renderAsistentes(participantes, personasExtra) {
    const cont      = document.getElementById('tabla-asistentes');
    const subtitulo = document.getElementById('subtitulo-asistentes');
    let html  = '';
    let total = 0;

    // Se muestra a quien ASISTIÓ en algún momento (según el historial), esté o no
    // presente al final: en una reunión cerrada, todos suelen haberse retirado.
    Object.entries(participantes).forEach(([claustro, listas]) => {
        let htmlClaustro = '';

        listas.forEach(lista => {
            const asistentes = lista.postulantes.filter(p => p.asistio);
            if (asistentes.length === 0) return;

            total += asistentes.length;

            htmlClaustro += `
                <div class="px-3 pb-1">
                    <span class="text-muted small">
                        Lista #${esc(lista.numero)} — ${esc(lista.nombre)}
                    </span>
                </div>
                <table class="table table-sm mb-0 tabla-asistentes" style="width: 100%; table-layout: fixed;">
                    ${ENCABEZADO_ASISTENTES}
                    <tbody>
                        ${asistentes.map(p => filaAsistente(p, true)).join('')}
                    </tbody>
                </table>
            `;
        });

        if (htmlClaustro) {
            html += `
                <div class="px-3 pt-3 pb-1"><strong>${esc(claustro)}</strong></div>
                ${htmlClaustro}
            `;
        }
    });

    // Personas extra (asamblea)
    const extras = personasExtra.filter(p => p.asistio);
    if (extras.length > 0) {
        total += extras.length;

        html += `
            <div class="px-3 pt-3 pb-1"><strong>Invitados / Extra</strong></div>
            <table class="table table-sm mb-0 tabla-asistentes" style="width: 100%; table-layout: fixed;">
                ${ENCABEZADO_ASISTENTES}
                <tbody>
                    ${extras.map(p => filaAsistente(p, false)).join('')}
                </tbody>
            </table>
        `;
    }

    if (!html) {
        html = `<p class="text-muted p-3 mb-0">No se registraron asistentes.</p>`;
    }

    subtitulo.textContent = `${total} persona${total !== 1 ? 's' : ''} asistieron`;
    cont.innerHTML = html;
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
                                ? '<span class="badge bg-light text-muted">Ya finalizó</span>'
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