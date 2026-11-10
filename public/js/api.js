// Obtener cookie por nombre
/*function getCookie(name) {
    return document.cookie
        .split('; ')
        .find(row => row.startsWith(name + '='))
        ?.split('=')[1]
}
*/
// Asegurar CSRF cookie
//let csrfLoaded = false;

/*async function ensureCsrf() {
    if (!csrfLoaded) {
        await fetch('/sanctum/csrf-cookie', {
            credentials: 'include'
        });
        csrfLoaded = true;
    }
}*/

// Fetch unificado
async function apiFetch(url, options = {}) {

    // ASEGURA CSRF SIEMPRE
    //await ensureCsrf()

    //const xsrfToken = decodeURIComponent(getCookie('XSRF-TOKEN') || '')

    const config = {
        credentials: 'include',
        /*headers: {
            'X-XSRF-TOKEN': xsrfToken,
            ...(options.headers || {})
        },*/
        ...options,
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content'),
            ...(options.headers || {})
        }
        
    }

    const res = await fetch(url, config)
    const text = await res.text()

    let data

try {
    data = JSON.parse(text)
} catch (e) {
    console.error("Respuesta NO es JSON:")
    console.error(text)
    throw new Error("Respuesta inválida del servidor")
}

// SOLO errores reales (500, 404, etc)
if (!res.ok && res.status !== 422) {
    console.error("Error HTTP:", res.status)
    console.error("Respuesta:", data)
    throw new Error(`HTTP ${res.status}`)
}

// devolvemos SIEMPRE el JSON (incluso 422)
return data
}

async function apiRequest(url, method = 'GET', body = null) {

    const res = await fetch(url, {
        method,
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content'),
        },
        body: body ? JSON.stringify(body) : undefined,
    })

    let data = null
    try {
        data = await res.json()
    } catch (e) {
        data = null
    }

    if (!res.ok) {
        const err = new Error(data?.message ?? `HTTP ${res.status}`)
        err.status = res.status
        err.data = data
        throw err
    }

    return data
}