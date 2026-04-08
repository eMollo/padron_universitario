@extends('layouts.app')

@section('content')

<h3>Agregar Sede</h3>

<form id="formSede">

<div class="row">

<div class="col-md-4 mb-3">
<label>Facultad</label>
<select id="facultad" class="form-control" required></select>
</div>

<div class="col-md-4 mb-3">
<label>Nombre de la sede</label>
<input type="text" id="nombre" class="form-control" required placeholder="Ej: General Roca">
</div>

</div>

<button class="btn btn-primary">
Crear sede
</button>

</form>

<script>

const facultad = document.getElementById("facultad")

async function cargarFacultades(){

    const data = await apiFetch("/api/facultad")

    facultad.innerHTML = `<option value="">Seleccione</option>`

    data.forEach(f=>{
        facultad.innerHTML += `<option value="${f.id}">${f.nombre}</option>`
    })
}

cargarFacultades()

formSede.addEventListener("submit", async e=>{

    e.preventDefault()

    const nombre = document.getElementById("nombre").value.trim()
    const id_facultad = facultad.value

    if(!nombre || !id_facultad){
        alert("Complete todos los campos")
        return
    }

    const data = await apiFetch("/api/sedes", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            nombre,
            id_facultad
        })
    })

    if(data.error){
        alert(data.error)
        return
    }

    alert(data.mensaje)

    document.getElementById("formSede").reset()

})

</script>

@endsection