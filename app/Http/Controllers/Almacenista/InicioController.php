<?php

namespace App\Http\Controllers\Almacenista;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/*
    Pantalla principal del rol ALMACENISTA.
    Por ahora sólo da la bienvenida; aquí se irán agregando sus módulos.
*/
class InicioController extends Controller
{
    public function index(): View
    {
        return view('almacenista.inicio');
    }
}
