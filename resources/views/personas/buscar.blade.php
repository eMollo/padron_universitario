@extends('layouts.app')

@section('content')

<div class="container">

    <h3>Buscar Persona</h3>

    <div class="row mb-3">

        <div class="col-md-2">
            <input type="text" id="dni" class="form-control" placeholder="DNI">
        </div>

        <div class="col-md-3">
            <input type="text" id="apellido" class="form-control" placeholder="Apellido">
        </div>

        <div class="col-md-3">
            <input type="text" id="nombre" class="form-control" placeholder="Nombre">
        </div>

        <div class="col-md-2">
            <input type="number" id="anio" class="form-control" placeholder="Año">
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100" onclick="nuevaBusqueda()">
                Buscar
            </button>
        </div>

    </div>

    <div id="resultados"></div>

</div>

@if(auth()->user()->hasRole('admin'))
<!-- MODAL EDICIÓN -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Editar Persona</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" id="edit_persona_id">
        <input type="hidden" id="edit_inscripcion_id">

        <div class="mb-2">
            <label>DNI</label>
            <input type="text" id="edit_dni" class="form-control">
        </div>

        <div class="mb-2">
            <label>Apellido</label>
            <input type="text" id="edit_apellido" class="form-control">
        </div>

        <div class="mb-2">
            <label>Nombre</label>
            <input type="text" id="edit_nombre" class="form-control">
        </div>

        <div class="mb-2">
            <label>Legajo</label>
            <input type="text" id="edit_legajo" class="form-control">
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-success" onclick="guardarEdicion()">
            Guardar
        </button>
      </div>

    </div>
  </div>
</div>
@endif

<script>

let paginaActual = 1
let orderBy = 'apellido'
let orderDir = 'asc'
let perPage = 15
let debounceTimer = null

const esAdmin = @json(auth()->user()->hasRole('admin'));

// HELPERS

function safe(value) {
    return value ?? ''
}

// INPUTS

document.querySelectorAll('#dni, #apellido, #nombre, #anio')
    .forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer)

            debounceTimer = setTimeout(() => {
                nuevaBusqueda()
            }, 400)
        })
    })

function nuevaBusqueda()
{
    buscar(1)
}

// BUSCAR

async function buscar(page = 1)
{
    paginaActual = page

    const data = {
        dni: document.getElementById('dni').value,
        apellido: document.getElementById('apellido').value,
        nombre: document.getElementById('nombre').value,
        anio: document.getElementById('anio').value,
        order_by: orderBy,
        order: orderDir,
        per_page: perPage
    }

    let res

    try {
        res = await apiFetch('/api/personas/buscar?page=' + page, {
            method: 'POST',
            headers: { 'Content-Type':'application/json' },
            body: JSON.stringify(data)
        })
    } catch (e) {
        document.getElementById('resultados').innerHTML =
            `<div class="alert alert-warning">Sin resultados</div>`
        return
    }

    renderResultados(res)
}

// ORDEN

function ordenar(campo)
{
    if (orderBy === campo) {
        orderDir = orderDir === 'asc' ? 'desc' : 'asc'
    } else {
        orderBy = campo
        orderDir = 'asc'
    }

    buscar(1)
}

function iconoOrden(campo)
{
    if (orderBy !== campo) return ''
    return orderDir === 'asc' ? '↑' : '↓'
}

// RENDER

function renderResultados(res)
{
    const personas = res.resultado
    const meta = res.meta

    if (!personas || personas.length === 0) {
        document.getElementById('resultados').innerHTML =
            `<div class="alert alert-warning">Sin resultados</div>`
        return
    }

    let html = `
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Total:</strong> ${meta.total}</div>

            <div>
                <select class="form-select form-select-sm"
                    onchange="cambiarPerPage(this.value)">
                    <option value="15" ${perPage==15?'selected':''}>15</option>
                    <option value="30" ${perPage==30?'selected':''}>30</option>
                    <option value="50" ${perPage==50?'selected':''}>50</option>
                    <option value="100" ${perPage==100?'selected':''}>100</option>
                </select>
            </div>
        </div>

        <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th onclick="ordenar('apellido')" style="cursor:pointer">
                    Apellido y Nombre ${iconoOrden('apellido')}
                </th>
                <th onclick="ordenar('dni')" style="cursor:pointer">
                    DNI ${iconoOrden('dni')}
                </th>
                <th>Año</th>
                <th>Unidad Electoral</th>
                <th>Sede</th>
                <th>Claustro</th>
                <th>Legajo</th>
                <th>Estado</th>
                ${esAdmin ? '<th>Acción</th>' : ''}
            </tr>
        </thead>
        <tbody>
    `

    personas.forEach(p => {
        p.inscripciones.forEach(i => {

            const estadoColor = i.estado === 'ACTIVA' ? 'success' : 'danger'

            html += `
                <tr>
                    <td>${safe(p.apellido)}, ${safe(p.nombre)}</td>
                    <td>${safe(p.dni)}</td>
                    <td>${safe(i.anio)}</td>
                    <td>${safe(i.facultad)}</td>
                    <td>${safe(i.sede)}</td>
                    <td>${safe(i.claustro)}</td>
                    <td>${safe(i.legajo)}</td>
                    <td>
                        <span class="badge bg-${estadoColor}">
                            ${i.estado}
                        </span>
                    </td>

                    ${esAdmin ? `
                    <td>
                        <button class="btn btn-warning btn-sm me-1 btn-editar"
                            data-persona="${p.persona_id}"
                            data-inscripcion="${i.inscripcion_id ?? ''}"
                            data-apellido="${safe(p.apellido)}"
                            data-nombre="${safe(p.nombre)}"
                            data-dni="${safe(p.dni)}"
                            data-legajo="${safe(i.legajo)}">
                            Editar
                        </button>

                        ${
                            i.estado === 'ACTIVA'
                            ? `<button class="btn btn-danger btn-sm btn-baja"
                                data-inscripcion="${i.inscripcion_id}">
                                Baja
                            </button>`
                            : ''
                        }
                    </td>
                    ` : ''}
                </tr>
            `
        })
    })

    html += `</tbody></table>`

    html += `
        <div class="d-flex justify-content-between align-items-center mt-3">
    `

    if (meta.pagina_actual > 1) {
        html += `<button class="btn btn-outline-primary"
            onclick="buscar(${meta.pagina_actual - 1})">← Anterior</button>`
    } else {
        html += `<div></div>`
    }

    html += `<span>Página ${meta.pagina_actual} de ${meta.ultima_pagina}</span>`

    if (meta.pagina_actual < meta.ultima_pagina) {
        html += `<button class="btn btn-outline-primary"
            onclick="buscar(${meta.pagina_actual + 1})">Siguiente →</button>`
    }

    html += `</div>`

    document.getElementById('resultados').innerHTML = html
}

// EVENTOS (SIN onclick)

document.addEventListener('click', async function(e) {

    // EDITAR
    if (e.target.classList.contains('btn-editar')) {

        const btn = e.target

        document.getElementById('edit_persona_id').value = btn.dataset.persona
        document.getElementById('edit_inscripcion_id').value = btn.dataset.inscripcion
        document.getElementById('edit_apellido').value = btn.dataset.apellido
        document.getElementById('edit_nombre').value = btn.dataset.nombre
        document.getElementById('edit_dni').value = btn.dataset.dni
        document.getElementById('edit_legajo').value = btn.dataset.legajo

        new bootstrap.Modal(document.getElementById('modalEditar')).show()
    }

    // BAJA
    if (e.target.classList.contains('btn-baja')) {

        const inscripcion_id = e.target.dataset.inscripcion

        if (!inscripcion_id) {
            alert('Error: inscripción inválida')
            return
        }

        const motivo = prompt("Motivo de la baja:")
        if (!motivo) return

        if (!confirm("¿Confirmar baja?")) return

        const json = await apiFetch('/api/comparador/baja-inscripcion', {
            method:'POST',
            headers:{ 'Content-Type':'application/json' },
            body: JSON.stringify({ inscripcion_id, motivo })
        })

        if(json.success){
            alert('OK')
            buscar(paginaActual)
        } else {
            alert(json.error)
        }
    }
})

// GUARDAR EDICIÓN

async function guardarEdicion()
{
    const persona_id = document.getElementById('edit_persona_id').value
    const inscripcion_id = document.getElementById('edit_inscripcion_id').value

    if (!persona_id) {
        alert('Error: persona inválida')
        return
    }

    if (!inscripcion_id) {
        alert('Error: inscripción inválida')
        return
    }

    const persona = {
        dni: document.getElementById('edit_dni').value,
        apellido: document.getElementById('edit_apellido').value,
        nombre: document.getElementById('edit_nombre').value
    }

    const inscripcion = {
        legajo: document.getElementById('edit_legajo').value
    }

    await apiFetch(`/api/personas/${persona_id}`, {
        method:'PUT',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(persona)
    })

    await apiFetch(`/api/inscripciones/${inscripcion_id}`, {
        method:'PUT',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(inscripcion)
    })

    alert('Actualizado correctamente')

    bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide()

    buscar(paginaActual)
}

// PER PAGE

function cambiarPerPage(valor)
{
    perPage = parseInt(valor)
    buscar(1)
}

</script>

@endsection