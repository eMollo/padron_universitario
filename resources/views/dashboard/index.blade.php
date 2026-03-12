@extends('layouts.app')

@section('content')

<h3>Panel de Administración</h3>

<div class="row mt-4">

<div class="col-md-4">

<div class="card">

<div class="card-body">

<h5 class="card-title">Padrones</h5>

<p class="card-text">
Importar archivos Excel de padrones.
</p>

<a href="/padrones/importar" class="btn btn-primary">
Importar padrón
</a>

</div>

</div>

</div>

</div>

@endsection