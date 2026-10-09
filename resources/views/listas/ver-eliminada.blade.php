@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('listas.eliminadas') }}" class="text-muted text-decoration-none small">
                ← Volver a listas eliminadas
            </a>
            <h3 class="mb-0 mt-1" id="titulo-lista">Cargando...</h3>
            <span class="badge mt-1" id="badge-tipo"></span>
        </div>
    </div>

    {{-- CARTEL DE LISTA ELIMINADA --}}
    <div class="alert alert-danger d-flex align-items-start gap-3 mb-4" role="alert">
        <span style="font-size:1.5rem; line-height:1;">🚫</span>
        <div>
            <strong class="d-block mb-1">LISTA ELIMINADA</strong>
            <div class="mb-1">
                <span class="text-muted small">Motivo: </span>
                <span id="baja-motivo" class="fw-semibold"></span>
            </div>
            <div class="mb-1">
                <span class="text-muted small">Eliminada por: </span>
                <span id="baja-usuario" class="fw-semibold"></span>
            </div>
            <div>
                <span class="text-muted small">Fecha: </span>
                <span id="baja-fecha" class="fw-semibold"></span>
            </div>
        </div>
    </div>

    <div id="estado-carga" class="text-muted">Cargando datos...</div>
    <div id="estado-error" class="alert alert-danger" style="display:none;"></div>

    <div id="contenido-lista" style="display:none;">

        {{-- DATOS GENERALES + APODERADO + AVALES --}}
        <div class="row g-4 mb-4">

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Datos generales</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted fw-normal w-40">Número</th>
                                    <td id="dato-numero">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Nombre</th>
                                    <td id="dato-nombre">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Sigla</th>
                                    <td id="dato-sigla">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Año</th>
                                    <td id="dato-anio">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Tipo</th>
                                    <td id="dato-tipo">—</td>
                                </tr>
                                <tr id="fila-facultad">
                                    <th class="text-muted fw-normal">Facultad</th>
                                    <td id="dato-facultad">—</td>
                                </tr>
                                <tr id="fila-claustro">
                                    <th class="text-muted fw-normal">Claustro</th>
                                    <td id="dato-claustro">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Modo carga</th>
                                    <td id="dato-modo">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 d-flex flex-column gap-4">

                <div class="card">
                    <div class="card-header fw-semibold">Apoderado</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted fw-normal w-40">Nombre</th>
                                    <td id="apo-nombre">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">DNI</th>
                                    <td id="apo-dni">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Teléfono</th>
                                    <td id="apo-telefono">—</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Email</th>
                                    <td id="apo-email">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold d-flex justify-content-between">
                        <span>Avales</span>
                        <span class="badge bg-secondary" id="badge-avales-total">0</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Estado</th>
                                    <th class="text-end">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-avales-resumen"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        {{-- POSTULANTES --}}
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header fw-semibold d-flex justify-content-between">
                        <span>Titulares</span>
                        <span class="badge bg-secondary" id="badge-titulares">0</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-muted">#</th>
                                    <th>Apellido</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-titulares"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header fw-semibold d-flex justify-content-between">
                        <span>Suplentes</span>
                        <span class="badge bg-secondary" id="badge-suplentes">0</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-muted">#</th>
                                    <th>Apellido</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-suplentes"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script>

const LISTA_ID = {{ $id }};

const ETIQUETAS_TIPO = {
    superior:  'Consejo Superior',
    directivo: 'Consejo Directivo',
    decano:    'Decano',
    rector:    'Rector',
};

const COLORES_TIPO = {
    superior: 'primary', directivo: 'success',
    decano:   'warning', rector:    'danger',
};

const ETIQUETAS_ESTADO_AVAL = { valido: 'Válido', invalido: 'Inválido' };
const COLORES_ESTADO_AVAL   = { valido: 'success', invalido: 'danger' };

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

function setText(id, valor) {
    const el = document.getElementById(id);
    if (el) el.textContent = valor ?? '—';
}

async function cargarLista() {
    try {
        const res = await fetch(`/api/listas-eliminadas/${LISTA_ID}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (res.status === 404) { mostrarError('Lista no encontrada.'); return; }
        if (!res.ok) throw new Error(`Error ${res.status}`);
        const lista = await res.json();
        renderLista(lista);
    } catch (e) {
        mostrarError('No se pudo cargar la lista.');
        console.error(e);
    }
}

function renderLista(l) {
    const color    = COLORES_TIPO[l.tipo] ?? 'secondary';
    const etiqueta = ETIQUETAS_TIPO[l.tipo] ?? l.tipo;

    // Encabezado
    document.getElementById('titulo-lista').textContent =
        l.nombre + (l.sigla ? ` (${l.sigla})` : '');
    const badge = document.getElementById('badge-tipo');
    badge.textContent = etiqueta + (l.numero ? ` · Lista #${l.numero}` : '');
    badge.className = `badge bg-${color} mt-1`;

    // Datos de baja
    setText('baja-motivo',  l.motivo_baja ?? '—');
    setText('baja-usuario', l.eliminado_por?.name ?? '—');
    setText('baja-fecha',   formatearFecha(l.deleted_at));

    // Datos generales
    setText('dato-numero', l.numero ?? '—');
    setText('dato-nombre', l.nombre);
    setText('dato-sigla',  l.sigla  ?? '—');
    setText('dato-anio',   l.anio);
    setText('dato-tipo',   etiqueta);
    setText('dato-modo',   l.modo_carga === 'historica' ? 'Histórica' : 'Normal');

    if (l.facultad) setText('dato-facultad', l.facultad.nombre);
    else document.getElementById('fila-facultad').style.display = 'none';

    if (l.claustro) setText('dato-claustro', l.claustro.nombre);
    else document.getElementById('fila-claustro').style.display = 'none';

    // Apoderado
    if (l.apoderado) {
        setText('apo-nombre',   `${l.apoderado.apellido}, ${l.apoderado.nombre}`);
        setText('apo-dni',      l.apoderado.dni      ?? '—');
        setText('apo-telefono', l.apoderado.telefono ?? '—');
        setText('apo-email',    l.apoderado.email    ?? '—');
    }

    // Postulantes
    const titulares = (l.postulantes ?? []).filter(p => p.tipo === 'titular').sort((a,b) => a.orden - b.orden);
    const suplentes = (l.postulantes ?? []).filter(p => p.tipo === 'suplente').sort((a,b) => a.orden - b.orden);

    document.getElementById('badge-titulares').textContent = titulares.length;
    document.getElementById('badge-suplentes').textContent = suplentes.length;
    renderPostulantes('tabla-titulares', titulares);
    renderPostulantes('tabla-suplentes', suplentes);

    // Avales
    const avales = l.avales ?? [];
    document.getElementById('badge-avales-total').textContent = avales.length;
    const conteo = {};
    avales.forEach(a => { conteo[a.estado] = (conteo[a.estado] ?? 0) + 1; });
    const tbody = document.getElementById('tabla-avales-resumen');
    tbody.innerHTML = avales.length === 0
        ? `<tr><td colspan="2" class="text-muted text-center py-2">Sin avales cargados</td></tr>`
        : Object.entries(conteo).map(([estado, cant]) => `
            <tr>
                <td><span class="badge bg-${COLORES_ESTADO_AVAL[estado] ?? 'secondary'}">${esc(ETIQUETAS_ESTADO_AVAL[estado] ?? estado)}</span></td>
                <td class="text-end fw-semibold">${cant}</td>
            </tr>`).join('');

    document.getElementById('estado-carga').style.display   = 'none';
    document.getElementById('contenido-lista').style.display = 'block';
}

function renderPostulantes(tbodyId, postulantes) {
    const tbody = document.getElementById(tbodyId);
    if (postulantes.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center py-3">Sin postulantes</td></tr>`;
        return;
    }
    tbody.innerHTML = postulantes.map(p => {
        const persona = p.persona ?? {};
        return `
            <tr>
                <td class="text-muted">${esc(p.orden)}</td>
                <td>${esc(persona.apellido ?? '—')}</td>
                <td>${esc(persona.nombre   ?? '—')}</td>
                <td class="text-muted">${esc(persona.dni ?? '—')}</td>
            </tr>`;
    }).join('');
}

function mostrarError(msg) {
    document.getElementById('estado-carga').style.display = 'none';
    const el = document.getElementById('estado-error');
    el.textContent = msg;
    el.style.display = 'block';
}

cargarLista();

</script>

@endsection