@extends('layouts.app')

@section('content')

<h3>Personas del padrón</h3>

<!-- BOTÓN NUEVO -->
@if(auth()->user()?->hasRole('admin'))
<button class="btn btn-success mb-3" onclick="abrirModal()">
    + Agregar persona
</button>
@endif

<input
type="text"
id="buscar"
class="form-control mb-3"
placeholder="Buscar por DNI, apellido o nombre">

<table class="table table-striped">

<thead>
<tr>
<th>DNI</th>
<th>Apellido</th>
<th>Nombre</th>
<th>Legajo</th>
</tr>
</thead>

<tbody id="tablaPersonas"></tbody>

</table>

<!-- MODAL -->
<div class="modal fade" id="modalPersona" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Agregar persona al padrón</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <form id="formPersona">

          <div class="mb-3">
            <label>DNI</label>
            <input type="text" id="dni" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Apellido</label>
            <input type="text" id="apellido" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Nombre</label>
            <input type="text" id="nombre" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Legajo</label>
            <input type="text" id="legajo" class="form-control">
          </div>

        </form>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarPersona()">Guardar</button>
      </div>

    </div>
  </div>
</div>

<script>

const padronId = {{ $id }}

async function cargarPersonas(buscar=""){

    const data = await apiFetch(`/api/padrones/${padronId}/personas?buscar=${buscar}`)

    tablaPersonas.innerHTML=""

    data.data.forEach(p=>{

        tablaPersonas.innerHTML += `
        <tr>
            <td>${p.dni}</td>
            <td>${p.apellido}</td>
            <td>${p.nombre}</td>
            <td>${p.legajo ?? ''}</td>
        </tr>
        `
    })
}

cargarPersonas()

buscar.addEventListener("keyup", e=>{
    cargarPersonas(e.target.value)
})


    //MODAL FUNCIONES


function abrirModal(){
    const modal = new bootstrap.Modal(document.getElementById('modalPersona'))
    modal.show()
}

async function guardarPersona(){

    const dni = document.getElementById("dni").value.trim()
    const apellido = document.getElementById("apellido").value.trim()
    const nombre = document.getElementById("nombre").value.trim()
    const legajo = document.getElementById("legajo").value.trim()

    if(!dni || !apellido || !nombre){
        alert("Complete todos los campos obligatorios")
        return
    }

    const data = await apiFetch(`/api/padrones/${padronId}/agregar-persona`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            dni,
            apellido,
            nombre,
            legajo
        })
    })

    if(data.error){
        alert(data.error)
        return
    }

    alert(data.mensaje)

    // cerrar modal
    const modalEl = document.getElementById('modalPersona')
    const modal = bootstrap.Modal.getInstance(modalEl)
    modal.hide()

    // limpiar form
    document.getElementById("formPersona").reset()

    //  recargar tabla SIN recargar página
    cargarPersonas()
}

</script>

@endsection