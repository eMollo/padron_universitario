@extends('layouts.app')

@section('content')

<h3>Métricas del Padrón</h3>

<div class="mb-3 d-flex gap-2">

    <button class="btn btn-warning" onclick="recalcularMetricas()">
        🔄 Recalcular métricas
    </button>

</div>

<div class="row mb-3">
    <div class="col-md-2">
        <label>Año</label>
        <input type="number" id="anio" class="form-control">
    </div>

    <div class="col-md-2">
        <label>&nbsp;</label>
        <button class="btn btn-primary w-100" onclick="cargarMetricas()">
            Filtrar
        </button>
    </div>
</div>

{{-- TOTAL --}}
<div class="card mb-4">
    <div class="card-body text-center">
        <h4>Total Empadronados</h4>
        <h1 id="totalGeneral">-</h1>
    </div>
</div>

<div class="row">

    {{-- POR FACULTAD --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Por Unidad Electoral</div>
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Unidad Electoral</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="tablaFacultad"></tbody>
            </table>
        </div>
    </div>

    {{-- POR CLAUSTRO --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Por Claustro</div>
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Claustro</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="tablaClaustro"></tbody>
            </table>
        </div>
    </div>

</div>

<script>

async function cargarMetricas(){

    let anio = document.getElementById('anio').value
    let url = '/api/metricas'

    if(anio){
        url += '?anio=' + anio
    }

    const data = await apiFetch(url)

    document.getElementById('totalGeneral').innerText = data.total

    // FACULTAD
    let htmlFac = ''
    data.por_facultad.forEach(f => {
        htmlFac += `
        <tr>
            <td>${f.nombre}</td>
            <td>${f.total}</td>
        </tr>`
    })
    document.getElementById('tablaFacultad').innerHTML = htmlFac

    // CLAUSTRO
    let htmlClau = ''
    data.por_claustro.forEach(c => {
        htmlClau += `
        <tr>
            <td>${c.nombre}</td>
            <td>${c.total}</td>
        </tr>`
    })
    document.getElementById('tablaClaustro').innerHTML = htmlClau
}

document.addEventListener('DOMContentLoaded', () => {
    const anioInput = document.getElementById('anio')
    const anioActual = new Date().getFullYear()

    if (!anioInput.value) {
        anioInput.value = anioActual
    }
})

async function recalcularMetricas(){

    const anio = document.getElementById('anio').value

    if(!anio){
        alert('Debe ingresar un año')
        return
    }

    if(!confirm(`¿Recalcular métricas para el año ${anio}?`)){
        return
    }

    try {

        const btn = event.target
        btn.disabled = true
        btn.innerText = 'Recalculando...'

        await apiFetch('/api/metricas/recalcular', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ anio })
        })

        alert('Métricas recalculadas correctamente')

        await cargarMetricas()

    } catch (e) {
        alert('Error al recalcular métricas')
        console.error(e)
    } finally {
        const btn = document.querySelector('[onclick="recalcularMetricas()"]')
        btn.disabled = false
        btn.innerText = '🔄 Recalcular métricas'
    }
}

cargarMetricas()

</script>

@endsection