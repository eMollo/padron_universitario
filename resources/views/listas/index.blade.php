@extends('layouts.app')

@section('content')

<div class="container">

    <h2 class="mb-4">
        Listas Electorales
    </h2>

    <a href="{{ route('listas.crear') }}" class="btn btn-primary mb-3">
        Nueva Lista
    </a>

    <div class="card">
        <div class="card-body">

            <p class="text-muted mb-0">
                Próximamente se mostrará aquí el listado de listas.
            </p>

        </div>
    </div>

</div>

@endsection