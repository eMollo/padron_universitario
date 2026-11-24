@extends('layouts.app')

@section('content')

<div class="container text-center" style="max-width:480px;">

    <h4 class="mb-1" id="titulo-reunion">Cargando...</h4>
    <p class="text-muted small mb-4" id="subtitulo-reunion"></p>

    {{-- Resultado del escaneo --}}
    <div id="resultado-scan" class="alert mb-4" style="display:none; font-size:1.1rem;"></div>

    {{-- Contador --}}
    <div class="mb-4">
        <div class="text-muted small">PRESENTES</div>
        <div style="font-size:3rem; font-weight:700;" class="text-success" id="contador">—</div>
    </div>

    {{-- Input de DNI manual (para cuando no hay lector) --}}
    <div class="card mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold">Ingresar DNI manualmente</label>
            <div class="d-flex gap-2">
                <input type="number" class="form-control form-control-lg"
                    id="input-dni" placeholder="DNI..." autofocus>
                <button class="btn btn-primary btn-lg" onclick="procesarDni()">
                    →
                </button>
            </div>
        </div>
    </div>

    <p class="text-muted small">
        Ingresá el DNI o escaneá el QR del consejero.<br>
        Un registro = entrada · Dos registros = salida.
    </p>

</div>

<script>

const REUNION_ID = {{ $id }};
let cooldown = false;

async function cargarReunion() {
    try {
        const r = await apiRequest(`/api/reuniones/${REUNION_ID}`);
        document.getElementById('titulo-reunion').textContent   = r.nombre;
        document.getElementById('subtitulo-reunion').textContent =
            String(r.fecha).substring(0, 10).split('-').reverse().join('/');

        // Contador inicial
        const data = await apiRequest(`/api/reuniones/${REUNION_ID}/participantes`);
        document.getElementById('contador').textContent = data.presentes;
    } catch (e) {
        document.getElementById('titulo-reunion').textContent = 'Error al cargar la reunión';
    }
}

async function procesarDni() {
    if (cooldown) return;

    const dni = document.getElementById('input-dni').value.trim();
    if (!dni) return;

    // Buscar persona por DNI
    try {
        const personas = await apiRequest('/api/personas/buscar', 'POST', { dni });
        const persona  = personas.resultado?.[0];

        if (!persona) {
            mostrarResultado('warning', `DNI ${dni} no encontrado en el sistema.`);
            return;
        }

        // Registrar QR toggle
        const data = await apiRequest(`/api/reuniones/${REUNION_ID}/qr`, 'POST', {
            id_persona: persona.id,
        });

        const accion = data.tipo === 'entrada' ? '✅ ENTRADA' : '🚪 SALIDA';
        const color  = data.tipo === 'entrada' ? 'success' : 'secondary';
        mostrarResultado(color, `${accion}: ${data.persona}`);

        document.getElementById('contador').textContent = data.presentes;

        // Cooldown visual
        cooldown = true;
        setTimeout(() => { cooldown = false; }, 2000);

    } catch (err) {
        if (err.status === 429) {
            mostrarResultado('warning', 'Escaneo demasiado rápido. Esperá un momento.');
        } else if (err.status === 422) {
            // Cupo de la lista completo (u otra regla): se muestra el motivo del servidor
            const quien = err.data?.persona ? `${err.data.persona}: ` : '';
            mostrarResultado('warning', quien + err.message);
        } else {
            mostrarResultado('danger', 'Error al registrar.');
        }
    }

    document.getElementById('input-dni').value = '';
    document.getElementById('input-dni').focus();
}

function mostrarResultado(color, mensaje) {
    const el = document.getElementById('resultado-scan');
    el.className = `alert alert-${color} mb-4`;
    el.textContent = mensaje;
    el.style.display = 'block';

    clearTimeout(el._timeout);
    el._timeout = setTimeout(() => { el.style.display = 'none'; }, 4000);
}

// Enter en el input de DNI
document.getElementById('input-dni').addEventListener('keypress', e => {
    if (e.key === 'Enter') procesarDni();
});

cargarReunion();

</script>

@endsection