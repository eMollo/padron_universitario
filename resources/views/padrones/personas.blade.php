@extends('layouts.app')

@section('content')



<h3>Personas del padrón</h3>

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

<script>

const padronId = {{ $id }}

async function cargarPersonas(buscar=""){

/*const res = await fetch(`/api/padrones/${padronId}/personas?buscar=${buscar}`,{
    credentials: 'include'
})*/

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

</script>

@endsection