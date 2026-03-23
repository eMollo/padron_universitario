@extends('layouts.app')

@section('content')


<div class="container">

<h2>Comparador de Padrones</h2>

<div class="card p-3 mb-4">

<div class="row">

<div class="col-md-2">
<input type="number" id="anio" class="form-control" placeholder="Año">
</div>

<div class="col-md-3">
<select id="mode" class="form-control">
<option value="global">Global</option>
<option value="mismo_claustro_global">Mismo claustro</option>
<option value="misma_facultad_entre_claustros">Misma facultad</option>
<option value="facultad_vs_resto">Facultad vs resto</option>
</select>
</div>

<div class="col-md-3">
<input type="number" id="id_facultad" class="form-control" placeholder="ID Facultad">
</div>

<div class="col-md-2">
<input type="number" id="id_claustro" class="form-control" placeholder="ID Claustro">
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100" onclick="buscarDuplicados()">
Comparar
</button>
</div>

</div>

</div>

<div id="meta" class="mb-3"></div>

<h4>Duplicados exactos (DNI)</h4>

<table class="table table-bordered" id="tablaExactos">

<thead>

<tr>
<th>DNI</th>
<th>Apellido</th>
<th>Nombre</th>
<th>Unidad Electoral</th>
<th>Claustro</th>
<th>Acción</th>
</tr>

</thead>

<tbody></tbody>

</table>

<h4 class="mt-5">Posibles duplicados (Nombre + Apellido)</h4>

<table class="table table-bordered" id="tablaPosibles">

<thead>

<tr>
<th>DNI</th>
<th>Apellido</th>
<th>Nombre</th>
<th>Unidad Electoral</th>
<th>Claustro</th>
<th>Acción</th>
</tr>

</thead>

<tbody></tbody>

</table>

</div>

<script>

async function buscarDuplicados()
{
    const data = {
        anio: document.getElementById('anio').value,
        mode: document.getElementById('mode').value,
        id_facultad: document.getElementById('id_facultad').value,
        id_claustro: document.getElementById('id_claustro').value
    }

    const json = await apiFetch('/api/comparador/comparar', {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify(data)
    })

    renderExactos(json.DUPLICADOS_EXACTOS)
    renderPosibles(json.POSIBLES_DUPLICADOS)

    document.getElementById('meta').innerHTML =
    `Exactos: ${json.meta.total_exactos} |
     Posibles: ${json.meta.total_posibles}`
}

function renderExactos(grupos)
{
    const tbody = document.querySelector('#tablaExactos tbody')
    tbody.innerHTML = ''

    grupos.forEach(grupo => {
        grupo.forEach(persona => {

            const tr = document.createElement('tr')

            tr.innerHTML = `
                <td>${persona.dni_normalizado}</td>
                <td>${persona.apellido}</td>
                <td>${persona.nombre}</td>
                <td>${persona.facultad}</td>
                <td>${persona.claustro}</td>
                <td>
                    <button onclick="darBaja(${persona.inscripcion_id})"
                    class="btn btn-danger btn-sm">
                    Dar baja
                    </button>
                </td>
            `

            tbody.appendChild(tr)
        })
    })
}

function renderPosibles(grupos)
{
    const tbody = document.querySelector('#tablaPosibles tbody')
    tbody.innerHTML = ''

    grupos.forEach(grupo => {
        grupo.forEach(persona => {

            const tr = document.createElement('tr')

            tr.innerHTML = `
                <td>${persona.dni}</td>
                <td>${persona.apellido}</td>
                <td>${persona.nombre}</td>
                <td>${persona.facultad}</td>
                <td>${persona.claustro}</td>
                <td>
                    <button onclick="darBaja(${persona.inscripcion_id})"
                    class="btn btn-danger btn-sm">
                    Dar baja
                    </button>
                </td>
            `

            tbody.appendChild(tr)
        })
    })
}

async function darBaja(inscripcion_id)
{
    if(!confirm("¿Dar de baja esta inscripción?")) return

    const json = await apiFetch('/api/comparador/baja-inscripcion', {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify({
            inscripcion_id,
            motivo:'Duplicado detectado en comparador'
        })
    })

    if(json.success){
        alert('Inscripción dada de baja')
        buscarDuplicados()
    } else {
        alert(json.error)
    }
}

</script>

@endsection