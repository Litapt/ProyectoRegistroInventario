<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/*
    Pantalla principal del rol CAJERO.
    Por ahora sólo da la bienvenida; aquí se irán agregando sus módulos.
*/
class InicioController extends Controller
{
    public function index(): View
    {
        return view('cajero.inicio');
    }
}
