function checkAuth() {
    fetch('/test-auth', {
        credentials: 'include'
    })
    .then(res => res.text())
    .then(text => {
        if (text !== 'LOGUEADO') {
            window.location.href = '/login';
        }
    });
}

function logout() {
    fetch('/logout', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    }).then(() => {
        window.location.href = '/login';
    });
}

// AUTO-EJECUCIÓN SEGURA
/*document.addEventListener('DOMContentLoaded', function () {
    checkAuth();
});*/