@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Listas Electorales</h3>
        @if(auth()->user()?->hasRole('admin'))
            <div class="d-flex gap-2">
                <a href="{{ route('listas.eliminadas') }}" class="btn btn-outline-danger btn-sm">
                    🗑 Ver eliminadas
                </a>
                <a href="{{ route('listas.crear') }}" class="btn btn-primary">
                    + Nueva Lista
                </a>
            </div>
        @endif
    </div>

    {{-- SELECTOR DE AÑO --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <label class="form-label">Año</label>
            <div class="input-group">
                <input
                    type="number"
                    id="filtroAnio"
                    class="form-control"
                    value="{{ date('Y') }}"
                >
                <button class="btn btn-outline-secondary" onclick="cargarTodo()">
                    Filtrar
                </button>
            </div>
        </div>
    </div>

    {{-- PESTAÑAS: MODO DE VISTA --}}
    <ul class="nav nav-tabs mb-4" id="modoVista">
        <li class="nav-item">
            <button
                class="nav-link active"
                data-modo="tipo"
                onclick="cambiarModo('tipo')"
            >
                Por Tipo
            </button>
        </li>
        <li class="nav-item">
            <button
                class="nav-link"
                data-modo="facultad"
                onclick="cambiarModo('facultad')"
            >
                Por Unidad Electoral
            </button>
        </li>
    </ul>

    {{-- =============================== --}}
    {{-- MODO: POR TIPO --}}
    {{-- =============================== --}}
    <div id="vista-tipo">

        {{-- Botones de tipo --}}
        <div class="d-flex gap-2 flex-wrap mb-3" id="botonesTipo">
            <button class="btn btn-outline-primary activo-tipo" data-tipo="superior"  onclick="seleccionarTipo('superior')">Consejo Superior</button>
            <button class="btn btn-outline-primary"              data-tipo="directivo" onclick="seleccionarTipo('directivo')">Consejo Directivo</button>
            <button class="btn btn-outline-primary"              data-tipo="decano"    onclick="seleccionarTipo('decano')">Decano</button>
            <button class="btn btn-outline-primary"              data-tipo="rector"    onclick="seleccionarTipo('rector')">Rector</button>
        </div>

        {{-- Filtro por facultad (solo cuando aplica) --}}
        <div id="contenedor-filtro-facultad" class="mb-3" style="display:none;">
            <select id="filtroFacultadTipo" class="form-select w-auto" onchange="renderVistaTipo()">
                <option value="">Todas las facultades</option>
            </select>
        </div>

        {{-- Resultado --}}
        <div id="resultado-tipo">
            <p class="text-muted">Cargando...</p>
        </div>

    </div>

    {{-- =============================== --}}
    {{-- MODO: POR UNIDAD ELECTORAL --}}
    {{-- =============================== --}}
    <div id="vista-facultad" style="display:none;">

        {{-- Botones de facultad --}}
        <div class="d-flex gap-2 flex-wrap mb-3" id="botonesFacultad">
            {{-- Se llenan dinámicamente --}}
        </div>

        {{-- Filtro por tipo --}}
        <div id="contenedor-filtro-tipo" class="mb-3" style="display:none;">
            <select id="filtroTipoFacultad" class="form-select w-auto" onchange="renderVistaFacultad()">
                <option value="">Todos los tipos</option>
                <option value="superior">Consejo Superior</option>
                <option value="directivo">Consejo Directivo</option>
                <option value="decano">Decano</option>
                <option value="rector">Rector</option>
            </select>
        </div>

        {{-- Resultado --}}
        <div id="resultado-facultad">
            <p class="text-muted">Seleccioná una facultad.</p>
        </div>

    </div>

</div>


<script>

// ============================================================
// ESTADO
// ============================================================

let todasLasListas = [];
let catalogoFacultades = [];
let modoActual = 'tipo';
let tipoSeleccionado = 'superior';
let facultadSeleccionada = null;

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


// ============================================================
// CARGA INICIAL
// ============================================================

async function cargarTodo() {
    const anio = document.getElementById('filtroAnio').value;

    try {
        const [listas, facultades] = await Promise.all([
            apiFetch(`/api/listas?anio=${anio}`),
            apiFetch('/api/facultad'),
        ]);

        todasLasListas    = listas;
        catalogoFacultades = facultades;

        llenarFiltroFacultades();
        llenarBotonesFacultad();

        if (modoActual === 'tipo') {
            renderVistaTipo();
        } else {
            renderVistaFacultad();
        }

    } catch (e) {
        console.error(e);
    }
}


// ============================================================
// CAMBIAR MODO (PESTAÑAS)
// ============================================================

function cambiarModo(modo) {
    modoActual = modo;

    document.querySelectorAll('#modoVista .nav-link').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.modo === modo);
    });

    document.getElementById('vista-tipo').style.display     = modo === 'tipo'     ? 'block' : 'none';
    document.getElementById('vista-facultad').style.display = modo === 'facultad' ? 'block' : 'none';

    if (modo === 'facultad' && !facultadSeleccionada && catalogoFacultades.length > 0) {
        seleccionarFacultad(catalogoFacultades[0].id);
    }
}


// ============================================================
// MODO POR TIPO
// ============================================================

function seleccionarTipo(tipo) {
    tipoSeleccionado = tipo;

    document.querySelectorAll('[data-tipo]').forEach(btn => {
        btn.classList.toggle('activo-tipo', btn.dataset.tipo === tipo);
        btn.classList.toggle('btn-primary', btn.dataset.tipo === tipo);
        btn.classList.toggle('btn-outline-primary', btn.dataset.tipo !== tipo);
    });

    // Solo directivo y decano tienen filtro por facultad
    const conFacultad = ['directivo', 'decano'].includes(tipo);
    document.getElementById('contenedor-filtro-facultad').style.display = conFacultad ? 'block' : 'none';

    renderVistaTipo();
}

function llenarFiltroFacultades() {
    const sel = document.getElementById('filtroFacultadTipo');
    const valorActual = sel.value;

    sel.innerHTML = '<option value="">Todas las facultades</option>';

    catalogoFacultades.forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.id;
        opt.textContent = f.nombre;
        sel.appendChild(opt);
    });

    if (valorActual) sel.value = valorActual;
}

function renderVistaTipo() {
    const filtroFacultad = parseInt(document.getElementById('filtroFacultadTipo').value) || null;

    let listas = todasLasListas.filter(l => l.tipo === tipoSeleccionado);

    if (filtroFacultad) {
        listas = listas.filter(l => l.id_facultad === filtroFacultad);
    }

    const contenedor = document.getElementById('resultado-tipo');

    if (listas.length === 0) {
        contenedor.innerHTML = `<p class="text-muted">No hay listas de este tipo para el año seleccionado.</p>`;
        return;
    }

    // Agrupar según el tipo
    if (tipoSeleccionado === 'directivo') {
        // Agrupar por claustro → dentro por facultad
        contenedor.innerHTML = renderAgrupadoPorClaustroYFacultad(listas);
    } else if (tipoSeleccionado === 'superior') {
        // Agrupar por claustro
        contenedor.innerHTML = renderAgrupadoPorClaustro(listas);
    } else if (tipoSeleccionado === 'decano') {
        // Agrupar por facultad
        contenedor.innerHTML = renderAgrupadoPorFacultad(listas);
    } else {
        // Rector: sin agrupación
        contenedor.innerHTML = `
            <div class="row g-3">
                ${listas.map(l => cardLista(l)).join('')}
            </div>
        `;
    }
}


// ============================================================
// MODO POR UNIDAD ELECTORAL
// ============================================================

function llenarBotonesFacultad() {
    const cont = document.getElementById('botonesFacultad');

    // Solo mostrar facultades que tengan listas
    const idsConListas = new Set(
        todasLasListas.map(l => l.id_facultad).filter(Boolean)
    );

    const facultadesConListas = catalogoFacultades.filter(f => idsConListas.has(f.id));

    cont.innerHTML = facultadesConListas.map(f => `
        <button
            class="btn btn-outline-secondary"
            data-facultad-id="${f.id}"
            onclick="seleccionarFacultad(${f.id})"
        >
            ${f.nombre}
        </button>
    `).join('');
}

function seleccionarFacultad(id) {
    facultadSeleccionada = id;

    document.querySelectorAll('[data-facultad-id]').forEach(btn => {
        const activo = parseInt(btn.dataset.facultadId) === id;
        btn.classList.toggle('btn-secondary', activo);
        btn.classList.toggle('btn-outline-secondary', !activo);
    });

    document.getElementById('contenedor-filtro-tipo').style.display = 'block';

    renderVistaFacultad();
}

function renderVistaFacultad() {
    if (!facultadSeleccionada) return;

    const filtroTipo = document.getElementById('filtroTipoFacultad').value;

    let listas = todasLasListas.filter(l => l.id_facultad === facultadSeleccionada);

    // Rector aplica a todas → también lo incluimos aunque no tenga id_facultad
    const listasRector = filtroTipo === '' || filtroTipo === 'rector'
        ? todasLasListas.filter(l => l.tipo === 'rector')
        : [];

    if (filtroTipo) {
        listas = listas.filter(l => l.tipo === filtroTipo);
    }

    // Rector: unir y deduplicar
    if (!filtroTipo || filtroTipo === 'rector') {
        const idsYa = new Set(listas.map(l => l.id));
        listasRector.forEach(l => { if (!idsYa.has(l.id)) listas.push(l); });
    }

    const contenedor = document.getElementById('resultado-facultad');

    if (listas.length === 0) {
        contenedor.innerHTML = `<p class="text-muted">No hay listas para esta facultad en el año seleccionado.</p>`;
        return;
    }

    // Agrupar por tipo
    const grupos = {};
    listas.forEach(l => {
        if (!grupos[l.tipo]) grupos[l.tipo] = [];
        grupos[l.tipo].push(l);
    });

    const ordenTipos = ['superior', 'directivo', 'decano', 'rector'];

    let html = '';

    ordenTipos.forEach(tipo => {
        if (!grupos[tipo]) return;

        const color = COLORES_TIPO[tipo];

        html += `
            <h5 class="mt-4 mb-3 text-${color}">${ETIQUETAS_TIPO[tipo]}</h5>
        `;

        if (tipo === 'directivo') {
            html += renderAgrupadoPorClaustro(grupos[tipo]);
        } else {
            html += `<div class="row g-3">${grupos[tipo].map(l => cardLista(l)).join('')}</div>`;
        }
    });

    contenedor.innerHTML = html;
}


// ============================================================
// AGRUPADORES
// ============================================================

function renderAgrupadoPorClaustro(listas) {
    const grupos = agruparPor(listas, l => l.claustro?.nombre ?? 'Sin claustro');
    return Object.entries(grupos).map(([nombre, items]) => `
        <h6 class="text-muted mt-3 mb-2">${esc(nombre)}</h6>
        <div class="row g-3 mb-3">
            ${items.map(l => cardLista(l)).join('')}
        </div>
    `).join('');
}

function renderAgrupadoPorFacultad(listas) {
    const grupos = agruparPor(listas, l => l.facultad?.nombre ?? 'Sin facultad');
    return Object.entries(grupos).map(([nombre, items]) => `
        <h6 class="text-muted mt-3 mb-2">${esc(nombre)}</h6>
        <div class="row g-3 mb-3">
            ${items.map(l => cardLista(l)).join('')}
        </div>
    `).join('');
}

function renderAgrupadoPorClaustroYFacultad(listas) {
    // Consejo Directivo: primero por claustro, dentro de cada uno por facultad
    const porClaustro = agruparPor(listas, l => l.claustro?.nombre ?? 'Sin claustro');

    return Object.entries(porClaustro).map(([claustro, items]) => {
        const porFacultad = agruparPor(items, l => l.facultad?.nombre ?? 'Sin facultad');

        const subgrupos = Object.entries(porFacultad).map(([facultad, sublistas]) => `
            <h6 class="text-muted mb-2 ms-2">↳ ${esc(facultad)}</h6>
            <div class="row g-3 mb-3 ms-1">
                ${sublistas.map(l => cardLista(l)).join('')}
            </div>
        `).join('');

        return `
            <div class="mb-4">
                <h5 class="border-bottom pb-1">${esc(claustro)}</h5>
                ${subgrupos}
            </div>
        `;
    }).join('');
}


// ============================================================
// CARD DE LISTA
// ============================================================

function cardLista(l) {
    const color  = COLORES_TIPO[l.tipo] ?? 'secondary';
    const numero = l.numero ? `#${esc(l.numero)}` : 'Sin número';
    const sigla  = l.sigla  ? ` · ${esc(l.sigla)}` : '';
    const apoderado = l.apoderado
        ? `${esc(l.apoderado.apellido)}, ${esc(l.apoderado.nombre)}`
        : '—';

    return `
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-${color}">
                <div class="card-header bg-${color} bg-opacity-10 d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Lista ${numero}</span>
                    <span class="badge bg-${color}">${ETIQUETAS_TIPO[l.tipo]}</span>
                </div>
                <div class="card-body">
                    <p class="card-title fw-semibold mb-1">${esc(l.nombre)}${sigla}</p>
                    <p class="card-text text-muted small mb-1">
                        Apoderado: ${apoderado}
                    </p>
                    ${l.facultad ? `<p class="card-text text-muted small mb-1">${esc(l.facultad.nombre)}</p>` : ''}
                    ${l.claustro ? `<p class="card-text text-muted small mb-0">${esc(l.claustro.nombre)}</p>` : ''}
                </div>
                <div class="card-footer bg-transparent">
                    <a href="/listas/${l.id}" class="btn btn-sm btn-outline-${color} w-100">
                        Ver detalle
                    </a>
                </div>
            </div>
        </div>
    `;
}


// ============================================================
// UTILIDADES
// ============================================================

/**
 * Escapa caracteres HTML para evitar XSS al interpolar datos
 * de la BD en template strings.
 */
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

function agruparPor(arr, keyFn) {
    return arr.reduce((acc, item) => {
        const k = keyFn(item);
        if (!acc[k]) acc[k] = [];
        acc[k].push(item);
        return acc;
    }, {});
}


// ============================================================
// ARRANQUE
// ============================================================

cargarTodo().then(() => seleccionarTipo('superior'));

</script>

@endsection
