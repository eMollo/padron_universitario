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
Sistema de Padrones
</span>

<button onclick="logout()" class="btn btn-outline-light btn-sm">
Salir
</button>

</div>

</nav>

<div class="container-fluid">

<div class="row">

<!-- Sidebar -->
<div class="col-md-2 bg-light vh-100 pt-3">

<ul class="nav flex-column">

<li class="nav-item">
<a class="nav-link" href="/">Dashboard</a>
</li>

<li class="nav-item">
    <a href="/personas/buscar" class="nav-link">
        Buscar Persona
    </a>
</li>

<li class="nav-item">
<a class="nav-link" href="/padrones">Ver Padrones</a>
</li>

@if(auth()->user()?->hasRole('admin'))

    <li class="nav-item">
    <a class="nav-link" href="/padrones/importar">Importar Padrones</a>
    </li>

    <li class="nav-item">
        <a href="/admin/comparador" class="nav-link">
            Comparador
        </a>
    </li>

    <li class="nav-item">
        <a href="/admin/comparador/bajas" class="nav-link">
            Inscripciones dadas de baja
        </a>
    </li>

    <li class="nav-item">
        <a href="/admin/sedes" class="nav-link">
            Gestionar Sedes
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