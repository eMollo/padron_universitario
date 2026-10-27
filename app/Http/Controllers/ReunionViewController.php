<?php

namespace App\Http\Controllers;

class ReunionViewController extends Controller
{
    // Pantalla principal: grilla de tipos de reunión
    public function index()
    {
        return view('asistencia.index');
    }

    // Abrir/crear una reunión
    public function crear()
    {
        return view('asistencia.crear');
    }

    // Vista de una reunión activa (asistencia en tiempo real)
    public function sala($id)
    {
        return view('asistencia.sala', compact('id'));
    }

    // Historial de reuniones pasadas
    public function historial()
    {
        return view('asistencia.historial');
    }

    // Detalle/reporte de una reunión cerrada
    public function detalle($id)
    {
        return view('asistencia.detalle', compact('id'));
    }

    // Vista de escaneo QR (pensada para proyectarse o abrirse en tablet)
    public function qr($id)
    {
        return view('asistencia.qr', compact('id'));
    }
}
