<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="{{ csrf_token() }}">

<title>Elecciones UNCO - Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container">

<div class="row justify-content-center align-items-center vh-100">

<div class="col-md-4">

<div class="card shadow">

<div class="card-header text-center">

<h4>Elecciones UNCO</h4>

</div>

<div class="card-body">

<div id="error" class="alert alert-danger d-none"></div>

<form id="loginForm">

<div class="mb-3">

<label class="form-label">Usuario</label>

<input
type="text"
class="form-control"
id="user"
required
>

</div>

<div class="mb-3">

<label class="form-label">Contraseña</label>

<input
type="password"
class="form-control"
id="password"
required
>

</div>

<button class="btn btn-primary w-100">
Ingresar
</button>

</form>

</div>

</div>

</div>

</div>

</div>

<script>

const form = document.getElementById("loginForm")
const errorBox = document.getElementById("error")

form.addEventListener("submit", async function(e){

e.preventDefault()

errorBox.classList.add("d-none")

const user = document.getElementById("user").value
const password = document.getElementById("password").value

try {
// 1. Pedir cookie CSRF
await fetch('/sanctum/csrf-cookie', {
    credentials: 'include'
})

// 2. Leer token desde cookie
function getCookie(name) {
    return document.cookie
        .split('; ')
        .find(row => row.startsWith(name + '='))
        ?.split('=')[1]
}

const xsrfToken = decodeURIComponent(getCookie('XSRF-TOKEN'))

// 3. Login
const response = await fetch("/login", {
    method: 'POST',
    credentials: 'include',
    headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrfToken
    },
    body: JSON.stringify({
        user: user,
        password: password
    })
})

if(!response.ok){

const text = await response.text()
console.error(text)

errorBox.innerText = "Error al iniciar sesión"
errorBox.classList.remove("d-none")

return

}

const data = await response.json()

if(data.success){

//localStorage.setItem("token", data.token)
localStorage.setItem("user", JSON.stringify(data.user))

window.location="/"

}else{

errorBox.innerText = "Credenciales incorrectas"
errorBox.classList.remove("d-none")

}

}catch(error){

console.error(error)

errorBox.innerText = "Error de conexión con el servidor"
errorBox.classList.remove("d-none")

}

})

</script>

</body>

</html>