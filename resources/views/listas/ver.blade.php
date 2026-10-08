@extends('layouts.app')

@section('content')

<div class="container-fluid pb-4" id="app-lista">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('listas.index') }}" class="text-muted text-decoration-none small">
                ← Volver a Listas
            </a>
            <h3 class="mb-0 mt-1" id="titulo-lista">Cargando...</h3>
            <span class="badge mt-1" id="badge-tipo"></span>
        </div>
        @if(auth()->user()?->hasRole('admin'))
        <div class="d-flex gap-2">
            <a href="/listas/{{ $id }}/editar" class="btn btn-outline-secondary btn-sm">
                 Editar
            </a>
        </div>
        @endif
    </div>

    {{-- LOADING / ERROR --}}
    <div id="estado-carga" class="text-muted">Cargando datos...</div>
    <div id="estado-error" class="alert alert-danger" style="display:none;"></div>

    {{-- CONTENIDO (oculto hasta que cargue) --}}
    <div id="contenido-lista" style="display:none;">

        {{-- FILA: datos generales + avales --}}
        <div class="row g-4 mb-4">

            {{-- Datos generales --}}
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

            {{-- Apoderado + Avales --}}
            <div class="col-md-6 d-flex flex-column gap-4">

                {{-- Apoderado --}}
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

                {{-- Avales --}}
                <div class="card" id="card-avales">
                    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
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
                    @if(auth()->user()?->hasRole('admin'))
                    <div class="card-footer bg-transparent">
                        <a href="#" class="btn btn-sm btn-outline-secondary">
                            Importar avales
                        </a>
                        <button
                            class="btn btn-sm btn-outline-primary ms-2"
                            onclick="toggleDetalleAvales()"
                            id="btn-detalle-avales"
                        >
                            Ver detalle
                        </button>
                    </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- DETALLE DE AVALES (desplegable) --}}
        <div id="detalle-avales" style="display:none;" class="mb-4">
            <div class="card">
                <div class="card-header fw-semibold">Detalle de avales</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Apellido</th>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Estado</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-avales-detalle"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- POSTULANTES --}}
        <div class="row g-4">

            {{-- Titulares --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header fw-semibold d-flex justify-content-between">
                        <span>Titulares</span>
                        <span class="badge bg-primary" id="badge-titulares">0</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
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

            {{-- Suplentes --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header fw-semibold d-flex justify-content-between">
                        <span>Suplentes</span>
                        <span class="badge bg-secondary" id="badge-suplentes">0</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
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

    </div>{{-- fin #contenido-lista --}}

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
    superior:  'primary',
    directivo: 'success',
    decano:    'warning',
    rector:    'danger',
};

const ETIQUETAS_ESTADO_AVAL = {
    valido:   'Válido',
    invalido: 'Inválido',
};

const COLORES_ESTADO_AVAL = {
    valido:   'success',
    invalido: 'danger',
};

// ============================================================
// ESCAPE
// ============================================================

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

// ============================================================
// CARGA
// ============================================================

async function cargarLista() {
    try {
        const res = await fetch(`/api/listas/${LISTA_ID}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (res.status === 404) {
            mostrarError('Lista no encontrada.');
            return;
        }
        if (!res.ok) throw new Error(`Error ${res.status}`);

        const lista = await res.json();
        renderLista(lista);

    } catch (e) {
        mostrarError('No se pudo cargar la lista. Intentá de nuevo más tarde.');
        console.error(e);
    }
}

// ============================================================
// RENDER
// ============================================================

function renderLista(l) {
    const color = COLORES_TIPO[l.tipo] ?? 'secondary';
    const etiqueta = ETIQUETAS_TIPO[l.tipo] ?? l.tipo;

    // Encabezado
    document.getElementById('titulo-lista').textContent =
        l.nombre + (l.sigla ? ` (${l.sigla})` : '');

    const badge = document.getElementById('badge-tipo');
    badge.textContent = etiqueta + (l.numero ? ` · Lista #${l.numero}` : '');
    badge.className = `badge bg-${color} mt-1`;

    // Datos generales
    setText('dato-numero',  l.numero   ?? '—');
    setText('dato-nombre',  l.nombre);
    setText('dato-sigla',   l.sigla    ?? '—');
    setText('dato-anio',    l.anio);
    setText('dato-tipo',    etiqueta);
    setText('dato-modo',    l.modo_carga === 'historica' ? 'Histórica' : 'Normal');

    // Facultad / Claustro según tipo
    if (l.facultad) {
        setText('dato-facultad', l.facultad.nombre);
    } else {
        document.getElementById('fila-facultad').style.display = 'none';
    }

    if (l.claustro) {
        setText('dato-claustro', l.claustro.nombre);
    } else {
        document.getElementById('fila-claustro').style.display = 'none';
    }

    // Apoderado
    if (l.apoderado) {
        setText('apo-nombre',    `${l.apoderado.apellido}, ${l.apoderado.nombre}`);
        setText('apo-dni',       l.apoderado.dni    ?? '—');
        setText('apo-telefono',  l.apoderado.telefono ?? '—');
        setText('apo-email',     l.apoderado.email    ?? '—');
    }

    // Postulantes
    const titulares = (l.postulantes ?? []).filter(p => p.tipo === 'titular')
        .sort((a, b) => a.orden - b.orden);
    const suplentes = (l.postulantes ?? []).filter(p => p.tipo === 'suplente')
        .sort((a, b) => a.orden - b.orden);

    document.getElementById('badge-titulares').textContent = titulares.length;
    document.getElementById('badge-suplentes').textContent = suplentes.length;

    renderPostulantes('tabla-titulares', titulares);
    renderPostulantes('tabla-suplentes', suplentes);

    // Avales
    renderAvales(l.avales ?? []);

    // Mostrar contenido
    document.getElementById('estado-carga').style.display  = 'none';
    document.getElementById('contenido-lista').style.display = 'block';
}

function renderPostulantes(tbodyId, postulantes) {
    const tbody = document.getElementById(tbodyId);

    if (postulantes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-muted text-center py-3">Sin postulantes cargados</td>
            </tr>`;
        return;
    }

    tbody.innerHTML = postulantes.map(p => {
        const persona = p.persona ?? {};

        // Para Consejo Superior: tooltip con la facultad en la celda de apellido
        const celdaApellido = p.facultad_nombre
            ? `<td title="${esc(p.facultad_nombre)}" style="cursor:help; text-decoration: underline dotted;">${esc(persona.apellido ?? '—')}</td>`
            : `<td>${esc(persona.apellido ?? '—')}</td>`;

        return `
            <tr>
                <td class="text-muted">${esc(p.orden)}</td>
                ${celdaApellido}
                <td>${esc(persona.nombre ?? '—')}</td>
                <td class="text-muted">${esc(persona.dni ?? '—')}</td>
            </tr>
        `;
    }).join('');
}

function renderAvales(avales) {
    const total = avales.length;
    document.getElementById('badge-avales-total').textContent = total;

    // Resumen por estado
    const conteo = {};
    avales.forEach(a => {
        conteo[a.estado] = (conteo[a.estado] ?? 0) + 1;
    });

    const tbodyResumen = document.getElementById('tabla-avales-resumen');

    if (total === 0) {
        tbodyResumen.innerHTML = `
            <tr>
                <td colspan="2" class="text-muted text-center py-2">Sin avales cargados</td>
            </tr>`;
    } else {
        tbodyResumen.innerHTML = Object.entries(conteo).map(([estado, cant]) => {
            const color = COLORES_ESTADO_AVAL[estado] ?? 'secondary';
            const etiq  = ETIQUETAS_ESTADO_AVAL[estado] ?? estado;
            return `
                <tr>
                    <td><span class="badge bg-${color}">${esc(etiq)}</span></td>
                    <td class="text-end fw-semibold">${cant}</td>
                </tr>
            `;
        }).join('');
    }

    // Detalle completo
    const tbodyDetalle = document.getElementById('tabla-avales-detalle');
    tbodyDetalle.innerHTML = avales.map(a => {
        const persona = a.persona ?? {};
        const color = COLORES_ESTADO_AVAL[a.estado] ?? 'secondary';
        const etiq  = ETIQUETAS_ESTADO_AVAL[a.estado] ?? a.estado;
        return `
            <tr>
                <td>${esc(persona.apellido ?? '—')}</td>
                <td>${esc(persona.nombre   ?? '—')}</td>
                <td class="text-muted">${esc(persona.dni ?? '—')}</td>
                <td><span class="badge bg-${color}">${esc(etiq)}</span></td>
                <td class="text-muted small">${esc(a.motivo_invalidez ?? '')}</td>
            </tr>
        `;
    }).join('');
}

// ============================================================
// TOGGLE DETALLE AVALES
// ============================================================

function toggleDetalleAvales() {
    const detalle = document.getElementById('detalle-avales');
    const btn     = document.getElementById('btn-detalle-avales');
    const visible = detalle.style.display !== 'none';

    detalle.style.display = visible ? 'none' : 'block';
    btn.textContent       = visible ? 'Ver detalle' : 'Ocultar detalle';
}

// ============================================================
// UTILIDADES
// ============================================================

// Asigna texto escapado a un elemento por id
function setText(id, valor) {
    const el = document.getElementById(id);
    if (el) el.textContent = valor ?? '—';
}

// ============================================================
// ARRANQUE
// ============================================================

cargarLista();

</script>

@endsection