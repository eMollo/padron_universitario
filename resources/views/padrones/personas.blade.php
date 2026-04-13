@extends('layouts.app')

@section('content')

<h3>Personas del padrón</h3>

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
<th>#</th>
<th>DNI</th>
<th>Apellido</th>
<th>Nombre</th>
<th>Legajo</th>
</tr>
</thead>

<tbody id="tablaPersonas"></tbody>

</table>

<!-- PAGINACIÓN -->
<div class="d-flex justify-content-between align-items-center mt-3">

    <button id="btnPrev" class="btn btn-outline-primary">← Anterior</button>

    <span id="infoPagina"></span>

    <button id="btnNext" class="btn btn-outline-primary">Siguiente →</button>

</div>

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

let paginaActual = 1
let ultimaPagina = 1
let perPage = 50
let textoBusqueda = ""

// CARGAR PERSONAS

async function cargarPersonas(page = 1)
{
    paginaActual = page

    const res = await apiFetch(
        `/api/padrones/${padronId}/personas?buscar=${textoBusqueda}&page=${page}&per_page=${perPage}`
    )

    const data = res.data
    const meta = res.meta

    ultimaPagina = meta.last_page

    tablaPersonas.innerHTML = ""

    data.forEach((p, index) => {

        const numeroGlobal =
            ((meta.current_page - 1) * meta.per_page) + index + 1

        tablaPersonas.innerHTML += `
        <tr>
            <td>${numeroGlobal}</td>
            <td>${p.dni}</td>
            <td>${p.apellido}</td>
            <td>${p.nombre}</td>
            <td>${p.legajo ?? ''}</td>
        </tr>
        `
    })

    // INFO
    infoPagina.innerText =
        `Página ${meta.current_page} de ${meta.last_page} | Total: ${meta.total}`

    // BOTONES
    btnPrev.disabled = meta.current_page === 1
    btnNext.disabled = meta.current_page === meta.last_page
}

// BUSCADOR (con debounce)

let debounceTimer = null

buscar.addEventListener("input", e => {

    clearTimeout(debounceTimer)

    debounceTimer = setTimeout(() => {
        textoBusqueda = e.target.value
        cargarPersonas(1)
    }, 400)
})

// BOTONES PAGINACIÓN

btnPrev.addEventListener("click", () => {
    if (paginaActual > 1) {
        cargarPersonas(paginaActual - 1)
    }
})

btnNext.addEventListener("click", () => {
    if (paginaActual < ultimaPagina) {
        cargarPersonas(paginaActual + 1)
    }
})

// MODAL

function abrirModal(){
    new bootstrap.Modal(document.getElementById('modalPersona')).show()
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
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ dni, apellido, nombre, legajo })
    })

    if(data.error){
        alert(data.error)
        return
    }

    alert(data.mensaje)

    bootstrap.Modal.getInstance(document.getElementById('modalPersona')).hide()

    document.getElementById("formPersona").reset()

    cargarPersonas(paginaActual)
}

// INIT

cargarPersonas()

</script>

@endsection