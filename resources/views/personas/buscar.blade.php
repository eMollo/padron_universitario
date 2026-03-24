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
            <button class="btn btn-primary w-100" onclick="buscar()">
                Buscar
            </button>
        </div>

    </div>

    <div id="resultados"></div>

</div>

<script>

async function buscar()
{
    const data = {
        dni: document.getElementById('dni').value,
        apellido: document.getElementById('apellido').value,
        nombre: document.getElementById('nombre').value,
        anio: document.getElementById('anio').value
    }

    let res

    try {
        res = await apiFetch('/api/personas/buscar', {
            method: 'POST',
            headers: { 'Content-Type':'application/json' },
            body: JSON.stringify(data)
        })
    } catch (e) {
        document.getElementById('resultados').innerHTML =
            `<div class="alert alert-warning">Sin resultados</div>`
        return
    }

    renderResultados(res.resultado)
}

function renderResultados(personas)
{
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

    html += `
            </tbody>
        </table>
    `

    document.getElementById('resultados').innerHTML = html
}

</script>

@endsection