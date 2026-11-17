<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="{{ csrf_token() }}">

<title>Sistema de Padrones</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<script src="/js/api.js"></script>
<script src="/js/auth.js"></script>

<script>
    checkAuth();
</script>

<nav class="navbar navbar-dark bg-dark">

<div class="container-fluid">

<span class="navbar-brand">
    Sistema Electoral
</span>

<div class="d-flex gap-2">
    <a href="/mi-cuenta" class="btn btn-outline-light btn-sm">Mi cuenta</a>
    <button onclick="logout()" class="btn btn-outline-light btn-sm">
    Salir
    </button>
</div>

</div>

</nav>

<div class="container-fluid">

<div class="row">

<!-- Sidebar -->
<div class="col-md-2 bg-light vh-100 pt-3">

<ul class="nav flex-column">

        {{-- Dashboard — todos --}}
    <li class="nav-item">
        <a href="/" class="nav-link">
            <i class="bi bi-house"></i>
            Inicio
        </a>
    </li>
 
    {{-- MÓDULO ELECCIONES — admin y consulta --}}
    @if(auth()->user()?->hasAnyRole(['admin','consulta','consejo_superior']))
 
    <li class="nav-item mt-2">
        <span class="nav-link text-muted small py-1 px-3 text-uppercase fw-semibold"
              style="font-size:.65rem; letter-spacing:.05em;">
            Elecciones
        </span>
    </li>
 
    <li class="nav-item">
        <a href="/padrones" class="nav-link">
            <i class="bi bi-journals"></i>
            Padrones
        </a>
    </li>
 
    <li class="nav-item">
        <a href="/personas/buscar" class="nav-link">
            <i class="bi bi-search"></i>
            Buscar persona
        </a>
    </li>

    <li class="nav-item">
        <a href="/padrones/metricas" class="nav-link">
            <i class="bi bi-bar-chart"></i>
            Métricas
        </a>
    </li>
 
    <li class="nav-item">
        {{-- Listas ocultas temporalmente hasta terminar el módulo
        <a href="/listas" class="nav-link">
            <i class="bi bi-card-list"></i>
            Listas
        </a>
        --}}
    </li>
 
    @endif
 
    {{-- MÓDULO ELECCIONES — solo admin --}}
    @if(auth()->user()?->hasRole('admin'))
 
    <li class="nav-item">
        <a href="/admin/comparador" class="nav-link">
            <i class="bi bi-arrows-angle-contract"></i>
            Comparador
        </a>
    </li>
 
    <li class="nav-item">
        <a href="/admin/comparador/bajas" class="nav-link">
            <i class="bi bi-person-dash"></i>
            Bajas
        </a>
    </li>
 
    
 
    @endif
 
    {{-- MÓDULO ASISTENCIAS — admin y consejo_superior --}}
    @if(auth()->user()?->hasAnyRole(['admin','consejo_superior']))
 
    <li class="nav-item mt-2">
        <span class="nav-link text-muted small py-1 px-3 text-uppercase fw-semibold"
              style="font-size:.65rem; letter-spacing:.05em;">
            Asistencias
        </span>
    </li>
 
    <li class="nav-item">
        <a href="/asistencia" class="nav-link">
            <i class="bi bi-check2-square"></i>
            Reuniones
        </a>
    </li>
 
    <li class="nav-item">
        <a href="/asistencia/historial" class="nav-link">
            <i class="bi bi-clock-history"></i>
            Historial
        </a>
    </li>
 
    @endif
 
    {{-- ADMIN — gestión general --}}
    @if(auth()->user()?->hasRole('admin'))
 
    <li class="nav-item mt-2">
        <span class="nav-link text-muted small py-1 px-3 text-uppercase fw-semibold"
              style="font-size:.65rem; letter-spacing:.05em;">
            Administración
        </span>
    </li>
 
    <li class="nav-item">
        <a href="/admin/usuarios" class="nav-link">
            <i class="bi bi-people"></i>
            Usuarios
        </a>
    </li>

    <li class="nav-item">
        <a href="/admin/sedes" class="nav-link">
            <i class="bi bi-building"></i>
            Sedes
        </a>
    </li>
 
    @endif

</ul>

</div>

<!-- Contenido -->
<div class="col-md-10 pt-4">
    @yield('content')
</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>