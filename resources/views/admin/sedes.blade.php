@extends('layouts.app')

@section('content')

<h3>Gestión de Sedes</h3>

<!-- FORM CREAR -->

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

<div class="col-md-2 mb-3 d-flex align-items-end">
<button class="btn btn-primary w-100">
Crear sede
</button>
</div>

</div>

</form>

<hr>

<!-- ===================== -->
<!-- LISTADO -->
<!-- ===================== -->

<h5>Listado de sedes</h5>

<table class="table table-bordered">

<thead>
<tr>
<th>Sede</th>
<th>Facultad</th>
<th width="180">Estado / Acciones</th>
</tr>
</thead>

<tbody id="tablaSedes"></tbody>

</table>

<script>

const facultad = document.getElementById("facultad")

// =======================
// CARGAR FACULTADES
// =======================

async function cargarFacultades(){

    const data = await apiFetch("/api/facultad")

    facultad.innerHTML = `<option value="">Seleccione</option>`

    data.forEach(f=>{
        facultad.innerHTML += `<option value="${f.id}">${f.nombre}</option>`
    })
}

// =======================
// CARGAR SEDES
// =======================

async function cargarSedes(){

    const data = await apiFetch("/api/sedes")

    let html = ""

    data.forEach(s=>{

        html += `
        <tr>
            <td>${s.nombre}</td>
            <td>${s.facultad?.sigla ?? ''}</td>
            <td>
                ${
                    s.usada
                    ? `<span class="badge bg-secondary">
                         En uso (${s.padrones_count})
                       </span>`
                    : `<button class="btn btn-danger btn-sm"
                               onclick="eliminarSede(${s.id})">
                           Eliminar
                       </button>`
                }
            </td>
        </tr>
        `
    })

    document.getElementById("tablaSedes").innerHTML = html
}

// =======================
// CREAR SEDE
// =======================

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

    cargarSedes() // 🔥 refresca la tabla automáticamente
})

// =======================
// ELIMINAR SEDE
// =======================

async function eliminarSede(id){

    if(!confirm("¿Seguro que desea eliminar esta sede?")){
        return
    }

    const data = await apiFetch(`/api/sedes/${id}`, {
        method: "DELETE"
    })

    if(data.error){
        alert(data.error + (data.detalle ? "\n" + data.detalle : ""))
        return
    }

    alert(data.message)

    cargarSedes()
}

// =======================
// INIT
// =======================

cargarFacultades()
cargarSedes()

</script>

@endsection