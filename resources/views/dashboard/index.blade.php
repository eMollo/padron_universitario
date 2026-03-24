@extends('layouts.app')

@section('content')

<h3>Panel de Administración</h3>

<div class="row">

    {{-- BUSCAR PERSONA --}}
    @if(auth()->user()->hasAnyRole(['admin','consulta']))
    <div class="col-md-3 mb-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <h5>Buscar Persona</h5>
                <p class="text-muted">Consultar padrón</p>
                <a href="/personas/buscar" class="btn btn-primary">
                    Ir
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- VER PADRONES --}}
    <div class="col-md-3 mb-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <h5>Ver Padrones</h5>
                <p class="text-muted">Listado general</p>
                <a href="/padrones" class="btn btn-primary">
                    Ver
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
    <div class="card h-100 shadow-sm border-info">
        <div class="card-body text-center">
            <h5>Métricas</h5>
            <p class="text-muted">Estadísticas del padrón</p>
            <a href="/padrones/metricas" class="btn btn-info">
                Ver métricas
            </a>
        </div>
    </div>
</div>

    {{-- IMPORTAR --}}
    @if(auth()->user()->hasRole('admin'))
    <div class="col-md-3 mb-3">
        <div class="card h-100 shadow-sm border-success">
            <div class="card-body text-center">
                <h5>Importar Padrones</h5>
                <p class="text-muted">Carga de Excel</p>
                <a href="/padrones/importar" class="btn btn-success">
                    Importar
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- COMPARADOR --}}
    @if(auth()->user()->hasRole('admin'))
    <div class="col-md-3 mb-3">
        <div class="card h-100 shadow-sm border-warning">
            <div class="card-body text-center">
                <h5>Comparador</h5>
                <p class="text-muted">Detección de duplicados</p>
                <a href="/admin/comparador" class="btn btn-warning">
                    Abrir
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- BAJAS --}}
    @if(auth()->user()->hasRole('admin'))
    <div class="col-md-3 mb-3">
        <div class="card h-100 shadow-sm border-danger">
            <div class="card-body text-center">
                <h5>Inscripciones dadas de baja</h5>
                <p class="text-muted">Gestión y restauración</p>
                <a href="/admin/comparador/bajas" class="btn btn-danger">
                    Ver bajas
                </a>
            </div>
        </div>
    </div>
    @endif

</div>

@endsection