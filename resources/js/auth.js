


function checkAuth(){

    const token = localStorage.getItem("token")

    if(!token){

        window.location = "/login"

    }

}

function logout(){

    localStorage.removeItem("token")
    localStorage.removeItem("user")

    window.location = "/login"

}