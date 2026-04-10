@extends('layouts.app')

@section('content')

<div class="container">

<h2>Comparador de Padrones</h2>

<div class="card p-3 mb-4">

<div class="row">

<!-- AÑO -->
<div class="col-md-2">
<input type="number" id="anio" class="form-control" placeholder="Año">
</div>

<!-- MODE -->
<div class="col-md-3">
<select id="mode" class="form-control">
<option value="global">Global</option>
<option value="mismo_claustro_global">Mismo claustro</option>
<option value="misma_facultad_entre_claustros">Misma facultad</option>
<option value="facultad_vs_resto">Facultad vs resto</option>
<option value="entre_claustros">Entre 2 claustros</option>
</select>
</div>

<!-- FACULTAD -->
<div class="col-md-3 d-none" id="box_facultad">
<select id="id_facultad" class="form-control">
<option value="">Seleccionar facultad</option>
@foreach($facultades as $f)
<option value="{{ $f->id }}">{{ $f->sigla }}</option>
@endforeach
</select>
</div>

<!-- CLAUSTRO SIMPLE -->
<div class="col-md-2 d-none" id="box_claustro">
<select id="id_claustro" class="form-control">
<option value="">Seleccionar claustro</option>
@foreach($claustros as $c)
<option value="{{ $c->id }}">{{ $c->nombre }}</option>
@endforeach
</select>
</div>

<!-- CLAUSTRO 1 -->
<div class="col-md-2 d-none" id="box_claustro_1">
<select id="id_claustro_1" class="form-control">
<option value="">Claustro 1</option>
@foreach($claustros as $c)
<option value="{{ $c->id }}">{{ $c->nombre }}</option>
@endforeach
</select>
</div>

<!-- CLAUSTRO 2 -->
<div class="col-md-2 d-none" id="box_claustro_2">
<select id="id_claustro_2" class="form-control">
<option value="">Claustro 2</option>
@foreach($claustros as $c)
<option value="{{ $c->id }}">{{ $c->nombre }}</option>
@endforeach
</select>
</div>

<!-- BOTON -->
<div class="col-md-2">
<button class="btn btn-primary w-100" onclick="buscarDuplicados()">
Comparar
</button>
</div>

</div>

</div>

<!-- META -->
<div id="meta" class="mb-3"></div>

<!-- EXPORT -->
<div class="col-md-2 mb-3">
<button onclick="exportarComparador(event)" class="btn btn-success">
Exportar Excel
</button>
</div>

<!-- TABLA EXACTOS -->
<h4>Duplicados exactos (DNI)</h4>

<table class="table table-bordered" id="tablaExactos">
<thead>
<tr>
<th>#</th>
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

<!-- TABLA POSIBLES -->
<h4 class="mt-5">Posibles duplicados (Nombre + Apellido)</h4>

<table class="table table-bordered" id="tablaPosibles">
<thead>
<tr>
<th>#</th>
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

// =======================
// UI DINAMICA
// =======================

document.getElementById('mode').addEventListener('change', actualizarCampos)

function actualizarCampos()
{
    const mode = document.getElementById('mode').value

    // ocultar todo
    document.getElementById('box_facultad').classList.add('d-none')
    document.getElementById('box_claustro').classList.add('d-none')
    document.getElementById('box_claustro_1').classList.add('d-none')
    document.getElementById('box_claustro_2').classList.add('d-none')

    if (mode === 'mismo_claustro_global') {
        document.getElementById('box_claustro').classList.remove('d-none')
    }

    if (mode === 'misma_facultad_entre_claustros' || mode === 'facultad_vs_resto') {
        document.getElementById('box_facultad').classList.remove('d-none')
    }

    if (mode === 'entre_claustros') {
        document.getElementById('box_claustro_1').classList.remove('d-none')
        document.getElementById('box_claustro_2').classList.remove('d-none')
    }
}

// inicializar
actualizarCampos()

// =======================
// BUSCAR
// =======================

async function buscarDuplicados()
{
    const data = {
        anio: document.getElementById('anio').value,
        mode: document.getElementById('mode').value,
        id_facultad: document.getElementById('id_facultad').value || null,
        id_claustro: document.getElementById('id_claustro').value || null,
        id_claustro_1: document.getElementById('id_claustro_1').value || null,
        id_claustro_2: document.getElementById('id_claustro_2').value || null
    }

    // validación simple
    if (data.mode === 'entre_claustros' && data.id_claustro_1 === data.id_claustro_2) {
        alert('Seleccioná dos claustros distintos')
        return
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

// =======================
// EXPORTAR
// =======================

async function exportarComparador(event)
{
    const btn = event.target
    btn.disabled = true
    btn.innerText = 'Exportando...'

    try {
        const data = {
            anio: document.getElementById('anio').value,
            mode: document.getElementById('mode').value,
            id_facultad: document.getElementById('id_facultad').value || null,
            id_claustro: document.getElementById('id_claustro').value || null,
            id_claustro_1: document.getElementById('id_claustro_1').value || null,
            id_claustro_2: document.getElementById('id_claustro_2').value || null
        }

        const res = await fetch('/api/comparador/export', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })

        const blob = await res.blob()

        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `comparador_${data.anio}.xlsx`
        a.click()
        window.URL.revokeObjectURL(url)

    } finally {
        btn.disabled = false
        btn.innerText = 'Exportar Excel'
    }
}

// =======================
// RENDER
// =======================

function renderExactos(grupos)
{
    const tbody = document.querySelector('#tablaExactos tbody')
    tbody.innerHTML = ''

    let index = 1

    grupos.forEach(grupo => {
        grupo.forEach(persona => {

            const tr = document.createElement('tr')

            tr.innerHTML = `
                <td>${index++}</td>
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

    let index = 1

    grupos.forEach(grupo => {
        grupo.forEach(persona => {

            const tr = document.createElement('tr')

            tr.innerHTML = `
                <td>${index++}</td>
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

// =======================
// BAJA
// =======================

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