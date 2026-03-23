@extends('layouts.app')

@section('content')

<div class="container">

    <h2>Inscripciones dadas de baja</h2>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>DNI</th>
                <th>Apellido</th>
                <th>Nombre</th>
                <th>Unidad Electoral</th>
                <th>Motivo</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="tablaBajas"></tbody>
    </table>

</div>

<script>

document.addEventListener('DOMContentLoaded', cargarBajas)

async function cargarBajas()
{
    const json = await apiFetch('/api/comparador/bajas')

    const tbody = document.getElementById('tablaBajas')
    tbody.innerHTML = ''

    json.forEach(inscripcion => {

    const tr = document.createElement('tr')

    tr.innerHTML = `
        <td>${inscripcion.dni ?? ''}</td>
        <td>${inscripcion.apellido ?? ''}</td>
        <td>${inscripcion.nombre ?? ''}</td>
        <td>${inscripcion.facultad ?? ''}</td>
        <td>${inscripcion.motivo_baja ?? ''}</td>
        <td>
            <button class="btn btn-success btn-sm"
                onclick="restaurar(${inscripcion.inscripcion_id})">
                Restaurar
            </button>
        </td>
    `

    tbody.appendChild(tr)
})
}

async function restaurar(id)
{
    if (!confirm('¿Restaurar inscripción?')) return

    const json = await apiFetch(`/api/inscripciones/${id}/restaurar`, {
        method: 'POST'
    })

    alert(json.mensaje)

    cargarBajas()
}

</script>

@endsection