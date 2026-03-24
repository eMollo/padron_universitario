@extends('layouts.app')

@section('content')

<div class="container">

    <h2>Inscripciones dadas de baja</h2>

    <div class="row mb-3">

    <div class="col-md-3">
        <input type="number" id="anio_bajas" class="form-control" placeholder="Año (opcional)">
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary" onclick="cargarBajas()">
            Filtrar
        </button>
    </div>

    <div class="col-md-3">
        <button class="btn btn-success" onclick="exportarBajas()">
            Exportar Excel
        </button>
    </div>

</div>

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
    const anio = document.getElementById('anio_bajas').value

    let url = '/api/comparador/bajas'
    if (anio) {
        url += `?anio=${anio}`
    }

    const json = await apiFetch(url)

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

async function exportarBajas()
{
    const anio = document.getElementById('anio_bajas').value

    const res = await fetch('/api/comparador/export-bajas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ anio })
    })

    if (!res.ok) {
        alert('Error al exportar')
        return
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)

    const a = document.createElement('a')
    a.href = url
    a.download = `bajas-${Date.now()}.xlsx`
    document.body.appendChild(a)
    a.click()
    a.remove()

    window.URL.revokeObjectURL(url)
}

</script>

@endsection