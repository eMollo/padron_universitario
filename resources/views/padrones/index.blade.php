@extends('layouts.app')

@section('content')



<h3>Padrones</h3>

<div class="row mb-3">

<div class="col-md-2">
<label>Año</label>
<input type="number" class="form-control" id="filtroAnio">
</div>

<div class="col-md-2">
<label>&nbsp;</label>
<button class="btn btn-primary w-100" onclick="cargarPadrones()">
Filtrar
</button>
</div>

</div>

<table id="tablaPadrones" class="table table-bordered">

<thead>

<tr>

<th>Año</th>
<th>Unidad Electoral</th>
<th>Claustro</th>
<th>Sede</th>
<th>Personas</th>
<th>Acciones</th>

</tr>

</thead>

<tbody></tbody>

</table>

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>

let tabla = null

async function cargarPadrones(){

let anio = document.getElementById("filtroAnio").value

let url = "/api/padrones"

if(anio){
url += "?anio="+anio
}

/*const res = await fetch(url,{
    credentials: 'include'
})*/

const data = await apiFetch(url)

if(tabla){
tabla.destroy()
}

let html=""

data.forEach(p=>{

html+=`

<tr>
<td>${p.anio}</td>
<td>${p.facultad?.nombre ?? ''}</td>
<td>${p.claustro?.nombre ?? ''}</td>
<td>${p.sede?.nombre ?? ''}</td>
<td>${p.inscripciones_activas_count}</td>
<td>
<a class="btn btn-sm btn-primary" href="/padrones/${p.id}/personas">
Ver
</a>

<a class="btn btn-sm btn-success"
   href="/api/padrones/${p.id}/export">
Exportar
</a>
</td>
</tr>

`

})

document.querySelector("#tablaPadrones tbody").innerHTML = html

tabla = new DataTable("#tablaPadrones")

}

cargarPadrones()

</script>

@endsection