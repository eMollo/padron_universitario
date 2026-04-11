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

<script>

let paginaActual = 1
let orderBy = 'apellido'
let orderDir = 'asc'
let perPage = 15
let debounceTimer = null
const esAdmin = @json(auth()->user()->hasRole('admin'));

// EVENTOS INPUT

document.querySelectorAll('#dni, #apellido, #nombre, #anio')
    .forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer)

            debounceTimer = setTimeout(() => {
                nuevaBusqueda()
            }, 400)
        })
    })



// BUSQUEDA

function nuevaBusqueda()
{
    buscar(1)
}

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

            <div>
                <strong>Total:</strong> ${meta.total}
            </div>

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
                <th>Facultad</th>
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

            const estadoColor = i.estado === 'ACTIVA'
                ? 'success'
                : 'danger'

            html += `
                <tr>
                    <td>${p.apellido}, ${p.nombre}</td>
                    <td>${p.dni}</td>
                    <td>${i.anio ?? ''}</td>
                    <td>${i.facultad ?? ''}</td>
                    <td>${i.claustro ?? ''}</td>
                    <td>${i.legajo ?? ''}</td>
                    <td>
                        <span class="badge bg-${estadoColor}">
                            ${i.estado}
                        </span>
                    </td>
                    ${esAdmin ? `
                    <td>
                        ${
                            i.estado === 'ACTIVA'
                            ? `<button class="btn btn-danger btn-sm"
                                onclick="darBaja(${i.inscripcion_id})">
                                Dar baja
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
        html += `
            <button class="btn btn-outline-primary"
                onclick="buscar(${meta.pagina_actual - 1})">
                ← Anterior
            </button>
        `
    } else {
        html += `<div></div>`
    }

    html += `
        <span>
            Página ${meta.pagina_actual} de ${meta.ultima_pagina}
        </span>
    `

    if (meta.pagina_actual < meta.ultima_pagina) {
        html += `
            <button class="btn btn-outline-primary"
                onclick="buscar(${meta.pagina_actual + 1})">
                Siguiente →
            </button>
        `
    }

    html += `</div>`

    document.getElementById('resultados').innerHTML = html
}


// BAJA MANUAL

async function darBaja(inscripcion_id)
{
    const motivo = prompt("Motivo de la baja:")

    if (!motivo) return

    if (!confirm("¿Confirmar baja de inscripción?")) return

    const json = await apiFetch('/api/comparador/baja-inscripcion', {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify({
            inscripcion_id,
            motivo
        })
    })

    if(json.success){
        alert('Inscripción dada de baja')
        buscar(paginaActual)
    } else {
        alert(json.error)
    }
}


// PER PAGE

function cambiarPerPage(valor)
{
    perPage = parseInt(valor)
    buscar(1)
}

</script>

@endsection