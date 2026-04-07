@extends('layouts.app')

@section('content')

<h3>Importar padrón</h3>

<form id="formImportar">

<div class="row">

<div class="col-md-2 mb-3">
<label>Año</label>
<input type="number" class="form-control" id="anio" required>
</div>

<div class="col-md-3 mb-3">
<label>Unidad Electoral</label>
<select id="facultad" class="form-control" required></select>
</div>

<div class="col-md-3 mb-3">
<label>Claustro</label>
<select id="claustro" class="form-control" required></select>
</div>

<div class="col-md-3 mb-3" id="grupoSede">
<label>Sede</label>
<select id="sede" class="form-control" required></select>
</div>

<div class="col-md-6 mb-3">
<label>Archivo Excel</label>
<input type="file" class="form-control" id="archivo" required>
</div>

</div>

<button class="btn btn-primary">
Importar padrón
</button>

</form>

<script>

const facultad = document.getElementById("facultad")
const claustro = document.getElementById("claustro")
const sede = document.getElementById("sede")

let claustros = []

async function cargarCatalogos(){

    const f = await apiFetch("/api/facultad")
    const c = await apiFetch("/api/claustros")

    claustros = c

    facultad.innerHTML = `<option value="">Seleccione</option>`
    claustro.innerHTML = `<option value="">Seleccione</option>`
    sede.innerHTML = `<option value="">Seleccione</option>`

    f.forEach(x=>{
        facultad.innerHTML += `<option value="${x.id}">${x.nombre}</option>`
    })

    c.forEach(x=>{
        claustro.innerHTML += `<option value="${x.id}">${x.nombre}</option>`
    })
}

async function cargarSedes(){

    const idFacultad = facultad.value

    if(!idFacultad){
        sede.innerHTML = `<option value="">Seleccione</option>`
        return
    }

    const data = await apiFetch(`/api/sede/facultad/${idFacultad}`)

    sede.innerHTML = `<option value="">Seleccione</option>`

    data.forEach(x=>{
        sede.innerHTML += `<option value="${x.id}">${x.nombre}</option>`
    })
}

facultad.addEventListener("change", cargarSedes)

cargarCatalogos()

formImportar.addEventListener("submit", async e=>{

    e.preventDefault()

    // VALIDACIÓN SIMPLE (ahora SIEMPRE obligatoria)
    if(!sede.value){
        alert("Debe seleccionar una sede")
        return
    }

    const formData = new FormData()

    formData.append("archivo", archivo.files[0])
    formData.append("anio", anio.value)
    formData.append("id_facultad", facultad.value)
    formData.append("id_claustro", claustro.value)
    formData.append("id_sede", sede.value)

    const data = await apiFetch('/api/padrones/importar', {
        method: "POST",
        body: formData
    })

    if(data.duplicados){
        let mensaje = "El padrón contiene personas duplicadas:\n\n"
        data.duplicados.forEach(p => {
            mensaje += `${p.dni} - ${p.nombre}\n`
        })
        alert(mensaje)
        return
    }

    alert(data.mensaje || data.error)

})

</script>

@endsection