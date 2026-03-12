@extends('layouts.app')

@section('content')

<h3>Importar padrón Excel</h3>

<div class="card mt-4">

<div class="card-body">

<form id="importForm">

<div class="mb-3">

<label>Archivo Excel</label>

<input
type="file"
class="form-control"
id="archivo"
required
>

</div>

<button class="btn btn-success">
Subir padrón
</button>

</form>

<hr>

<div id="resultado"></div>

</div>

</div>

<script>

document.getElementById("importForm").addEventListener("submit", async function(e){

e.preventDefault()

const file = document.getElementById("archivo").files[0]

if(!file){

alert("Seleccione un archivo")

return

}

const formData = new FormData()

formData.append("archivo", file)

const response = await fetch("/api/padrones/importar",{

method:"POST",

headers:{
"Authorization":"Bearer "+localStorage.getItem("token")
},

body:formData

})

const data = await response.json()

document.getElementById("resultado").innerHTML =
"<div class='alert alert-info mt-3'>"+data.message+"</div>"

})

</script>

@endsection