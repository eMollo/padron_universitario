@extends('layouts.app')

@section('content')

<div class="container">

    <h1 class="mb-4">Crear lista</h1>

    <div id="mensaje"></div>

    <form id="formCrearLista">

        {{-- ========================= --}}
        {{-- DATOS DE LA LISTA --}}
        {{-- ========================= --}}

        <div class="card mb-4">
            <div class="card-header">
                <strong>Datos de la lista</strong>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label for="anio" class="form-label">Año</label>
                        <input
                            type="number"
                            class="form-control"
                            id="anio"
                            name="anio"
                            value="{{ date('Y') }}"
                            required
                        >
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="tipo" class="form-label">Tipo de lista</label>

                        <select
                            class="form-select"
                            id="tipo"
                            name="tipo"
                            required
                        >
                            <option value="">Seleccionar...</option>
                            <option value="superior">Consejo Superior</option>
                            <option value="directivo">Consejo Directivo</option>
                            <option value="decano">Decano</option>
                            <option value="rector">Rector</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="modo_carga" class="form-label">
                            Modo de carga
                        </label>

                        <select
                            class="form-select"
                            id="modo_carga"
                            name="modo_carga"
                            required
                        >
                            <option value="normal">Normal</option>
                            <option value="historica">Histórica</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3" id="contenedor_numero" style="display:none;">
                        <label for="numero" class="form-label">
                            Número de lista
                        </label>

                        <select
                            class="form-select"
                            id="numero"
                            name="numero"
                        >
                            <option value="">Seleccionar número...</option>
                        </select>
                    </div>

                </div>

                <div class="row">

                    {{-- CLAUSTRO --}}
                    <div
                        class="col-md-6 mb-3"
                        id="contenedor_claustro"
                        style="display:none;"
                    >
                        <label for="id_claustro" class="form-label">
                            Claustro
                        </label>

                        <select
                            class="form-select"
                            id="id_claustro"
                            name="id_claustro"
                        >
                            <option value="">Seleccionar...</option>

                            @foreach ($claustros as $claustro)
                                <option value="{{ $claustro->id }}">
                                    {{ $claustro->nombre }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    {{-- FACULTAD --}}
                    <div
                        class="col-md-6 mb-3"
                        id="contenedor_facultad"
                        style="display:none;"
                    >
                        <label for="id_facultad" class="form-label">
                            Facultad
                        </label>

                        <select
                            class="form-select"
                            id="id_facultad"
                            name="id_facultad"
                        >
                            <option value="">Seleccionar...</option>

                            @foreach ($facultades as $facultad)
                                <option value="{{ $facultad->id }}">
                                    {{ $facultad->nombre }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-8 mb-3">
                        <label for="nombre" class="form-label">
                            Nombre de la lista
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            maxlength="90"
                            required
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="sigla" class="form-label">
                            Sigla
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="sigla"
                            name="sigla"
                            maxlength="10"
                        >
                    </div>

                </div>

            </div>
        </div>


        {{-- ========================= --}}
        {{-- APODERADO --}}
        {{-- ========================= --}}

        <div class="card mb-4">

            <div class="card-header">
                <strong>Apoderado</strong>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label for="apoderado_dni" class="form-label">
                            DNI
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="apoderado_dni"
                            required
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="apoderado_nombre" class="form-label">
                            Nombre
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="apoderado_nombre"
                            required
                        >
                    </div>

                    <div class="col-md-5 mb-3">
                        <label for="apoderado_apellido" class="form-label">
                            Apellido
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="apoderado_apellido"
                            required
                        >
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label for="apoderado_telefono" class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="apoderado_telefono"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="apoderado_email" class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="apoderado_email"
                        >
                    </div>

                </div>

            </div>
        </div>


        {{-- ========================= --}}
        {{-- TITULARES --}}
        {{-- ========================= --}}

        <div class="card mb-4">

            <div class="card-header">
                <strong>Titulares</strong>
            </div>

            <div class="card-body">

                <div id="titulares"></div>

            </div>
        </div>


        {{-- ========================= --}}
        {{-- SUPLENTES --}}
        {{-- ========================= --}}

        <div class="card mb-4">

            <div class="card-header d-flex justify-content-between align-items-center">

                <strong>Suplentes</strong>

                <button
                    type="button"
                    class="btn btn-sm btn-secondary"
                    id="agregarSuplente"
                >
                    + Agregar suplente
                </button>

            </div>

            <div class="card-body">

                <div id="suplentes"></div>

            </div>
        </div>


        {{-- ========================= --}}
        {{-- BOTÓN --}}
        {{-- ========================= --}}

        <div class="mb-5">

            <button
                type="submit"
                class="btn btn-primary"
                id="btnCrear"
            >
                Crear lista
            </button>

        </div>

    </form>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const tipo = document.getElementById('tipo');
    const modoCarga = document.getElementById('modo_carga');

    const contenedorClaustro =
        document.getElementById('contenedor_claustro');

    const contenedorFacultad =
        document.getElementById('contenedor_facultad');

    const contenedorNumero =
        document.getElementById('contenedor_numero');

    const idClaustro =
        document.getElementById('id_claustro');

    const idFacultad =
        document.getElementById('id_facultad');

    const titulares =
        document.getElementById('titulares');

    const suplentes =
        document.getElementById('suplentes');

    const form =
        document.getElementById('formCrearLista');

    const mensaje =
        document.getElementById('mensaje');


    // ========================================
    // ACTUALIZAR CAMPOS SEGÚN TIPO
    // ========================================

    function actualizarTipo() {

        const valor = tipo.value;

        contenedorClaustro.style.display = 'none';
        contenedorFacultad.style.display = 'none';

        idClaustro.required = false;
        idFacultad.required = false;

        if (valor === 'superior') {

            contenedorClaustro.style.display = 'block';
            idClaustro.required = true;

        }

        if (valor === 'directivo') {

            contenedorClaustro.style.display = 'block';
            contenedorFacultad.style.display = 'block';

            idClaustro.required = true;
            idFacultad.required = true;

        }

        if (valor === 'decano') {

            contenedorFacultad.style.display = 'block';
            idFacultad.required = true;

        }

        // rector no necesita ni claustro ni facultad

        generarTitulares();

        actualizarBotonAgregarSuplente();

    }


    // ========================================
    // MODO DE CARGA
    // ========================================

    function actualizarModoCarga() {

        const numero = document.getElementById('numero');

        if (modoCarga.value === 'historica') {

            contenedorNumero.style.display = 'block';
            numero.required = true;

            cargarNumerosDisponibles();

        } else {

            contenedorNumero.style.display = 'none';
            numero.required = false;
            numero.value = '';
            //document.getElementById('numero').value = '';

        }

    }

    async function cargarNumerosDisponibles() {

        const anio = document.getElementById('anio').value;
        const tipoValor = tipo.value;
        const claustro = idClaustro.value;

        const numero = document.getElementById('numero');

        numero.innerHTML = `
            <option value="">Seleccionar número...</option>
        `;

        if (!anio || !tipoValor) {
            return;
        }

        if ( 
            ['superior', 'directivo'].includes(tipoValor) && !claustro
        ) {
            return;
        }

        try {

            const params = new URLSearchParams({
                anio: anio,
                tipo: tipoValor
            });

            if (claustro) {
                params.append('id_claustro', claustro);
            }

            const response = await fetch(
                `/api/listas/numeros-disponibles?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error('No se pudieron obtener los numeros disponibles.');
            }

            const data = await response.json();

            data.disponibles.forEach(numeroDisponible => {

                const option = document.createElement('option');

                option.value = numeroDisponible;
                option.textContent = numeroDisponible;

                numero.appendChild(option);

            });

            if (data.disponibles.length === 0) {
                
                numero.innerHTML = `
                    <option value="">
                        No hay números disponibles
                    </option>
                `;
            }
        } catch (error) {

            console.error(error);

            numero.innerHTML = `
                <option value="">
                    Error al cargar números
                </option>
            `;
        }
    }


    // ========================================
    // GENERAR TITULARES
    // ========================================

    function generarTitulares() {

        titulares.innerHTML = '';

        let cantidad = 0;

        const claustroTexto =
                    idClaustro.options[idClaustro.selectedIndex]?.text
                    .toLowerCase() || '';

        switch (tipo.value) {

            case 'superior':

                if (claustroTexto.includes('graduad')) {
                    cantidad = 4;
                } else {
                    cantidad = 12;
                }
                break;

            case 'directivo':

                if (claustroTexto.includes('graduad')) {
                    cantidad = 1;
                } else if (
                    claustroTexto.includes('docente') ||
                    claustroTexto.includes('estudiante') ||
                    claustroTexto.includes('nodocente')
                ) {
                    cantidad =
                        claustroTexto.includes('nodocente') ? 3 :
                        claustroTexto.includes('docente') ? 8 :
                        claustroTexto.includes('estudiante') ? 4 :
                        0;
                }

                break;

            case 'decano':
            case 'rector':
                cantidad = 1;
                break;
        }


        for (let i = 1; i <= cantidad; i++) {

            agregarPostulante(
                titulares,
                'titulares',
                i
            );

        }

    }


    // ========================================
    // AGREGAR POSTULANTE
    // ========================================

    /*function agregarPostulante(
        contenedor,
        grupo,
        orden
    ) {

        const div = document.createElement('div');

        div.className = 'row mb-2';

        div.innerHTML = `

            <div class="col-md-1">
                <label class="form-label">
                    ${orden}
                </label>
            </div>

            <div class="col-md-4">

                <input
                    type="text"
                    class="form-control"
                    placeholder="DNI"
                    data-grupo="${grupo}"
                    data-orden="${orden}"
                    data-campo="dni"
                    required
                >

            </div>

        `;

        contenedor.appendChild(div);

    }*/

    function agregarPostulante(
        contenedor,
        grupo,
        orden
    ) {
        const div = document.createElement('div');

        div.className = 'row mb-2 fila-postulante';

        if (grupo === 'suplentes') {
            div.classList.add('fila-suplente');
        }

        div.dataset.orden = orden;

        div.innerHTML = `
            <div class="col-md-1">
                <label class="form-label">
                    ${orden}
                </label>
            </div>

            <div class="col-md-5">
                <input
                    type="text"
                    class="form-control"
                    name="${grupo}_dni[]"
                    data-grupo="${grupo}"
                    data-orden="${orden}"
                    placeholder="DNI"
                    required
                >
            </div>

            ${
                grupo === 'suplentes'
                    ? `
                        <div class="col-md-3">
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-eliminar-suplente"
                            >
                                Eliminar
                            </button>
                        </div>
                    `
                    : ''
            }
        `;

        contenedor.appendChild(div);

        if (grupo === 'suplentes') {
            const botonEliminar = div.querySelector(
                '.btn-eliminar-suplente'
            );

            botonEliminar.addEventListener('click', function () {
                div.remove();

                renumerarSuplentes();
            });
        }
    }

    function renumerarSuplentes() {
        const filas = suplentes.querySelectorAll('.fila-suplente');

        filas.forEach((fila, index) => {
            const nuevoOrden = index + 1;

            fila.dataset.orden = nuevoOrden;

            const etiqueta = fila.querySelector('label');
            if (etiqueta) {
                etiqueta.textContent = nuevoOrden;
            }

            const inputDni = fila.querySelector(
                '[data-grupo="suplentes"]'
            );

            if (inputDni) {
                inputDni.dataset.orden = nuevoOrden;
            }
        });

        cantidadSuplentes = filas.length;

        actualizarBotonAgregarSuplente();
    }


    // ========================================
    // SUPLENTES
    // ========================================

    let cantidadSuplentes = 0;

    document.getElementById('agregarSuplente')
        .addEventListener('click', function () {

        const maximo = obtenerMaxSuplentes();

        if (cantidadSuplentes >= maximo) {
            return;
        }

            cantidadSuplentes++;

            agregarPostulante(
                suplentes,
                'suplentes',
                cantidadSuplentes
            );

            actualizarBotonAgregarSuplente();

        });

    function obtenerMaxSuplentes() {

        const claustro = idClaustro.value;

        switch (tipo.value) {
            case 'superior':
                if (claustro === '4') {
                    return 4; //Personas Graduadas
                }

                return 12; //Docentes, NoDocentes y Estudiantes
            
            case 'directivo':
                if (claustro === '1') {
                    return 8; //Docentes
                }

                if (claustro === '2') {
                    return 3; //NoDocentes
                }

                if (claustro === '3') {
                    return 4; //Estudiantes
                }

                if (claustro === '4') {
                    return 3; //Personas Graduadas
                }

                return 0;

            case 'decano':
            case 'rector':
                return 1;

            default:
                return 0;
        }
    }

    function actualizarBotonAgregarSuplente() {
        const boton = document.getElementById('agregarSuplente');

        if (!boton) {
            return;
        }

        const maximo = obtenerMaxSuplentes();

        boton.disabled = cantidadSuplentes >= maximo;
    }

    // ========================================
    // CAMBIAR TIPO
    // ========================================

    tipo.addEventListener('change', function () {
        actualizarTipo();
        cargarNumerosDisponibles();
    });

    idClaustro.addEventListener('change', function () {
        generarTitulares();
        cargarNumerosDisponibles();
    });

    modoCarga.addEventListener('change', function () {
        actualizarModoCarga();
        cargarNumerosDisponibles();
    });

    document.getElementById('anio').addEventListener('change', function () {
        cargarNumerosDisponibles();
    });


    // ========================================
    // SUBMIT
    // ========================================

    form.addEventListener('submit', async function (event) {

        event.preventDefault();

        mensaje.innerHTML = '';

        const btn =
            document.getElementById('btnCrear');

        btn.disabled = true;

        const payload = {

            anio:
                parseInt(document.getElementById('anio').value),

            tipo:
                tipo.value,

            nombre:
                document.getElementById('nombre').value,

            sigla:
                document.getElementById('sigla').value || null,

            modo_carga:
                modoCarga.value,

            numero:
                document.getElementById('numero').value
                    ? parseInt(document.getElementById('numero').value)
                    : null,

            id_claustro:
                idClaustro.value
                    ? parseInt(idClaustro.value)
                    : null,

            id_facultad:
                idFacultad.value
                    ? parseInt(idFacultad.value)
                    : null,

            apoderado: {

                dni:
                    document.getElementById('apoderado_dni').value,

                nombre:
                    document.getElementById('apoderado_nombre').value,

                apellido:
                    document.getElementById('apoderado_apellido').value,

                telefono:
                    document.getElementById('apoderado_telefono').value
                    || null,

                email:
                    document.getElementById('apoderado_email').value
                    || null
            },

            postulantes: {

                titulares: obtenerPostulantes('titulares'),

                suplentes: obtenerPostulantes('suplentes')

            }

        };


        try {

            const response = await fetch('/api/listas', {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/json',

                    'Accept': 'application/json',

                    'X-CSRF-TOKEN':
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.getAttribute('content')

                },

                body: JSON.stringify(payload)

            });


            const data = await response.json();


            if (!response.ok) {

                mostrarError(data);

                return;
            }


            mensaje.innerHTML = `

                <div class="alert alert-success">

                    <strong>Lista creada correctamente.</strong>

                    

                    <br>

                    Número: ${data.lista.numero}

                </div>

            `;

            form.reset();

            titulares.innerHTML = '';
            suplentes.innerHTML = '';

            cantidadSuplentes = 0;

            actualizarTipo();
            actualizarModoCarga();


        } catch (error) {

            console.error(error);

            mensaje.innerHTML = `

                <div class="alert alert-danger">

                    Error de conexión con el servidor.

                </div>

            `;

        } finally {

            btn.disabled = false;

        }

    });


    // ========================================
    // OBTENER POSTULANTES
    // ========================================

    function obtenerPostulantes(grupo) {

        const elementos =
            document.querySelectorAll(
                `[data-grupo="${grupo}"]`
            );

        const resultado = {};

        elementos.forEach(input => {

            const orden = input.dataset.orden;

            if (!resultado[orden]) {

                resultado[orden] = {};

            }

            resultado[orden][input.dataset.campo] =
                input.value;

        });


        return Object.values(resultado);

    }


    // ========================================
    // MOSTRAR ERROR
    // ========================================

    function mostrarError(data) {

        let html = '';

        if (data.error) {

            html += `
                <div class="alert alert-danger">
                    <strong>${data.error}</strong>
                </div>
            `;

        }


        if (Array.isArray(data.details)) {

            data.details.forEach(error => {

                html += `

                    <div class="alert alert-warning">

                        ${error.message || 'Error de validación'}

                        ${error.dni
                            ? `<br>DNI: ${error.dni}`
                            : ''
                        }

                        ${error.nombre
                            ? `<br>Persona: ${error.nombre}`
                            : ''
                        }

                    </div>

                `;

            });

        }


        if (!html) {

            html = `

                <div class="alert alert-danger">

                    Error desconocido.

                </div>

            `;

        }

        mensaje.innerHTML = html;

    }


    actualizarTipo();
    actualizarModoCarga();

});

</script>

@endsection