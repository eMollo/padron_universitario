@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('listas.index') }}" class="text-muted text-decoration-none small">
                ← Volver a Listas
            </a>
            <h3 class="mb-0 mt-1">Asignación de Bancas y Proclamados</h3>
        </div>
    </div>

    <div class="alert alert-info small mb-4">
        Solo se muestran listas en estado <strong>oficializada</strong>.
        Los <span class="badge bg-success">Proclamados</span> son los primeros N titulares
        y primeros N suplentes según las bancas asignadas.
        Asignar <strong>0 bancas</strong> quita todos los proclamados de esa lista.
    </div>

    {{-- FILTROS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <label class="form-label">Año</label>
            <input type="number" id="filtroAnio" class="form-control" value="{{ date('Y') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select id="filtroTipo" class="form-select">
                <option value="superior">Consejo Superior</option>
                <option value="directivo">Consejo Directivo</option>
                <option value="decano">Decano</option>
                <option value="rector">Rector</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" onclick="cargarListas()">Cargar</button>
        </div>
    </div>

    <div id="estado-carga" class="text-muted" style="display:none;">Cargando...</div>
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

// CARGA

async function cargarListas() {
    const anio = document.getElementById('filtroAnio').value;
    const tipo = document.getElementById('filtroTipo').value;

    document.getElementById('estado-carga').style.display = 'block';
    document.getElementById('resultado').innerHTML = '';

    try {
        const res = await fetch('/api/proclamados/por-tipo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ anio, tipo }),
        });

        if (!res.ok) throw new Error(`Error ${res.status}`);
        const listas = await res.json();

        document.getElementById('estado-carga').style.display = 'none';
        renderResultado(listas, tipo);

    } catch (e) {
        document.getElementById('estado-carga').textContent = 'Error al cargar.';
        console.error(e);
    }
}

// RENDER PRINCIPAL — agrupa por claustro para superior/directivo

function renderResultado(listas, tipo) {
    const contenedor = document.getElementById('resultado');

    if (listas.length === 0) {
        contenedor.innerHTML = `<p class="text-muted">No hay listas oficializadas de tipo
            <strong>${ETIQUETAS_TIPO[tipo]}</strong> para el año seleccionado.</p>`;
        return;
    }

    if (['superior', 'directivo'].includes(tipo)) {
        // Agrupar por claustro
        const grupos = {};
        listas.forEach(l => {
            const k = l.claustro?.nombre ?? 'Sin claustro';
            if (!grupos[k]) grupos[k] = [];
            grupos[k].push(l);
        });
        contenedor.innerHTML = Object.entries(grupos).map(([claustro, items]) => `
            <h5 class="border-bottom pb-1 mt-4 mb-3">${esc(claustro)}</h5>
            ${items.map(l => cardLista(l, tipo)).join('')}
        `).join('');
    } else {
        contenedor.innerHTML = listas.map(l => cardLista(l, tipo)).join('');
    }
}

// CARD DE LISTA

function cardLista(l, tipo) {
    const bancas         = l.bancas ?? 0;
    const esDecanoRector = ['decano', 'rector'].includes(tipo);
    const facultadLabel  = l.facultad ? ` · ${esc(l.facultad.nombre)}` : '';

    const titulares = (l.postulantes ?? [])
        .filter(p => p.tipo === 'titular').sort((a, b) => a.orden - b.orden);
    const suplentes = (l.postulantes ?? [])
        .filter(p => p.tipo === 'suplente').sort((a, b) => a.orden - b.orden);

    // Control de bancas: checkbox para decano/rector, input numérico para los demás
    const controlBancas = esDecanoRector
        ? `<div class="form-check form-switch mb-0">
               <input class="form-check-input" type="checkbox" id="ganador-${l.id}"
                   ${bancas > 0 ? 'checked' : ''}
                   onchange="toggleGanador(${l.id}, this.checked)">
               <label class="form-check-label small" for="ganador-${l.id}">Lista ganadora</label>
           </div>`
        : `<div class="d-flex align-items-center gap-2">
               <label class="form-label mb-0 text-muted small">Bancas:</label>
               <input type="number" class="form-control form-control-sm" style="width:5rem;"
                   id="bancas-${l.id}" value="${bancas}" min="0" max="${titulares.length}">
               <button class="btn btn-sm btn-primary" onclick="guardarBancas(${l.id})">
                   Guardar
               </button>
               <span class="small" id="feedback-${l.id}"></span>
           </div>`;

    return `
        <div class="card mb-3" id="card-lista-${l.id}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-semibold">Lista #${esc(l.numero)} — ${esc(l.nombre)}</span>
                    ${l.sigla ? `<span class="text-muted small ms-1">(${esc(l.sigla)})</span>` : ''}
                    <span class="text-muted small">${facultadLabel}</span>
                </div>
                ${controlBancas}
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col-md-6 border-end">
                        <div class="px-3 py-2 bg-light border-bottom">
                            <small class="fw-semibold text-muted">TITULARES</small>
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody id="titulares-${l.id}">
                                ${renderFilasPostulante(titulares, bancas)}
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="px-3 py-2 bg-light border-bottom">
                            <small class="fw-semibold text-muted">SUPLENTES</small>
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody id="suplentes-${l.id}">
                                ${renderFilasPostulante(suplentes, bancas)}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// FILAS DE POSTULANTES
// bancas > 0 && orden <= bancas  →  proclamado natural

function renderFilasPostulante(postulantes, bancas) {
    if (postulantes.length === 0) {
        return `<tr><td class="text-muted text-center py-2 px-3">Sin postulantes</td></tr>`;
    }
    return postulantes.map(p => {
        const persona     = p.persona ?? {};
        const proclamado  = bancas > 0 && p.orden <= bancas;
        return `
            <tr class="${proclamado ? 'table-success' : ''}">
                <td class="text-muted ps-3" style="width:2rem;">${esc(p.orden)}</td>
                <td>${esc(persona.apellido ?? '—')}, ${esc(persona.nombre ?? '—')}</td>
                <td class="text-end pe-3">
                    ${proclamado
                        ? '<span class="badge bg-success">Proclamado</span>'
                        : ''}
                </td>
            </tr>
        `;
    }).join('');
}

// GUARDAR BANCAS (superior / directivo)

async function guardarBancas(listaId) {
    const input    = document.getElementById(`bancas-${listaId}`);
    const feedback = document.getElementById(`feedback-${listaId}`);
    const bancas   = parseInt(input.value) || 0;

    feedback.textContent = 'Guardando...';
    feedback.className   = 'small text-muted';

    const ok = await enviarBancas(listaId, bancas);

    if (ok) {
        feedback.textContent = '✓ Guardado';
        feedback.className   = 'small text-success';
        actualizarProclamadosEnCard(listaId, bancas);
        setTimeout(() => { feedback.textContent = ''; }, 2500);
    } else {
        feedback.textContent = 'Error al guardar';
        feedback.className   = 'small text-danger';
    }
}

// TOGGLE GANADOR (decano / rector)

async function toggleGanador(listaId, ganador) {
    const bancas = ganador ? 1 : 0;
    const ok     = await enviarBancas(listaId, bancas);

    if (ok) {
        actualizarProclamadosEnCard(listaId, bancas);
    } else {
        document.getElementById(`ganador-${listaId}`).checked = !ganador;
        alert('Error al guardar. Intentá de nuevo.');
    }
}

// FETCH COMÚN

async function enviarBancas(listaId, bancas) {
    try {
        const res = await fetch(`/api/proclamados/${listaId}/bancas`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ bancas }),
        });
        return res.ok;
    } catch {
        return false;
    }
}

// ACTUALIZAR FILAS EN EL DOM SIN RECARGAR
// Recalcula proclamados usando orden <= bancas

function actualizarProclamadosEnCard(listaId, bancas) {
    ['titulares', 'suplentes'].forEach(grupo => {
        const tbody = document.getElementById(`${grupo}-${listaId}`);
        if (!tbody) return;
        tbody.querySelectorAll('tr').forEach((fila, i) => {
            // i es 0-based, orden es 1-based
            const orden      = i + 1;
            const proclamado = bancas > 0 && orden <= bancas;
            fila.className   = proclamado ? 'table-success' : '';
            const badgeCell  = fila.querySelector('td:last-child');
            if (badgeCell) {
                badgeCell.innerHTML = proclamado
                    ? '<span class="badge bg-success">Proclamado</span>'
                    : '';
            }
        });
    });
}

</script>

@endsection