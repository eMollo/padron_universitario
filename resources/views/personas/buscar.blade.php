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
        anio: document.getElementById('anio').value
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
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Apellido y Nombre</th>
                    <th>DNI</th>
                    <th>Año</th>
                    <th>Facultad</th>
                    <th>Claustro</th>
                    <th>Legajo</th>
                    <th>Estado</th>
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
                </tr>
            `
        })
    })

    html += `</tbody></table>`

    //  PAGINACIÓN
    html += `
        <div class="d-flex justify-content-between align-items-center mt-3">
    `

    // anterior
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

    // info
    html += `
        <span>
            Página ${meta.pagina_actual} de ${meta.ultima_pagina}
            (Total: ${meta.total})
        </span>
    `

    // siguiente
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

</script>

@endsection