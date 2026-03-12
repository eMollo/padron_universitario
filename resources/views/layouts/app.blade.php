<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Sistema de Padrones</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<nav class="navbar navbar-dark bg-dark">

<div class="container-fluid">

<span class="navbar-brand">
Sistema de Padrones
</span>

<button class="btn btn-outline-light" onclick="logout()">
Salir
</button>

</div>

</nav>

<div class="container mt-4">

@yield('content')

</div>

<script>

function logout(){

localStorage.removeItem("token")

window.location="/login"

}

</script>

</body>

</html>