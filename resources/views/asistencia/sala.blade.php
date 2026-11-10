@extends('layouts.app')

@section('content')

<style>
    .persona-row { cursor: pointer; transition: background 0.15s; }
    .persona-row:hover { background: rgba(0,0,0,.04); }
    .persona-row.presente     { background: #d1e7dd; }
    .persona-row.presente_virtual { background: #cff4fc; }
    .badge-proclamado { font-size: .65rem; vertical-align: middle; }
    .contador-quorum {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
    }
    .orden-palabra-item.atendido { opacity: .45; text-decoration: line-through; }
    @media (max-width: 768px) {
        .contador-quorum { font-size: 2rem; }
    }
</style>

<div class="container-fluid">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('asistencia.index') }}" class="text-muted text-decoration-none small">
                ← Volver
            </a>
            <h3 class="mb-0 mt-1" id="titulo-reunion">Cargando...</h3>
            <span class="text-muted small" id="subtitulo-reunion"></span>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Contador de quórum --}}
            <div class="text-center px-3 py-1 border rounded" id="bloque-quorum">
                <div class="text-muted" style="font-size:.7rem;">PRESENTES</div>
                <div class="contador-quorum text-success" id="contador-presentes">0</div>
            </div>
            {{-- Botones --}}
            <a href="/asistencia/{{ $id }}/qr"
               target="_blank" class="btn btn-outline-secondary btn-sm">
                QR
            </a>
            @if(auth()->user()?->hasAnyRole(['admin','consejo_superior']))
            <button class="btn btn-outline-warning btn-sm" onclick="abrirModalSalidaMasiva()">
                Salida general
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="abrirModalCerrar()">
                Cerrar reunión
            </button>
            @endif
        </div>
    </div>

    {{-- PESTAÑAS --}}
    <ul class="nav nav-tabs mb-3" id="tabsSala">
        <li class="nav-item">
            <button class="nav-link active" data-tab="asistencia" onclick="cambiarTab('asistencia')">
                Asistencia
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-tab="palabra" onclick="cambiarTab('palabra')">
                Orden de palabra
                <span class="badge bg-secondary ms-1" id="badge-palabra">0</span>
            </button>
        </li>
        <li class="nav-item" id="tab-votaciones-li">
            <button class="nav-link" data-tab="votaciones" onclick="cambiarTab('votaciones')">
                Votaciones
                <span class="badge bg-secondary ms-1" id="badge-votaciones">0</span>
            </button>
        </li>
    </ul>

    {{-- TAB: ASISTENCIA --}}
    <div id="tab-asistencia">
        <div id="estado-carga" class="text-muted">Cargando participantes...</div>
        <div id="contenido-asistencia"></div>
    </div>

    {{-- TAB: ORDEN DE PALABRA --}}
    <div id="tab-palabra" style="display:none;">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header fw-semibold">Cola de oradores</div>
                    <div class="card-body p-0">
                        <div id="lista-orden-palabra">
                            <p class="text-muted text-center py-3 mb-0">Sin turnos pendientes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header fw-semibold">Agregar orador</div>
                    <div class="card-body">
                        <select class="form-select mb-2" id="select-orador">
                            <option value="">Seleccionar persona...</option>
                        </select>
                        <button class="btn btn-primary w-100" onclick="pedirPalabra()">
                            + Agregar a la cola
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB: VOTACIONES --}}
    <div id="tab-votaciones" style="display:none;">
        @if(auth()->user()?->hasAnyRole(['admin','consejo_superior']))
        <div class="card mb-3">
            <div class="card-header fw-semibold">Nueva votación nominal</div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" id="nueva-votacion"
                        placeholder="Descripción de la votación..." maxlength="300">
                    <button class="btn btn-primary" onclick="crearVotacion()">
                        Crear
                    </button>
                </div>
            </div>
        </div>
        @endif
        <div id="lista-votaciones"></div>
    </div>

</div>

{{-- MODAL: Salida masiva --}}
<div class="modal fade" id="modalSalidaMasiva" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Salida masiva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Marcar salida a todos los presentes en esta reunión?</p>
                <p class="text-muted small">
                    Útil para cerrar una comisión antes de pasar a la siguiente.
                </p>
                <div id="cont-traspasar" class="mt-3" style="display:none;">
                    <label class="form-label fw-semibold">Traspasar presentes a otra reunión abierta:</label>
                    <select class="form-select" id="select-reunion-destino">
                        <option value="">Solo marcar salida (sin traspasar)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btn-confirmar-salida-masiva">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Cerrar reunión --}}
<div class="modal fade" id="modalCerrar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Cerrar reunión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro que querés cerrar esta reunión?</p>
                <p class="text-muted small">
                    Se registrará la hora de fin. La reunión quedará en modo solo lectura.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-cerrar">
                    Sí, cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Cambiar estado de persona --}}
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-estado-nombre">—</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-grid gap-2">
                <button class="btn btn-success"
                    onclick="registrarMovimiento(personaSeleccionada, 'entrada')">
                    Presente
                </button>
                <button class="btn btn-info"
                    onclick="registrarMovimiento(personaSeleccionada, 'entrada_virtual')">
                    Presente Virtual
                </button>
                <button class="btn btn-secondary"
                    onclick="registrarMovimiento(personaSeleccionada, 'salida')">
                    Salida
                </button>
            </div>
        </div>
    </div>
</div>

<script>

const REUNION_ID = {{ $id }};
let personaSeleccionada = null;
let todasLasPersonas    = [];
let reunionActual       = null;
let modalEstado         = null;
let modalSalidaMasiva   = null;
let modalCerrar         = null;
let datosParticipantes  = null;   // última respuesta de /participantes (estado actual de todos)
let votacionesData      = [];
let votacionAbierta;              // undefined = sin decidir (abre la última) · null = todas cerradas

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

// CARGA INICIAL

async function cargarSala() {
    try {
        const data = await apiRequest(`/api/reuniones/${REUNION_ID}/participantes`);
        reunionActual = data.reunion;

        // Encabezado
        document.getElementById('titulo-reunion').textContent = data.reunion.nombre;
        document.getElementById('subtitulo-reunion').textContent =
            `${String(data.reunion.fecha).substring(0, 10).split('-').reverse().join('/')} · ${data.reunion.hora_inicio ?? ''}`;

        // Contador
        actualizarContador(data.presentes);

        // Render asistencia
        renderAsistencia(data.participantes, data.personas_extra ?? []);

        // Select de oradores: solo los presentes
        datosParticipantes = data;
        poblarSelectOradores(data);

        // Ocultar tab votaciones si no es sesión
        if (data.reunion.tipo !== 'sesion') {
            document.getElementById('tab-votaciones-li').style.display = 'none';
        }

        document.getElementById('estado-carga').style.display = 'none';

        // Cargar orden de palabra y votaciones
        cargarOrdenPalabra();
        if (data.reunion.tipo === 'sesion') cargarVotaciones();

    } catch (e) {
        document.getElementById('estado-carga').textContent = 'Error al cargar la reunión.';
        console.error(e);
    }
}

// RENDER ASISTENCIA

function renderAsistencia(participantes, personasExtra) {
    const cont = document.getElementById('contenido-asistencia');
    let html   = '';

    Object.entries(participantes).forEach(([claustro, listas]) => {
        html += `<h5 class="border-bottom pb-1 mt-4 mb-3">${esc(claustro)}</h5>`;

        listas.forEach(lista => {
            html += `
                <div class="mb-3">
                    <div class="text-muted small mb-1 fw-semibold">
                        Lista #${esc(lista.numero)} — ${esc(lista.nombre)}
                        ${lista.sigla ? `(${esc(lista.sigla)})` : ''}
                        <span class="ms-2">· ${lista.bancas} banca${lista.bancas !== 1 ? 's' : ''}</span>
                    </div>
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            ${lista.postulantes.map(p => filaPersona(p)).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            // Agregar a la lista global de personas
            lista.postulantes.forEach(p => todasLasPersonas.push(p));
        });
    });

    // Personas extra (solo asamblea)
    if (personasExtra.length > 0) {
        html += `<h5 class="border-bottom pb-1 mt-4 mb-3">Invitados / Extra</h5>
            <table class="table table-sm table-hover">
                <tbody>
                    ${personasExtra.map(p => filaPersona(p)).join('')}
                </tbody>
            </table>`;
        personasExtra.forEach(p => todasLasPersonas.push(p));
    }

    cont.innerHTML = html;
}

function filaPersona(p) {
    const estado    = p.estado ?? 'ausente';
    const claseRow  = ['entrada', 'entrada_virtual'].includes(estado) ? estado : '';
    const badgeEstado = badgeParaEstado(estado);
    const esProclamado = p.proclamado;
    const tipoLabel    = p.tipo_postulante === 'titular' ? 'T' : 'S';

    return `
        <tr class="persona-row ${claseRow}"
            id="row-persona-${p.id}"
            data-id="${p.id}"
            data-nombre="${esc(p.apellido)}, ${esc(p.nombre)}"
            onclick="abrirModalEstadoDesdeFila(this)"
        >
            <td style="width:2rem;" class="text-muted">${esc(p.orden)}</td>
            <td>
                <span class="${esProclamado ? 'fw-semibold' : ''}">
                    ${esc(p.apellido)}, ${esc(p.nombre)}
                </span>
                ${esProclamado
                    ? `<span class="badge bg-success badge-proclamado ms-1">P</span>`
                    : ''}
                <span class="badge bg-light text-dark badge-proclamado ms-1">${tipoLabel}</span>
            </td>
            <td class="text-end">${badgeEstado}</td>
        </tr>
    `;
}

function badgeParaEstado(estado) {
    switch (estado) {
        case 'entrada':         return '<span class="badge bg-success">Presente</span>';
        case 'entrada_virtual': return '<span class="badge bg-info text-dark">Virtual</span>';
        case 'salida':          return '<span class="badge bg-secondary">Retirado</span>';
        default:                return '<span class="badge bg-light text-muted">Ausente</span>';
    }
}

// MODAL DE ESTADO

// Lee id y nombre desde data-attributes (evita romper el JS con apellidos como D'ANGELO)
function abrirModalEstadoDesdeFila(fila) {
    abrirModalEstado(parseInt(fila.dataset.id, 10), fila.dataset.nombre);
}

function abrirModalEstado(idPersona, nombre) {
    personaSeleccionada = idPersona;
    document.getElementById('modal-estado-nombre').textContent = nombre;
    modalEstado = modalEstado ?? new bootstrap.Modal(document.getElementById('modalEstado'));
    modalEstado.show();
}

// REGISTRAR MOVIMIENTO

async function registrarMovimiento(idPersona, tipo) {
    modalEstado?.hide();

    try {
        const data = await apiRequest(`/api/reuniones/${REUNION_ID}/movimiento`, 'POST', {
            id_persona: idPersona,
            tipo,
        });

        actualizarContador(data.presentes);
        actualizarFilaPersona(idPersona, tipo);

    } catch (err) {
        if (err.status === 429) {
            alert('Movimiento demasiado reciente. Esperá unos segundos.');
        } else {
            alert('Error al registrar el movimiento.');
        }
        console.error(err);
    }
}

function actualizarFilaPersona(idPersona, tipo) {
    const fila = document.getElementById(`row-persona-${idPersona}`);
    if (!fila) return;

    fila.className = `persona-row ${['entrada','entrada_virtual'].includes(tipo) ? tipo : ''}`;
    const badgeCell = fila.querySelector('td:last-child');
    if (badgeCell) badgeCell.innerHTML = badgeParaEstado(tipo);
}

function actualizarContador(presentes) {
    document.getElementById('contador-presentes').textContent = presentes;
}

// SALIDA MASIVA / TRASPASAR

async function abrirModalSalidaMasiva() {
    // Cargar reuniones abiertas para poder traspasar
    const reuniones = await apiRequest('/api/reuniones?estado=abierta');
    const otras     = (reuniones.data ?? reuniones).filter(r => r.id !== REUNION_ID && r.estado === 'abierta');

    const sel = document.getElementById('select-reunion-destino');
    sel.innerHTML = '<option value="">Solo marcar salida (sin traspasar)</option>';
    otras.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.nombre;
        sel.appendChild(opt);
    });

    document.getElementById('cont-traspasar').style.display =
        otras.length > 0 ? 'block' : 'none';

    modalSalidaMasiva = modalSalidaMasiva
        ?? new bootstrap.Modal(document.getElementById('modalSalidaMasiva'));
    modalSalidaMasiva.show();
}

document.getElementById('btn-confirmar-salida-masiva').addEventListener('click', async () => {
    const destino = document.getElementById('select-reunion-destino').value;

    try {
        if (destino) {
            await apiRequest('/api/reuniones/traspasar-presentes', 'POST', {
                id_reunion_origen:  REUNION_ID,
                id_reunion_destino: parseInt(destino),
            });
        } else {
            await apiRequest(`/api/reuniones/${REUNION_ID}/salida-masiva`, 'POST');
        }

        modalSalidaMasiva.hide();
        cargarSala(); // Recargar para reflejar los cambios

    } catch (err) {
        alert('Error al procesar la salida masiva.');
        console.error(err);
    }
});

// CERRAR REUNIÓN

function abrirModalCerrar() {
    modalCerrar = modalCerrar ?? new bootstrap.Modal(document.getElementById('modalCerrar'));
    modalCerrar.show();
}

document.getElementById('btn-confirmar-cerrar').addEventListener('click', async () => {
    try {
        await apiRequest(`/api/reuniones/${REUNION_ID}/cerrar`, 'POST');
        modalCerrar.hide();
        window.location.href = `/asistencia/${REUNION_ID}/detalle`;
    } catch {
        alert('Error al cerrar la reunión.');
    }
});

// ORDEN DE PALABRA

async function cargarOrdenPalabra() {
    const turnos = await apiRequest(`/api/reuniones/${REUNION_ID}/orden-palabra`);
    renderOrdenPalabra(turnos);
    document.getElementById('badge-palabra').textContent =
        turnos.filter(t => !t.atendido).length;
}

function renderOrdenPalabra(turnos) {
    const cont = document.getElementById('lista-orden-palabra');
    const pendientes = turnos.filter(t => !t.atendido);

    if (turnos.length === 0) {
        cont.innerHTML = `<p class="text-muted text-center py-3 mb-0">Sin turnos</p>`;
        return;
    }

    cont.innerHTML = `
        <table class="table table-sm mb-0">
            <tbody>
                ${turnos.map(t => `
                    <tr class="${t.atendido ? 'opacity-50' : ''}">
                        <td class="text-muted ps-3" style="width:2rem;">${t.orden}</td>
                        <td>${esc(t.persona?.apellido ?? '—')}, ${esc(t.persona?.nombre ?? '—')}</td>
                        <td class="text-end pe-3">
                            ${!t.atendido
                                ? `<button class="btn btn-sm btn-outline-success"
                                       onclick="atenderTurno(${t.id})">
                                       ✓ Atendido
                                   </button>`
                                : '<span class="badge bg-light text-muted">Atendido</span>'}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function pedirPalabra() {
    const idPersona = document.getElementById('select-orador').value;
    if (!idPersona) return;

    await apiRequest(`/api/reuniones/${REUNION_ID}/orden-palabra`, 'POST', {
        id_persona: parseInt(idPersona),
    });

    document.getElementById('select-orador').value = '';
    cargarOrdenPalabra();
}

async function atenderTurno(turnoId) {
    await apiRequest(`/api/reuniones/${REUNION_ID}/orden-palabra/${turnoId}/atender`, 'PATCH');
    cargarOrdenPalabra();
}

// HELPERS DE PRESENTES

async function refrescarParticipantes() {
    const data = await apiRequest(`/api/reuniones/${REUNION_ID}/participantes`);
    datosParticipantes = data;
    actualizarContador(data.presentes);
    return data;
}

function estaPresente(p) {
    return ['entrada', 'entrada_virtual'].includes(p.estado);
}

// Lista plana de quienes están presentes ahora (listas + invitados de asamblea)
function personasPresentes(data) {
    const res = [];
    Object.entries(data?.participantes ?? {}).forEach(([claustro, listas]) => {
        listas.forEach(lista => {
            lista.postulantes.forEach(p => {
                if (estaPresente(p)) res.push({ ...p, grupo: claustro });
            });
        });
    });
    (data?.personas_extra ?? []).forEach(p => {
        if (estaPresente(p)) res.push({ ...p, grupo: 'Invitados / Extra' });
    });
    return res;
}

// El listado de oradores muestra solo a quienes están presentes
function poblarSelectOradores(data) {
    const sel       = document.getElementById('select-orador');
    const presentes = personasPresentes(data);

    if (presentes.length === 0) {
        sel.innerHTML = '<option value="">No hay presentes registrados</option>';
        return;
    }

    sel.innerHTML = '<option value="">Seleccionar persona...</option>';

    const grupos = {};
    presentes.forEach(p => {
        if (!grupos[p.grupo]) grupos[p.grupo] = [];
        grupos[p.grupo].push(p);
    });

    Object.entries(grupos).forEach(([nombre, personas]) => {
        const group = document.createElement('optgroup');
        group.label = nombre;
        personas.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.apellido}, ${p.nombre}` +
                (p.estado === 'entrada_virtual' ? ' (virtual)' : '');
            group.appendChild(opt);
        });
        sel.appendChild(group);
    });
}


// VOTACIONES NOMINALES (solo sesiones)

async function cargarVotaciones() {
    try {
        const [votaciones] = await Promise.all([
            apiRequest(`/api/reuniones/${REUNION_ID}/votaciones`),
            refrescarParticipantes(),   // para saber quiénes están presentes ahora
        ]);
        votacionesData = votaciones;
        renderVotaciones();
        document.getElementById('badge-votaciones').textContent = votaciones.length;
    } catch (e) {
        console.error(e);
        document.getElementById('lista-votaciones').innerHTML =
            '<div class="alert alert-danger">No se pudieron cargar las votaciones.</div>';
    }
}

const OPCIONES_VOTO = [
    { valor: 'afirmativo', etiqueta: 'Afirmativo', color: 'success'   },
    { valor: 'negativo',   etiqueta: 'Negativo',   color: 'danger'    },
    { valor: 'abstencion', etiqueta: 'Abstención', color: 'secondary' },
];

function botonesVoto(votacionId, personaId, votoActual) {
    return OPCIONES_VOTO.map(o => `
        <button type="button"
            class="btn btn-${votoActual === o.valor ? '' : 'outline-'}${o.color}"
            onclick="votar(${votacionId}, ${personaId}, '${o.valor}')">${o.etiqueta}</button>
    `).join('');
}

function filaVoto(votacionId, p, votoActual) {
    const retirado = p.ausente
        ? '<span class="badge bg-light text-muted ms-1">ya no está presente</span>'
        : '';

    return `
        <tr class="fila-voto" id="vrow-${votacionId}-${p.id}"
            data-voto="${votoActual ?? ''}" data-ausente="${p.ausente ? 1 : 0}">
            <td class="ps-3">${esc(p.apellido)}, ${esc(p.nombre)}${retirado}</td>
            <td class="text-end pe-3 text-nowrap">
                <div class="btn-group btn-group-sm" role="group">
                    ${botonesVoto(votacionId, p.id, votoActual)}
                </div>
            </td>
        </tr>
    `;
}

function cardVotacion(v, presentes, abierta) {
    const votoDe = {};
    (v.votos ?? []).forEach(x => { votoDe[x.id_persona] = x.voto; });

    // Presentes ahora + quienes ya votaron pero se retiraron (su voto se conserva)
    const idsPresentes = new Set(presentes.map(p => p.id));
    const retirados = (v.votos ?? [])
        .filter(x => !idsPresentes.has(x.id_persona))
        .map(x => ({
            id:       x.id_persona,
            apellido: x.persona?.apellido ?? '—',
            nombre:   x.persona?.nombre   ?? '',
            grupo:    'Votaron y ya no están presentes',
            ausente:  true,
        }));

    const filas = presentes.concat(retirados);

    const grupos = {};
    filas.forEach(p => {
        if (!grupos[p.grupo]) grupos[p.grupo] = [];
        grupos[p.grupo].push(p);
    });

    const cuerpo = filas.length === 0
        ? '<p class="text-muted p-3 mb-0">No hay presentes registrados. Marcá la asistencia primero.</p>'
        : Object.entries(grupos).map(([grupo, personas]) => `
            <div class="px-3 py-1 bg-light border-top border-bottom">
                <small class="fw-semibold text-muted">${esc(grupo)}</small>
            </div>
            <table class="table table-sm table-hover mb-0">
                <tbody>
                    ${personas.map(p => filaVoto(v.id, p, votoDe[p.id])).join('')}
                </tbody>
            </table>
        `).join('');

    const r       = v.resumen;
    const sinVoto = presentes.filter(p => !votoDe[p.id]).length;

    return `
        <div class="card mb-3" id="vcard-${v.id}">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                 style="cursor:pointer;" onclick="toggleVotacion(${v.id})">
                <span class="fw-semibold">${v.orden}. ${esc(v.descripcion)}</span>
                <div class="d-flex gap-3 small align-items-center">
                    <span class="text-success fw-bold">✅ <span id="tot-afirmativo-${v.id}">${r.afirmativo}</span></span>
                    <span class="text-danger fw-bold">❌ <span id="tot-negativo-${v.id}">${r.negativo}</span></span>
                    <span class="text-secondary fw-bold">⬜ <span id="tot-abstencion-${v.id}">${r.abstencion}</span></span>
                    <span class="text-muted">Sin voto: <span id="tot-sin-${v.id}">${sinVoto}</span></span>
                </div>
            </div>
            <div id="vbody-${v.id}" class="card-body p-0" style="display:${abierta ? 'block' : 'none'};">
                ${cuerpo}
            </div>
        </div>
    `;
}

function renderVotaciones() {
    const cont = document.getElementById('lista-votaciones');

    if (votacionesData.length === 0) {
        cont.innerHTML = '<p class="text-muted">No hay votaciones registradas.</p>';
        return;
    }

    // Por defecto queda desplegada la última votación creada
    if (votacionAbierta === undefined) {
        votacionAbierta = votacionesData[votacionesData.length - 1].id;
    }

    const presentes = personasPresentes(datosParticipantes);

    // Más reciente primero
    cont.innerHTML = [...votacionesData].reverse()
        .map(v => cardVotacion(v, presentes, v.id === votacionAbierta))
        .join('');
}

function toggleVotacion(id) {
    votacionAbierta = (votacionAbierta === id) ? null : id;
    votacionesData.forEach(v => {
        const body = document.getElementById(`vbody-${v.id}`);
        if (body) body.style.display = (v.id === votacionAbierta) ? 'block' : 'none';
    });
}

async function votar(votacionId, personaId, voto) {
    try {
        const r = await apiRequest(
            `/api/reuniones/${REUNION_ID}/votaciones/${votacionId}/votos`,
            'POST',
            { id_persona: personaId, voto }
        );

        // Estado local (por si se vuelve a dibujar)
        const v = votacionesData.find(x => x.id === votacionId);
        if (v) {
            v.votos = v.votos ?? [];
            const existente = v.votos.find(x => x.id_persona === personaId);
            if (existente) {
                existente.voto = voto;
            } else {
                const persona = personasPresentes(datosParticipantes).find(p => p.id === personaId);
                v.votos.push({
                    id_persona: personaId,
                    voto,
                    persona: { apellido: persona?.apellido, nombre: persona?.nombre },
                });
            }
            v.resumen = r.resumen;
        }

        // DOM: fila + totales
        const fila = document.getElementById(`vrow-${votacionId}-${personaId}`);
        if (fila) {
            fila.dataset.voto = voto;
            fila.querySelector('.btn-group').innerHTML = botonesVoto(votacionId, personaId, voto);
        }
        ['afirmativo', 'negativo', 'abstencion'].forEach(k => {
            const el = document.getElementById(`tot-${k}-${votacionId}`);
            if (el) el.textContent = r.resumen[k];
        });
        recalcularSinVoto(votacionId);

    } catch (e) {
        console.error(e);
        alert('No se pudo registrar el voto. Intentá de nuevo.');
    }
}

function recalcularSinVoto(votacionId) {
    let sin = 0;
    document.querySelectorAll(`#vbody-${votacionId} .fila-voto`).forEach(f => {
        if (f.dataset.ausente !== '1' && !f.dataset.voto) sin++;
    });
    const el = document.getElementById(`tot-sin-${votacionId}`);
    if (el) el.textContent = sin;
}

async function crearVotacion() {
    const input = document.getElementById('nueva-votacion');
    const desc  = input.value.trim();
    if (!desc) return;

    try {
        const nueva = await apiRequest(`/api/reuniones/${REUNION_ID}/votaciones`, 'POST', { descripcion: desc });
        input.value     = '';
        votacionAbierta = nueva.id;     // queda desplegada para empezar a votar
        await cargarVotaciones();
    } catch (e) {
        console.error(e);
        alert('No se pudo crear la votación.');
    }
}

// ============================================================
// PESTAÑAS
// ============================================================

async function cambiarTab(tab) {
    ['asistencia', 'palabra', 'votaciones'].forEach(t => {
        document.getElementById(`tab-${t}`).style.display = t === tab ? 'block' : 'none';
        document.querySelector(`[data-tab="${t}"]`)?.classList.toggle('active', t === tab);
    });

    try {
        if (tab === 'palabra') {
            // La presencia cambia durante la reunión: se vuelve a consultar
            await refrescarParticipantes();
            poblarSelectOradores(datosParticipantes);
            cargarOrdenPalabra();
        }
        if (tab === 'votaciones') await cargarVotaciones();
    } catch (e) {
        console.error(e);
    }
}

// ============================================================
// ARRANQUE
// ============================================================

cargarSala();

</script>

@endsection