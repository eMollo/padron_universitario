@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('listas.ver', $id) }}" class="text-muted text-decoration-none small">
                ← Volver al detalle
            </a>
            <h3 class="mb-0 mt-1" id="titulo-editar">Editar lista...</h3>
        </div>
        <button
            class="btn btn-outline-danger btn-sm"
            onclick="confirmarEliminar()"
            id="btn-eliminar"
            style="display:none;"
        >
            🗑 Eliminar lista
        </button>
    </div>

    <div id="estado-carga" class="text-muted mb-3">Cargando datos...</div>
    <div id="estado-error" class="alert alert-danger" style="display:none;"></div>
    <div id="mensaje" class="mb-3"></div>

    <form id="formEditar" style="display:none;">

        {{-- ========================= --}}
        {{-- DATOS GENERALES --}}
        {{-- ========================= --}}

        <div class="card mb-4">
            <div class="card-header fw-semibold">Datos de la lista</div>
            <div class="card-body">

                {{-- Info no editable --}}
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted small">Año</label>
                        <p class="fw-semibold mb-0" id="info-anio">—</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small">Tipo</label>
                        <p class="fw-semibold mb-0" id="info-tipo">—</p>
                    </div>
                    <div class="col-md-3" id="info-facultad-cont">
                        <label class="form-label text-muted small">Facultad</label>
                        <p class="fw-semibold mb-0" id="info-facultad">—</p>
                    </div>
                    <div class="col-md-3" id="info-claustro-cont">
                        <label class="form-label text-muted small">Claustro</label>
                        <p class="fw-semibold mb-0" id="info-claustro">—</p>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Campos editables --}}
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="nombre" class="form-label">Nombre de la lista</label>
                        <input
                            type="text"
                            class="form-control"
                            id="nombre"
                            maxlength="90"
                            required
                        >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="sigla" class="form-label">Sigla</label>
                        <input
                            type="text"
                            class="form-control"
                            id="sigla"
                            maxlength="13"
                        >
                    </div>
                </div>

            </div>
        </div>

        {{-- ========================= --}}
        {{-- APODERADO --}}
        {{-- ========================= --}}

        <div class="card mb-4">
            <div class="card-header fw-semibold">Apoderado</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="apoderado_dni" class="form-label">DNI</label>
                        <input type="text" class="form-control" id="apoderado_dni" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="apoderado_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="apoderado_nombre" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="apoderado_apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apoderado_apellido" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="apoderado_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="apoderado_telefono">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apoderado_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="apoderado_email">
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================= --}}
        {{-- TITULARES --}}
        {{-- ========================= --}}

        <div class="card mb-4">
            <div class="card-header fw-semibold">Titulares</div>
            <div class="card-body">
                <div id="contenedor-titulares"></div>
            </div>
        </div>

        {{-- ========================= --}}
        {{-- SUPLENTES --}}
        {{-- ========================= --}}

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                <span>Suplentes</span>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    id="btn-agregar-suplente"
                >
                    + Agregar suplente
                </button>
            </div>
            <div class="card-body">
                <div id="contenedor-suplentes"></div>
            </div>
        </div>

        {{-- ========================= --}}
        {{-- BOTONES --}}
        {{-- ========================= --}}

        <div class="d-flex gap-2 mb-5">
            <button type="submit" class="btn btn-primary" id="btn-guardar">
                Guardar cambios
            </button>
            <a href="{{ route('listas.ver', $id) }}" class="btn btn-outline-secondary">
                Cancelar
            </a>
        </div>

    </form>

</div>

{{-- Modal confirmación eliminar --}}
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar lista</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro que querés eliminar esta lista?</p>
                <p class="text-muted small">
                    La lista quedará registrada en el sistema pero no aparecerá
                    en las vistas normales. Su número quedará libre.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar">
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>


<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #e9ecef;
        border-radius: 4px;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>

<script>

const LISTA_ID = {{ $id }};

const ETIQUETAS_TIPO = {
    superior:  'Consejo Superior',
    directivo: 'Consejo Directivo',
    decano:    'Decano',
    rector:    'Rector',
};

let listaActual = null;
let cantidadSuplentes = 0;
let modalEliminar = null;

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
// CARGA INICIAL
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

        listaActual = await res.json();
        poblarFormulario(listaActual);

    } catch (e) {
        mostrarError('No se pudo cargar la lista.');
        console.error(e);
    }
}

// ============================================================
// POBLAR FORMULARIO
// ============================================================

function poblarFormulario(l) {
    // Título
    document.getElementById('titulo-editar').textContent =
        `Editar: ${l.nombre}${l.sigla ? ` (${l.sigla})` : ''}`;

    // Info no editable
    document.getElementById('info-anio').textContent  = l.anio;
    document.getElementById('info-tipo').textContent  = ETIQUETAS_TIPO[l.tipo] ?? l.tipo;

    if (l.facultad) {
        document.getElementById('info-facultad').textContent = l.facultad.nombre;
    } else {
        document.getElementById('info-facultad-cont').style.display = 'none';
    }

    if (l.claustro) {
        document.getElementById('info-claustro').textContent = l.claustro.nombre;
    } else {
        document.getElementById('info-claustro-cont').style.display = 'none';
    }

    // Campos editables
    document.getElementById('nombre').value = l.nombre ?? '';
    document.getElementById('sigla').value  = l.sigla  ?? '';

    // Apoderado
    if (l.apoderado) {
        document.getElementById('apoderado_dni').value      = l.apoderado.dni      ?? '';
        document.getElementById('apoderado_nombre').value   = l.apoderado.nombre   ?? '';
        document.getElementById('apoderado_apellido').value = l.apoderado.apellido ?? '';
        document.getElementById('apoderado_telefono').value = l.apoderado.telefono ?? '';
        document.getElementById('apoderado_email').value    = l.apoderado.email    ?? '';
    }

    // Postulantes
    const titulares = (l.postulantes ?? [])
        .filter(p => p.tipo === 'titular')
        .sort((a, b) => a.orden - b.orden);

    const suplentes = (l.postulantes ?? [])
        .filter(p => p.tipo === 'suplente')
        .sort((a, b) => a.orden - b.orden);

    const contTitulares = document.getElementById('contenedor-titulares');
    const contSuplentes = document.getElementById('contenedor-suplentes');

    contTitulares.innerHTML = '';
    contSuplentes.innerHTML = '';
    cantidadSuplentes = 0;

    titulares.forEach((p, i) => {
        const nombreCompleto = p.persona
            ? `${p.persona.apellido}, ${p.persona.nombre}`
            : '';
        agregarFilaPostulante(contTitulares, 'titulares', i + 1, p.persona?.dni ?? '', nombreCompleto);
    });

    suplentes.forEach((p, i) => {
        cantidadSuplentes++;
        const nombreCompleto = p.persona
            ? `${p.persona.apellido}, ${p.persona.nombre}`
            : '';
        agregarFilaPostulante(contSuplentes, 'suplentes', i + 1, p.persona?.dni ?? '', nombreCompleto);
    });

    actualizarBotonSuplente();

    // Activar drag & drop en ambos contenedores
    activarSortable('contenedor-titulares', '.fila-postulante:not(.fila-suplente)', 'titulares');
    activarSortable('contenedor-suplentes', '.fila-suplente', 'suplentes');

    // Mostrar formulario y botón eliminar
    document.getElementById('estado-carga').style.display = 'none';
    document.getElementById('formEditar').style.display   = 'block';
    document.getElementById('btn-eliminar').style.display = 'inline-block';
}

// ============================================================
// FILAS DE POSTULANTES
// ============================================================

function agregarFilaPostulante(contenedor, grupo, orden, dniInicial = '', nombreInicial = '') {
    const div = document.createElement('div');
    div.className = 'row mb-2 align-items-center fila-postulante';
    if (grupo === 'suplentes') div.classList.add('fila-suplente');
    div.dataset.orden = orden;

    div.innerHTML = `
        <div class="col-auto d-flex align-items-center gap-2" style="min-width:3.5rem;">
            <span class="drag-handle text-muted" style="cursor:grab; font-size:1.1rem;" title="Arrastrar para reordenar">⠿</span>
            <span class="text-muted numero-fila">${orden}</span>
        </div>
        <div class="col-md-3">
            <input
                type="text"
                class="form-control input-dni"
                placeholder="DNI"
                data-grupo="${grupo}"
                data-orden="${orden}"
                value="${esc(dniInicial)}"
                required
            >
        </div>
        <div class="col">
            <span class="nombre-persona text-muted fst-italic small">
                ${esc(nombreInicial) || '<span class="text-muted">—</span>'}
            </span>
        </div>
        ${grupo === 'suplentes' ? `
            <div class="col-auto">
                <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-suplente">
                    Quitar
                </button>
            </div>
        ` : ''}
    `;

    // Lookup al perder el foco en el DNI
    const inputDni = div.querySelector('.input-dni');
    const spanNombre = div.querySelector('.nombre-persona');

    inputDni.addEventListener('blur', async () => {
        const dni = inputDni.value.trim();
        if (!dni) {
            spanNombre.innerHTML = '<span class="text-muted">—</span>';
            return;
        }
        spanNombre.innerHTML = '<span class="text-muted fst-italic">Buscando...</span>';
        const persona = await buscarPersonaPorDni(dni);
        if (persona) {
            spanNombre.textContent = `${persona.apellido}, ${persona.nombre}`;
            spanNombre.className = 'nombre-persona small';
        } else {
            spanNombre.innerHTML = '<span class="text-danger small">DNI no encontrado</span>';
        }
    });

    if (grupo === 'suplentes') {
        div.querySelector('.btn-quitar-suplente').addEventListener('click', () => {
            div.remove();
            renumerarSuplentes();
        });
    }

    contenedor.appendChild(div);
}

// Cache para no repetir llamadas al mismo DNI
const cachePersonas = {};

async function buscarPersonaPorDni(dni) {
    if (cachePersonas[dni] !== undefined) return cachePersonas[dni];

    try {
        const res = await fetch('/api/personas/buscar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ dni }),
        });

        if (!res.ok) {
            cachePersonas[dni] = null;
            return null;
        }

        const data = await res.json();
        const persona = data.resultado?.[0] ?? null;
        cachePersonas[dni] = persona;
        return persona;

    } catch {
        cachePersonas[dni] = null;
        return null;
    }
}

function renumerarFilas(selector, grupo) {
    const filas = document.querySelectorAll(selector);
    filas.forEach((fila, i) => {
        const nuevoOrden = i + 1;
        fila.dataset.orden = nuevoOrden;
        fila.querySelector('.numero-fila').textContent = nuevoOrden;
        const input = fila.querySelector(`[data-grupo="${grupo}"]`);
        if (input) input.dataset.orden = nuevoOrden;
    });
    if (grupo === 'suplentes') {
        cantidadSuplentes = filas.length;
        actualizarBotonSuplente();
    }
}

function renumerarSuplentes() {
    renumerarFilas('.fila-suplente', 'suplentes');
}

function activarSortable(contenedorId, selector, grupo) {
    const el = document.getElementById(contenedorId);
    Sortable.create(el, {
        animation:  150,
        handle:     '.drag-handle',
        draggable:  selector,
        ghostClass: 'sortable-ghost',
        onEnd() {
            renumerarFilas(selector, grupo);
        },
    });
}

function obtenerMaxSuplentes() {
    if (!listaActual) return 0;
    const claustroId = listaActual.id_claustro;

    switch (listaActual.tipo) {
        case 'superior':
            return claustroId === 4 ? 4 : 12;
        case 'directivo':
            if (claustroId === 1) return 8;
            if (claustroId === 2) return 3;
            if (claustroId === 3) return 4;
            if (claustroId === 4) return 3;
            return 0;
        case 'decano':
        case 'rector':
            return 1;
        default:
            return 0;
    }
}

function actualizarBotonSuplente() {
    const btn = document.getElementById('btn-agregar-suplente');
    btn.disabled = cantidadSuplentes >= obtenerMaxSuplentes();
}

document.getElementById('btn-agregar-suplente').addEventListener('click', () => {
    if (cantidadSuplentes >= obtenerMaxSuplentes()) return;
    cantidadSuplentes++;
    agregarFilaPostulante(
        document.getElementById('contenedor-suplentes'),
        'suplentes',
        cantidadSuplentes
    );
    actualizarBotonSuplente();
});

// ============================================================
// SUBMIT — GUARDAR CAMBIOS
// ============================================================

document.getElementById('formEditar').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;

    document.getElementById('mensaje').innerHTML = '';

    const payload = {
        nombre: document.getElementById('nombre').value,
        sigla:  document.getElementById('sigla').value || null,

        apoderado: {
            dni:      document.getElementById('apoderado_dni').value,
            nombre:   document.getElementById('apoderado_nombre').value,
            apellido: document.getElementById('apoderado_apellido').value,
            telefono: document.getElementById('apoderado_telefono').value || null,
            email:    document.getElementById('apoderado_email').value    || null,
        },

        postulantes: {
            titulares: obtenerDNIs('titulares'),
            suplentes: obtenerDNIs('suplentes'),
        },
    };

    try {
        const res = await fetch(`/api/listas/${LISTA_ID}`, {
            method: 'PUT',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (!res.ok) {
            mostrarErrores(data);
            return;
        }

        document.getElementById('mensaje').innerHTML = `
            <div class="alert alert-success">Cambios guardados correctamente.</div>
        `;

        // Actualizar título con el nuevo nombre
        listaActual = data.lista;
        document.getElementById('titulo-editar').textContent =
            `Editar: ${data.lista.nombre}${data.lista.sigla ? ` (${data.lista.sigla})` : ''}`;

    } catch (err) {
        console.error(err);
        document.getElementById('mensaje').innerHTML = `
            <div class="alert alert-danger">Error de conexión con el servidor.</div>
        `;
    } finally {
        btn.disabled = false;
    }
});

// ============================================================
// ELIMINAR
// ============================================================

function confirmarEliminar() {
    modalEliminar = modalEliminar ?? new bootstrap.Modal(
        document.getElementById('modalEliminar')
    );
    modalEliminar.show();
}

document.getElementById('btn-confirmar-eliminar').addEventListener('click', async () => {
    const btn = document.getElementById('btn-confirmar-eliminar');
    btn.disabled = true;

    try {
        const res = await fetch(`/api/listas/${LISTA_ID}`, {
            method: 'DELETE',
            headers: {
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });

        if (!res.ok) throw new Error(`Error ${res.status}`);

        // Redirigir al índice después de eliminar
        window.location.href = '{{ route("listas.index") }}';

    } catch (err) {
        console.error(err);
        modalEliminar.hide();
        document.getElementById('mensaje').innerHTML = `
            <div class="alert alert-danger">No se pudo eliminar la lista.</div>
        `;
    } finally {
        btn.disabled = false;
    }
});

// ============================================================
// UTILIDADES
// ============================================================

function obtenerDNIs(grupo) {
    return Array.from(
        document.querySelectorAll(`[data-grupo="${grupo}"]`)
    ).map(input => ({ dni: input.value }));
}

function mostrarError(msg) {
    document.getElementById('estado-carga').style.display = 'none';
    const el = document.getElementById('estado-error');
    el.textContent = msg;
    el.style.display = 'block';
}

function mostrarErrores(data) {
    let html = '';

    if (data.error) {
        html += `<div class="alert alert-danger"><strong>${esc(data.error)}</strong></div>`;
    }

    if (Array.isArray(data.details)) {
        data.details.forEach(d => {
            html += `
                <div class="alert alert-warning">
                    ${esc(d.message ?? 'Error de validación')}
                    ${d.dni    ? `<br>DNI: ${esc(d.dni)}`       : ''}
                    ${d.nombre ? `<br>Persona: ${esc(d.nombre)}` : ''}
                </div>
            `;
        });
    }

    if (!html) html = `<div class="alert alert-danger">Error desconocido.</div>`;

    document.getElementById('mensaje').innerHTML = html;
}

// ============================================================
// ARRANQUE
// ============================================================

cargarLista();

</script>

@endsection